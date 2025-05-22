---
sidebar_position: 14
---

# Using FieldAlias

Field alias is an alternate name for a field. This is useful for disambiguation on join and left join queries.
Imagine in the example above if both tables ITEM and ORDER have the same field called 'ID'.

In that scenario, the value of ID will be overridden. The solution is use the FieldAlias like below:

```php
<?php
// Create the Mapper and the proper fieldAlias
$orderMapper  = new \ByJG\MicroOrm\Mapper(...);
$orderMapper->addFieldMapping(FieldMapping::create('id')->withFieldAlias('orderid'));
$itemMapper  = new \ByJG\MicroOrm\Mapper(...);
$itemMapper->addFieldMappping(FieldMapping::create('id')->withFieldAlias('itemid'));

$query = \ByJG\MicroOrm\Query::getInstance()
    ->field('order.id', 'orderid')
    ->field('item.id', 'itemid')
        /* Other fields here */
    ->table('order')
    ->join('item', 'order.id = item.orderid')
    ->where('name like :part', ['part' => 'A%']);

// Will return a collection of Orders and Items:
// $collection = [
//     [ $order, $item ],
//     [ $order, $item ],
//     ...
// ];
$collection = $orderRepository->getByQuery(
    $query,
    [
        $itemRepository->getMapper()
    ]
);
```

You can also add a MAPPER as a Field. In that case the MAPPER will create the field and the correct aliases.

```php
<?php
$query = \ByJG\MicroOrm\Query::getInstance()
    ->fields([
        $orderRepository->getMapper(),
        $itemRepository->getMapper,
    ]);
```
