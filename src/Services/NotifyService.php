<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class NotifyService
{
    public const ENDPOINT = 'https://util.devi.tools/api/v1/notify';
    public const MOCK_ENDPOINT = '/mock/notify';

    private Client $client;
    private bool $silentMode = false;
    private string $endpoint;

    public function __construct(bool $silentMode = false)
    {
        $this->silentMode = $silentMode || (getenv('APP_ENV') === 'testing');
        $this->endpoint = (getenv('APP_ENV') === 'testing') ? self::MOCK_ENDPOINT : self::ENDPOINT;

        $this->client = new Client([
            'timeout' => 5,
            'connect_timeout' => 3,
        ]);
    }

    public function notify(int $payeeId): void
    {
        try {
            $this->client->postAsync($this->endpoint, [
                'json' => ['user_id' => $payeeId],
            ])->wait(false);
        } catch (GuzzleException $e) {
            if (! $this->silentMode) {
                error_log("Error sending notification to user {$payeeId}: " . $e->getMessage());
            }
        }
    }

    public function notifySync(int $payeeId): bool
    {
        try {
            $response = $this->client->post($this->endpoint, [
                'json' => ['user_id' => $payeeId],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            return isset($data['message']) && $data['message'] === 'Success';
        } catch (GuzzleException $e) {
            if (! $this->silentMode) {
                error_log("Error sending notification to user {$payeeId}: " . $e->getMessage());
            }

            return false;
        }
    }
}
