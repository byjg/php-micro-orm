<?php

namespace ByJG\MicroOrm;

use Throwable;

interface ObserverProcessorInterface
{
    public function process(ObserverData $observerData);

    public function getObserverdTable(): string;

    public function onError(Throwable $exception, ObserverData $onbserverData): void;
}