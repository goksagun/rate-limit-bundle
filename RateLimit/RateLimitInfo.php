<?php

namespace Goksagun\RateLimitBundle\RateLimit;

class RateLimitInfo
{
    private $key;
    private $limit;
    private $increment;

    private $dynamicLimit;

    // Must be store
    private $calls;
    private $reset;

    /**
     * RateLimit constructor.
     * @param string $key
     * @param int $limit
     * @param int $calls
     * @param string $reset
     * @param null $dynamicLimit
     * @param int|null $increment
     */
    public function __construct(
        string $key,
        int $limit,
        int $calls,
        string $reset,
        $dynamicLimit = null,
        $increment = null
    ) {
        $this->key = $key;
        $this->limit = $limit;
        $this->calls = $calls;
        $this->reset = $reset;
        $this->dynamicLimit = $dynamicLimit;
        $this->increment = $increment;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return RateLimitInfo
     */
    public function setKey(string $key): RateLimitInfo
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return RateLimitInfo
     */
    public function setLimit(int $limit): RateLimitInfo
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getDynamicLimit()
    {
        return $this->dynamicLimit;
    }

    /**
     * @param null|int $dynamicLimit
     * @return RateLimitInfo
     */
    public function setDynamicLimit($dynamicLimit)
    {
        $this->dynamicLimit = $dynamicLimit;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getIncrement()
    {
        return $this->increment;
    }

    /**
     * @param null|int $increment
     * @return RateLimitInfo
     */
    public function setIncrement($increment): RateLimitInfo
    {
        $this->increment = $increment;

        return $this;
    }

    /**
     * @return int
     */
    public function getCalls(): int
    {
        return $this->calls;
    }

    /**
     * @param int $calls
     * @return RateLimitInfo
     */
    public function setCalls(int $calls): RateLimitInfo
    {
        $this->calls = $calls;

        return $this;
    }

    /**
     * @return RateLimitInfo
     */
    public function incrementCalls()
    {
        ++$this->calls;

        return $this;
    }

    /**
     * @return string
     */
    public function getReset(): string
    {
        return $this->reset;
    }

    /**
     * @return string
     */
    public function getResetDate(): string
    {
        return (new \DateTime())->setTimestamp($this->reset)->format('D, d M Y H:i:s O');
    }

    /**
     * @param string $reset
     * @return RateLimitInfo
     */
    public function setReset(string $reset): RateLimitInfo
    {
        $this->reset = $reset;

        return $this;
    }

    /**
     * @return int
     */
    public function getRemaining(): int
    {
        return $this->limit - $this->calls;
    }

    /**
     * @return int|null
     */
    public function getDynamicRemaining()
    {
        if (!$this->dynamicLimit) {
            return null;
        }

        return $this->dynamicLimit - $this->calls;
    }
}