<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;

# 데이터 등록일
enum ToDateEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    case TODATE = 'todate';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return $data;
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return $data;
    }

    public function validate(mixed $data = null, ...$params): void
    {
        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[0] ?? 'required' );
        $validation->datef();
    }
}