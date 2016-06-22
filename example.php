<?php

require_once 'vendor/autoload.php';

class Users
{
    public $id;
    public $name;
    public $createdate;
}

$mapper = new \ByJG\MicroOrm\Mapper(Users::class, 'users', 'id');

$repository = new \ByJG\MicroOrm\Repository(new \ByJG\AnyDataset\ConnectionManagement('sqlite:///tmp/teste.db'), $mapper);

$result = $repository->get(2);


print_r($result);


// Insert
$users = new Users();
$users->name = 'Bla99991919';
$users->createdate = '2015-08-09';
$repository->save($users);

print_r($users);

// update
$users->name = 'mudou';
$repository->save($users);



//$query = new \ByJG\MicroOrm\Query();
//
//$query->table('users u')
//    ->where('u.id = [[bla]]', ['id' => 1])
//print_r($query->getSelect());

