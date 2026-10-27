<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class TrustDeploymentProxies extends TrustProxies
{
    protected $headers = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT;

    protected function setTrustedProxyIpAddresses(Request $request)
    {
        // Config is available at request time, including when config:cache is used.
        // No automatic trust based on a client-controlled Host header.
        if (config('deployment.target') === 'cloud-run') {
            $this->setTrustedProxyIpAddressesToTheCallingIp($request);
        }
    }
}
