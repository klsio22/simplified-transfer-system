<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AuthorizeService
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
     *
     * Returns true immediately when SKIP_AUTH=1 or APP_ENV=testing to
     * allow running tests without depending on external service.
     */
    public function isAuthorized(): bool
    {
        // Bypass para desenvolvimento ou testes
        if (getenv('SKIP_AUTH') === '1' || getenv('APP_ENV') === 'testing') {
            return true;
        }

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
            error_log("Error contacting authorization service: " . $e->getMessage());
        }

        return $authorized;
    }
}
