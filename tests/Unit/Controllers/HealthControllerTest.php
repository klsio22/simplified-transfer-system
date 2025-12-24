<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\HealthController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;

class HealthControllerTest extends TestCase
{
    public function testHelloReturnsSuccessResponse(): void
    {
        $controller = new HealthController();
        $response = (new ResponseFactory())->createResponse(200);

        $result = $controller->hello($response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('application/json', $result->getHeaderLine('Content-Type'));

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals('Hello, World!', $body['message']);
        $this->assertEquals('ok', $body['status']);
        $this->assertArrayHasKey('timestamp', $body);
    }
}
