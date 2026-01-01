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
            error_log("Error contacting authorization service: " . $e->getMessage());
        }

        return $authorized;
    }
}
