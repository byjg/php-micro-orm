<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\Exception\InvalidArgumentException;

class ORMSubject
{
    const EVENT_INSERT = 'insert';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';
    const EVENT_CHANGE = 'select';
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
    protected $observers = [];

    public function addObserver(ObserverProcessorInterface $observerProcessor, Repository $observer_in) {
        $observer_in->getDbDriver()->log("Observer: entity " . $observer_in->getMapper()->getTable() . ", listening for {$observerProcessor->getObserverdTable()}");
        if (!isset($this->observers[$observerProcessor->getObserverdTable()])) {
            $this->observers[$observerProcessor->getObserverdTable()] = [];
        }
        foreach ($this->observers[$observerProcessor->getObserverdTable()] as $observer) {
            if (get_class($observer->getObserverdProcessor()) === get_class($observerProcessor) && get_class($observer->getRepository()) === get_class($observer_in)) {
                throw new InvalidArgumentException("Observer already exists");
            }
        }
        $this->observers[$observerProcessor->getObserverdTable()][] = new ObserverProcessorInternal($observerProcessor, $observer_in);
    }

    public function notify($entitySource, $event, $data, $oldData = null) {
        if (!isset($this->observers[$entitySource])) {
            return;
        }
        foreach ((array)$this->observers[$entitySource] as $observer) {
            $observer->log("Observer: notifying " . $observer->getMapper()->getTable() . ", changes in $entitySource");
            $observer->getObserverdProcessor()->process(new ObserverData($entitySource, $event, $data, $oldData, $observer->getRepository()));
        }
    }

    public function clearObservers() {
        $this->observers = [];
    }
}