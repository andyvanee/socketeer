<?php

namespace Socketeer;

use React\Stream\ThroughStream;

class Broadcaster {
    public static function create() {
        return new ThroughStream();
    }
}
