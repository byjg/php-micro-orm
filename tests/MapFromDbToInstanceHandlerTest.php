<?php

namespace Tests;

use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\PropertyHandler\MapFromDbToInstanceHandler;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Model\CountingMapper;
use Tests\Model\Info;
use Tests\Model\Order;
use Tests\Model\OrderStatus;
use Tests\Model\Users;

/**
 * As an entity hydrator, the handler must fill each entity exactly as ObjectCopy::copy() does with the handler as
 * the property handler (the path GenericDbIterator takes for any other transformer)
 */
class MapFromDbToInstanceHandlerTest extends TestCase
{
    public static function hydrateProvider(): array
    {
        $info = new Mapper(Info::class, 'info', 'id');
        $info->addFieldMapping(FieldMapping::create('value')->withFieldName('property'));
        $info->addFieldMapping(FieldMapping::create('registrationId')->withFieldName('registration_id'));

        $selectFunction = new Mapper(Users::class, 'users', 'Id');
        $selectFunction->addFieldMapping(FieldMapping::create('name')->withSelectFunction(CountingMapper::class));

        return [
            'setters, protected properties' => [
                new Mapper(Users::class, 'users', 'Id'),
                [['id' => '1', 'name' => 'Joao', 'createdate' => '2020-01-01'], ['id' => '2', 'name' => null, 'createdate' => '']],
            ],
            'column mapped to another property, a column without property' => [
                $info,
                [['id' => '1', 'iduser' => '3', 'property' => '10.5', 'registration_id' => 'r1', 'created_at' => 'x']],
            ],
            'selectFunction' => [
                $selectFunction,
                [['id' => '1', 'name' => 'Joao', 'createdate' => '2020-01-01'], ['id' => '2', 'name' => 'Maria', 'createdate' => '2021-01-01']],
            ],
            'backed enum property, typed public properties' => [
                new Mapper(Order::class),
                [['id' => '1', 'status' => 'PAID'], ['id' => '2', 'status' => null], ['id' => '3', 'status' => OrderStatus::Pending]],
            ],
            'stdClass' => [
                new Mapper(stdClass::class, 'anything', 'id'),
                [['id' => '1', 'name' => 'Joao'], ['id' => '2', 'other' => '']],
            ],
        ];
    }

    #[DataProvider('hydrateProvider')]
    public function testHydrateFillsAsObjectCopyWithTheHandler(Mapper $mapper, array $rows): void
    {
        $class = $mapper->getEntityClass();
        $defaults = Serialize::from(new $class())->toArray();
        // One handler per query, for each path
        $hydrator = new MapFromDbToInstanceHandler($mapper);
        $handler = new MapFromDbToInstanceHandler($mapper);

        foreach ($rows as $i => $row) {
            $hydrated = new $class();
            $hydrator->hydrate($row, $hydrated, $defaults);

            $copied = new $class();
            ObjectCopy::copy(array_merge($defaults, $row), $copied, $handler);

            $this->assertEquals((array)$copied, (array)$hydrated, "Row $i");
        }
    }

    public function testHydrateUsesTheSelectFunctionAndTheMapping(): void
    {
        $mapper = new Mapper(Info::class, 'info', 'id');
        $mapper->addFieldMapping(FieldMapping::create('value')->withFieldName('property')->withSelectFunction(CountingMapper::class));

        $info = new Info();
        (new MapFromDbToInstanceHandler($mapper))->hydrate(['id' => '7', 'property' => '10.5'], $info, []);

        $this->assertEquals('7', $info->getId());
        $this->assertEquals('mapped:10.5', $info->getValue());
    }
}
