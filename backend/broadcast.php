<?php

require_once __DIR__ . '/vendor/autoload.php';

if (in_array($argv, '--mute')) {
    require_once __DIR__ . '/mute_logger.php';
}

use Sn4k3\Application;

$app = new Application();