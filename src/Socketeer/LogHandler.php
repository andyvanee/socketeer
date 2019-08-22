<?php

namespace Socketeer;

use Psr\Http\Message\ServerRequestInterface as IReq;
use Psr\Http\Message\ResponseInterface as IRes;

class LogHandler implements HandlerInterface {
    public function register(Server $server) {
        $this->server = $server;
    }

    public function __invoke(IReq $request, callable $next): IRes {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $this->server->log('%s %s', $method, $path);
        return $next($request);
    }
}
