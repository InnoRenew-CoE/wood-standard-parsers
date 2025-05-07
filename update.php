<?php
error_reporting(E_ALL ^ E_WARNING);

echo "Updates.php" . "\n";
include_once "eota.php";
include_once "standards.php";

store_eads();
store_etas();
store_standards();
