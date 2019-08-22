<?php

namespace Socketeer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Response;

class Controller implements HandlerInterface {
    public static $viewfile;

    public static $identifier;

    private $viewContents;

    public function send($action, $data) {
        $this->socket->send($action, $data);
    }

    public function broadcast($action, $data) {
        $this->socket->broadcast($action, $data);
    }

    public function addPeriodicTimer($timeout, $callback) {
        $loop = $this->server->get('loop');
        $loop->addPeriodicTimer($timeout, $callback);
    }

    public function cancelTimer($timer) {
        $loop = $this->server->get('loop');
        $loop->cancelTimer($timer);
    }

    public function __construct(array $routes) {
        $this->routes = $routes;

        $f = stream_resolve_include_path(static::$viewfile);

        if (!($f && is_file($f) && is_readable($f))) {
            throw new \Exception(
                'View file not found for controller: ' . static::$viewfile
            );
        }

        $this->viewContents = file_get_contents($f);

        if (!static::$identifier) {
            throw new \Exception(
                'Subclasses of Controller must define an identifier for their websocket'
            );
        }
    }

    public function register(Server $server) {
        $this->server = $server;
        $this->socket = new ControllerSocket($this, $server->get('loop'));
    }

    public function __invoke(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface {
        return $this->socket->__invoke($request, function ($request) use ($next) {
            $path = $request->getUri()->getPath();

            if (in_array($path, $this->routes)) {
                return new Response(200, [], $this->viewContents);
            }

            return $next($request);
        });
    }

    public function log($formatString, ...$arguments) {
        $this->server->log($formatString, ...$arguments);
    }
}
