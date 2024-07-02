<?php

namespace ByJG\MicroOrm;

use Throwable;

class ObserverOnErrorData
{

    protected Throwable $exception;
    protected $data;
    protected $oldData;

    public function __construct(Throwable $exception, $data, $oldData)
    {
        $this->exception = $exception;
        $this->data = $data;
        $this->oldData = $oldData;
    }

    /**
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getOldData()
    {
        return $this->oldData;
    }
}