<?php

namespace Goksagun\RateLimitBundle\Tests\Service;

use Goksagun\RateLimitBundle\Service\RateLimitService;
use PHPUnit\Framework\TestCase;

class RateLimitServiceTest extends TestCase
{
    public function testRulesMin()
    {
        $rules = [
            ['min' => 10, 'increment' => $expected = 10],
        ];

        $actual = RateLimitService::matchIncrement($rules,19);

        $this->assertEquals($actual, $expected);
    }

    public function testRulesMax()
    {
        $rules = [
            ['max' => 10, 'increment' => $expected = 11],
        ];

        $actual = RateLimitService::matchIncrement($rules, 1);

        $this->assertEquals($actual, $expected);
    }

    public function testRulesMinNull()
    {
        $rules = [
            ['min' => 10, 'increment' => 10],
        ];

        $actual = RateLimitService::matchIncrement($rules, 9);

        $this->assertNull($actual);
    }

    public function testRulesMaxNull()
    {
        $rules = [
            ['max' => 10, 'increment' => 11],
        ];

        $actual = RateLimitService::matchIncrement($rules, 11);

        $this->assertNull($actual);
    }

    public function testRulesMinAndMax()
    {
        $rules = [
            ['min' => 0, 'max' => 15, 'increment' => 10],
            ['min' => 15, 'max' => 30, 'increment' => 5],
            ['min' => 30, 'max' => 45, 'increment' => $expected = 2],
            ['min' => 45, 'increment' => 1],
        ];

        $actual = RateLimitService::matchIncrement($rules,35);

        $this->assertEquals($actual, $expected);
    }

    public function testRulesMinAndMaxLast()
    {
        $rules = [
            ['min' => 0, 'max' => 15, 'increment' => 10],
            ['min' => 15, 'max' => 30, 'increment' => 5],
            ['min' => 30, 'max' => 45, 'increment' => 2],
            ['min' => 45, 'increment' => $expected = 1],
        ];

        $actual = RateLimitService::matchIncrement($rules,45);

        $this->assertEquals($actual, $expected);
    }
}