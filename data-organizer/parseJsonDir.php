<?
if(!isset($argv[1]))
  die("Must provide filename as first argument");

require_once('parser.php');

define('DB', 'mysql');
ini_set('memory_limit', '2G');
ini_set('mysqli.reconnect', 'on');

$db = new mysqli("localhost", "root", "", "sincere");
if ($db->connect_error) {
  die('Connect Error (' . $db->connect_errno . ') '
    . $db->connect_error);
}


$tmpDir = "./tmp";//sys_get_temp_dir();
if(!is_dir($tmpDir))
  mkdir($tmpDir);
$outDir = "./out";
if(!is_dir($outDir))
  mkdir($outDir);

$tars = glob($argv[1]."/*.json", GLOB_BRACE);
#if(file_exists("done.log"))
#  $tars = array_diff($tars, explode("\n", file_get_contents("done.log")));
        $table = [];
$index = 1;
  $i = 1; $stat='m'; $csv='errors';
foreach($tars as $json){
    printf("\r%s- %6.2F%% %18s",$stat,$i++/count($tars)*100, " ");
    if( substr($json, -strlen('.json')) != '.json') continue; //does not end with '.json'
    try {
      parseJsonString(file_get_contents($json), $table);
      unlink($json);
      if( memory_get_usage() > 1073741824) { // 1GB = 1073741824, 2GB (2147483648)/ 2.5
        print "\033[18D- writing to file.";
        createInserts($csv, $table, $db);
        print "\033[5DDB.  ";
        if (!$db->ping()) $db = new mysqli("localhost", "root", "", "sincere");
        insertToDB($table, $db);
        $table = [];
      }
    } catch(Exception $e) {
#      fwrite(STDERR, $json . " - " . $e->getMessage() . " ". $e->getFile() . ":". $e->getLine(). PHP_EOL);
      file_put_contents("error.log", $json . ",". $e->getMessage().",".$e->getFile().":".$e->getLine().PHP_EOL, FILE_APPEND);
    }
  }
  try {
    print "\033[18D- writing to file.";
    createInserts($csv, $table, $db);
    print "\033[5DDB.  ";
    if (!$db->ping()) $db = new mysqli("localhost", "root", "", "sincere");
    insertToDB($table, $db);
    unset($table);
  } catch(Exception $e) {}
try {
  $db->close();
} catch(Exception $e) {}
?>
