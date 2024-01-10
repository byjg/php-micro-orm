<?php

namespace ByJG\MicroOrm;

interface ObserverProcessorInterface
{
    public function process(ObserverData $observerData): void;

    public function getObservedTable(): string;
}