<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;

use Flex\Banana\Classes\R;

# 마지막 로그인 시간
enum AlarmReaddateEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use \Flex\Banana\Traits\TimeZoneTrait;

    case ALARM_READDATE = 'alarm_readdate';

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