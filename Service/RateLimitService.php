<?php

namespace Goksagun\RateLimitBundle\Service;

use Goksagun\RateLimitBundle\EventListener\RateLimitListener;

class RateLimitService
{
    public static function matchIncrement(array $rules, int $calls)
    {
        foreach ($rules as $rule) {
            $min = $rule['min'] ?? RateLimitListener::RATE_FIELD_RULES_MIN;
            $max = $rule['max'] ?? RateLimitListener::RATE_FIELD_RULES_MAX;
            $increment = $rule['increment'];

            if ($min <= $calls && $calls < $max) {
                return $increment;
            }
        }

        return null;
    }
}