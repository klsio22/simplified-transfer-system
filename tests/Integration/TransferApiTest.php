<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Testes de integração do endpoint /transfer
 *
 * NOTA: Requer ambiente rodando (docker-compose up)
 */
class TransferApiTest extends TestCase
{
    private Client $client;
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        // Determine base URL:
        // - explicit env `API_BASE_URL` (phpunit.xml or runtime)
        // - if running inside Docker container, reach nginx by service name
        // - otherwise default to localhost:8080
        $envBase = getenv('API_BASE_URL') ?: null;
        if ($envBase !== null && $envBase !== '') {
            $this->baseUrl = $envBase;
        } elseif (file_exists('/.dockerenv')) {
            $this->baseUrl = 'http://nginx:80';
        } else {
            $this->baseUrl = 'http://localhost:8080';
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'http_errors' => false, // Do not throw on 4xx/5xx
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @group integration
     * @group skip
     * Teste básico - transferência válida com notificação
     */
    public function testSuccessfulTransfer(): void
    {
        $response = $this->client->post('/transfer', [
            'json' => [
                'value' => 10.00,
                'payer' => 1,
                'payee' => 4,
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Transfer completed successfully', $data['message']);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('notification_sent', $data);
    }

    /**
     * @group integration
     * @group skip
     */
    public function testTransferWithMissingFields(): void
    {
        $response = $this->client->post('/transfer', [
            'json' => [
                'value' => 10.00,
                // Faltando payer e payee
            ],
        ]);

        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
    }

    /**
     * @group integration
     * @group skip
     */
    public function testTransferFromShopkeeper(): void
    {
        $response = $this->client->post('/transfer', [
            'json' => [
                'value' => 10.00,
                'payer' => 4, // Lojista
                'payee' => 1,
            ],
        ]);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Shopkeepers', $data['error']);
    }

    /**
     * @group integration
     * @group skip
     */
    public function testTransferWithInsufficientBalance(): void
    {
        $response = $this->client->post('/transfer', [
            'json' => [
                'value' => 999999.00,
                'payer' => 1,
                'payee' => 2,
            ],
        ]);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Insufficient balance', $data['error']);
    }

    /**
     * @group integration
     * @group skip
     */
    public function testTransferWithInvalidPayload(): void
    {
        $response = $this->client->post('/transfer', [
            'body' => 'invalid json',
        ]);

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @group integration
     * @group skip
     */
    public function testTransferToSelf(): void
    {
        $response = $this->client->post('/transfer', [
            'json' => [
                'value' => 10.00,
                'payer' => 1,
                'payee' => 1, // Mesmo usuário
            ],
        ]);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('yourself', $data['error']);
    }

    /**
     * @group integration
     * @group skip
     * Teste - Notificação enviada com sucesso após transferência
     */
    public function testTransferWithSuccessfulNotification(): void
    {
        $response = $this->client->post('/transfer', [
            'json' => [
                'value' => 15.00,
                'payer' => 2,
                'payee' => 3,
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertTrue($data['notification_sent']);
    }

    /**
     * @group integration
     * @group skip
     * Teste - Transferência completa mesmo se notificação falhar
     */
    public function testTransferCompleteEvenIfNotificationFails(): void
    {
        $response = $this->client->post('/transfer', [
            'json' => [
                'value' => 20.00,
                'payer' => 1,
                'payee' => 2,
            ],
        ]);

        // Transfer should succeed regardless of notification status
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('notification_sent', $data);
    }
