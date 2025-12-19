<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class TransferTest extends TestCase
{
    private static string $baseUrl = 'http://localhost:8080';

    public function testSuccessfulTransfer(): void
    {
        $data = [
            'value' => 50.00,
            'payer' => 1,
            'payee' => 2,
        ];

        $response = $this->makeRequest('POST', '/transfer', $data);
        $body = json_decode($response['body'], true);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('transaction', $body);
    }

    public function testTransferWithInvalidValue(): void
    {
        $data = [
            'value' => -50.00,
            'payer' => 1,
            'payee' => 2,
        ];

        $response = $this->makeRequest('POST', '/transfer', $data);
        $body = json_decode($response['body'], true);

        $this->assertEquals(422, $response['status']);
        $this->assertTrue($body['error']);
    }

    public function testTransferWithMissingFields(): void
    {
        $data = [
            'value' => 50.00,
        ];

        $response = $this->makeRequest('POST', '/transfer', $data);
        $body = json_decode($response['body'], true);

        $this->assertEquals(422, $response['status']);
        $this->assertTrue($body['error']);
        $this->assertArrayHasKey('errors', $body);
    }

    public function testMerchantCannotSendTransfer(): void
    {
        $data = [
            'value' => 50.00,
            'payer' => 3, // Lojista
            'payee' => 1,
        ];

        $response = $this->makeRequest('POST', '/transfer', $data);
        $body = json_decode($response['body'], true);

        $this->assertEquals(422, $response['status']);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('Lojistas', $body['message']);
    }

    public function testTransferWithNonExistentUser(): void
    {
        $data = [
            'value' => 50.00,
            'payer' => 1,
            'payee' => 999, // Não existe
        ];

        $response = $this->makeRequest('POST', '/transfer', $data);
        $body = json_decode($response['body'], true);

        $this->assertEquals(404, $response['status']);
        $this->assertTrue($body['error']);
    }

    public function testTransferWithSamePayerAndPayee(): void
    {
        $data = [
            'value' => 50.00,
            'payer' => 1,
            'payee' => 1,
        ];

        $response = $this->makeRequest('POST', '/transfer', $data);
        $body = json_decode($response['body'], true);

        $this->assertEquals(422, $response['status']);
        $this->assertTrue($body['error']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function makeRequest(string $method, string $path, array $data = []): array
    {
        $ch = curl_init(self::$baseUrl . $path);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $status,
            'body' => $body,
        ];
    }
}
