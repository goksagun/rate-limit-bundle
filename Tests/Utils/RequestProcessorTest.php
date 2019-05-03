<?php

namespace Goksagun\RateLimitBundle\Tests\Utils;

use Goksagun\RateLimitBundle\Utils\RequestProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestProcessorTest extends TestCase
{
    public function testUriWithAnyMethodNotMatched()
    {
        $requestMock = $this->createRequestMock(['getRequestUri' => '/buzz']);

        $paths = [
            ['path' => '/foo', 'limit' => 10, 'period' => 10, 'methods' => ['*']],
            ['path' => '/foo/bar', 'limit' => 10, 'period' => 10, 'methods' => ['GET']],
            ['path' => '/baz', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
        ];

        $actual = RequestProcessor::process($paths, $requestMock);

        $this->assertFalse($actual);
    }

    public function testUriWithAnyMethod()
    {
        $requestMock = $this->createRequestMock(['getRequestUri' => '/foo']);

        $paths = [
            $expected = ['path' => '/foo', 'limit' => 10, 'period' => 10, 'methods' => ['*']],
            ['path' => '/foo/bar', 'limit' => 10, 'period' => 10, 'methods' => ['GET']],
            ['path' => '/baz', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
        ];

        $actual = RequestProcessor::process($paths, $requestMock);

        $this->assertEquals($expected, $actual);
    }

    public function testRootUriWithAnyMethod()
    {
        $requestMock = $this->createRequestMock(['getRequestUri' => '/']);

        $paths = [
            $expected = ['path' => '/', 'limit' => 10, 'period' => 10, 'methods' => ['*']],
            ['path' => '/foo/bar', 'limit' => 10, 'period' => 10, 'methods' => ['GET']],
            ['path' => '/baz', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
        ];

        $actual = RequestProcessor::process($paths, $requestMock);

        $this->assertEquals($expected, $actual);
    }

    public function testUriWithSpecificMethod()
    {
        $requestMock = $this->createRequestMock(['getRequestUri' => '/foo', 'getMethod' => 'GET']);

        $paths = [
            $expected = ['path' => '/foo', 'limit' => 10, 'period' => 10, 'methods' => ['GET']],
            ['path' => '/foo/bar', 'limit' => 10, 'period' => 10, 'methods' => ['*']],
            ['path' => '/baz', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
        ];

        $actual = RequestProcessor::process($paths, $requestMock);

        $this->assertEquals($expected, $actual);
    }

    public function testUriWithSpecificMethods()
    {
        $requestMock = $this->createRequestMock(['getRequestUri' => '/foo', 'getMethod' => 'POST']);

        $paths = [
            $expected = ['path' => '/foo', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
            ['path' => '/foo/bar', 'limit' => 10, 'period' => 10, 'methods' => ['*']],
            ['path' => '/baz', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
        ];

        $actual = RequestProcessor::process($paths, $requestMock);

        $this->assertEquals($expected, $actual);
    }

    public function testSubUriWithSpecificMethods()
    {
        $requestMock = $this->createRequestMock(['getRequestUri' => '/foo/bar', 'getMethod' => '*']);

        $paths = [
            ['path' => '/foo', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
            $expected = ['path' => '/foo/bar', 'limit' => 10, 'period' => 10, 'methods' => ['*']],
            ['path' => '/baz', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
        ];

        $actual = RequestProcessor::process($paths, $requestMock);

        $this->assertEquals($expected, $actual);
    }

    public function testRouteWithSpecificMethods()
    {
        $requestMock = $this->createRequestMock([['method' => 'get', 'args' => '_route', 'return' => 'acme_foo_bar'], 'getMethod' => '*']);

        $paths = [
            ['path' => '/foo', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
            $expected = ['path' => 'acme_foo_bar', 'limit' => 10, 'period' => 10, 'methods' => ['*']],
            ['path' => '/baz', 'limit' => 10, 'period' => 10, 'methods' => ['GET', 'POST']],
        ];

        $actual = RequestProcessor::process($paths, $requestMock);

        $this->assertEquals($expected, $actual);
    }

    private function createRequestMock($methods): \PHPUnit\Framework\MockObject\MockObject
    {
        $requestMock = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        foreach ($methods as $key => $value) {
            if (is_array($value)) {
                $requestMock
                    ->expects($this->exactly(1))
                    ->method($value['method'])
                    ->with($value['args'])
                    ->willReturn($value['return'])
                ;

                continue;
            }

            $requestMock
                ->expects($this->exactly(1))
                ->method($key)
                ->willReturn($value)
            ;
        }

        return $requestMock;
    }
}