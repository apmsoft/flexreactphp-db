<?php
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

use Flex\Banana\Classes\App;
use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Date\DateTimez;
use Flex\Banana\Classes\Db\DbMySqli;
use Flex\Banana\Utils\Requested;

# autoload
require __DIR__. '/vendor/autoload.php';

# config 설정
Log::init( Log::MESSAGE_ECHO );


// 허용할 IP 주소 목록
$allowedIps = ['192.168.65.1']; // 허용 IP 주소

# router
$browser = new React\Http\Browser();
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($browser)
{
    $r->addRoute('GET', '/', function(Requested $requested) use ($browser): string {
        return  JsonEncoder::toJson( ["result"=>"true","msg"=>"Hello"] );
    });

    $r->addGroup('/service', function (FastRoute\RouteCollector $r) use ($browser) 
    {
        $r->addRoute('GET', '/test', function(Requested $requested) use ($browser): string {
            return  (new \My\Service\Test\Test())->do();
        });
        $r->addRoute('GET', '/r', function(Requested $requested) use ($browser): string {
            return  (new \My\Service\R\Reso($requested))->do();
        });
    });
});

# $db = new DbMySqli("flexreact-php-mysql:test_db","test","test!@!@",3306);
$handler = function (Psr\Http\Message\ServerRequestInterface $request) use ($dispatcher)
{
    # 기본정보
    $requested = new Requested($request);

    # promise
    $deferred = new \React\Promise\Deferred();
    $deferred->promise()
    ->then(function($requested) use ($dispatcher, $deferred){
        try {
            $routeInfo = $dispatcher->dispatch($requested->getMethod(), $requested->getUri()->getPath());
            match($routeInfo[0]) {
                FastRoute\Dispatcher::NOT_FOUND => throw new \Exception(JsonEncoder::toJson(["result"=>"false","msg_code"=>404,"msg"=>"Not Found"])),
                FastRoute\Dispatcher::METHOD_NOT_ALLOWED => throw new \Exception(JsonEncoder::toJson(["result"=>"false","msg_code"=>405,"msg"=>"Method Not Allowed"])),
                FastRoute\Dispatcher::FOUND => (function() use ($requested,$routeInfo,$deferred)
                {
                    $handler = $routeInfo[1];
                    $data = $handler($requested);

                    Log::v($data);
                    $deferred->resolve(new Response(status: 200, headers: ['Content-Type' => 'application/json'], body: $data));
                })()
            };

        # 예외처리
        } catch (\Exception $e) {
            Log::e($e->getMessage());
            $deferred->resolve(new Response(status: 200, headers: ['Content-Type' => 'application/json'], body: $e->getMessage()));
        }
    })
    ->otherwise(function ($e) use ($deferred){
        $err_msg = $e->getMessage();
        Log::e( $err_msg);
        $deferred->resolve(  new React\Promise\Promise(function ($resolve) use ($err_msg) {
            $resolve(new React\Http\Message\Response( status: 200, headers: ['Content-Type' => 'application/json'], body: $err_msg));
        }));
    });

    # run
    $deferred->resolve($requested);

    # return
    return $deferred->promise();
};

$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    new React\Http\Middleware\RequestBodyBufferMiddleware(20 * 1024 * 1024), // 20 MiB per request
    new React\Http\Middleware\RequestBodyParserMiddleware(),

    # 웹브라우저 허용 URL
    function (ServerRequestInterface $request, callable $next)
    {
        $uri_path  = $request->getUri()->getPath();
        $method    = $request->getMethod();
        if($uri_path == '/favicon.ico' || $method == 'OPTIONS')
        {
            return new Response( 
                status: 200, headers: ['Content-Type' => 'application/json'],
                body: JsonEncoder::toJson(["result"=>"true","msg"=>"health"])
            );
        }

        return $next($request);
    },

    # IP 체크 미들웨어 추가
    function (ServerRequestInterface $request, callable $next) use ($allowedIps) 
    {
        $clientIp = $request->getServerParams()['REMOTE_ADDR'];
        Log::d('clientIp',$clientIp);

        # reject
        // if (!in_array($clientIp, $allowedIps)) {
        //     return new Response(
        //         status: 403,
        //         headers: ['Content-Type' => 'application/json'],
        //         body: JsonEncoder::toJson(['result'=>'false', 'msg'=>'Access Denied'])
        //     );
        // }

        return $next($request);
    },

    # 언어 및 리소스 설정
    function (ServerRequestInterface $request, callable $next)
    {
        # 언어설정
        $acceptLanguage = $request->getHeaderLine('Accept-Language');
        $languages = explode(',', $acceptLanguage);
        $primaryLanguage = substr($languages[0], 0, 2);
        Log::d('primaryLanguage',$primaryLanguage);

        # 리소스
        R::init($primaryLanguage ?? '');
        R::parser(__DIR__.'/res/values/sysmsg.json', 'sysmsg');
        R::parser(__DIR__.'/res/values/strings.json', 'strings');
        R::parser(__DIR__.'/res/values/arrays.json', 'arrays');
        R::parser(__DIR__.'/res/values/tables.json', 'tables');
        R::parser(__DIR__.'/res/values/numbers.json', 'numbers');

        return $next($request);
    },

    # 접속 토큰
    function (ServerRequestInterface $request, callable $next)
    {
        // try{
        //     $header_access_token = $request->getHeaderLine('Authorization-Access-Token') ?? 'Nan';
        //     (new CheckAccessToken( $header_access_token ))->matching();
        // }catch(\Exception $e){
        //     Log::e($e->getMessage());
        //     return new Response(
        //         status: 403,
        //         headers: ['Content-Type' => 'application/json'],
        //         body: JsonEncoder::toJson(['result'=>'false', 'msg'=>$e->getMessage()])
        //     );
        // }

        return $next($request);
    },
    $handler
);

$socket = new React\Socket\SocketServer('0.0.0.0:80');
echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . "\n";
$http->listen($socket);