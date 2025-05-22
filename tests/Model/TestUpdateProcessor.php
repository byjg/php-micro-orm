<?php

namespace Tests\Model;

use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use Override;

class TestUpdateProcessor implements EntityProcessorInterface
{
    #[Override]
    public function process(array $instance): array
    {
        $instance['name'] .= "-updated";
        $instance['createdate'] = "2023-02-20";
        return $instance;
    }
}