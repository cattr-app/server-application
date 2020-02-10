<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    protected $trustedDomains = [];

    public function __construct()
    {
        // get domain list in "domain1.com, domain2.com, domain3.com, domainN.com"
        $domains = config('cors.trustedDomains');

        // remove spaces
        $domains = str_replace(' ', '', $domains);

        if ($domains) {
            // string => array ['domain1.com', 'domain2.com', 'domain3.com', 'domainN.com']
            $this->trustedDomains = explode(',', $domains);
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->allowAllCors($request);
        return $next($request);
    }


    public function terminate(Request $request): void
    {
        if ($request->getMethod() === 'OPTIONS') {
            $this->allowAllCors($request);
        }
    }

    protected function allowAllCors(Request $request): void
    {
        if (!config('cors.enable')) {
            foreach ([
                'Access-Control-Allow-Origin',
                'Access-Control-Allow-Methods',
                'Access-Control-Allow-Headers',
                'Access-Control-Max-Age',
            ] as $headerName) {
                header_remove($headerName);
            }
            return;
        }

        $origin = $request->header('Origin');
        $allowedDomain = $this->allowedDomain($origin);

        if (!$allowedDomain) {
            return;
        }

        header('Access-Control-Allow-Origin: ' . $allowedDomain);
        header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Max-Age: 1728000');
    }


    protected function allowedDomain($origin)
    {
        if ($origin === null) {
            return false;
        }

        $requestProtocol = parse_url($origin, PHP_URL_SCHEME);
        $requestDomain = parse_url($origin, PHP_URL_HOST);
        $requestPort = parse_url($origin, PHP_URL_PORT);

        if ($requestDomain === null) {
            return false;
        }

        // has port? add it to domain
        if ($requestPort !== null) {
            $requestDomain .= ':' . $requestPort;
        }

        $requestDomain = $requestProtocol . '://' . $requestDomain;

        if (!in_array($requestDomain, $this->trustedDomains, true)) {
            return false;
        }

        return $requestDomain;
    }
}
