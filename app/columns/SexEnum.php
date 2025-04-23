<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Array\ArrayHelper;

# userid
enum SexEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    case SEX = 'sex';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return trim( $data );
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return ((new ArrayHelper( R::arrays('sex') ))->find("code",trim($data))->value)['title'];
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[0] ?? 'required' );
        $validation->enum(
            (new ArrayHelper( R::arrays('sex') ))->pluck( 'code' )->value
        );
    }
}