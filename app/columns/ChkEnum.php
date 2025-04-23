<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;

# userid
enum ChkEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    case CHK = 'chk';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return '';
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        $chks = [];
        if(strpos($data,',') !==false){
            $chks = explode(',', $data);
        }else{
            $chks = [$data];
        }
        return $chks;
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[0] ?? 'required' );
        $validation->space()->disliking([',','-'])->alnum();
    }
}