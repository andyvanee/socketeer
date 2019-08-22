<?php

namespace Socketeer;

use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class Server {
    private $host;
    private $port;
    private $handlers = [];
    private $settings = [];

    public function __construct($host = '0.0.0.0', $port = 8080) {
        $this->host = $host;
        $this->port = $port;
        $this->set('loop', Loop::create());
        $this->set('uri', $host . ':' . $port);
    }

    public function set(string $key, $value) {
        $this->settings[$key] = $value;
    }

    public function get(string $key) {
        return $this->settings[$key];
    }

    public function register(HandlerInterface ...$handlers) {
        foreach ($handlers as $handler) {
            $handler->register($this);
            $this->handlers[] = $handler;
        }
    }

    public function run() {
        $loop = $this->get('loop');
        $uri = $this->get('uri');

        $handlers = array_values($this->handlers);

        // "Bottom" handler in case all else fails
        $handlers[] = function ($request) {
            return new Response(500, [], 'server error');
        };

        $server = new HttpServer($handlers);

        $server->on('error', function (\Exception $e) {
            file_put_contents('php://stdout', 'Error: ' . $e->getMessage() . PHP_EOL);
        });

        $server->listen(new SocketServer($uri, $loop));

        $loop->run();
    }

    public function log($formatString, ...$arguments) {
        file_put_contents('php://stdout', sprintf($formatString, ...$arguments) . "\n");
    }
}
