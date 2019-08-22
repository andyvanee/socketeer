<?php

namespace Socketeer;

use React\EventLoop\Factory;

class Loop {
    public static function create() {
        return Factory::create();
    }
}
