<?php

namespace Goksagun\RateLimitBundle\Utils;

use Symfony\Component\HttpFoundation\Request;

class RequestProcessor
{
    public static function process(array $paths, Request $request)
    {
        $requestUri = $request->getRequestUri();
        $requestRoute = $request->get('_route');
        $requestMethod = $request->getMethod();

        $matched = array_filter(
            $paths,
            function ($uri) use ($requestUri, $requestRoute, $requestMethod) {
                if ($requestUri === $uri['path'] || $requestRoute === $uri['path']) {
                    if (in_array($requestMethod, $uri['methods']) || in_array('*', $uri['methods'])) {
                        return true;
                    }
                }

                return false;
            }
        );

        return current($matched);
    }
}