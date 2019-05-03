<?php

namespace Goksagun\RateLimitBundle\Utils;

class KeyGenerator
{
    const RATE_KEY_LIMIT = 'x-rate-limit.%s.%s'; // x-rate-limit.[path].[methods]

    public static function generate(string $path, array $methods): string
    {
        if (($key = array_search('*', $methods)) !== false) {
            unset($methods[$key]);
        }

        $path = trim($path, '/');
        $methods = strtolower(implode(':', $methods));

        return trim(sprintf(static::RATE_KEY_LIMIT, $path, $methods), '.');
    }
}