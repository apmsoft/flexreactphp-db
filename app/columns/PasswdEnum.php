<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Cipher\CipherGeneric;
use Flex\Banana\Classes\Cipher\PasswordHash;
use Flex\Banana\Classes\Json\JsonEncoder;

# 비밀번호
enum PasswdEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;
    use \Flex\Banana\Traits\PasswordHashTrait;

    case PASSWD = 'passwd';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return $this->hashPassword( $data );
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return $data;
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[0] ?? 'required' );
        $validation->length(8,16)->space()->liking();
    }

    # 비밀번호 비교 try{}catch(){}
    public function verify(string $input_passwd, string $db_passwd): void
    {
        if(!(new CipherGeneric(new PasswordHash()))->verify($input_passwd, $db_passwd) ){
            throw new \Exception( JsonEncoder::toJson(
                [
                    "result"=>"false",
                    "msg_code"=>"w_enter_userid_passwd",
                    "msg"=>R::sysmsg('w_enter_userid_passwd')
                ]
            ));
        }
    }
}