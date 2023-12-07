<?php

namespace ByJG\MicroOrm;

interface ObserverProcessorInterface
{
    public function process(ObserverData $observerData);

    public function getObserverdTable(): string;
}