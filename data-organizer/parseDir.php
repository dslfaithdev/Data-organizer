<?
if(!isset($argv[1]))
  die("Must provide filename as first argument" . PHP_EOL);

require_once('parser.php');
require_once('config.php');

ini_set("mysqli.reconnect", 1);
$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);
if ($db->connect_error) {
  die('Connect Error (' . $db->connect_errno . ') '
    . $db->connect_error);
}


$tmpDir = "./tmp";//sys_get_temp_dir();
if(!is_dir($tmpDir))
  mkdir($tmpDir, 0777, true);
if(!is_dir($tmpDir)) die();
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

//Sort with oldest first.
//usort($tars, create_function('$a,$b', 'return filemtime($a) - filemtime($b);'));
//Sort with the newest first.
usort($tars, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));

foreach($tars as $tar){
  exec('rm -r '.$tmpDir.' 2>&1 > /dev/null');
  if(!is_dir($tmpDir))
    mkdir($tmpDir, 0777, true);
  if(!is_dir($tmpDir)) die();
  if( substr_compare($tar, '.tar.gz', -strlen('.tar.gz'), strlen('.tar.gz')) === 0 )
    $csv = basename(substr($tar, 0, -7));
  else
    $csv = basename(substr($tar, 0, -4));
  $stat = sprintf("[%".strlen(count($tars))."d/%d] (%5.1F%%) %s ", $index, count($tars), $index++/count($tars)*100, $csv);
  print $stat. "- uncompressing";
  $tarCmd="tar -xzvf ";
  if( substr_compare($tar, '.tar', -strlen('.tar'), strlen('.tar')) === 0 )
    $tarCmd="tar -xvf ";
  $tarCmd = $tarCmd."'".addslashes($tar)."' -C $tmpDir 2>&1 | awk '{print $2;}' | sort -n";
  exec($tarCmd, $jsons);
  $table = [];
  $keys = array_keys($jsons);
  get_execution_time(true);
  $writeEveryJson = false;
  for($i=0, $n = count($keys); $i<$n;) {
    $json = &$jsons[$keys[$i++]];
    printf("\r%s- %6.2F%% %18s",$stat,$i/$n*100, " ");
    if( substr($json, -strlen('.json')) != '.json')  continue; //does not end with '.json'
    try {
      if($writeEveryJson)
        $table = [];
      parseJsonString(file_get_contents($tmpDir."/".$json), $table);
      if($writeEveryJson) {
        try {
          writeData($table, $csv);
        } catch(Exception $e) {
          rename($tmpDir."/".$json, "brokenJsons/". $json);
          print $e.PHP_EOL; file_put_contents("db-insert.error", $tar . "," . $json . ",". $e->getMessage(), FILE_APPEND);
          continue;
        }
      }
      else if( memory_get_usage() > 1073741824 || $i==$n) { // 1GB = 1073741824, 2GB (2147483648)/ 2.5
        try {
          writeData($table, $csv);
        } catch(Exception $e) {
          print "DB insert error, will start over and write for each JSON".PHP_EOL;
          file_put_contents("db-insert.error", $tar . "," . $e->getMessage(), FILE_APPEND);
          $i = 0; $writeEveryJson = true;
          continue;
        }
        unset($table);
        $table = [];
      }
    } catch(Exception $e) {
      file_put_contents("error.log", $json . ",". $e->getMessage().",".$e->getFile().":".$e->getLine().PHP_EOL, FILE_APPEND);
       try { rename($tmpDir."/".$json, "brokenJsons/". $json); } catch(Exception $ex) {}
    }
  }
  try {
    try {
      writeData($table, $csv);
    } catch(Exception $e) { print $e.PHP_EOL; file_put_contents("db-insert.error", $tar . "," . $e->getMessage(), FILE_APPEND); continue;}
    unset($table);
    if( is_dir($tmpDir."/".$jsons[0]))
      rmdir($tmpDir."/".$jsons[0]);
    exec('rm -r '.$tmpDir.' 2>&1 > /dev/null');
  } catch(Exception $e) {}
  $time = get_execution_time(true);
  unset($jsons);
  exec('mv -v '.$csv.'*.sql '.$outDir.' 2>&1');
  printf("\r%s- DONE %22s".PHP_EOL, $stat," $n jsons parsed in ". round($time,0)."s (".($n ? round($time/$n,5) : 0) .")");
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

function writeData($table, $csv) {
  $db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);
  if ($db->connect_error) {
    die('Connect Error (' . $db->connect_errno . ') '
    . $db->connect_error);
  }
  /*
   *print "\033[18D- writing to file.";
   *createInserts($csv, $table, $db);
   */
  print "\033[5DDB  ";
  insertToDB($table, $db);
  $db->close();
}


/**
 * get execution time in seconds at current point of call in seconds
 * @return float Execution time at this point of call
 */
function get_execution_time($delta = false) {
  static $microtime_start = null;
  static $microtime_delta = null;
  if($microtime_start === null) {
    $microtime_start = microtime(true);
    $microtime_delta = $microtime_start;
    return 0.0;
  }
  if($delta) {
    $delta = microtime(true) - $microtime_delta;
    $microtime_delta = microtime(true);
    return $delta;
  }
  $microtime_delta = microtime(true);
  return microtime(true) - $microtime_start;
}
?>
