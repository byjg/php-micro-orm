<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\Enum\ObserverEvent;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\ObserverProcessorInterface;
use Throwable;

class ORMSubject
{
    // Define a singleton instance
    private static ?ORMSubject $instance = null;

    private function __construct() {
        // Do nothing
    }

    // Define a singleton method
    public static function getInstance(): ?ORMSubject
    {
        if (self::$instance == null) {
            self::$instance = new ORMSubject();
        }
        return self::$instance;
    }

    /**
     * @var array<string, ObserverProcessorInternal[]>
     */
    protected array $observers = [];

    public function addObserver(ObserverProcessorInterface $observerProcessor, Repository $repoObserverIn): void
    {
        $repoObserverIn->getExecutor()->getDriver()->log("Observer: entity " . $repoObserverIn->getMapper()->getTable() . ", listening for {$observerProcessor->getObservedTable()}");
        if (!isset($this->observers[$observerProcessor->getObservedTable()])) {
            $this->observers[$observerProcessor->getObservedTable()] = [];
        }
        /** @var ObserverProcessorInternal $observer */
        foreach ($this->observers[$observerProcessor->getObservedTable()] as $observer) {
            if (get_class($observer->getObservedProcessor()) === get_class($observerProcessor) && get_class($observer->getRepository()) === get_class($repoObserverIn)) {
                throw new InvalidArgumentException("Observer already exists");
            }
        }
        $this->observers[$observerProcessor->getObservedTable()][] = new ObserverProcessorInternal($observerProcessor, $repoObserverIn);
    }

    public function notify(string $entitySource, ObserverEvent $event, mixed $data, mixed $oldData = null): void
    {
        if (!isset($this->observers[$entitySource])) {
            return;
        }
        foreach ($this->observers[$entitySource] as $observer) {
            $observer->log("Observer: notifying " . $observer->getMapper()->getTable() . ", changes in $entitySource");

            $observerData = new ObserverData($entitySource, $event, $data, $oldData, $observer->getRepository());

            try {
                $observer->getObservedProcessor()->process($observerData);
            } catch (Throwable $e) {
                $observer->getObservedProcessor()->onError($e, $observerData);
            }
        }
    }

    public function clearObservers(): void
    {
        $this->observers = [];
    }
}