<?php

namespace Kodiak\Response;


class PdfResponse extends Response
{
    public function __construct($content = '', $filename = '')
    {
        $headers = [
            "Content-Type: application/pdf",
            "Content-Transfer-Encoding: Binary",
            "Content-Length:".strlen($content),
            "Content-Disposition: attachment; filename=$filename"
        ];
        parent::__construct($content, 200, $headers);
    }

}