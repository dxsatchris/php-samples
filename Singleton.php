<?php


require('Global.cfg');

class SugarSingleton extends PDO {

    private static $_instance = NULL;

    /**
     * Public constructor - PHP PDO
     */
    private function __construct() {
        try {
            $settings = 'mysql:host=' . CFG_GLOBAL_MYSQL_SUGAR_SRV . ';dbname=' . CFG_GLOBAL_DB_SUGAR_NAME . ';port=' . CFG_GLOBAL_DB_SUGAR_PORT;
            parent::__construct($settings, CFG_GLOBAL_DB_SUGAR_USER, CFG_GLOBAL_DB_SUGAR_PWD, array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => true, PDO::ERRMODE_EXCEPTION => true));
        } catch (PDOException $Exception) {
            echo $Exception->getMessage(), (int) $Exception->getCode();
            die();
        }
    }

    /**
     * 
     * @return Singleton
     */
    public static function getInstance() {
        if (!(self::$_instance instanceof SugarSingleton)) {
            self::$_instance = new SugarSingleton();
        }
        return self::$_instance;
    }

}

class SageSingleton extends PDO {

    private static $_instance = NULL;

    /**
     * Public constructor - PHP PDO
     */
    public function __construct() {
        try {
            $settings = 'mysql:host=' . CFG_GLOBAL_MYSQL_SAGE_SRV . ';dbname=' . CFG_GLOBAL_DB_SAGE_NAME . ';port=' . CFG_GLOBAL_DB_SAGE_PORT;
            parent::__construct($settings, CFG_GLOBAL_DB_SAGE_USER, CFG_GLOBAL_DB_SAGE_PWD, array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => true, PDO::ERRMODE_EXCEPTION => true));
        } catch (PDOException $Exception) {
            echo $Exception->getMessage(), (int) $Exception->getCode();
            die();
        }
    }

    /**
     * 
     * @return Singleton
     */
    public static function getInstance() {
        if (!(self::$_instance instanceof SageSingleton)) {
            self::$_instance = new SageSingleton();
        }
        return self::$_instance;
    }

}
