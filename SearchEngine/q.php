<?
ini_set('default_charset','utf8');
header('Content-Type: application/json');
set_time_limit(59);
/*
 * For JSON:
 *   header('Content-Type: application/json');
 * For JSON-P:
 *   header('Content-Type: application/javascript');
 */
######################
# Change this settings to match your setup...

$CONF = array();

$CONF['sphinx_host'] = 'localhost';
$CONF['sphinx_port'] = 9312; //this demo uses the SphinxAPI interface

$CONF['mysql_host'] = "localhost";
$CONF['mysql_port'] = "3306";
$CONF['mysql_username'] = "sincere-local";
$CONF['mysql_password'] = "";
$CONF['mysql_database'] = "sincere";

$CONF['sphinx_index'] = "sincere_post"; // can also be a list of indexes, "main, delta"

$CONF['sphinx_attributes'] = array(
  "fb_id"=> "numeric",
  "page_id"=> "numeric",
  "likes_count"=> "numeric",
  "comments_count"=> "numeric",
  "entr_ug"=> "numeric",
  "entr_pg"=> "numeric"); // this defines attributes to use for breakdowns, each need defining as string/numeric
#can use 'excerpt' to highlight using the query, or 'asis' to show description as is.
$CONF['body'] = 'excerpt';

#the link for the title (only $id) placeholder supported
$CONF['link_format'] = '/page.php?page_id=$id';

#Change this to FALSE on a live site!
$CONF['debug'] = FALSE;

#How many results per page (also controls the number of attributes shown)
$CONF['page_size'] = 25;

#maximum number of results - should match sphinxes max_matches. default 1000
$CONF['max_matches'] = 1000;


$CONF['mysql_query'] = '
SELECT id, story AS title, message AS body
FROM post
WHERE id IN ($ids)
';

#might need to put in path to your file
#if (!empty($_GET['q']))
require("sphinxapi.php");

$json = ['status' => [
  'matches' => 0, 'total' => 0,
  'total_found' => 0, 'time' => 0,
  'query' => "" ], 'results' => []];


//Sanitise the input
$q = isset($_GET['q'])?$_GET['q']:'';

$q = preg_replace('/ OR /',' | ',$q);

$q = preg_replace('/[^\w~\|\(\)"\/=-]+/',' ',trim(strtolower($q)));
if (empty($q)) {
  die(json_encode($json));
}

//If the user entered something

//produce a version for display
$qo = $q;
if (strlen($qo) > 64) {
  $qo = '--complex query--';
}

//setup paging...
if (!empty($_GET['page'])) {
  $currentPage = intval($_GET['page']);
  if (empty($currentPage) || $currentPage < 1) {$currentPage = 1;}
  $currentOffset = ($currentPage -1)* $CONF['page_size'];
  if ($currentOffset > ($CONF['max_matches']-$CONF['page_size']) ) {
    die("Only the first {$CONF['max_matches']} results accessible");
  }
} else {
  $currentPage = 1;
  $currentOffset = 0;
}

//Connect to sphinx, and run the query
$cl = new SphinxClient();
$cl->SetServer($CONF['sphinx_host'], $CONF['sphinx_port']);
//$cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
$cl->SetMatchMode(SPH_MATCH_EXTENDED);
$cl->SetLimits($currentOffset,$CONF['page_size']); //current page and number of results

//set sorting
if(isset($_GET['orderBy'])){
  switch(strtolower($_GET['orderBy'])){
  case 'likes':
    $cl->SetSortMode(SPH_SORT_EXTENDED, 'likes_count DESC, @relevance DESC, @id DESC');
    break;
  case 'comments':
    $cl->SetSortMode(SPH_SORT_EXTENDED, 'comments_count DESC, @relevance DESC, @id DESC');
    break;
  case 'entropy':
    $cl->SetSortMode(SPH_SORT_EXTENDED, 'entr_pg DESC, @relevance DESC, @id DESC');
    break;
  case 'post-entropy':
    $cl->SetSortMode(SPH_SORT_EXTENDED, 'entr_pg DESC, @relevance DESC, @id DESC');
    break;
  case 'user-entropy':
    $cl->SetSortMode(SPH_SORT_EXTENDED, 'entr_ug DESC, @relevance DESC, @id DESC');
    break;
  case 'frequency':
    $cl->SetSortMode(SPH_SORT_EXTENDED, 'entr_ug DESC, @relevance DESC, @id DESC');
    break;
  default:
    $cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
    break;
  }
}
else
  $cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");

foreach ($CONF['sphinx_attributes'] as $attr => $type) {
  if (!empty($_GET[$attr])) {
    if ($type == 'numeric') { //string attributes must go in the fulltext query and we are not supporting this
      $cl->setFilter($attr,array(intval($_GET[$attr])));
    }
  }
}
//plain text search
$res = $cl->Query($q, $CONF['sphinx_index']);

//Check for failure
if (empty($res)) {
  if ($CONF['debug'] && $cl->GetLastError())
    $json['status']['warning'] = $cl->GetLastError();
  die(json_encode($json));
} else {
  //We have results to display!
  if ($CONF['debug'] && $cl->GetLastWarning())
    $json['status']['warning'] = $cl->GetLastWarning();
}

$json['status'] = [
  'matches' => count($res['matches']),
  'total' => $res['total'],
  'total_found' => $res['total_found'],
  'query' => $qo,
  'time' => $res['time']];

if (isset($res["matches"]) && is_array($res["matches"])) {
  //Build a list of IDs for use in the mysql Query and looping though the results
  $ids = array_keys($res["matches"]);
} else die(json_encode($json));

//We have results to display
if (!empty($ids)) {
  //Setup Database Connection
  $db = mysqli_connect($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password'], $CONF['mysql_database'], $CONF['mysql_port']) or die("ERROR: unable to connect to database");

  //Run the Mysql Query
  $sql = str_replace('$ids',implode(',',$ids),'SELECT page.id AS page_id, message,post.id as  post_id, created_time as createdtime,
    (SELECT count(*) FROM likedby AS ls WHERE ls.page_id=post.page_id AND ls.post_id=post.id AND ls.comment_id=0) as likes_count,
    (SELECT COUNT(*) FROM comment co WHERE co.page_id=post.page_id AND co.post_id=post.id) as comments_count,
    likes_count as likes, shares_count as shares, comments_count as comments, entr_ug as u_entropy,
    entr_pg as p_entropy, page.name AS \'group\', picture FROM post LEFT OUTER JOIN page ON (post.page_id=page.id) WHERE post.id in ($ids) ');
  $result = mysqli_query($db, $sql);
  if($result === FALSE) {
    $json['status']['errror'] = "ERROR: sql query failed: ".mysqli_error($db); die(json_encode($json)); }

  if (mysqli_num_rows($result) > 0) {
    $rows = array();

    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
      $row['link'] = $row['page_id'].'/posts/'.$row['post_id'];
      $rows[$row['post_id']] = $row;
    }
    //Build Excerpts.
    $docs = array();
    foreach ($ids as $c => $id) {
      $docs[$c] = preg_replace('/(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', '', strip_tags($rows[$id]['message']));
    }
    $reply = $cl->BuildExcerpts($docs, $CONF['sphinx_index'], $q);

    foreach ($ids as $c => $id) {
      $rows[$id]['excerpt'] = mb_convert_encoding($reply[$c],'UTF-8');
      $rows[$id]['message'] = nl2br(htmlentities($rows[$id]['message']));
      $json['results'][] = $rows[$id];
    }

    if(json_last_error() != JSON_ERROR_NONE) {
      $json['results'] = [];
      $json['status']['error'] = json_last_error_msg();
    }
  }
}
print json_encode($json);
return;

#########################################
# Functions
# Created by Barry Hunter for use in the geograph.org.uk project, reused here because convenient :)

function linktoself($params,$selflink= '') {
    $a = array();
    $b = explode('?',$_SERVER['REQUEST_URI']);
    if (isset($b[1]))
        parse_str($b[1],$a);

    if (isset($params['value']) && isset($a[$params['name']])) {
        if ($params['value'] == 'null') {
            unset($a[$params['name']]);
        } else {
            $a[$params['name']] = $params['value'];
        }

    } else {
        foreach ($params as $key => $value)
            $a[$key] = $value;
    }

    if (!empty($params['delete'])) {
        if (is_array($params['delete'])) {
            foreach ($params['delete'] as $del) {
                unset($a[$del]);
            }
        } else {
            unset($a[$params['delete']]);
        }
        unset($a['delete']);
    }
    if (empty($selflink)) {
        $selflink = $_SERVER['SCRIPT_NAME'];
    }
    if ($selflink == '/index.php') {
        $selflink = '/';
    }

    return htmlentities($selflink.(count($a)?("?".http_build_query($a,'','&')):''));
}


function pagesString($currentPage,$numberOfPages,$postfix = '',$extrahtml ='') {
    static $r;
    if (!empty($r))
        return($r);

    if ($currentPage > 1)
        $r .= "<a href=\"".linktoself(array('page'=>$currentPage-1))."$postfix\"$extrahtml>&lt; &lt; prev</a> ";
    $start = max(1,$currentPage-5);
    $endr = min($numberOfPages+1,$currentPage+8);

    if ($start > 1)
        $r .= "<a href=\"".linktoself(array('page'=>1))."$postfix\"$extrahtml>1</a> ... ";

    for($index = $start;$index<$endr;$index++) {
        if ($index == $currentPage)
            $r .= "<b>$index</b> ";
        else
            $r .= "<a href=\"".linktoself(array('page'=>$index))."$postfix\"$extrahtml>$index</a> ";
    }
    if ($endr < $numberOfPages+1)
        $r .= "... ";

    if ($numberOfPages > $currentPage)
        $r .= "<a href=\"".linktoself(array('page'=>$currentPage+1))."$postfix\"$extrahtml>next &gt;&gt;</a> ";

    return $r;
}

?>
