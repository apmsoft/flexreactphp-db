<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

enum FidEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    case FID = 'fid';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return $data;
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return $data;
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
    }
}