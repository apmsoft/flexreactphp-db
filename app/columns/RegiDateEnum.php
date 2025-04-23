<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;

use Flex\Banana\Classes\R;

# 데이터 등록일
enum RegiDateEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use \Flex\Banana\Traits\TimeZoneTrait;

    case REGI_DATE = 'regi_date';

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