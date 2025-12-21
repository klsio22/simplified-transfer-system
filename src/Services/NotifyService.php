<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class NotifyService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 5,
            'connect_timeout' => 3,
        ]);
    }

    /**
     * Envia notificação ao usuário recebedor (payee)
     * 
     * Executa de forma assíncrona para não bloquear a transferência
     * Em produção, isso deveria usar uma fila real (Redis, RabbitMQ, etc)
     */
    public function notify(int $payeeId): void
    {
        try {
            // Fire-and-forget: não espera resposta e não bloqueia
            $this->client->postAsync('https://util.devi.tools/api/v1/notify', [
                'json' => ['user_id' => $payeeId],
            ])->wait(false); // false = não espera completar
        } catch (GuzzleException $e) {
            // Silent log - unstable notification service should not break transfer
            error_log("Error sending notification to user {$payeeId}: " . $e->getMessage());
            
            // In production: enqueue for retry or dead letter
        }
    }

    /**
     * Versão síncrona para testes
     */
    public function notifySync(int $payeeId): bool
    {
        try {
            $response = $this->client->post('https://util.devi.tools/api/v1/notify', [
                'json' => ['user_id' => $payeeId],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            
            return isset($data['message']) && $data['message'] === 'Success';
        } catch (GuzzleException $e) {
            error_log("Error sending notification to user {$payeeId}: " . $e->getMessage());
            return false;
        }
    }
}
