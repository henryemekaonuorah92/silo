<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silo\Silo();
$app->get('/', function(){});
$app->run();
