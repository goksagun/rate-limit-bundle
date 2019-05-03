<?php

namespace Goksagun\RateLimitBundle\Tests\Utils;

use Goksagun\RateLimitBundle\Utils\KeyGenerator;
use PHPUnit\Framework\TestCase;

class KeyGeneratorTest extends TestCase
{
    public function testBasicPathWrong()
    {
        $key = KeyGenerator::generate('/foo', []);

        $this->assertNotEquals('x-rate-limit./foo', $key);
    }

    public function testBasicPath()
    {
        $key = KeyGenerator::generate('/foo', []);

        $this->assertEquals('x-rate-limit.foo', $key);
    }

    public function testBasicPathWithMethods()
    {
        $key = KeyGenerator::generate('/foo', ['*']);

        $this->assertEquals('x-rate-limit.foo', $key);
    }

    public function testBasicPathWithSpecificMethods()
    {
        $key = KeyGenerator::generate('/foo', ['GET', 'POST']);

        $this->assertEquals('x-rate-limit.foo.get:post', $key);
    }

    public function testSubPathWithSpecificMethods()
    {
        $key = KeyGenerator::generate('/foo/bar', ['GET', 'POST']);

        $this->assertEquals('x-rate-limit.foo:bar.get:post', $key);
    }

    public function testSubPathWithSpecificMethodsWrong()
    {
        $key = KeyGenerator::generate('/foo/bar', ['GET', 'POST']);

        $this->assertNotEquals('x-rate-limit.foo/bar.get,post', $key);
    }
}