<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;

enum LastLogoutDateEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;
    use \Flex\Banana\Traits\TimeZoneTrait;

    case LAST_LOGOUT_DATE = 'last_logout_date';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return $this->nowInTZ( UTCGMTTIME );
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return $this->toTZFormat( $data, UTCGMTTIME, getenv("TIMEZONE"), R::arrays('timezone_formats'));
    }

    public function validate(mixed $data = null, ...$params): void
    {
    }
}