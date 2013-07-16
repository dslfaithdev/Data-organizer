<?php
$db_ip = 'localhost';           
$db_user = '';
$db_pass = '';
$db_port = 3306; 

// the name of the database that you create for footprints.
$db_name = 'sincere';
$mysqli = new mysqli($db_ip, $db_user, $db_pass, $db_name, $db_port);
if(!$mysqli)
{
	die('Could not connect: ' . $mysqli->error);
}
$mysqli->set_charset('utf8');


//$mysqli->select_db($db_name);
?>
