<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\NotifyService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class NotifyServiceTest extends TestCase
{
    private NotifyService $notifyService;
    /** @var Client&\PHPUnit\Framework\MockObject\MockObject */
    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createMock(Client::class);

        $this->notifyService = new NotifyService();

        $reflection = new \ReflectionClass(NotifyService::class);
        $property = $reflection->getProperty('client');

        // Remove this line completely — it's deprecated and does nothing in PHP 8.1+
        // $property->setAccessible(true);

        $property->setValue($this->notifyService, $this->mockClient);
    }

    /**
     * Test successful synchronous notification
     */
    public function testNotifySyncSuccess(): void
    {
        // Mock successful response
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn(
            Utils::streamFor(json_encode(['message' => 'Success']))
        );

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->notifyService->notifySync(24);

        $this->assertTrue($result);
    }

    /**
     * Test synchronous notification with failure response
     */
    public function testNotifySyncFailure(): void
    {
        // Mock failed response
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn(
            Utils::streamFor(json_encode(['message' => 'Failed']))
        );

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->notifyService->notifySync(24);

        $this->assertFalse($result);
    }

    /**
     * Test synchronous notification with empty response
     */
    public function testNotifySyncEmptyResponse(): void
    {
        // Mock empty response
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn(Utils::streamFor(json_encode([])));

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->notifyService->notifySync(24);

        $this->assertFalse($result);
    }

    /**
     * Test synchronous notification with GuzzleException
     */
    public function testNotifySyncWithException(): void
    {
        $request = new Request('POST', 'https://util.devi.tools/api/v1/notify');

        $this->mockClient->method('post')
            ->willThrowException(
                new RequestException('Connection failed', $request)
            );

        $result = $this->notifyService->notifySync(24);

        $this->assertFalse($result);
    }

    /**
     * Test synchronous notification timeout (5s)
     */
    public function testNotifySyncTimeout(): void
    {
        $request = new Request('POST', 'https://util.devi.tools/api/v1/notify');

        $this->mockClient->method('post')
            ->willThrowException(
                new ConnectException('Connection timeout', $request)
            );

        $result = $this->notifyService->notifySync(24);

        $this->assertFalse($result);
    }

    /**
     * Test asynchronous notification doesn't throw on failure
     */
    public function testNotifyAsyncWithException(): void
    {
        $request = new Request('POST', 'https://util.devi.tools/api/v1/notify');

        // Mock postAsync that throws
        $mockPromise = $this->createMock(\GuzzleHttp\Promise\PromiseInterface::class);
        $mockPromise->method('wait')
            ->willThrowException(
                new RequestException('Connection failed', $request)
            );

        $this->mockClient->method('postAsync')->willReturn($mockPromise);

        // Should not throw, just log
        $this->notifyService->notify(24);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Test notification with different user IDs
     */
    public function testNotifyWithDifferentUserIds(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn(Utils::streamFor(json_encode(['message' => 'Success'])));

        $this->mockClient->method('post')->willReturn($mockResponse);

        // Test with common user
        $result1 = $this->notifyService->notifySync(24);
        $this->assertTrue($result1);

        // Test with shopkeeper
        $result2 = $this->notifyService->notifySync(26);
        $this->assertTrue($result2);
    }

    /**
     * Test that notify payload contains correct user_id
     */
    public function testNotifyPayloadStructure(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn(Utils::streamFor(json_encode(['message' => 'Success'])));

        // Capture the call arguments
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'https://util.devi.tools/api/v1/notify',
                $this->callback(function ($options) {
                    return isset($options['json']['user_id']) && $options['json']['user_id'] === 24;
                })
            )
            ->willReturn($mockResponse);

        $this->notifyService->notifySync(24);
    }
}
