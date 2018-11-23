<?php

namespace Kodiak\Response;


use PandaBase\Record\InstanceRecord;

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
        /*
         * If 'values' array contains InstanceRecord, it will be replaced with instance's getAll() method call result.
         */
        function recursiveCheck(&$array) {
            $r = array();
            foreach ($array as $key => &$item) {
                if(is_array($item)) {
                    $r[$key] = recursiveCheck($item);
                }
                elseif ($item instanceof InstanceRecord) {
                    $t = $item->getAll();
                    $r[$key] = recursiveCheck($t);
                } else {
                    $r[$key] = &$item;
                }
            }
            return $r;
        }
        $values = recursiveCheck($values);

        parent::__construct(
            json_encode($values, $options),
            $status,
            [
                'Content-type: application/json',
            ]
        );
    }
}