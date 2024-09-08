<?php

require 'vendor/autoload.php';

use App\Application;


$application = new Application();
$application->configure($argv[1]);
$application->run();

