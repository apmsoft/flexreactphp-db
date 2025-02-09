<?php
namespace My\Columns\Test;

use Flex\Banana\Classes\Date\DateTimez;

trait TestEnumTypesTrait
{
    # ID
    public function setId(mixed $id): self
    {
        $this->setValue(self::byName('ID')->value, $id);
        return $this;
    }

    public function getId(): mixed
    {
        return $this->getValue(self::byName('ID')->value) ?? null;
    }

    # TITLE
    public function setTitle(string $title): self
    {
        $this->setValue(self::byName('TITLE')->value, $title);
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->getValue(self::byName('TITLE')->value) ?? null;
    }

    # SIGNDATE
    public function setSigndate(string $signdate): self
    {
        $this->setValue(self::byName('SIGNDATE')->value, $signdate);
        return $this;
    }

    public function getSigndate(?string $format = 'Y-m-d H:i:s'): ?string
    {
        return (new DateTimez($this->getValue(self::byName('SIGNDATE')->value)))->format($format);
    }

    # VIEW COunt
    public function setViewCount(string $count): self
    {
        $this->setValue(self::byName('VIEW_COUNT')->value, $count);
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->getValue(self::byName('VIEW_COUNT')->value);
    }
}