<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Text\TextUtil;

# 휴대전화번호
enum CellphoneEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    case CELLPHONE = 'cellphone';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return strtr($data,["-"=>""]);
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return (new TextUtil( str_replace("-","", $data) ))->numberf( '-' )->value;
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[0] ?? 'required' );
        $validation->length(8,20)->space()->disliking(['-'])->number();
    }
}