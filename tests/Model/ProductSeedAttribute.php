<?php

namespace Tests\Model;

use Attribute;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use Override;

#[Attribute(Attribute::TARGET_CLASS)]
class ProductSeedAttribute extends TableAttribute implements MapperFunctionInterface
{
    public function __construct()
    {
        parent::__construct('products', ProductSeedAttribute::class);
    }

    #[Override]
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        // Return the SKU value that's already set in the instance
        if ($instance instanceof ActiveRecordProduct) {
            return $instance->getSku();
        }
        return $value;
    }
}
