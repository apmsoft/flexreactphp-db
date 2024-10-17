<?php
namespace My\Columns\Test;

use Flex\Banana\Classes\Date\DateTimez;

trait TestEnumTypesTrait
{
    # ID
    public function setId(int $id): self
    {
        ($enum = self::byName('ID')) && $this->setValue($enum->value, $id);
        return $this;
    }

    public function getId(): ?int
    {
        return ($enum = self::byName('ID')) !== null ? $this->getValue($enum->value) : null;
    }

    # TITLE
    public function setTitle(string $title): self
    {
        ($enum = self::byName('TITLE')) && $this->setValue($enum->value, $title);
        return $this;
    }

    public function getTitle(): ?string
    {
        return ($enum = self::byName('TITLE')) !== null ? $this->getValue($enum->value) : null;
    }

    # SIGNDATE
    public function setSigndate(string $signdate): self
    {
        ($enum = self::byName('SIGNDATE')) && $this->setValue($enum->value, $signdate);
        return $this;
    }

    public function getSigndate(?string $format = 'Y-m-d H:i:s'): ?string
    {
        if (($enum = self::byName('SIGNDATE')) === null) {
            return null;
        }
        $value = $this->getValue($enum->value);
        return $value !== null ? (new DateTimez($value))->format($format) : null;
    }
}