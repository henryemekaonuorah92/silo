<?php

if (preg_match('/\.(?:png|jpg|jpeg|gif|js|css)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // return the request as is
}

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silo\Silo([
    //'em.dsn' => 'sqlite:///silo.sqlite'
    'em.dsn' => 'mysql://root@127.0.0.1:3306/projectx'
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
