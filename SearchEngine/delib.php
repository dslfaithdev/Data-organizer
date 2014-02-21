<?

header('Content-Type: application/json');
set_time_limit(600);
// Report all PHP errors (see changelog)
error_reporting(E_ALL);
ini_set('display_errors string', TRUE);

######################
# Change this settings to match your setup...

$CONF = array();

$CONF['sphinx_host'] = 'localhost';
$CONF['sphinx_port'] = 9312; //this demo uses the SphinxAPI interface

$CONF['mysql_host'] = "194.47.148.113";
$CONF['mysql_port'] = "3307";
$CONF['mysql_username'] = "sincere";
$CONF['mysql_password'] = "sincerePass";
$CONF['mysql_database'] = "sincere";

$pageId = isset($_GET['pageId'])? $_GET['pageId'] : '';

$top15 = file_get_contents("lists/".$pageId.".json");
//print_r($top15);
$top15 = json_decode($top15,true);
//$return =array_as_props(array());
$mysqli = new mysqli($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password'],$CONF['mysql_database'],$CONF['mysql_port']);
foreach ($top15 as $type => $users) {
  foreach ($users as $user) {
    $comments = "";
    $sql = "SELECT message, created_time FROM comment WHERE fb_id=".$user." AND page_id=".$pageId." ORDER BY RAND() LIMIT 3";
    $result = $mysqli->query($sql);
    while($row=$result->fetch_assoc())
      $return[$type][$user]['comments'][] = $row;
    $sql = "SELECT name FROM fb_user WHERE id=".$user;
    $result = $mysqli->query($sql);
    while($row=$result->fetch_row())
      $return[$type][$user]['name'] = $row[0];
    //$info = json_decode(file_get_contents("http://graph.facebook.com/".$user), false);
    //$return[$type][$user]['info']= $info;
  }
}
if(isset($return))
  print json_encode($return);
else
  print $top15;
?>
