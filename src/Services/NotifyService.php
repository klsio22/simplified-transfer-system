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
     * @param bool $silentMode When true, suppresses error logging
     * @param Client|null $client Optional Guzzle client (useful for injecting mocks in tests)
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
     * Send notification asynchronously (non-blocking)
     * Uses Guzzle's postAsync with ->wait(false) to prevent blocking
     * the main transaction flow. Failures are logged but don't impact
     * the transfer response.
     *
     * @param int $payeeId The ID of the user to notify
     * @return void
     */
    public function notify(int $payeeId): void
    {
        try {
            // postAsync + wait(false) = truly async, fire-and-forget
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
     * Send notification synchronously (BLOCKING)
     * ⚠️ WARNING: This method BLOCKS until the notification service responds.
     * Use only in testing or scenarios where blocking is acceptable.
     * For production transfers, use notify() instead (non-blocking).
     *
     * @param int $payeeId The ID of the user to notify
     * @return bool True if notification was sent successfully, false otherwise
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
