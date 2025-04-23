<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Json\JsonEncoder;

enum AllowedIpsEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;
    use \Flex\Banana\Traits\DelimitedStringTrait;

    case ALLOWED_IPS = 'allowed_ips';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return JsonEncoder::toJson(
            $this->parseDelimited( 
                separator:",", value: $data, default: ["0.0.0.0"]
            )
        );
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return json_decode($data, true);
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[0] ?? 'required' );
        $validation->disliking(['.',',']);
    }

    public function checkIn(string $ip, array $allowedIps) : void {
        if (!in_array('0.0.0.0', $allowedIps)) {
            if (!in_array($clientIp, $allowedIps)) {
                throw new Exception( JsonEncoder::toJson(
                    ['result'=>'false','msg_code'=>'e_not_have_login', 'msg'=>'Access Denied']
                ));
            }
        }
    }
}