<?php

class Database
{
    /**
     *  The MySQL link identifier created by {@link connect()}
     *
     * @var resource
     */
    public $link;


    /**
     * @var Database
     */
    private static $instance;


    /**
     *  Constructor
     *
     *  Private constructor as part of the singleton pattern implementation.
     */
    private function __construct()
    {
        $this->connect();
    }


    public function connect()
    {
        $this->link = new \phpSweetPDO\Connection('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASSWORD);

        return $this->link;
    }


    /**
     *  Get Instance
     *
     *  Gets the singleton instance for this object. This method should be called
     *  statically in order to use the Database object:
     *
     *  <code>
     *  $db = MySqlDatabase::getInstance();
     *  </code>
     *
     * @return Database
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Database();
        }

        return self::$instance->link;
    }


}