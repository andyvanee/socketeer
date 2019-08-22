<?php

namespace Socketeer;

interface ServerExtensionInterface {
    public function register(Server $server);
}
