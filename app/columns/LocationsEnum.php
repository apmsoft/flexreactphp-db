<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Json\JsonEncoder;

enum LocationsEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    case LOCATIONS = 'locations';

    # [lat,lng]
    public function filter(mixed $data = null, ...$params): mixed
    {
        return JsonEncoder::toJson( $data );
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return json_decode($data , true);
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
    }
}