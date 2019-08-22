<?php

namespace Socketeer;

use Socketeer\{Server, HandlerInterface};
use Psr\Http\Message\ResponseInterface as IRes;
use Psr\Http\Message\ServerRequestInterface as IReq;
use React\Http\Response;

class HttpController implements HandlerInterface {
    public function __construct(array $routes) {
        $this->routes = $routes;
    }

    public function setHeaders() {
        return [];
    }

    public function register(Server $server) {
        // HttpController does not require any special server setup
    }

    public function __invoke(IReq $request, callable $next): IRes {
        $path = $request->getUri()->getPath();

        $action = $this->routes[$path] ?? null;

        if ($action && method_exists($this, $action)) {
            try {
                $response = $this->$action($request);
                $headers = $this->setHeaders();
                if (is_string($response)) {
                    $headers['Content-type'] = 'text/html';
                    return new Response(200, $headers, $response);
                }
                if (is_array($response)) {
                    $headers['Content-type'] = 'application/json';
                    $body = json_encode($response) . "\n";
                    return new Response(200, $headers, $body);
                }
                if (!$response instanceof IRes) {
                    throw new \Exception('Response does not implement ResponseInterface');
                }
                return $response;
            } catch (\Throwable $err) {
                return new Response(500, [], $err->getMessage());
            }
        }

        return $next($request);
    }
}
