<?php

namespace Goksagun\RateLimitBundle\RateLimit;

class RateLimitInfo
{
    private $id;
    private $key;
    private $limit;
    private $period;
    private $increment;
    private $rules;

    private $dynamicLimit;

    private $calls;
    private $reset;

    /**
     * RateLimit constructor.
     * @param string $key
     * @param int $limit
     * @param int $period
     * @param int $increment
     * @param array $rules
     * @param int $calls
     * @param int $reset
     * @param int $dynamicLimit
     */
    public function __construct(
        string $key,
        int $limit,
        int $period,
        int $increment,
        array $rules,
        int $calls,
        int $reset,
        int $dynamicLimit = 0
    ) {
        $this->key = $key;
        $this->limit = $limit;
        $this->period = $period;
        $this->increment = $increment;
        $this->rules = $rules;
        $this->calls = $calls;
        $this->reset = $reset;
        $this->dynamicLimit = $dynamicLimit;

        $this->setId();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @param int $period
     * @return RateLimitInfo
     */
    public function setPeriod(int $period): RateLimitInfo
    {
        $this->period = $period;

        return $this;
    }

    /**
     * @return int
     */
    public function getDynamicLimit(): int
    {
        return $this->dynamicLimit;
    }

    /**
     * @param int $dynamicLimit
     * @return RateLimitInfo
     */
    public function setDynamicLimit(int $dynamicLimit): RateLimitInfo
    {
        $this->dynamicLimit = $dynamicLimit;

        return $this;
    }

    /**
     * @return int
     */
    public function getIncrement(): int
    {
        return $this->increment;
    }

    /**
     * @param int $increment
     * @return RateLimitInfo
     */
    public function setIncrement(int $increment): RateLimitInfo
    {
        $this->increment = $increment;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasDynamicLimit(): bool
    {
        return $this->increment > 0;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param array $rules
     * @return RateLimitInfo
     */
    public function setRules($rules): RateLimitInfo
    {
        $this->rules = $rules;

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
    public function incrementCalls(): RateLimitInfo
    {
        ++$this->calls;

        return $this;
    }

    /**
     * @return int
     */
    public function getReset(): int
    {
        return $this->reset;
    }

    /**
     * @param int $reset
     * @return RateLimitInfo
     */
    public function setReset(int $reset): RateLimitInfo
    {
        $this->reset = $reset;

        return $this;
    }

    /**
     * @param string $format
     * @return string
     * @throws \Exception
     */
    public function getResetDate(string $format = 'D, d M Y H:i:s O'): string
    {
        return (new \DateTime())->setTimestamp($this->reset)->format($format);
    }

    /**
     * @return int
     */
    public function getRemaining(): int
    {
        return $this->limit - $this->calls;
    }

    /**
     * @return int
     */
    public function getDynamicRemaining(): int
    {
        if (!$this->dynamicLimit) {
            return 0;
        }

        return $this->dynamicLimit - $this->calls;
    }

    private function setId()
    {
        $this->id = md5(implode(':', [$this->key, $this->limit, $this->period, $this->increment, serialize($this->rules)]));
    }
}