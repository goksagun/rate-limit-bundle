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
    const RATE_FIELD_PERIOD = 'period';
    const RATE_FIELD_INCREMENT = 'increment';
    const RATE_FIELD_RESET = 'reset';
    const RATE_FIELD_CALLS = 'calls';
    const RATE_FIELD_DYNAMIC_LIMIT = 'dynamic_limit';

    private $config;
    private $client;

    private $uri;
    private $time;

    private $arrayCache = [];

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

        if ($rateLimit->getIncrement() && $rateLimit->getDynamicLimit() <= $rateLimit->getCalls()) {
            $this->setResponse($event);

            return;
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
        if (array_key_exists(static::RATE_FIELD_LIMIT, $this->arrayCache)) {
            return $this->arrayCache[static::RATE_FIELD_LIMIT];
        }

        $data = $this->client->hgetall($key);

        if (array_key_exists(static::RATE_FIELD_LIMIT, $data)
            && array_key_exists(static::RATE_FIELD_PERIOD, $data)
            && array_key_exists(static::RATE_FIELD_INCREMENT, $data)
            && array_key_exists(static::RATE_FIELD_CALLS, $data)
            && array_key_exists(static::RATE_FIELD_RESET, $data)
            && array_key_exists(static::RATE_FIELD_DYNAMIC_LIMIT, $data)
        ) {
            $rateLimit = $this->createRateLimit(
                (string)$key,
                (int)$data[static::RATE_FIELD_LIMIT],
                (int)$data[static::RATE_FIELD_PERIOD],
                (int)$data[static::RATE_FIELD_INCREMENT],
                (int)$data[static::RATE_FIELD_CALLS],
                (int)$data[static::RATE_FIELD_RESET],
                (int)$data[static::RATE_FIELD_DYNAMIC_LIMIT]
            );

            $this->arrayCache[static::RATE_FIELD_LIMIT] = $rateLimit;

            return $rateLimit;
        }

        return null;
    }

    private function storeRateLimit(RateLimitInfo $rateLimit)
    {
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_LIMIT, $rateLimit->getLimit());
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_PERIOD, $rateLimit->getPeriod());
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_INCREMENT, $rateLimit->getIncrement());
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_CALLS, $rateLimit->getCalls());
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_RESET, $rateLimit->getReset());
        $this->client->hset($rateLimit->getKey(), static::RATE_FIELD_DYNAMIC_LIMIT, $rateLimit->getDynamicLimit());
    }

    private function incrementRateLimitCalls(RateLimitInfo $rateLimit)
    {
        $this->client->hincrby($rateLimit->getKey(), 'calls', 1);
    }

    private function createRateLimit(
        string $key,
        int $limit,
        int $period,
        int $increment,
        int $calls,
        int $reset,
        int $dynamicLimit = 0
    ) {
        return new RateLimitInfo($key, $limit, $period, $increment, $calls, $reset, $dynamicLimit);
    }

    private function incrementAndStoreRateLimitCalls(RateLimitInfo $rateLimit)
    {
        $rateLimit->incrementCalls();

        $this->incrementRateLimitCalls($rateLimit);
    }

    private function getOrNewRateLimit(string $key)
    {
        $rateLimit = $this->getRateLimit($key);

        $proxyRateLimit = $this->createRateLimit(
            $key,
            $this->uri['limit'],
            $this->uri['period'],
            $this->uri['increment'],
            0,
            $this->time + $this->uri['period'],
            $this->uri['increment']
        );

        // Create rate limit if there is none or changed config parameters
        if (null === $rateLimit || $rateLimit->getId() !== $proxyRateLimit->getId()) {
            $rateLimit = $this->createRateLimit(
                $key,
                $this->uri['limit'],
                $this->uri['period'],
                $this->uri['increment'],
                0,
                $this->time + $this->uri['period'],
                $this->uri['increment']
            );

            $this->storeRateLimit($rateLimit);
        } // Period end re-create rate limit
        elseif ($this->time >= $rateLimit->getReset()) {
            $dynamicLimit = 0;

            if ($rateLimit->hasDynamicLimit()) {
                $dynamicLimit = $rateLimit->getIncrement() + $rateLimit->getCalls();

                if ($dynamicLimit > $rateLimit->getLimit()) {
                    $dynamicLimit = $rateLimit->getLimit();
                }
            }

            $rateLimit->setCalls(0);
            $rateLimit->setReset($this->time + $rateLimit->getPeriod());
            $rateLimit->setDynamicLimit($dynamicLimit);

            $this->storeRateLimit($rateLimit);
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

        if ($rateLimit->hasDynamicLimit()) {
            $headers['X-RateLimit-Limit'] = $rateLimit->getDynamicLimit();
            $headers['X-RateLimit-Remaining'] = $rateLimit->getDynamicRemaining();
        }

        $response->headers->add($headers);
    }
}