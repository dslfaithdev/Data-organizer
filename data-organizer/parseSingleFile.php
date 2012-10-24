<?
require_once('parser.php');

if(!isset($argv[1]))
  die("Must provide filename as first argument");
$filename = $argv[1];
$s = file_get_contents($filename);
$dbconn = pg_connect("dbname=sincere user=sincere password=dslbigdata")
  or die('Could not connect: ' . pg_last_error());

insertToDb(parseJsonString($s, NULL), $dbconn);
?>
