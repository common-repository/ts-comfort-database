<?php
// The file has JSON type.
header('Content-Type: application/json');

$hash = htmlentities(strip_tags($_GET['hash']), ENT_QUOTES);
require(dirname(__FILE__) . '/../../../wp-config.php');


$con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
mysqli_query($con, "SET NAMES 'utf8';");

$sql = "SELECT option_value FROM " . $table_prefix . "options WHERE option_name = '_transient_" . $hash ."' LIMIT 1;";
$results = mysqli_query($con, $sql);
$row = $results->fetch_object();


if(is_object($row))
{
    $text = $row->option_value;
   
    echo $text;
    
    // Convert to JSON to read the status.
    $obj = json_decode($text);
    
} else {
    echo json_encode(array('percent' => null, 'current_count' => null, 'total_count' => null, 'message' => null));
}
?>