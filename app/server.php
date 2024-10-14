<?php
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\Promise;
use React\Cache\ArrayCache;

use Flex\Banana\Classes\App;
use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Date\DateTimez;
use Flex\Banana\Classes\Db\DbMySqli;

# autoload
require __DIR__. '/vendor/autoload.php';

# classes
App::init();
R::init(App::$language ?? '');

# config 설정
Log::init( Log::MESSAGE_ECHO );

# Resource
R::parser(__DIR__.'/res/values/sysmsg.json', 'sysmsg');


$handler = function (Psr\Http\Message\ServerRequestInterface $request)
{
    $db = new DbMySqli("flexreact-php-mysql:test_db","test","test!@!@",3306);
    try {
        Log::d("request2 -> ", (new DateTimez("now"))->format('Y-m-d H:i:s'));

        #간단한 쿼리 실행
        $result = $db->query("SELECT 1 + 1 as result");
        if ($result) {
            $row = $result->fetch_assoc();
            $response = [
                'status' => 'success',
                'message' => 'Successfully connected to MySQL 8888',
                'result' => 'ddd'
            ];
            $result->free();
        } else {
            throw new Exception('Query failed: ' . $db->error);
        }

        return new React\Http\Message\Response(
            200, 
            ['Content-Type' => 'application/json'],
            json_encode($response)
        );
    } catch (Exception $e) {
        Log::e('Error details: ' . $e->getMessage());
        Log::e('Stack trace: ' . $e->getTraceAsString());
        return new React\Http\Message\Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ])
        );
    }
};

$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    new React\Http\Middleware\RequestBodyBufferMiddleware(20 * 1024 * 1024), // 20 MiB per request
    new React\Http\Middleware\RequestBodyParserMiddleware(),
    $handler
);

$socket = new React\Socket\SocketServer('0.0.0.0:80');
echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . "\n";
$http->listen($socket);