<?
require_once('parser.php');

if(!isset($argv[1]))
  die("Must provide filename as first argument");

$tmpDir = "./tmp";//sys_get_temp_dir();
if(!is_dir($tmpDir))
  mkdir($tmpDir);

foreach(glob($argv[1].'/*.tar.gz') as $tar){
  print $tar.PHP_EOL;
  exec("tar -xzvf $tar -C $tmpDir 2>&1 | awk '{print $2;}' | sort -n", $jsons);
  $csv = "./out/".basename(substr($tar, 0, -7));
  foreach($jsons as $json){
    if( substr($json, -strlen('.json')) != '.json') continue; //does not end with '.json'
    try {
      exportToCsv($csv, parseJsonString(file_get_contents($tmpDir."/".$json)));
      unlink($tmpDir."/".$json);
    } catch(Exception $e) {
      fwrite(STDERR, $json . " - " . $e->getMessage() . " ". $e->getFile() . ":". $e->getLine(). PHP_EOL);
      file_put_contents("error.log", $json . ",". $e->getMessage().PHP_EOL, FILE_APPEND);
    }
  }
  try {
  if( is_dir($tmpDir."/".$jsons[0]))
    rmdir($tmpDir."/".$jsons[0]);
    exec('rm -r '.$tmpDir."/".$jsons[0].' 2>&1 > /dev/null');
  } catch(Exception $e) {}
  unset($jsons);
  exec('mv -v '.$csv.'* '.$argv[1]);
}
#    pg_close($dbconn);
?>
