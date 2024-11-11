<?php

namespace ByJG\MicroOrm\Trait;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\MapperFunctions;

trait UpdatedAt
{
    /**
     * @var string|null
     */
    #[FieldAttribute(fieldName: "updated_at", updateFunction: MapperFunctions::NOW_UTC)]
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