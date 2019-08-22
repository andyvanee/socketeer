<?php

namespace Socketeer;

use Voryx\WebSocketMiddleware\WebSocketConnection;
use Voryx\WebSocketMiddleware\WebSocketMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ratchet\RFC6455\Messaging\Message;

class ControllerSocket {
    private $broadcaster;
    private $currentHandle;
    private $handler;

    public function send(string $action, $data = null, $meta = null) {
        $this->currentHandle->send($this->serializeMessage($action, $data, $meta));
    }

    public function broadcast(string $action, $data = null, $meta = null) {
        $this->broadcaster->write($this->serializeMessage($action, $data, $meta));
    }

    private function serializeMessage(string $action, $data = null, $meta = null) {
        $meta = $meta ?? [];
        $meta['ts'] = time();
        $data = $data ?? [];
        return json_encode([$action, $meta, $data]);
    }

    public function __construct($controller, $loop) {
        $this->controller = $controller;

        $this->loop = $loop;
        $this->path = sprintf('/%s', $controller::$identifier);
        $this->controller->log('Controller registered at %s', $this->path);
        $this->broadcaster = Broadcaster::create();

        $wscallback = function (
            WebSocketConnection $conn,
            ServerRequestInterface $request,
            ResponseInterface $response
        ) {
            static $user = 1;

            $broadcastHandler = function ($data) use ($conn) {
                $conn->send($data);
            };

            $this->broadcaster->on('data', $broadcastHandler);

            $conn->on('message', function (Message $message) use ($conn, $user) {
                $this->currentHandle = $conn;
                $this->dispatchMessage($message);
            });

            $conn->on('error', function (\Throwable $e) use ($user, $broadcastHandler) {
                $this->broadcaster->removeListener('data', $broadcastHandler);
            });

            $conn->on('close', function () use ($user, $broadcastHandler) {
                $this->broadcaster->removeListener('data', $broadcastHandler);
            });

            $user++;
        };

        $this->handler = new WebSocketMiddleware([$this->path], $wscallback);
    }

    private function dispatchMessage(Message $message) {
        if ($message->getPayload() == 'ping') {
            $conn->send('pong');
        } else {
            $action = new ControllerAction($this->controller);
            $action->dispatch($message);
        }
    }

    public function __invoke($request, $next) {
        return $this->handler->__invoke($request, $next);
    }
}
