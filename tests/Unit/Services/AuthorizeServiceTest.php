<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AuthorizeService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class AuthorizeServiceTest extends TestCase
{
    private AuthorizeService $authorizeService;
    /** @var Client&\PHPUnit\Framework\MockObject\MockObject */
    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createMock(Client::class);
        $this->authorizeService = new AuthorizeService();

        $reflection = new \ReflectionClass(AuthorizeService::class);
        $property = $reflection->getProperty('client');
        $property->setValue($this->authorizeService, $this->mockClient);
    }

    public function testIsAuthorizedReturnsTrueWithAuthorizationTrue(): void
    {
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['data' => ['authorization' => true]])
        );

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('https://util.devi.tools/api/v2/authorize')
            ->willReturn($mockResponse);

        $result = $this->authorizeService->isAuthorized();

        $this->assertTrue($result);
    }

    public function testIsAuthorizedReturnsTrueWithStatusSuccess(): void
    {
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['status' => 'success'])
        );

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('https://util.devi.tools/api/v2/authorize')
            ->willReturn($mockResponse);

        $result = $this->authorizeService->isAuthorized();

        $this->assertTrue($result);
    }

    public function testIsAuthorizedReturnsFalseWithAuthorizationFalse(): void
    {
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['data' => ['authorization' => false]])
        );

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('https://util.devi.tools/api/v2/authorize')
            ->willReturn($mockResponse);

        $result = $this->authorizeService->isAuthorized();

        $this->assertFalse($result);
    }

    public function testIsAuthorizedReturnsFalseOnException(): void
    {
        $request = new Request('GET', 'https://util.devi.tools/api/v2/authorize');

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('https://util.devi.tools/api/v2/authorize')
            ->willThrowException(new RequestException('Service unavailable', $request));

        $result = $this->authorizeService->isAuthorized();

        $this->assertFalse($result);
    }

    public function testIsAuthorizedReturnsFalseOnConnectionTimeout(): void
    {
        $request = new Request('GET', 'https://util.devi.tools/api/v2/authorize');

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('https://util.devi.tools/api/v2/authorize')
            ->willThrowException(new ConnectException('Connection timeout', $request));

        $result = $this->authorizeService->isAuthorized();

        $this->assertFalse($result);
    }

    public function testIsAuthorizedReturnsFalseWithInvalidJson(): void
    {
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            'invalid json'
        );

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('https://util.devi.tools/api/v2/authorize')
            ->willReturn($mockResponse);

        $result = $this->authorizeService->isAuthorized();

        $this->assertFalse($result);
    }

    public function testIsAuthorizedReturnsFalseWithEmptyResponse(): void
    {
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([])
        );

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('https://util.devi.tools/api/v2/authorize')
            ->willReturn($mockResponse);

        $result = $this->authorizeService->isAuthorized();

        $this->assertFalse($result);
    }
}
