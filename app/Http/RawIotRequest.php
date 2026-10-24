<?php

namespace App\Http;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\InputBag;

class RawIotRequest extends Request
{
    public static function capture()
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/api/iot')) {
            // Laravel createFromBase eagerly decodes JSON. This boundary deliberately
            // supplies neither parsed input nor cookies; the controller authenticates RAW.
            return new self([], [], [], [], [], $_SERVER);
        }

        return Request::capture();
    }

    public function json($key = null, $default = null)
    {
        return $key === null ? new InputBag : $default;
    }
}
