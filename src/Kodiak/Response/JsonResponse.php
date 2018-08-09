<?php

namespace Kodiak\Response;


class JsonResponse extends Response
{
    /**
     * JsonResponse constructor.
     * @param array $values
     * @param int $options [optional]
     * @param int $status
     */
    public function __construct(array $values,$options = JSON_NUMERIC_CHECK, $status = 200)
    {
        parent::__construct(
            json_encode($values, $options),
            $status,
            [
                'Content-type: application/json',
            ]
        );
    }
}