<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Predis\Client as Redis;

class NotifyService
{
    private string $notifyUrl;

    public function __construct(
        private Client $httpClient,
        private ?Redis $redis = null
    ) {
        $this->notifyUrl = $_ENV['NOTIFY_URL'] ?? 'https://util.devi.tools/api/v1/notify';
    }

    /**
     * Enfileira notificação para envio assíncrono
     *
     * @param array<string, mixed> $payload
     */
    public function enqueue(array $payload): void
    {
        if ($this->redis) {
            $this->redis->rpush('notifications', [json_encode($payload)]);
        } else {
            // Fallback: envia sincronamente se Redis não estiver disponível
            $this->send($payload);
        }
    }

    /**
     * Envia notificação diretamente (síncrono)
     *
     * @param array<string, mixed> $payload
     */
    public function send(array $payload): bool
    {
        try {
            $response = $this->httpClient->post($this->notifyUrl, [
                'json' => $payload,
                'timeout' => 5,
                'connect_timeout' => 3,
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (GuzzleException $e) {
            // Log do erro, mas não interrompe o fluxo
            error_log('Falha ao enviar notificação: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Processa notificações da fila (usado pelo worker)
     */
    public function processQueue(): void
    {
        if (!$this->redis) {
            return;
        }

        while (true) {
            $item = $this->redis->blpop(['notifications'], 5);

            if ($item) {
                $payload = json_decode($item[1], true);
                $this->send($payload);
            }
        }
    }
}
