<?php

namespace ByJG\MicroOrm;

class ORMSubject
{
    const EVENT_INSERT = 'insert';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';
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
     * @var ObserverProcessorInternal[]
     */
    protected array $observers = [];

    public function addObserver(ObserverProcessorInterface $observerProcessor, Repository $observer_in): void
    {
        $observer_in->getDbDriver()->log("Observer: entity " . $observer_in->getMapper()->getTable() . ", listening for {$observerProcessor->getObservedTable()}");
        if (!isset($this->observers[$observerProcessor->getObservedTable()])) {
            $this->observers[$observerProcessor->getObservedTable()] = [];
        }
        $this->observers[$observerProcessor->getObservedTable()][] = new ObserverProcessorInternal($observerProcessor, $observer_in);
    }

    public function notify($entitySource, $event, $data, $oldData = null): void
    {
        if (!isset($this->observers[$entitySource])) {
            return;
        }
        foreach ((array)$this->observers[$entitySource] as $observer) {
            $observer->log("Observer: notifying " . $observer->getMapper()->getTable() . ", changes in $entitySource");
            $observer->getObservedProcessor()->process(new ObserverData($entitySource, $event, $data, $oldData, $observer->getRepository()));
        }
    }

    public function clearObservers(): void
    {
        $this->observers = [];
    }
}