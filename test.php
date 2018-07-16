<?php
require 'vendor/autoload.php';

use \Rapidmod\Mysql\Connection;
use \Rapidmod\Mysql\Table;
use \Rapidmod\Mysql\Model;
use \Rapidmod\Dev;
$info = require "database.conf.php";
Dev::printVar($info);
$Connection = Connection::init();
$Connection->addConnection($info["database"]);
//Dev::printVar($Connection);
$Table = new Table("test_types");
$Table->tableFields();
//Dev::printVar($Table);
$Model = Model::createModel("test_types");
Dev::printVar($Model->tableFields());
;