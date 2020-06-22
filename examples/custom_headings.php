<?php

include('../ExportQuery.php');// include class

$eq = new ExportQuery();// init export object
$eq->sqlquery = 'SELECT * FROM `eq_test`';// set query
$eq->filename = 'users';// set filename without extension
$eq->batchcount = 1000;// set batch size
$headings = array('Col 1', 'Col 2', 'Col 3');
$eq->colheadings = $headings;// set column heading array
$eq->export();// start export