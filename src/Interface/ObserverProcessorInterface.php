<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\MicroOrm\ObserverData;
use Throwable;

interface ObserverProcessorInterface
{
    public function process(ObserverData $observerData): void;

    public function getObservedTable(): string;

    public function onError(Throwable $exception, ObserverData $observerData): void;
}