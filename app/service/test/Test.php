<?php
namespace My\Service\Test;

use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Date\DateTimez;
use Flex\Banana\Interfaces\DoInterface;

class Test implements DoInterface {
    public function __construct() {

    }

    public function do(?array $params=[]) : ?string
    {
        return JsonEncoder::toJson([
            "result" => "true",
            "msg" => sprintf(
                "Test2 Time : %s ", ((new DateTimez("now"))->format('Y-m-d H:i:s'))
            )
        ]);
    }
}
