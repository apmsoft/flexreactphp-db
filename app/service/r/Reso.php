<?php
namespace My\Service\R;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Interfaces\DoInterface;
use Flex\Banana\Classes\Model;
use Flex\Banana\Utils\Requested;

class Reso implements DoInterface {
    public function __construct(
        private Requested $requested
    ) { }

    public function do(?array $params=[]) : ?string
    {
        # model
        $model = new Model($params+$this->requested->get()->fetch());

        # output
        return JsonEncoder::toJson([
            "result"  => "true",
            "sysmsg"  => R::fetch('sysmsg'),
            "strings" => R::fetch('strings'),
            "arrays"  => R::fetch('arrays'),
            "tables"  => R::fetch('tables'),
            "numbers" => R::fetch('numbers'),
            "msg"     => $model->fetch()
        ]);
    }
}
