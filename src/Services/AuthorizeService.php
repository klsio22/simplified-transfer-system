<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class AuthorizeService
{
    private Client $client;
    private ?LoggerInterface $logger;

    /**
     * @param Client|null $client Optional Guzzle client for testing
     * @param LoggerInterface|null $logger Optional PSR-3 logger
     */
    public function __construct(?Client $client = null, ?LoggerInterface $logger = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => 5,
            'connect_timeout' => 3,
        ]);

        $this->logger = $logger;
    }

    public function isAuthorized(): bool
    {
        $authorized = false;

        try {
            $response = $this->client->get('https://util.devi.tools/api/v2/authorize');
            $data = json_decode((string) $response->getBody(), true);

            if (isset($data['data']['authorization']) && $data['data']['authorization'] === true) {
                $authorized = true;
            }

            if (isset($data['status']) && $data['status'] === 'success') {
                $authorized = true;
            }
        } catch (GuzzleException $e) {
            $this->logger?->warning('Error contacting authorization service: ' . $e->getMessage());
        }

        return $authorized;
    }
}
