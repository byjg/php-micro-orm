<?php

namespace ByJG\MicroOrm\Trait;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;

trait UpdatedAt
{
    /**
     * @var string|null
     */
    #[FieldAttribute(fieldName: "updated_at", updateFunction: NowUtcMapper::class)]
    protected ?string $updatedAt = null;

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}