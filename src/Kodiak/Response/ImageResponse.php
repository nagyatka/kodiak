<?php

namespace Kodiak\Response;


class ImageResponse extends Response
{
    public function __construct($image = '', $filename = '', $contentType = 'image/jpeg')
    {
        $headers = [
            "Content-Type: ".$contentType,
            "Content-Transfer-Encoding: Binary",
            "Content-Length:".strlen($image),
            "Content-Disposition: inline; filename=$filename"
        ];
        parent::__construct($image, 200, $headers);
    }

}