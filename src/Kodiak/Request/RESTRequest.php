<?php


namespace Kodiak\Request;


class RESTRequest implements \ArrayAccess
{
    /**
     * @var array
     */
    private $data;

    /**
     * RESTRequest constructor.
     * @param $request_data
     */
    public function __construct($request_data)
    {
        $this->data = $request_data;
    }

    public static function read($source = 'php://input') {
        return new RESTRequest(json_decode(file_get_contents($source),true));
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->data;
    }
}