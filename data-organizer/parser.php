<?
// define('DB', 'psql')
ini_set('memory_limit', '64M');

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

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
      my_escape($post['from']['name']),
      "NULL"));
    array_push($table["fb_user"], array(
      $post['to']['data'][0]['id'],
      my_escape($post['to']['data'][0]['name']),
      my_escape($post['to']['data'][0]['category'])));
    array_push($table["page"], array(
      $post['to']['data'][0]['id'],
      my_escape($post['to']['data'][0]['name']),
      my_escape($post['to']['data'][0]['category'])));
  } else {
    array_push($table["fb_user"], array(
      $post['from']['id'],
      my_escape($post['from']['name']),
      my_escape($post['from']['category'])));
    array_push($table["page"], array(
      $post['from']['id'],
      my_escape($post['from']['name']),
      my_escape($post['from']['category'])));

  }

  array_push($table["post"], array(
    substr(strstr($post['id'],'_'),1),
    strstr($post['id'],'_', true),
    $post['from']['id'],
    my_escape(isSetOr($post['message'])),
    my_escape(isSetOr($post['type'], "''")),
    my_escape(isSetOr($post['picture'])),
    my_escape(isSetOr($post['story'])),
    my_escape(isSetOr($post['link'])),
    my_escape(isSetOr($post['name'])),
    my_escape(isSetOr($post['description'])),
    my_escape(isSetOr($post['caption'])),
    my_escape(isSetOr($post['icon'])),
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
    $post['shares'], $post['likes'], $post['comments'], $post['actions']);
  $missed = json_encode($post);
  $missed = '{"id":"'.$msg_id.'","missed":['.$missed.']},'."\n";
#  print $missed."\n";
  file_put_contents('missed_data.json', $missed, FILE_APPEND);


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
            my_escape($like['name']),
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
            $c['from']['id'], my_escape($c['from']['name']), "NULL"));
          $ids=explode('_',$c['id']);
          array_push($table["comment"], array(
            array_pop($ids), array_pop($ids), array_pop($ids), $c['from']['id'],
            my_escape($c['message']),
            ($c['can_remove'] ? "true" : "false"),
            "to_timestamp('".  pg_escape_string(isSetOr($c['created_time'])).  "', 'YYYY-MM-DD HH24:MI:SS')"));
        }
      }
    }
    if(isset($d['ec_likes'])) {
      if(empty($d['ec_likes']['data']))
        continue;
      preg_match('/([0-9]+)_([0-9]+)_([0-9]+)\\/likes/', current($d['ec_likes']['paging']),$matches);
      foreach ($d['ec_likes']['data'] as $like)
        if(isset($like['id'])) {
          array_push($table["fb_user"], array(
            $like['id'], my_escape($like['name']), "NULL"));
          array_push($table["likedby"], array(
            $matches[1], $matches[2], $matches[3], $like['id'], "to_timestamp('".isSetOr($like['created_time'])."', 'YYYY-MM-DD HH24:MI:SS')"));
        }
    }
  }

  return $table;
}

function insertToDB($query, $db) {
  foreach ($query as &$t)
    foreach ($t as &$line)
      $line = "(".implode(",", $line).")";
  $sql = "";
  //Test with temp tables: http://robbat2.livejournal.com/214267.html
  //Better solution..
  //http://stackoverflow.com/questions/7463842/postgresql-clean-way-to-insert-records-if-they-dont-exist-update-if-they-do
  //$rows = $query['fb_user'];
  //$sql .= "DROP TABLE IF EXISTS tmp_fb_user; CREATE TEMPORARY TABLE tmp_fb_user AS SELECT * FROM fb_user; ";
  $sql .= "INSERT INTO fb_user VALUES ";
  $sql .= implode(",",array_unique($query['fb_user']));
  $sql .= ";"; //" INSERT INTO fb_user SELECT tmp_fb_user.* FROM tmp_fb_user WHERE (id) NOT IN (SELECT id FROM fb_user)";
  //$sql .= " EXCEPT SELECT id, name, category FROM fb_user;\n";
  $result = pg_query($db, $sql);// or die('Query failed: ' . pg_last_error());

  //$rows = $query['page'];
  $sql = "INSERT INTO page VALUES ";
  $sql .= implode(",",array_unique($query['page']));
  $sql .= " EXCEPT SELECT id, name, category FROM page;\n";
  $result = pg_query($db, $sql);// or die('Query failed: ' . pg_last_error());

  //$rows = $query['post'];
  $sql = "INSERT INTO post VALUES ";
  $sql .= implode(",",array_unique($query['post']));
  $sql .= " EXCEPT SELECT id, page_id, fb_id, message, type, picture, ".
    "story, link, link_name, link_description, link_caption, icon, ".
    "created_time, updated_time, can_remove, shares_count, likes_count, comments_count FROM post;\n";
  $result = pg_query($db, $sql);// or die('Query failed: ' . pg_last_error());

  //$rows = $query['comment'];
  $sql = "INSERT INTO comment VALUES ";
  $sql .= implode(",",array_unique($query['comment']));
  $sql .= " EXCEPT SELECT id, post_id, page_id, fb_id, message, can_remove, created_time FROM comment;\n";
  $result = pg_query($db, $sql);// or die('Query failed: ' . pg_last_error());

  //$rows = $query['likedby'];
  $sql = "INSERT INTO likedby VALUES ";
  $sql .= implode(",",array_unique($query['likedby']));
  $sql .= " EXCEPT SELECT page_id, post_id, comment_id, fb_id, created_time FROM likedby;\n";
  $result = pg_query($db, $sql);// or die('Query failed: ' . pg_last_error());

  if($db == NULL)
    die($sql);
  $result = pg_query($db, $sql);// or die('Query failed: ' . pg_last_error());
  /*if (!$result) {
    die("error inserting in DB");
  }
  print pg_affected_rows($result);
   */
  if($result === FALSE)
    return -1;
  return pg_affected_rows($result);
}

function exportToCsv($filePrefix, $array) {
/*
  if(myputcsv($filePrefix.".user.csv", $array['fb_user']) != 0)
    return "error opening ".$filePrefix.".user.csv";

  if(myputcsv($filePrefix.".page.csv", $array['page']) != 0)
    return "error opening ".$filePrefix.".page.csv";

  if(myputcsv($filePrefix.".post.csv", $array['post']) != 0)
    return "error opening ".$filePrefix.".user.csv";

  if(myputcsv($filePrefix.".comment.csv", $array['comment']) != 0)
    return "error opening ".$filePrefix.".comment.csv";
 */
  foreach($array as $key => $value){
    $f = fopen($filePrefix.".".$key.".csv", "a");
    if($f === FALSE)
      return "error opening ".$filePrefix.".".$key.".csv".PHP_EOL;

    foreach(array_unique($value, SORT_REGULAR) as $fields) {
      fputcsv($f, $fields, ',','"');
    }
    fclose($f);
  }
  return 0;
}
/*
function myputcsv($fileName, $a) {
  $f = fopen($fileName, "a");
  if($f === FALSE)
    return -1;

  foreach($a as $fields) {
    fputcsv($f, $fields, ',', '"');
  }
  fclose($f);

  return 0;
}
 */
function isSetOr(&$var, $or=NULL){
  return $var === NULL ? $or: $var;
}

function shiftAndEscape(&$array, $key){
  $return = $array[$key];
  unset($array[$key]);
  $array = array_values($array);
  return pg_escape_string($return);
  break;
}

function my_escape($key) {
  if(defined('DB')) {
    if(DB == "psql")
      return "'".pg_escape_string($key)."'";
  }
  return $key;
}

?>
