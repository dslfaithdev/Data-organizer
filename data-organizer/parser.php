<?
if(!isset($argv[1]))
  die("Must provide filename as first argument");
# set_error_handler("myErrorHandler");
$filename = $argv[1];
$s = file_get_contents($filename);
$dbconn = pg_connect("dbname=rezaur_search_engine_db user=socinfo password=dslbigdata")
      or die('Could not connect: ' . pg_last_error());
$query = parseJsonString($s);
#die($query);
$result = pg_query($query) or die('Query failed: ' . pg_last_error());

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
  #Warning: Illegal string offset 'from' in /usr/local/www/data/SocialCrawler/controller/parser.php on line 24
  global $argv;
  #print $GLOBALS["argv[1]"];
  die($argv[1] ."\n");
  return false;
}

function parseJsonString($string) {
  $data = preg_split("/(\r\n|\n)/", $string);
  //Parse the first row as a post(message).
  $post = json_decode(array_shift($data),true);
  if($post == "")
    throw new Exception("Empty post", E_WARNING);
  if(isset($post['to'])) {//We have a user post.
    ##$sql = "DELETE FROM user where id = ".$post['from']['id']."; ".
    $sql = "INSERT INTO fb_user (id, name) VALUES (".$post['from']['id'].", '".
      pg_escape_string($post['from']['name'])."')) EXCEPT SELECT id, name FROM fb_user;\n";
    ##  "DELETE FROM user where id =".$post['to']['data'][0]['id']."; ".
    $sql .= "INSERT INTO fb_user (id, category, name) VALUES (".
      $post['to']['data'][0]['id'].", '".pg_escape_string($post['to']['data'][0]['category']).
      "', '".pg_escape_string($post['to']['data'][0]['name'])."') ".
      "EXCEPT SELECT id, category, name FROM fb_user;\n";
    $sql .= "INSERT INTO page (id, category, name) VALUES (".
      $post['to']['data'][0]['id'].", '".pg_escape_string($post['to']['data'][0]['category']).
      "', '".pg_escape_string($post['to']['data'][0]['name'])."') ".
      "EXCEPT SELECT id, category, name FROM page;\n";
  } else {
    ##$sql = "DELETE FROM user where id = ".$post['from']['id']."; ".
    $sql = "INSERT INTO fb_user (id, category, name) VALUES (".
      $post['from']['id'].", '".pg_escape_string(isSetOr($post['from']['category'])).
      "', '".pg_escape_string($post['from']['name'])."') ".
      "EXCEPT SELECT id, category, name FROM fb_user;\n";
    $sql .= "INSERT INTO page (id, category, name) VALUES (".
      $post['from']['id'].", '".pg_escape_string(isSetOr($post['from']['category'])).
      "', '".pg_escape_string($post['from']['name'])."') ".
      "EXCEPT SELECT id, category, name FROM page;\n";

  }

  /*$sql.="DELETE FROM post where id = ".
    substr(strstr($post['id'],'_'),1) . " AND page_id = ".
    strstr($post['id'],'_', true) . "; ".*/
  $sql .=
    "INSERT INTO post (id, page_id, fb_id, message, type, picture, ".
    "story, link, link_name, link_description, link_caption, icon, ".
    "created_time, updated_time, can_remove) VALUES (".
    substr(strstr($post['id'],'_'),1) . ", ".
    strstr($post['id'],'_', true) . ", ".
    $post['from']['id'] .", '".
    pg_escape_string(isSetOr($post['message']))."', '".
    pg_escape_string($post['type'])."', '".
    pg_escape_string(isSetOr($post['picture']))."', '".
    pg_escape_string(isSetOr($post['story']))."', '".
    pg_escape_string(isSetOr($post['link']))."', '".
    pg_escape_string(isSetOr($post['name']))."', '".
    pg_escape_string(isSetOr($post['description']))."', '".
    pg_escape_string(isSetOr($post['caption']))."', '".
    pg_escape_string(isSetOr($post['icon']))."', ".
    "to_timestamp('".
    pg_escape_string(isSetOr($post['created_time'])).
    "', 'YYYY-MM-DD HH24:MI:SS'), ".
    "to_timestamp('".
    pg_escape_string(isSetOr($post['updated_time'])).
    "', 'YYYY-MM-DD HH24:MI:SS'), ".
    pg_escape_string(isSetOr($post['can_remove'], "false")).  ") ".
    "EXCEPT SELECT id, page_id, fb_id, message, type, picture, ".
    "story, link, link_name, link_description, link_caption, icon, ".
    "created_time, updated_time, can_remove FROM post;\n";

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
          $sql .= "INSERT INTO fb_user (id, name) VALUES (".$like['id'].
            ", '".pg_escape_string(isSetOr($like['name']))."') ".
            "EXCEPT SELECT id, name FROM fb_user;\n";
          $sql .= "INSERT INTO likedby (page_id, post_id, comment_id, fb_id) VALUES (".$matches[1].", ".
            $matches[2].", 0, ".$like['id'].") ".
            "EXCEPT SELECT page_id, post_id, comment_id, fb_id FROM likedby;\n";
        }
    }
    if(isset($d['ec_comments'])) {
      if(empty($d['ec_comments']['data']))
        continue;
      preg_match('/([0-9]+)_([0-9]+)\\/comments/', current($d['ec_comments']['paging']),$matches);
      foreach ($d['ec_comments']['data'] as $c) {
        if(isset($c['id'])) {
          $sql .= "INSERT INTO fb_user (id, name) VALUES (".$c['from']['id'].
            ", '".pg_escape_string($c['from']['name'])."') ".
            "EXCEPT SELECT id, name FROM fb_user;\n";
          $ids=explode('_',$c['id']);
          $sql .= "INSERT INTO comment (id, post_id, page_id, fb_id, message, can_remove, created_time) VALUES (".
            array_pop($ids).", ".
            array_pop($ids).", ".
            array_pop($ids).", ".
            $c['from']['id']. ", ".
            "'". pg_escape_string($c['message'])."', ".
            ($c['can_remove'] ? "true" : "false") . ", ".
            "to_timestamp('".
            pg_escape_string($c['created_time']).
            "', 'YYYY-MM-DD HH24:MI:SS')) ".
            "EXCEPT SELECT id, post_id, page_id, fb_id, message, can_remove, created_time FROM comment;\n";
        }
      }
    }
    if(isset($d['ec_likes'])) {
      if(empty($d['ec_likes']['data']))
        continue;
      preg_match('/([0-9])+_([0-9])+_([0-9]+)\\/likes/', current($d['ec_likes']['paging']),$matches);
      foreach ($d['ec_likes']['data'] as $like)
        if(isset($like['id'])) {
          $sql .= "INSERT INTO fb_user (id, name) VALUES ('".$like['id'].
            "', '".pg_escape_string($like['name'])."') ".
            "EXCEPT SELECT id, name FROM fb_user;\n";
          $sql .= "INSERT INTO likedby (page_id, post_id, comment_id, fb_id) VALUES (".
            $matches[1].", ".$matches[2].", ".$like['id'].") ".
            "EXCEPT SELECT page_id, post_id, comment_id, fb_id FROM likedby;\n";
        }
    }
  }
  //Normalise the $sql;
  $arr = explode("\n", $sql);
#  print count($arr)."\n";
  $arr = array_unique($arr);
#  print count($arr)."\n";
  $sql = implode("\n", $arr);
  return $sql;
}


function isSetOr(&$var, $or=NULL){
  return $var === NULL ? $or: $var;
}


?>
