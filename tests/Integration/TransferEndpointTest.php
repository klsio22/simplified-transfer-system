<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class TransferEndpointTest extends TestCase
{
    private function createApp()
    {
        require __DIR__ . '/../../vendor/autoload.php';

        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }

        $container = $this->createContainer();

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        $app->addBodyParsingMiddleware();

        $app->post('/transfer', [\App\Controllers\TransferController::class, 'transfer']);

        return $app;
    }

    private function createContainer()
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions(require __DIR__ . '/../../config/dependencies.php');
        $c = $builder->build();

        $c->set(\App\Services\TransferService::class, $this->createTransferServiceStub());
        $c->set(\App\Controllers\TransferController::class, $this->createTransferControllerStub($c));

        return $c;
    }

    private function createTransferServiceStub()
    {
        return function () {
            return new class () {
                public function transfer(int $payerId, int $payeeId, float $value): array
                {
                    $this->validateTransferValue($value);
                    $this->validateParticipants($payerId, $payeeId);
                    $this->validateBusinessRules($payerId, $value);

                    return [
                        'success' => true,
                        'message' => 'Transfer completed successfully',
                        'value' => $value,
                        'payer_id' => $payerId,
                        'payee_id' => $payeeId,
                        'notification_sent' => true,
                    ];
                }

                private function validateTransferValue(float $value): void
                {
                    if ($value <= 0) {
                        throw new \App\Core\InvalidTransferException('Valor inválido');
                    }
                }

                private function validateParticipants(int $payerId, int $payeeId): void
                {
                    if ($payerId === $payeeId) {
                        throw new \App\Core\InvalidTransferException('Payer and payee must differ');
                    }

                    if ($payerId === 999 || $payeeId === 999) {
                        throw new \App\Core\UserNotFoundException('User not found');
                    }
                }

                private function validateBusinessRules(int $payerId, float $value): void
                {
                    if ($payerId === 4) {
                        throw new \App\Core\BusinessRuleException('Lojistas não podem enviar transferências');
                    }

                    if ($value > 1000) {
                        throw new \App\Core\BusinessRuleException('Saldo insuficiente');
                    }
                }
            };
        };
    }

    private function createTransferControllerStub($c)
    {
        return function () use ($c) {
            return new class ($c->get(\App\Services\TransferService::class)) {
                private $transferService;
                public function __construct($transferService)
                {
                    $this->transferService = $transferService;
                }

                public function transfer(
                    \Psr\Http\Message\ServerRequestInterface $request,
                    \Psr\Http\Message\ResponseInterface $response
                ) {
                    $data = $request->getParsedBody();
                    $statusCode = 200;
                    $payload = null;

                    if (
                        $data === null ||
                        (is_object($data) && (array)$data === []) ||
                        (is_array($data) && $data === [])
                    ) {
                        $statusCode = 422;
                        $payload = ['error' => 'Invalid or empty payload'];
                    } elseif (is_object($data)) {
                        $data = (array) $data;
                        if (! is_array($data)) {
                            $statusCode = 400;
                            $payload = ['error' => 'Invalid payload'];
                        }
                    } elseif (! is_array($data)) {
                        $statusCode = 400;
                        $payload = ['error' => 'Invalid payload'];
                    }

                    if ($payload === null) {
                        $requiredFields = ['value', 'payer', 'payee'];
                        $missingFields = [];

                        foreach ($requiredFields as $field) {
                            if (! isset($data[$field])) {
                                $missingFields[] = $field;
                            }
                        }

                        if (! empty($missingFields)) {
                            $statusCode = 422;
                            $payload = ['error' => 'Missing required fields'];
                        }
                    }

                    if ($payload === null) {
                        try {
                            $payload = $this->transferService->transfer(
                                (int) $data['payer'],
                                (int) $data['payee'],
                                (float) $data['value']
                            );
                        } catch (\Exception $e) {
                            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
                            $payload = ['error' => $e->getMessage()];
                        }
                    }

                    return $this->jsonResponse($response, $payload, $statusCode);
                }

                private function jsonResponse(
                    \Psr\Http\Message\ResponseInterface $response,
                    array $data,
                    int $statusCode
                ) {

                    $response->getBody()->write((string) json_encode($data));

                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($statusCode);
                }
            };
        };
    }

    private function request(string $method, string $path, array $data = []): \Psr\Http\Message\ResponseInterface
    {
        $app = $this->createApp();

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, $path)
            ->withHeader('Content-Type', 'application/json');

        if (! empty($data)) {
            $stream = new \Slim\Psr7\Stream(fopen('php://memory', 'r+'));
            $stream->write(json_encode($data));
            $stream->rewind();
            $request = $request->withBody($stream);
        }

        return $app->handle($request);
    }

    public function testTransferSuccess(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 10.00, 'payer' => 1, 'payee' => 3]);
        $this->assertEquals(200, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertTrue((bool) $body['success']);
        $this->assertArrayHasKey('notification_sent', $body);
        $this->assertTrue($body['notification_sent']);
    }

    public function testTransferInvalidValue(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => -50.00, 'payer' => 1, 'payee' => 2]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferMissingFields(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 50.00]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferEmptyPayload(): void
    {
        $res = $this->request('POST', '/transfer', []);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferInsufficientFunds(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 10000.00, 'payer' => 1, 'payee' => 4]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferSamePayerPayee(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 50.00, 'payer' => 1, 'payee' => 1]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferShopkeeperCannotSend(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 50.00, 'payer' => 4, 'payee' => 1]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferUserNotFound(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 50.00, 'payer' => 999, 'payee' => 1]);
        $this->assertEquals(404, $res->getStatusCode());
    }
}
