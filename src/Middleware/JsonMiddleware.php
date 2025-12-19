<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class JsonMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');

        // Se for POST/PUT/PATCH e não tiver Content-Type application/json, retornar erro
        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !str_contains($contentType, 'application/json')) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Content-Type deve ser application/json',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $response = $handler->handle($request);

        return $response->withHeader('Content-Type', 'application/json');
    }
}
