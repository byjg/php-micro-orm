<?php

namespace ByJG\MicroOrm;

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

    protected array $observers = [];

    public function addObserver(string $entitySource, \Closure $closure, Repository $observer_in) {
        $observer_in->getDbDriver()->log("Observer: entity " . $observer_in->getMapper()->getTable() . ", listening for $entitySource");
        if (!isset($this->observers[$entitySource])) {
            $this->observers[$entitySource] = [];
        }
        $this->observers[$entitySource][] = ["closure" => $closure, "repository" => $observer_in];
    }

    public function notify($entitySource, $event, $data, $oldData = null) {
        if (!isset($this->observers[$entitySource])) {
            return;
        }
        foreach ((array)$this->observers[$entitySource] as $observer) {
            $observer["repository"]->getDbDriver()->log("Observer: notifying " . $observer["repository"]->getMapper()->getTable() . ", changes in $entitySource");
            $closure = $observer["closure"];
            $closure($entitySource, $event, $data, $oldData, $observer["repository"]);
        }
    }

    public function clearObservers() {
        $this->observers = [];
    }
}