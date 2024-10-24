<?php
use React\Socket\ConnectionInterface;
use React\EventLoop\Loop;

$path = __DIR__;
require $path. '/../vendor/autoload.php';

define ('_END_TIME_',((60.0*60)*4)); // 4h
// define ('_END_TIME_',60.0); // 1m

# data set
$member_id = (isset($argv[1])) ? $argv[1] : 12;
echo $member_id;
$loop = React\EventLoop\Factory::create();
$connector = new React\Socket\Connector(array(
    'timeout' => 60.0
));

// // $ports = array(3030,3031,3032);
$ports = array(3031);
// print_r($ports);
# 초당 던지기
$timer = Loop::addPeriodicTimer(1.0, function () use ($connector,$member_id,&$ports) 
{
    $portno = array_rand($ports);
    $port = $ports[$portno];
    echo ">>>>>".$member_id.PHP_EOL;

    $send_data_ch1 = sprintf(
        "Language: ko\nToken: a2R0ZWNoLjEyNjJiOGEyZGRiMzUyYWRiNmM1N2NhZmY5MzZmOTc0YjViNzM3N2ZkMjNkN2JkMDY2YTEyYzlmZTEyM2E5ZDY\nTimeZ: +0900\n\n,5C:F2:86:44:BC:F3,%s,ch1,77,35.5471075,129.2828181,%s,0,0,78,0:0:0:0:0:0:0,0.00,0,0-0-0-0-0,0",
        date('YmdHis'),$member_id
    );

    $send_data_ch2 = sprintf(
        "Language: ko\nToken: a2R0ZWNoLjEyNjJiOGEyZGRiMzUyYWRiNmM1N2NhZmY5MzZmOTc0YjViNzM3N2ZkMjNkN2JkMDY2YTEyYzlmZTEyM2E5ZDY\nTimeZ: +0900\n\n,5C:F2:86:44:BC:F3,%s,ch2,77,35.5471075,129.2828181,%s,0,0,78,0:0:0:0:0:0:0,0.00,0,0-0-0-0-0,0",
        date('YmdHis'),$member_id
    );

    $connector->connect('115.68.73.142:'.$port)
        ->then(function (ConnectionInterface $connection) use ($send_data_ch1,$member_id) {
            $connection->write($send_data_ch1);
            $connection->on('data', function ($data) use ($connection) {
                echo $data.PHP_EOL;
                $connection->end();
            });
            $connection->on('close', function () use ($member_id){
                echo '[close] >>> ch1 ::'.$member_id . PHP_EOL;
            });

        // $connection->write("GET / HTTP/1.0\r\nHost: $member_id\r\n\r\n");
    }, function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });

    $connector->connect('115.68.73.142:'.$port)
        ->then(function (ConnectionInterface $connection) use ($send_data_ch2,$member_id) {
            $connection->write($send_data_ch2);
            $connection->on('data', function ($data) use ($connection) {
                echo $data.PHP_EOL;
                $connection->end();
            });
            $connection->on('close', function () use ($member_id){
                echo '[close] >>>  ch2 :: '.$member_id . PHP_EOL;
            });
            // $connection->end();
            // ;

        // $connection->write("GET / HTTP/1.0\r\nHost: $member_id\r\n\r\n");
    }, function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });
});

# 10초후 종료
Loop::addTimer(_END_TIME_, function () use ($timer, $member_id) {
    Loop::cancelTimer($timer);
    echo '>>>'.$member_id.' : End' . PHP_EOL;
});


$loop->run();
?>