<?php
$db_ip = 'sincere.se';           

$db_user = 'sincere-read';
$db_pass = 'secretPassword';

// the name of the database that you create for footprints.
$db_name = 'crawled';
$mysqli = new mysqli($db_ip, $db_user, $db_pass, $db_name);
if(!$mysqli)
{
	die('Could not connect: ' . $mysqli->error);
}
$mysqli->set_charset('utf8');


//$mysqli->select_db($db_name);
?>
