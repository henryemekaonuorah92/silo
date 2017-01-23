<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silo\Silo([
    'em.dsn' => 'sqlite:///silo.sqlite'
]);
$app->get('/', function(){
    return <<<EOS
<html>
    <head><title>Silo</title></head>
    <body>
    <div id="ReactMount"></div>
    <script src="vendor.js"></script>
    <script src="app.js"></script>
    </body>
</html>
EOS;
});
$app->run();
