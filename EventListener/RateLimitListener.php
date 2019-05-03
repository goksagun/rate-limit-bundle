<?php

namespace Goksagun\RateLimitBundle\EventListener;

use Goksagun\RateLimitBundle\RateLimit\RateLimitInfo;
use Goksagun\RateLimitBundle\Utils\KeyGenerator;
use Goksagun\RateLimitBundle\Utils\RequestProcessor;
use Predis\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RateLimitListener
{
    const RATE_FIELD_LIMIT = 'limit';
    const RATE_FIELD_RESET = 'reset';
    const RATE_FIELD_CALLS = 'calls';
    const RATE_FIELD_DYNAMIC_LIMIT = 'dynamic_limit';
    const RATE_FIELD_DYNAMIC_LIMIT_INCREMENT = 'dynamic_limit_increment';

    private $config;
    private $client;

    private $uri;
    private $time;
    private $rateLimit;

    public function __construct(array $config, Client $client)
    {
        $this->config = $config;
        $this->client = $client;

        $this->time = time();
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        if (!$this->config['enabled']) {
            // don't do anything if it's not enabled
            return;
        }

        if (!$this->uri = RequestProcessor::process($this->config['paths'], $event->getRequest())) {
            // don't do anything if it's not matched
            return;
        }

        $key = KeyGenerator::generate($this->uri['path'], $this->uri['methods']);

        $rateLimit = $this->getOrNewRateLimit($key);

        if ($rateLimit->getLimit() <= $rateLimit->getCalls()) {
            $this->setResponse($event);

            return;
        }

        if ($rateLimit->getIncrement()) {
            if ($rateLimit->getDynamicLimit() <= $rateLimit->getCalls()) {
                $this->setResponse($event);

                return;
            }
        }

        $this->incrementAndStoreRateLimitCalls($rateLimit);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        if (!$this->config['enabled']) {
            // don't do anything if it's not enabled
            return;
        }

        if (!$this->uri) {
            // don't do anything if it's not matched
            return;
        }

        $key = KeyGenerator::generate($this->uri['path'], $this->uri['methods']);

        $rateLimit = $this->getOrNewRateLimit($key);

        if ($this->config['display_headers']) {
            $this->setHeaders($event->getResponse(), $rateLimit);
        }
    }

    private function getRateLimit($key)
    {
        $data = $this->client->hgetall($key);

        if (array_key_exists(static::RATE_FIELD_LIMIT, $data)
            && array_key_exists(static::RATE_FIELD_CALLS, $data)
            && array_key_exists(static::RATE_FIELD_RESET, $data)
            && array_key_exists(static::RATE_FIELD_DYNAMIC_LIMIT, $data)
        ) {
            return $this->createRateLimit(
                (string)$key,
                (int)$data[static::RATE_FIELD_LIMIT],
                (int)$data[static::RATE_FIELD_CALLS],
                (string)$data[static::RATE_FIELD_RESET],
                $data[static::RATE_FIELD_DYNAMIC_LIMIT] ? (int)$data[static::RATE_FIELD_DYNAMIC_LIMIT] : null,
                $data[static::RATE_FIELD_DYNAMIC_LIMIT_INCREMENT] ? (int)$data[static::RATE_FIELD_DYNAMIC_LIMIT_INCREMENT] : null
            );
        }

        return null;
    }

    private function storeRateLimit(RateLimitInfo $rateLimit)
    {
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_LIMIT, $rateLimit->getLimit());
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_CALLS, $rateLimit->getCalls());
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_RESET, $rateLimit->getReset());
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_DYNAMIC_LIMIT, $rateLimit->getDynamicLimit());
        $this->client->hset(
            $rateLimit->getKey(),
            static::RATE_FIELD_DYNAMIC_LIMIT_INCREMENT,
            $rateLimit->getIncrement()
        );
    }

    private function incrementRateLimitCalls(RateLimitInfo $rateLimit)
    {
        $this->client->hincrby($rateLimit->getKey(), 'calls', 1);
    }

    private function createRateLimit(
        string $key,
        int $limit,
        int $calls,
        string $reset,
        $dynamicLimit = null,
        $increment = null
    ) {
        return new RateLimitInfo($key, $limit, $calls, $reset, $dynamicLimit, $increment);
    }

    private function incrementAndStoreRateLimitCalls(RateLimitInfo $rateLimit)
    {
        $rateLimit->incrementCalls();

        $this->incrementRateLimitCalls($rateLimit);
    }

    private function getOrNewRateLimit(string $key)
    {
        if ($this->rateLimit) {
            return $this->rateLimit;
        }

        $rateLimit = $this->getRateLimit($key);

        // Create rate limit
        if (!$rateLimit instanceof RateLimitInfo) {
            $rateLimit = $this->createRateLimit(
                $key,
                $this->uri['limit'],
                0,
                $this->time + $this->uri['period'],
                $this->uri['increment'],
                $this->uri['increment']
            );

            $this->storeRateLimit($rateLimit);
        } // Period end re-create rate limit
        elseif ($this->time >= $rateLimit->getReset()) {
            $calls = $rateLimit->getCalls();

            $dynamicLimit = $this->uri['increment'] ? $this->uri['increment'] + $calls : null;

            if ($dynamicLimit && $dynamicLimit > $this->uri['limit']) {
                $dynamicLimit = $this->uri['limit'];
            }

            $rateLimit = $this->createRateLimit(
                $key,
                $this->uri['limit'],
                0,
                $this->time + $this->uri['period'],
                $dynamicLimit,
                $this->uri['increment']
            );

            $this->storeRateLimit($rateLimit);
        }

        if (!$this->rateLimit) {
            $this->rateLimit = $rateLimit;
        }

        return $rateLimit;
    }

    private function setResponse(GetResponseEvent $event)
    {
        if ($exception = $this->config['response_exception']) {
            throw new $exception();
        }

        // Customize your response object to display the exception details
        $response = new Response();
        $response->setContent($this->config['response_message']);
        $response->setStatusCode($this->config['response_status_code']);

        $event->setResponse($response);
    }

    private function setHeaders(Response $response, RateLimitInfo $rateLimit)
    {
        $headers = [
            'X-RateLimit-Limit' => $rateLimit->getLimit(),
            'X-RateLimit-Remaining' => $rateLimit->getRemaining(),
            'X-RateLimit-Reset' => $rateLimit->getReset(),
        ];

        if ($this->config['display_reset_date']) {
            $headers['X-RateLimit-Reset-Date'] = $rateLimit->getResetDate();
        }

        if ($rateLimit->getDynamicLimit()) {
            $headers['X-RateLimit-Limit'] = $rateLimit->getDynamicLimit();
            $headers['X-RateLimit-Remaining'] = $rateLimit->getDynamicRemaining();
        }

        $response->headers->add($headers);
    }
}