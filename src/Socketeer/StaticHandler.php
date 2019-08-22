<?php

namespace Socketeer;

use Psr\Http\Message\ServerRequestInterface as IReq;
use Psr\Http\Message\ResponseInterface as IRes;
use React\Http\Response;

class StaticHandler implements HandlerInterface {
    private $dirs;

    public function __construct(array $dirs) {
        $this->mime_types = include 'mime.types.php';
        $this->dirs = $dirs;
    }

    public function register(Server $server) {
        $this->server = $server;
    }

    public function __invoke(IReq $request, callable $next): IRes {
        $path = urldecode($request->getUri()->getPath());

        $routes = array_filter(
            array_map(function ($d) use ($path) {
                $d = stream_resolve_include_path($d . $path);
                return is_file($d) && is_readable($d) ? $d : null;
            }, $this->dirs)
        );

        if (!count($routes)) {
            return $next($request);
        }

        $f = array_shift($routes);
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        $mime = $this->mime_types['mimes'][$ext] ?? 'application/octet-stream';

        return new Response(200, ['Content-type' => $mime], file_get_contents($f));
    }
}
