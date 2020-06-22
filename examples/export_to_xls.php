<?php

include('../ExportQuery.php');// include class

$eq = new ExportQuery();// init export object
$eq->sqlquery = 'SELECT * FROM `eq_test` limit 10';// set query
$eq->filename = 'users';// set filename without extension
$eq->batchcount = 1000;// set batch size
$eq->export_to = 'xls';// set export to
$eq->export();// start export