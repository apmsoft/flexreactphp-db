<?php 
namespace My\Columns\Test;

use Flex\Banana\Interfaces\EnumValueInterface;
use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;

// 사용자 퀄럼명 정의
enum TestEnum: string implements EnumValueInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;

    # 사용자 정의 Trait 클래스
    use TestEnumTypesTrait;

    # 사용자 정의 Enum 퀄럼명 정의
    # Columns
    case ID       = 'id';
    case TITLE    = 'title';
    case SIGNDATE = 'signdate';
}