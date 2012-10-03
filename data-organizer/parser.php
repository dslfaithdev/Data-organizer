<?
if(!isset($argv[1]))
  die("Must provide filename as first argument");
ini_set('memory_limit', '64M');
# set_error_handler("myErrorHandler");
$filename = $argv[1];
$s = file_get_contents($filename);
$dbconn = pg_connect("dbname=rezaur_search_engine_db user=socinfo password=dslbigdata")
      or die('Could not connect: ' . pg_last_error());

$query = parseJsonString($s);
$sql ="";

$rows = $query['fb_user'];
$sql .= "INSERT INTO fb_user VALUES ";
foreach ($rows as &$line) {
  $line = "(".implode(",", $line).")";
}
$sql .= implode(",",$rows);
$sql .= " EXCEPT SELECT id, name, category FROM fb_user;\n";

$rows = $query['page'];
$sql .= "INSERT INTO page VALUES ";
foreach ($rows as &$line) {
  $line = "(".implode(",", $line).")";
}
$sql .= implode(",",$rows);
$sql .= " EXCEPT SELECT id, name, category FROM page;\n";

$rows = $query['post'];
$sql .= "INSERT INTO post VALUES ";
foreach ($rows as &$line) {
  $line = "(".implode(",", $line).")";
}
$sql .= implode(",",$rows);
$sql .= " EXCEPT SELECT id, page_id, fb_id, message, type, picture, ".
    "story, link, link_name, link_description, link_caption, icon, ".
    "created_time, updated_time, can_remove, shares_count, likes_count, comments_count FROM post;\n";

$rows = $query['comment'];
$sql .= "INSERT INTO comment VALUES ";
foreach ($rows as &$line) {
  $line = "(".implode(",", $line).")";
}
$sql .= implode(",",$rows);
$sql .= " EXCEPT SELECT id, post_id, page_id, fb_id, message, can_remove, created_time FROM comment;\n";

$rows = $query['likedby'];
$sql .= "INSERT INTO likedby VALUES ";
foreach ($rows as &$line) {
  $line = "(".implode(",", $line).")";
}
$sql .= implode(",",$rows);
$sql .= " EXCEPT SELECT page_id, post_id, comment_id, fb_id, created_time FROM likedby;\n";

#die($sql);
$result = pg_query($sql) or die('Query failed: ' . pg_last_error());
if (!$result) {
  die("error inserting in DB");
}
print pg_affected_rows($result);

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
  #Warning: Illegal string offset 'from' in /usr/local/www/data/SocialCrawler/controller/parser.php on line 24
  global $argv;
  #print $GLOBALS["argv[1]"];
  die($argv[1] ."\n");
  return false;
}

function parseJsonString($string) {
  $table = array("fb_user"=> array(),"page"=>array(),
    "post"=>array(), "comment"=> array(), "likedby"=> array());

  $data = preg_split("/(\r\n|\n)/", $string);
  //Parse the first row as a post(message).
  $post = json_decode(array_shift($data),true);
  if($post == "")
    throw new Exception("Empty post", E_WARNING);
  if(isset($post['to'])) {//We have a user post.
    array_push($table["fb_user"], array(
      $post['from']['id'],
      "'".pg_escape_string($post['from']['name'])."'",
      "NULL"));
    array_push($table["fb_user"], array(
      $post['to']['data'][0]['id'],
      "'".pg_escape_string($post['to']['data'][0]['name'])."'",
      "'".pg_escape_string($post['to']['data'][0]['category'])."'"));
    array_push($table["page"], array(
      $post['to']['data'][0]['id'],
      "'".pg_escape_string($post['to']['data'][0]['name'])."'",
      "'".pg_escape_string($post['to']['data'][0]['category'])."'"));
  } else {
    array_push($table["fb_user"], array(
      $post['from']['id'],
      "'".pg_escape_string($post['from']['name'])."'",
      "'".pg_escape_string($post['from']['category'])."'"));
    array_push($table["page"], array(
      $post['from']['id'],
      "'".pg_escape_string($post['from']['name'])."'",
      "'".pg_escape_string($post['from']['category'])."'"));

  }

  array_push($table["post"], array(
    substr(strstr($post['id'],'_'),1),
    strstr($post['id'],'_', true),
    $post['from']['id'],
    "'".pg_escape_string(isSetOr($post['message']))."'",
    "'".pg_escape_string(isSetOr($post['type'], "''"))."'",
    "'".pg_escape_string(isSetOr($post['picture']))."'",
    "'".pg_escape_string(isSetOr($post['story']))."'",
    "'".pg_escape_string(isSetOr($post['link']))."'",
    "'".pg_escape_string(isSetOr($post['name']))."'",
    "'".pg_escape_string(isSetOr($post['description']))."'",
    "'".pg_escape_string(isSetOr($post['caption']))."'",
    "'".pg_escape_string(isSetOr($post['icon']))."'",
    "to_timestamp('".  pg_escape_string(isSetOr($post['created_time'])).  "', 'YYYY-MM-DD HH24:MI:SS')",
    "to_timestamp('".  pg_escape_string(isSetOr($post['updated_time'])).  "', 'YYYY-MM-DD HH24:MI:SS')",
    pg_escape_string(isSetOr($post['can_remove'], "false")),
    0,0,0 ));

  //Remove the used info.
  $msg_id= $post['id'];
  unset($post['id'], $post['from'], $post['to'], $post['message'], $post['type'],
    $post['picture'], $post['story'], $post['link'], $post['name'],
    $post['description'], $post['caption'], $post['icon'],
    $post['created_time'], $post['updated_time'], $post['can_remove'],
    $post['likes'], $post['comments'], $post['actions']);
  $missed = json_encode($post)."\n";
#  print $missed."\n";


  foreach ($data as $line){
    $d = json_decode($line,true);
    if(isset($d['ep_likes'])) {
      if(empty($d['ep_likes']['data']))
        continue;
      preg_match('/([0-9]+)_([0-9]+)\\/likes/', current($d['ep_likes']['paging']),$matches);
      foreach ($d['ep_likes']['data'] as $like)
        if(isset($like['id'])) {
          array_push($table["fb_user"], array(
            $like['id'],
            "'".pg_escape_string($like['name'])."'",
            "NULL"));
          array_push($table["likedby"], array(
            $matches[1], $matches[2], 0, $like['id'], "to_timestamp('".isSetOr($like['created_time'])."', 'YYYY-MM-DD HH24:MI:SS')"));
        }
    }
    if(isset($d['ec_comments'])) {
      if(empty($d['ec_comments']['data']))
        continue;
      preg_match('/([0-9]+)_([0-9]+)\\/comments/', current($d['ec_comments']['paging']),$matches);
      foreach ($d['ec_comments']['data'] as $c) {
        if(isset($c['id'])) {
          array_push($table["fb_user"], array(
            $c['from']['id'], "'".pg_escape_string($c['from']['name'])."'", "NULL"));
          $ids=explode('_',$c['id']);
          array_push($table["comment"], array(
            array_pop($ids), array_pop($ids), array_pop($ids), $c['from']['id'],
            "'".pg_escape_string($c['message'])."'",
            ($c['can_remove'] ? "true" : "false"),
            "to_timestamp('".  pg_escape_string(isSetOr($c['created_time'])).  "', 'YYYY-MM-DD HH24:MI:SS')"));
        }
      }
    }
    if(isset($d['ec_likes'])) {
      if(empty($d['ec_likes']['data']))
        continue;
      preg_match('/([0-9])+_([0-9])+_([0-9]+)\\/likes/', current($d['ec_likes']['paging']),$matches);
      foreach ($d['ec_likes']['data'] as $like)
        if(isset($like['id'])) {
          array_push($table["fb_user"], array(
            $c['from']['id'], "'".pg_escape_string($c['from']['name'])."'", "NULL"));
          array_push($table["likedby"], array(
            $matches[1], $matches[2], $matches[3], $like['id'], "to_timestamp('".isSetOr($like['created_time'], "NULL")."', 'YYYY-MM-DD HH24:MI:SS')"));
        }
    }
  }

#  foreach ($table as &$arr)
#    $arr = array_unique($arr);
  return $table;
}


function isSetOr(&$var, $or=NULL){
  return $var === NULL ? $or: $var;
}


?>
