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
     * Consulta o serviço autorizador externo
     * 
     * @return bool True se a transferência foi autorizada
     */
    public function isAuthorized(): bool
    {
        try {
            $response = $this->client->get('https://util.devi.tools/api/v2/authorize');
            $data = json_decode((string) $response->getBody(), true);
            
            // Verifica se a resposta contém a chave 'data' e 'authorization' como true
            if (isset($data['data']['authorization']) && $data['data']['authorization'] === true) {
                return true;
            }

            // Check alternative message formats (Portuguese or English)
            if (isset($data['message']) && ($data['message'] === 'Autorizado' || $data['message'] === 'Authorized')) {
                return true;
            }

            return false;
        } catch (GuzzleException $e) {
            // Log error (use real logger in production)
            error_log("Error querying authorization service: " . $e->getMessage());
            
            // In case of error, deny authorization for safety
            return false;
        }
    }
}
