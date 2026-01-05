<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class NotifyService
{
    public const ENDPOINT = 'https://util.devi.tools/api/v1/notify';
    public const MOCK_ENDPOINT = '/mock/notify';

    private Client $client;
    private bool $silentMode = false;
    private string $endpoint;
    private ?LoggerInterface $logger;

    /**
     * @param bool $silentMode Suppress error logging when true
     * @param Client|null $client Optional Guzzle client for testing
     * @param LoggerInterface|null $logger Optional PSR-3 logger
     */
    public function __construct(bool $silentMode = false, ?Client $client = null, ?LoggerInterface $logger = null)
    {
        $this->silentMode = $silentMode || (getenv('APP_ENV') === 'testing');
        $this->endpoint = (getenv('APP_ENV') === 'testing') ? self::MOCK_ENDPOINT : self::ENDPOINT;

        $this->client = $client ?? new Client([
            'timeout' => 5,
            'connect_timeout' => 3,
        ]);

        $this->logger = $logger;
    }

    /**
     * Send notification asynchronously (non-blocking).
     * Uses an asynchronous POST request to perform a fire-and-forget call.
     * Failures are logged but do not affect the main flow.
     *
     * @param int $payeeId The ID of the user to notify
     * @return void
     */
    public function notify(int $payeeId): void
    {
        try {
            $this->client->postAsync($this->endpoint, [
                'json' => ['user_id' => $payeeId],
            ])->wait(false);
        } catch (GuzzleException $e) {
            if (! $this->silentMode) {
                $this->logger?->warning("Error sending notification to user {$payeeId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send notification synchronously (blocking).
     * Use only for testing or when a blocking call is acceptable.
     *
     * @param int $payeeId The ID of the user to notify
     * @return bool True on success, false on failure
     */
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
                $this->logger?->warning("Error sending notification to user {$payeeId}: " . $e->getMessage());
            }

            return false;
        }
    }
}
