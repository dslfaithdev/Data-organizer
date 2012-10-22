<?
require_once('parser.php');

if(!isset($argv[1]))
  die("Must provide filename as first argument");

$dbconn = pg_connect("dbname=sincere user=sincere password=dslbigdata")
  or die('Could not connect: ' . pg_last_error());
$tmpDir = "./tmp";//sys_get_temp_dir();
if(!is_dir($tmpDir))
  mkdir($tmpDir);

foreach(glob($argv[1].'/*.tar.gz') as $tar){
  print $tar.PHP_EOL;
  exec("tar -xzvf $tar -C $tmpDir 2>&1 | awk '{print $2;}'", $jsons);
  //$contents = shell_exec("tar xzvf $tar -C $tmpDir"); //Extract tar
  //$jsons = explode(PHP_EOL,$contents);
  foreach($jsons as $json){
    if( substr($json, -strlen('.json')) != '.json') continue; //does not end with '.json'
    try {
      exportToCsv(substr($tar, 0, -7), parseJsonString(file_get_contents($tmpDir."/".$json)));
      unlink($tmpDir."/".$json);
    } catch(Exception $e) { print $json . "-" . $e->getMessage() . PHP_EOL; }
  }
  if( is_dir($tmpDir."/".$jsons[0]))
    rmdir($tmpDir."/".$jsons[0]);
  unset($jsons);
}
    pg_close($dbconn);
?>
