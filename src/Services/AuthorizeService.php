<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ExternalServiceException;
use App\Exceptions\UnauthorizedTransferException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AuthorizeService
{
    private string $authorizeUrl;

    public function __construct(
        private Client $httpClient
    ) {
        $this->authorizeUrl = $_ENV['AUTHORIZE_URL'] ?? 'https://util.devi.tools/api/v2/authorize';
    }

    /**
     * Consulta o serviço autorizador externo
     *
     * @throws UnauthorizedTransferException
     * @throws ExternalServiceException
     */
    public function authorize(): bool
    {
        try {
            $response = $this->httpClient->get($this->authorizeUrl, [
                'timeout' => 5,
                'connect_timeout' => 3,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            // O serviço retorna {"status": "success", "data": {"authorization": true}}
            if (isset($body['data']['authorization']) && $body['data']['authorization'] === true) {
                return true;
            }

            if (isset($body['status']) && $body['status'] === 'success') {
                return true;
            }

            throw new UnauthorizedTransferException('Transferência não autorizada pelo serviço externo');
        } catch (GuzzleException $e) {
            throw new ExternalServiceException('Serviço autorizador indisponível: ' . $e->getMessage());
        }
    }
}
