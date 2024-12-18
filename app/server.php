<?php
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Db\DbMySql;
use Flex\Banana\Classes\Db\DbPgSql;
use Flex\Banana\Classes\Db\DbCouch;
use Flex\Banana\Classes\Db\WhereSql;
use Flex\Banana\Classes\Db\WhereCouch;
use Flex\Banana\Classes\Db\DbCipherGeneric;

# autoload
require __DIR__. '/vendor/autoload.php';

# Log
Log::init( Log::MESSAGE_ECHO );
Log::setDebugs('i','d','v','w','e');

# env
define('DB_HOST3', "flexreactphp-couchdb" );
define('DB_HOST2', "flexreactphp-postgres" );
define('DB_HOST', "flexreactphp-mysql" );
define('DB_NAME', getenv('DB_DATABASE'));
define('DB_USERID', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_PORT3', 5984);
define('DB_PORT2', 5432);
define('DB_PORT', 3306);

define('DB_HASH_KEY', "asdfsadfsafdsafdsafdsafdsafdsafdsafdsa");

Log::d('DB_HOST',DB_HOST);
Log::d('DB_NAME',DB_NAME);
Log::d('DB_USERID',DB_USERID);
Log::d('DB_PASSWORD',DB_PASSWORD);

// 허용할 IP 주소 목록
$allowedIps = ['192.168.65.1']; // 허용 IP 주소

# class
$browser = new React\Http\Browser();

# mysql
$mysql = new DbManager(new DbMySql(new WhereSql()));
$mysql->connect(host: DB_HOST, dbname: DB_NAME, user: DB_USERID, password: DB_PASSWORD, port: DB_PORT, charset: "utf8");

# pgsql
$pgsql = new DbManager(new DbPgSql(new WhereSql()));
$pgsql->connect(host: DB_HOST2, dbname: DB_NAME, user: DB_USERID, password: DB_PASSWORD, port: DB_PORT2, charset:"utf8");

# CouchDB
$couchdb = new DbManager(new DbCouch(new WhereCouch()));
$couchdb->connect(host:DB_HOST3, dbname: DB_NAME, user: DB_USERID, password: DB_PASSWORD, port: DB_PORT3, charset:"utf8");

# router
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($mysql, $pgsql, $couchdb)
{
    # test
    $r->addRoute('GET', '/', function(Requested $requested): string {
        return  JsonEncoder::toJson( ["result"=>"true","msg"=>"Hello"] );
    });

    # 사용자 패키지 관련 테스트
    $r->addGroup('/service', function (FastRoute\RouteCollector $r)
    {
        $r->addRoute('GET', '/test', function(Requested $requested): string {
            return  (new \My\Service\Test\Test())->do();
        });
        $r->addRoute('GET', '/r', function(Requested $requested): string {
            return  (new \My\Service\R\Reso($requested))->do();
        });
    });

    # mysql 관련 작업 테스트
    $r->addGroup('/db/mysql', function (FastRoute\RouteCollector $r) use ($mysql)
    {
        $r->addRoute('POST', '/list', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db($requested, $mysql))->doList();
        });
        $r->addRoute('POST', '/insert', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db($requested, $mysql))->doInsert();
        });
        $r->addRoute('POST', '/edit', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db($requested, $mysql))->doEdit();
        });
        $r->addRoute('POST', '/update', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db($requested, $mysql))->doUpdate();
        });
        $r->addRoute('POST', '/delete', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db($requested, $mysql))->doDelete();
        });
    });

    # postgres 관련 작업 테스트
    $r->addGroup('/db/pgsql', function (FastRoute\RouteCollector $r) use ($pgsql)
    {
        $r->addRoute('POST', '/list', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db($requested, $pgsql))->doList();
        });
        $r->addRoute('POST', '/insert', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db($requested, $pgsql))->doInsert();
        });
        $r->addRoute('POST', '/edit', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db($requested, $pgsql))->doEdit();
        });
        $r->addRoute('POST', '/update', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db($requested, $pgsql))->doUpdate();
        });
        $r->addRoute('POST', '/delete', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db($requested, $pgsql))->doDelete();
        });
    });

    # couchdb 관련 작업 테스트
    $r->addGroup('/db/couchdb', function (FastRoute\RouteCollector $r) use ($couchdb)
    {
        $r->addRoute('POST', '/list', function(Requested $requested) use ($couchdb): string {
            return  (new \My\Service\Test\Db($requested, $couchdb))->doList();
        });
        $r->addRoute('POST', '/insert', function(Requested $requested) use ($couchdb): string {
            return  (new \My\Service\Test\Db($requested, $couchdb))->doInsert();
        });
        $r->addRoute('POST', '/edit', function(Requested $requested) use ($couchdb): string {
            return  (new \My\Service\Test\Db($requested, $couchdb))->doEdit();
        });
        $r->addRoute('POST', '/update', function(Requested $requested) use ($couchdb): string {
            return  (new \My\Service\Test\Db($requested, $couchdb))->doUpdate();
        });
        $r->addRoute('POST', '/delete', function(Requested $requested) use ($couchdb): string {
            return  (new \My\Service\Test\Db($requested, $couchdb))->doDelete();
        });
    });

    # couchdb 멀티 쿼리 테스트
    $r->addGroup('/db/couchdb2', function (FastRoute\RouteCollector $r) use ($couchdb)
    {
        $r->addRoute('POST', '/list', function(Requested $requested) use ($couchdb): string {
            return  (new \My\Service\Test\DbCouchMulti($requested, $couchdb))->doList();
        });
        $r->addRoute('POST', '/insert', function(Requested $requested) use ($couchdb): string {
            return  (new \My\Service\Test\DbCouchMulti($requested, $couchdb))->doInsert();
        });
        $r->addRoute('POST', '/update', function(Requested $requested) use ($couchdb): string {
            return  (new \My\Service\Test\DbCouchMulti($requested, $couchdb))->doUpdate();
        });
    });

    # Distinct
    $r->addGroup('/db', function (FastRoute\RouteCollector $r) use ($mysql,$pgsql)
    {
        $r->addRoute('POST', '/mysql/distinct', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db2($requested, $mysql))->doDistinct();
        });
        $r->addRoute('POST', '/pgsql/distinct', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db2($requested, $pgsql))->doDistinct();
        });
    });

    # Join
    $r->addGroup('/db', function (FastRoute\RouteCollector $r) use ($mysql,$pgsql)
    {
        $r->addRoute('POST', '/mysql/join', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db2($requested, $mysql))->doJoin();
        });
        $r->addRoute('POST', '/pgsql/join', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db2($requested, $pgsql))->doJoin();
        });
    });

    # GroupBy
    $r->addGroup('/db', function (FastRoute\RouteCollector $r) use ($mysql,$pgsql)
    {
        $r->addRoute('POST', '/mysql/groupby', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db2($requested, $mysql))->doGroupBy();
        });
        $r->addRoute('POST', '/pgsql/groupby', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db2($requested, $pgsql))->doGroupBy();
        });
    });

    # Sub Query
    $r->addGroup('/db', function (FastRoute\RouteCollector $r) use ($mysql,$pgsql)
    {
        $r->addRoute('POST', '/mysql/subquery', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db2($requested, $mysql))->doSubQuery();
        });
        $r->addRoute('POST', '/pgsql/subquery', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db2($requested, $pgsql))->doSubQuery();
        });
    });

    # Db Aes Encrypt/Decrypt
    $r->addGroup('/db/cipher', function (FastRoute\RouteCollector $r) use ($mysql,$pgsql)
    {
        # mysql
        $r->addRoute('POST', '/mysql/list', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db3($requested, $mysql,
            new DbCipherGeneric(new \Flex\Banana\Classes\Db\CipherMysqlAes256Cbc(DB_HASH_KEY, $mysql))
            ))->doList();
        });
        $r->addRoute('POST', '/mysql/insert', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db3($requested, $mysql,
                new DbCipherGeneric(new \Flex\Banana\Classes\Db\CipherMysqlAes256Cbc(DB_HASH_KEY, $mysql))
            ))->doInsert();
        });
        $r->addRoute('POST', '/mysql/edit', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db3($requested, $mysql,
            new DbCipherGeneric(new \Flex\Banana\Classes\Db\CipherMysqlAes256Cbc(DB_HASH_KEY, $mysql))
            ))->doEdit();
        });
        $r->addRoute('POST', '/mysql/update', function(Requested $requested) use ($mysql): string {
            return  (new \My\Service\Test\Db3($requested, $mysql,
            new DbCipherGeneric(new \Flex\Banana\Classes\Db\CipherMysqlAes256Cbc(DB_HASH_KEY, $mysql))
            ))->doUpdate();
        });

        # pgsql
        $r->addRoute('POST', '/pgsql/list', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db3($requested, $pgsql,
            new DbCipherGeneric(new \Flex\Banana\Classes\Db\CipherPgsqlAes256Cbc( DB_HASH_KEY))
            ))->doList();
        });
        $r->addRoute('POST', '/pgsql/insert', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db3($requested, $pgsql,
            new DbCipherGeneric(new \Flex\Banana\Classes\Db\CipherPgsqlAes256Cbc( DB_HASH_KEY))
            ))->doInsert();
        });
        $r->addRoute('POST', '/pgsql/edit', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db3($requested, $pgsql,
            new DbCipherGeneric(new \Flex\Banana\Classes\Db\CipherPgsqlAes256Cbc( DB_HASH_KEY))
            ))->doEdit();
        });
        $r->addRoute('POST', '/pgsql/update', function(Requested $requested) use ($pgsql): string {
            return  (new \My\Service\Test\Db3($requested, $pgsql,
            new DbCipherGeneric(new \Flex\Banana\Classes\Db\CipherPgsqlAes256Cbc( DB_HASH_KEY))
            ))->doUpdate();
        });

    });
});


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
    ->catch(function ($e) use ($deferred){
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