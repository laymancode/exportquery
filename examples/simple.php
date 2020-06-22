<?php

include('../ExportQuery.php');// include class

$eq = new ExportQuery();// init export object
$eq->sqlquery = 'SELECT * FROM `eq_test`';// set query
$eq->filename = 'users';// set filename without extension
$eq->export();// start export