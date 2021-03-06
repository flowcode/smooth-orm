<?php

namespace flowcode\ceibo\support;

use PDO;

/**
 * Description of TestCase
 *
 * @author Juan Manuel Agüero <jaguero@flowcode.com.ar>
 */
abstract class TestCase extends \PHPUnit_Extensions_Database_TestCase {

    static private $pdo = null;
    private $conn = null;

    final public function getConnection() {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO("mysql:host=localhost;dbname=wing-ceibo-test", "root", "root");
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, "wing-ceibo-test");
        }

        return $this->conn;
    }

}

?>
