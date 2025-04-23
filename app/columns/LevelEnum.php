<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Array\ArrayHelper;

# userid
enum LevelEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    case LEVEL = 'level';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return $data;
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return ((new ArrayHelper( $params[0] ))->find("level", $data)->value)['title'];
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
        if(!isset($params[0]) || !is_array($params[0])){
            throw new \Exception("The first parameter must be an array.");
        }

        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[1] ?? 'required' );
        $validation->enum(
            (new ArrayHelper( $params[0] ))->pluck( 'level' )->value
        );
    }
}