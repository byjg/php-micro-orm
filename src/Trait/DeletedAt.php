<?php

namespace ByJG\MicroOrm\Trait;

use ByJG\MicroOrm\Attributes\FieldAttribute;

trait DeletedAt
{
    /**
     * @var string|null
     */
    #[FieldAttribute(fieldName: "deleted_at", syncWithDb: false)]
    protected ?string $deletedAt = null;

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?string $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }
}