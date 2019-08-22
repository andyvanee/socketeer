<?php

namespace Socketeer;

use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};

interface HandlerInterface extends ServerExtensionInterface {
    public function __invoke(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface;
}
