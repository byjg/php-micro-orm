<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use ByJG\MicroOrm\Interface\ObserverProcessorInterface;

class ObserverProcessorInternal
{
    protected ObserverProcessorInterface $observerProcessor;
    protected Repository $repository;

    public function __construct(ObserverProcessorInterface $observerProcessor, Repository $repository)
    {
        $this->observerProcessor = $observerProcessor;
        $this->repository = $repository;
    }

    public function getObservedProcessor(): ObserverProcessorInterface
    {
        return $this->observerProcessor;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function log($message): void
    {
        $this->repository->getExecutor()->getDriver()->log($message);
    }

    public function getMapper(): Mapper
    {
        return $this->repository->getMapper();
    }

    public function getDbDriver(): DbDriverInterface
    {
        return $this->repository->getExecutor()->getDriver();
    }
}
