<?php

if (preg_match('/\.(?:png|jpg|jpeg|gif|js|css)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // return the request as is
}

require_once __DIR__.'/../vendor/autoload.php';

// Load configuration
$configFile = getenv('SILO_CONFIG', true) ?: getenv('SILO_CONFIG');
$configFile = $configFile?: __DIR__.'/../config.php';

$app = new Silo\Silo([
    'config.cache' => new \Silo\Base\SinglePhpFileCache($configFile, \Silo\Base\Configuration::CACHE_KEY),
    'defaultErrorHandler' => true
]);
$app->get('/', function(){
    return <<<EOS
<html>
    <head>
        <title>Silo</title>
    </head>
    <body>
    <div id="ReactMount"></div>
    <script src="vendors.js"></script>
    <script src="app.js"></script>
    </body>
</html>
EOS;
});
$app->run();
