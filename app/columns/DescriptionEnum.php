<?php
namespace Columns;

use Flex\Banana\Traits\EntryArrayTrait;
use Flex\Banana\Traits\EnumInstanceTrait;
use Flex\Banana\Interfaces\EnumInterface;
use Flex\Banana\Traits\NullableValidationTrait;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Image\ImageGDS;
use Flex\Banana\Interfaces\ImageCompressorInterface;
use Flex\Banana\Traits\ImageCompressorBase64Trait;
use Flex\Banana\Traits\ImageComporessorEditjsTrait;

enum DescriptionEnum: string implements EnumInterface,ImageCompressorInterface
{
    # 기본필수옵션
    use EnumInstanceTrait;
    use EntryArrayTrait;
    use NullableValidationTrait;

    use ImageCompressorBase64Trait;
    use ImageComporessorEditjsTrait;

    case DESCRIPTION  = 'description';

    public function filter(mixed $data = null, ...$params): mixed
    {
        return json_encode(
            $this->compressDescriptionBase64Image(
                descriptions: json_decode($data,true),
                width:500,
                height:500
            )
        );
    }

    public function format(mixed $data = null, ...$params): mixed
    {
        return (json_decode($data, true)) ?? [];
    }

    # try{}catch(){}
    public function validate(mixed $data = null, ...$params): void
    {
        $validation = $this->checkNullOptional($this->value, R::strings($this->value), $data, $params[0] ?? 'required' );
    }

    public function getImageGDS(): ImageGDS
    {
        return new ImageGDS();
    }
}