<?php
namespace Kahlan\Spec\Fixture\Plugin\Monkey;

use PDO;

class User
{
    protected $_db;

    public function __construct()
    {
        $this->_db = new PDO(
            "mysql:dbname=testdb;host=localhost",
            'root',
            ''
        );
    }

    public function all()
    {
        $stmt = $this->_db->prepare('SELECT * FROM users');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create()
    {
        return new static();
    }
}
