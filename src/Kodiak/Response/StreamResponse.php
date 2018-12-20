<?php

namespace Kodiak\Response;


class StreamResponse extends Response
{

    /**
     * Video or audio stream response
     * Supports partial range response
     * @param [type] $file         Path of the file
     * @param string $content_type Content-type of the response
     */
    public function __construct($file, $content_type = 'video/mp4')
    {

        // Make sure the files exists
        if (!file_exists($file)) {
            header("HTTP/1.1 404 Not Found");
            exit;
        }

        // Get file size
        $filesize = sprintf("%u", filesize($file));

        // Handle 'Range' header
        if (isset($_SERVER['HTTP_RANGE'])){
            $range = $_SERVER['HTTP_RANGE'];
        } elseif($apache = apache_request_headers()){
            $headers = array();
            foreach ($apache as $header => $val){
                $headers[strtolower($header)] = $val;
            }
            if(isset($headers['range'])){
                $range = $headers['range'];
            }
            else $range = FALSE;
        } else $range = FALSE;

        //Is range
        if ($range) {
            $partial = true;
            list($param, $range) = explode('=',$range);
            // Bad request - range unit is not 'bytes'
            if(strtolower(trim($param)) != 'bytes'){
                header("HTTP/1.1 400 Invalid Request");
                exit;
            }
            // Get range values
            $range = explode(',',$range);
            $range = explode('-',$range[0]);
            // Deal with range values
            if ($range[0] === ''){
                $end = $filesize - 1;
                $start = $end - intval($range[0]);
            } else if ($range[1] === '') {
                $start = intval($range[0]);
                $end = $filesize - 1;
            }else{
                // Both numbers present, return specific range
                $start = intval($range[0]);
                $end = intval($range[1]);
                if ($end >= $filesize || (!$start && (!$end || $end == ($filesize - 1)))) $partial = false;
            }
            $length = $end - $start + 1;
        }
        // No range requested
        else {
            $partial = false;
            $length = $filesize;
        }

        // Send standard headers
        header("Content-Type: $content_type");
        header("Content-Length: $length");
        header('Accept-Ranges: bytes');

        // send extra headers for range handling...
        if ($partial) {
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$filesize");
            if (!$fp = fopen($file, 'rb')) { // Error out if we can't read the file
                header("HTTP/1.1 500 Internal Server Error");
                exit;
            }
            if ($start) fseek($fp,$start);
            while($length){
                set_time_limit(0);
                $read = ($length > 8192) ? 8192 : $length;
                $length -= $read;
                print(fread($fp,$read));
            }
            fclose($fp);
        }
        //just send the whole file
        else readfile($file);
        exit;
    }

}