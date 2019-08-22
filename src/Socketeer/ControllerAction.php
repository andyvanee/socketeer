<?php

namespace Socketeer;

use Ratchet\RFC6455\Messaging\Message;
use ReflectionClass, ReflectionException;

class ControllerAction {
    public function __construct(Controller $c) {
        $this->controller = $c;
        $this->reflection = new ReflectionClass($this->controller);
    }

    public function dispatch(Message $message) {
        $payload = $message->getPayload();

        $json = @json_decode($payload, true);

        // Fallback on message with string param (max length 48 characters)
        $action = $json[0] ?? substr(preg_replace('|[^\w]+|', '', $payload), 0, 48);

        $data = $json[2] ?? null;

        if (!$action) {
            return $this->controller->log('Error: dispatch without action');
        }

        try {
            $method = $this->reflection->getMethod($action);
        } catch (ReflectionException $ex) {
            return $this->controller->log('Controller method does not exist %s', $action);
        }

        $params = $method->getParameters();
        $p = array_shift($params);

        // Create parameter for controller action
        if ($p) {
            $messageClass = $p->getClass();

            if (!$messageClass) {
                return $this->controller->log(
                    'Cannot call method \'%s\' without declaring the message type',
                    $action
                );
            }

            $cls = $messageClass->name;

            try {
                $obj = new $cls($data);
            } catch (\Throwable $th) {
                return $this->controller->log(
                    'Could not create parameter for \'%s\'',
                    $action
                );
            }
        } else {
            $obj = null;
        }

        try {
            return $obj ? $this->controller->$action($obj) : $this->controller->$action();
        } catch (\Throwable $th) {
            return $this->controller->log(
                'Uncaught Exception in controller action\'%s\'',
                $action
            );
        }
    }
}
