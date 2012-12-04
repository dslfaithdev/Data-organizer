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

$tars = glob($argv[1]."/{*.tgz,*.tar.gz}", GLOB_BRACE);
$index = 1;
foreach($tars as $tar){
  if( substr_compare($tar, '.tar.gz', -strlen('.tar.gz'), strlen('.tar.gz')) === 0 )
    $csv = basename(substr($tar, 0, -7));
  else
    $csv = basename(substr($tar, 0, -4));
  $stat = sprintf("[%".strlen(count($tars))."d/%d] (%5.1F%%) %s ", $index, count($tars), $index++/count($tars)*100, $csv);
  print PHP_EOL.$stat;
  exec("tar -xzvf $tar -C $tmpDir 2>&1 | awk '{print $2;}' | sort -n", $jsons);
  $table = [];
  $i = 1;
  foreach($jsons as $json){
    print "\r ".$stat.sprintf(" - %6.2F%%  ",$i++/count($jsons)*100);
    if( substr($json, -strlen('.json')) != '.json') continue; //does not end with '.json'
    try {
      parseJsonString(file_get_contents($tmpDir."/".$json), $table);
      unlink($tmpDir."/".$json);
      if( memory_get_usage(true) > 858993459) { // 2GB / 2.5
        createInserts($csv, $table, $db);
        if (!$db->ping()) $db = new mysqli("localhost", "root", "", "sincere");
        insertToDB($table, $db);
        unset($table); $table = [];
      }
    } catch(Exception $e) {
#      fwrite(STDERR, $json . " - " . $e->getMessage() . " ". $e->getFile() . ":". $e->getLine(). PHP_EOL);
      file_put_contents("error.log", $json . ",". $e->getMessage().",".$e->getFile().":".$e->getLine().PHP_EOL, FILE_APPEND);
    }
  }
  try {
    createInserts($csv, $table, $db);
    if (!$db->ping()) $db = new mysqli("localhost", "root", "", "sincere");
    insertToDB($table, $db);
    unset($table);
  if( is_dir($tmpDir."/".$jsons[0]))
    rmdir($tmpDir."/".$jsons[0]);
    exec('rm -r '.$tmpDir."/".$jsons[0].' 2>&1 > /dev/null');
  } catch(Exception $e) {}
  unset($jsons);
  exec('mv -v '.$csv.'*.sql '.$outDir);
}
try {
  $db->close();
} catch(Exception $e) {}
?>
