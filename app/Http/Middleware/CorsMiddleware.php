<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{

    protected $trustedDomains = [];


    public function __construct()
    {
        // get domain list in "domain1.com, domain2.com, domain3.com, domainN.com"
        $domains = config('cors.trustedDomains');

        // remove spaces
        $domains = str_replace([' '],'', $domains);

        if ($domains) {
            // string => array ['domain1.com', 'domain2.com', 'domain3.com', 'domainN.com']
            $this->trustedDomains = explode(',', $domains);
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->allowAllCors($request);
        return $next($request);
    }


    public function terminate($request, $response)
    {
        if ($request->getMethod() == 'OPTIONS') {
            $this->allowAllCors($request);
        }
    }

    protected function allowAllCors($request)
    {
        $origin = $request->header('Origin');

        if (is_null($origin)) {
            return;
        }

        $requestDomain = parse_url($origin, PHP_URL_HOST);
        $requestPort = parse_url($origin, PHP_URL_PORT);

        if (is_null($requestDomain)) {
            return;
        }

        // has port? add it to domain
        if (!is_null($requestPort)) {
            $requestDomain = $requestDomain . ':' . $requestPort;
        }

        if (!in_array($requestDomain, $this->trustedDomains)) {
            return;
        }

        header('Access-Control-Allow-Origin: ' . $requestDomain);
        header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Max-Age: 1728000');
    }
}
