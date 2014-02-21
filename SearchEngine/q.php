<?
header('Content-Type: application/json');
set_time_limit(600);
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
/*
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
$CONF['debug'] = TRUE;

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
  require("/usr/local/share/examples/sphinxsearch/api/sphinxapi.php");


//Sanitise the input
$q = isset($_GET['q'])?$_GET['q']:'';

$q = preg_replace('/ OR /',' | ',$q);

$q = preg_replace('/[^\w~\|\(\)"\/=-]+/',' ',trim(strtolower($q)));

//If the user entered something
if (!empty($q)) {
    //produce a version for display
    $qo = $q;
    if (strlen($qo) > 64) {
        $qo = '--complex query--';
    }

    if (1) {
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
        //$cl->setFilter("page_id", array(intval("184749301592842")));
        //plain text search
        $res = $cl->Query($q, $CONF['sphinx_index']);
        /*
        if (empty($CONF['sphinx_attributes'])) {
            //plain text search
            $res = $cl->Query($q, $CONF['sphinx_index']);
        } else {
            $q2 = '';
            foreach ($CONF['sphinx_attributes'] as $attr => $type) {
                if (!empty($_GET[$attr])) {
                    if ($type == 'string') {
                        //string attributes must go in the fulltext query
                        $q2 .= " @{$attr} \"{$_GET[$attr]}\"";
                    } else {
                        $cl->setFilter($attr,array(intval($_GET[$attr])));
                    }
                }
            }
            $cl->AddQuery($q.$q2, $CONF['sphinx_index']);

            $cl->SetRankingMode(SPH_RANK_NONE); //because we dont use the rank, may as well not calculate it!
            $cl->SetSortMode(SPH_SORT_EXTENDED, "@id ASC"); //the natuarl order, so sphinx skips sorting step

            foreach ($CONF['sphinx_attributes'] as $attr => $type) {
                $cl->ResetFilters();
                $q2 = '';
                foreach ($CONF['sphinx_attributes'] as $attr2 => $type2) {
                    if ($attr == $attr2) //we dont want to filter on the current attribute. Otherwise the breakdown would only show matching.
                        continue;
                    if (!empty($_GET[$attr2])) {
                        if ($type2 == 'string') {
                            //string attributes must go in the fulltext query
                            $q2 .= " @{$attr2} \"{$_GET[$attr2]}\"";
                        } else {
                            $cl->setFilter($attr2,array(intval($_GET[$attr2])));
                        }
                    }
                }
                $cl->SetSortMode(SPH_SORT_EXTENDED, 'comments_count DESC, @relevance DESC, @id DESC');
                $cl->SetSelect($attr);
                $cl->SetGroupBy($attr,SPH_GROUPBY_ATTR,'@count DESC');
                $cl->AddQuery($q.$q2, $CONF['sphinx_index']);
            }
            $results = $cl->RunQueries();
            $res = $results[0];
        }
         */


        //Check for failure
        if (empty($res)) {
            print "Query failed: -- please try again later.\n";
            if ($CONF['debug'] && $cl->GetLastError())
                print "<br/>Error: ".$cl->GetLastError()."\n\n";
            return;
        } else {
            //We have results to display!
            if ($CONF['debug'] && $cl->GetLastWarning())
                print "<br/>WARNING: ".$cl->GetLastWarning()."\n\n";

            $resultCount = $res['total_found'];
            $numberOfPages = ceil($res['total']/$CONF['page_size']);
        }

          $json['status'] = array(
            'matches' => 0,
            'total' => $res['total'],
            'total_found' => $res['total_found'],
            'time' => $res['time'],
            'query' => $qo
          );
          $json['results'] = array();

        if (isset($res["matches"]) && is_array($res["matches"])) {
            //Build a list of IDs for use in the mysql Query and looping though the results
            $ids = array_keys($res["matches"]);
            $query_info = "Query '".htmlentities($qo)."' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
            $json['status']['matches'] = count($res['matches']);
        } else {
            //print "<pre class=\"results\">No Results for '".htmlentities($qo)."'</pre>";

          print json_encode($json); return;

        }
    }

    if (!empty($CONF['sphinx_attributes'])) {
        $counter = 1;
        foreach ($CONF['sphinx_attributes'] as $attr => $type) {
            if (empty($results[$counter]) || empty($results[$counter]['matches'])) {
                $counter++;
                continue;
            }
            print "<ul class='sidebar'><b>$attr</b>";
            foreach ($results[$counter]['matches'] as $idx => $row) {
                $value = $row['attrs'][$attr]; //we dont use @groupby, because it wrong for string attributes.
                if (isset($_GET[$attr]) && $_GET[$attr] == $value) {
                    print "<li><b>".htmlentities($value)."</b> <i>(<a href=\"".linktoself(array('delete'=>$attr))."\">remove</a>)</i></li>";
                } else {
                    print "<li><a href=\"".linktoself(array($attr=>$value))."\">".htmlentities($value)."</a> ({$row['attrs']['@count']})</li>";
                }
            }
            print "</ul>";
            $counter++;
        }
    }


    //We have results to display
    if (!empty($ids)) {

        //Setup Database Connection
        $db = mysqli_connect($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password'], $CONF['mysql_database'], $CONF['mysql_port']) or die("ERROR: unable to connect to database");


        // Fer json return


        //Run the Mysql Query
        //left outer join page ON (post.page_id=page.id)
        $sql = str_replace('$ids',implode(',',$ids),'SELECT page.id AS page_id, message,post.id as  post_id, created_time as createdtime,
          likes_count as likes, shares_count as shares, comments_count as comments, entr_ug as u_entropy,
          entr_pg as p_entropy, page.name AS \'group\', picture FROM post LEFT OUTER JOIN page ON (post.page_id=page.id) WHERE post.id in ($ids) ');
        $result = mysqli_query($db, $sql) or die($CONF['debug']?("ERROR: psql query failed: ".mysqli_error($db)):"ERROR: Please try later");

        if (mysqli_num_rows($result) > 0) {
          $rows = array();

          while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $row['link'] = $row['page_id'].'/posts/'.$row['post_id'];
            $rows[$row['post_id']] = $row;
          }
          //Build Excerpts.
          $docs = array();
          foreach ($ids as $c => $id) {
            $docs[$c] = strip_tags($rows[$id]['message']);
          }
          $reply = $cl->BuildExcerpts($docs, $CONF['sphinx_index'], $q);

          foreach ($ids as $c => $id) {
            $rows[$id]['excerpt'] = $reply[$c];
            $rows[$id]['message'] = nl2br(htmlentities($rows[$id]['message']));
            $json['results'][] = $rows[$id];
          }
          print json_encode($json);
        }
        return;
        //end fer json

        //Run the Mysql Query
        $sql = str_replace('$ids',implode(',',$ids),$CONF['mysql_query']);
        $result = mysqli_query($db, $sql) or die($CONF['debug']?("ERROR: psql query failed: ".mysqli_error($db)):"ERROR: Please try later");

        if (mysqli_num_rows($result) > 0) {

            //Fetch Results from Mysql (Store in an accociative array, because they wont be in the right order)
            $rows = array();
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $rows[$row['id']] = $row;
            }
/*
            //Call Sphinxes BuildExcerpts function
            if ($CONF['body'] == 'excerpt') {
                $docs = array();
                foreach ($ids as $c => $id) {
                    $docs[$c] = strip_tags($rows[$id]['body']);
                }
                $reply = $cl->BuildExcerpts($docs, $CONF['sphinx_index'], $q);
            }
 */
            if ($numberOfPages > 1 && $currentPage > 1) {
                print "<p class='pages'>".pagesString($currentPage,$numberOfPages)."</p>";
            }

            //Actully display the Results
            print "<ol class=\"results\" start=\"".($currentOffset+1)."\">";
            foreach ($ids as $c => $id) {
                $row = $rows[$id];

                $link = htmlentities(str_replace('$id',$row['id'],$CONF['link_format']));
                print "<li><a href=\"$link\">".htmlentities($row['title'])."</a><br/>";

                if ($CONF['body'] == 'excerpt' && !empty($reply[$c]))
                    print ($reply[$c])."</li>";
                else
                    print htmlentities($row['body'])."</li>";
            }
            print "</ol>";

            if ($currentPage == $numberOfPages && $resultCount > $CONF['max_matches']) {
                  print "<p>Note: You have reached the last page of results, for performance reasons we
                  only allow access to the first {$CONF['max_matches']}. To browse more results please add
                  more keywords to refine your search.</p>";
            }

            if ($numberOfPages > 1) {
                print "<p class='pages'>Page $currentPage of $numberOfPages. ";
                printf("Result %d..%d of %d. ",($currentOffset)+1,min(($currentOffset)+$CONF['page_size'],$resultCount),$resultCount);
                print pagesString($currentPage,$numberOfPages)."</p>";
            }

            print "<pre class=\"results\">$query_info</pre>";

        } else {

          $json['status'] = array(
            'matches' => count($res['matches']),
            'total' => $res['total_found'],
            'time' => $res['time']
          );
          print json_encode($json); return;

            //Error Message
            //print "<pre class=\"results\">Unable to get results for '".htmlentities($qo)."'</pre>";

        }
    }
}

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
