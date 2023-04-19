<?php

namespace Test;

class Items
{
    protected $storeId;
    protected $ItemId;
    protected $qty;

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param mixed $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->ItemId;
    }

    /**
     * @param mixed $ItemId
     */
    public function setItemId($ItemId)
    {
        $this->ItemId = $ItemId;
    }

    /**
     * @return mixed
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param mixed $qty
     */
    public function setQty($qty)
    {
        $this->qty = $qty;
    }

}
