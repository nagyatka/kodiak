<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 11. 23.
 * Time: 11:20
 */

namespace Kodiak\Response;


class RESTResponse extends JsonResponse
{
    const SUCCESS = true;
    const ERROR   = false;

    /**
     * RESTResponse constructor.
     * @param bool $result_type
     * @param array|null $content
     */
    public function __construct($result_type, $content = null, $options = JSON_NUMERIC_CHECK)
    {
        if($result_type == RESTResponse::SUCCESS) {
            parent::__construct([
                'success'   => RESTResponse::SUCCESS,
                'data'      => $content
            ], $options);
        }
        else {
            parent::__construct([
                'success'   => RESTResponse::ERROR,
                'error'      => $content
            ], $options);

        }

    }

    public static function error($message = null) {
        return new RESTResponse(RESTResponse::ERROR, $message);
    }

    public static function success($data = null) {
        return new RESTResponse(RESTResponse::SUCCESS, $data);
    }
}