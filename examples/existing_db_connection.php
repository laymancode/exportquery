<?php

include('../ExportQuery.php');// include class

$eq = new ExportQuery();// init export object
$eq->sqlquery = 'SELECT * FROM `eq_test` limit 10';// set query
$eq->filename = 'users';// set filename without extension
$eq->batchcount = 1000;// set batch size
$db_connection = new mysqli('localhost', 'user', 'pass', 'dbnam');
$eq->con = $db_connection;// set existing database connection
$eq->export();// start export