<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Array\ArrayHelper;

enum EnableEvtEnum: string implements EnumInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    case ENABLE_EVT = 'enable_evt';

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
        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[0] ?? 'required' );
        $validation->when(function() use ($data)
        {
            // enable_evt 값 처리 (콤마로 구분된 값일 경우 배열로 변환)
            $post_enable_evt_argv = (strpos($data, ",") !== false) ?
                explode(",", $data) :
                    [$data];
            // Log::d('post_enable_evt_argv',$post_enable_evt_argv);

            // 유효한 enable_evt 값 목록을 배열로 가져오기
            $validEvents = (new ArrayHelper(R::arrays('enable_evt')))->pluck('code')->value;
            // Log::d('validEvents',$validEvents);

            // 유효하지 않은 값들을 찾기
            $invalidEvents = array_diff($post_enable_evt_argv, $validEvents);
            // Log::d('invalidEvents',$invalidEvents);

            return (!empty($invalidEvents)) ? true : false;
        })->null()->disliking([',']);
    }
}