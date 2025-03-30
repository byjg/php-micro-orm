<?php

namespace Tests\Model;

use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use Override;

class TestInsertProcessor implements EntityProcessorInterface
{
    #[Override]
    public function process(array $instance): array
    {
        $instance['name'] .= "-processed";
        $instance['createdate'] = "2023-01-15";
        return $instance;
    }
}