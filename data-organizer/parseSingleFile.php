<?
require_once('parser.php');

if(!isset($argv[1]))
  die("Must provide filename as first argument");
# set_error_handler("myErrorHandler");
$filename = $argv[1];
$s = file_get_contents($filename);
#$dbconn = pg_connect("dbname=rezaur_search_engine_db user=socinfo password=dslbigdata")
#      or die('Could not connect: ' . pg_last_error());

$query = parseJsonString($s, NULL);
?>
