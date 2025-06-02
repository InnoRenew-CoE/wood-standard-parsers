<?php
// error_reporting(E_ALL ^ E_WARNING);

echo "Updates.php" . "\n";
include_once "eota.php";
include_once "standards.php";

echo "Usage: named arguments update, download\n";
echo "\t example: php update.php update=false download=true (ommited means x is false) \n";

$update = false;
$download = false;

for ($i = 1; $i < count($argv); $i++) {
    $a = $argv[$i];
    $split = explode("=", str_replace(" ", "", $a));
    $name = $split[0];
    $value = $split[1];
    switch ($name) {
        case "update":
            $update = boolval($value);
            break;
        case "download":
            $download = boolval($value);
            break;
        default:
            die("Unknown argument $name");
            break;
    }
}

echo "Update = " . json_encode($update) . "\n";
echo "Download = " . json_encode($download) . "\n";

if ($update) {
    store_eads();
    store_etas();
    store_standards();
}

if ($download) {
    // download_eads();
    download_standards();
}
