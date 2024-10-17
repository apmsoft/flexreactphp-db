<?php
namespace My\Service\Test;

#use Flex\Banana\Classes\Json\JsonEncoder;

class Test {
    public function __construct() {

    }

    public function do() : string {

        return json_encode([
            "result" => "true",
            "msg" => "Test"
        ]);
    }
}
