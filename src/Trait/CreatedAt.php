<?php

namespace ByJG\MicroOrm\Trait;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\MapperFunctions;

trait CreatedAt
{
    /**
     * @var string|null
     */
    #[FieldAttribute(fieldName: "created_at", updateFunction: MapperFunctions::READ_ONLY, insertFunction: MapperFunctions::NOW_UTC)]
    protected ?string $createdAt = null;

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}