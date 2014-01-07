<?
if(!isset($argv[1]))
  die("Must provide filename as first argument");

require_once('parser.php');
require_once('config.php');

$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);
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

$tars = rglob(rtrim($argv[1],'/')."/{*.tgz,*.tar.gz,*.tar,}", GLOB_BRACE);
foreach($tars as &$tar) {
  if(!is_file($tar)) {
    $tar = (unset) $tar;
    continue;
  }
  if( substr_compare($tar, '.tar', -strlen('.tar'), strlen('.tar')) === 0 ) {
    $tarStat = $tar ."," . filemtime($tar);
    if( strpos(file_get_contents("done.tar.log"),$tarStat) !== false) {
      // file have been parsed and have not been modified since
      $tar = (unset) $tar;
      continue;
    }
  }
}
$tars=array_filter($tars);

if(file_exists("done.log"))
  $tars = array_diff($tars, explode("\n", file_get_contents("done.log")));
$index = 1;
if(count($tars) > 0)
  file_put_contents("done.log", date("c",time())."\n", FILE_APPEND);

foreach($tars as $tar){
  if( substr_compare($tar, '.tar.gz', -strlen('.tar.gz'), strlen('.tar.gz')) === 0 )
    $csv = basename(substr($tar, 0, -7));
  else
    $csv = basename(substr($tar, 0, -4));
  $stat = sprintf("[%".strlen(count($tars))."d/%d] (%5.1F%%) %s ", $index, count($tars), $index++/count($tars)*100, $csv);
  print $stat. "- uncompressing";
  $tarCmd="tar -xzvf ";
  if( substr_compare($tar, '.tar', -strlen('.tar'), strlen('.tar')) === 0 )
    $tarCmd="tar -xvf ";
  exec($tarCmd."'".addslashes($tar)."' -C $tmpDir 2>&1 | awk '{print $2;}' | sort -n", $jsons);
  $table = [];
  $i = 1;
  foreach($jsons as $json){
    printf("\r%s- %6.2F%% %18s",$stat,$i++/count($jsons)*100, " ");
    if( substr($json, -strlen('.json')) != '.json' | !is_file($tmpDir."/".$json)) continue; //does not end with '.json'
    try {
      parseJsonString(file_get_contents($tmpDir."/".$json), $table);
      unlink($tmpDir."/".$json);
      if( memory_get_usage() > 1073741824) { // 1GB = 1073741824, 2GB (2147483648)/ 2.5
        print "\033[18D- writing to file.";
        createInserts($csv, $table, $db);
        print "\033[5DDB.  ";
        if (!$db->ping()) {
          $db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);
          if ($db->connect_error) {
            die('Connect Error (' . $db->connect_errno . ') '
              . $db->connect_error);
          }
        }
        insertToDB($table, $db);
        $table = [];
      }
    } catch(Exception $e) {
#      fwrite(STDERR, $json . " - " . $e->getMessage() . " ". $e->getFile() . ":". $e->getLine(). PHP_EOL);
      file_put_contents("error.log", $json . ",". $e->getMessage().",".$e->getFile().":".$e->getLine().PHP_EOL, FILE_APPEND);
 /*     if(strpos($e->getMessage(),"Broken post") === false)
  *      try { unlink($tmpDir."/".$json); } catch(Exception $ex) {}
  *    if(strpos($e->getMessage(),"Empty post") === false)
  *      try { unlink($tmpDir."/".$json); } catch(Exception $ex) {}
  */
    }
  }
  try {
    print "\033[18D- writing to file.";
    createInserts($csv, $table, $db);
    print "\033[5DDB.  ";
    if (!$db->ping()) {
      $db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);
      if ($db->connect_error) {
        die('Connect Error (' . $db->connect_errno . ') '
          . $db->connect_error);
      }
    }
    insertToDB($table, $db);
    unset($table);
  if( is_dir($tmpDir."/".$jsons[0]))
    rmdir($tmpDir."/".$jsons[0]);
    exec('rm -r '.$tmpDir."/".$jsons[0].' 2>&1 > /dev/null');
  } catch(Exception $e) {}
  unset($jsons);
  exec('mv -v '.$csv.'*.sql '.$outDir.' 2>&1');
  printf("\r%s- DONE%22s".PHP_EOL, $stat," ");
  if( substr_compare($tar, '.tar', -strlen('.tar'), strlen('.tar')) === 0 ) {
    file_put_contents("done.tar.log", $tarStat."\n", FILE_APPEND);
    $tarStat = $tar ."," . filemtime($tar);
  } else
    file_put_contents("done.log", $tar."\n", FILE_APPEND);
}
try {
  $db->close();
} catch(Exception $e) {}

function rglob($pattern, $flags = 0) {
  $files = glob($pattern, $flags);
  foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
    $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
  }
  return $files;
}
?>
