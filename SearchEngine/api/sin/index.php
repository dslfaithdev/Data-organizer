<?php
require "../db.php";
require "../api.php";

// This class manages all sin requests.
// This class is extended from the apiClass which manages connecting to the DataBase and
// creates a DB Object which can be used to fully access our database.
class api_sin extends apiClass{
  
  // Given a community ID, [page, group, user ID], we return $limited number of interactions on
  // $limit number of posts.
  public function RetrieveCommunityInformation($pageId) {
    $query = "SELECT * FROM page WHERE id IN ($pageId)";
  
    $query_result = $this->safe_mysql_query($query);
  
    $count = 0;
    $response = array();
    while($result = $query_result->fetch_array(MYSQLI_ASSOC)) {
      $pageId = $result['id'];
  
      $post_query = "SELECT * FROM post WHERE page_id=$pageId LIMIT " . $this->limit;
      
      $result = $this->ProcessPostQuery($post_query);
      $response = array_merge($response, $result); 
  
      $count += 1;
    }
  
    $row_json = json_encode($response);
    return $row_json;
  }

  // This function is protected and used only within this class and extended classes.
  // Given a request for a given post ID, it computes and returns $limit number of SINs.
  protected function ProcessPostQuery($query) {
    $query_result = $this->safe_mysql_query($query);
  
    $count = 0;
    $row = array();
    while($result = $query_result->fetch_array(MYSQLI_ASSOC)) {
      $pageId = $result['page_id'];
      $postId = $result['id'];
      $poster = $result['from_id'];
    
      $post_info = $this->getPostRow($result);
    
      $comment_row = $this->getCommentsOnPost($pageId, $postId, $this->limit, $poster);
    
      $likes_row = $this->getLikesOnPost($pageId, $postId, $this->limit, $poster);
      
      $likes_on_comments_row = $this->getLieksSinOnComments($pageId, $postId);
      
      $row = array_merge($row, $comment_row, $likes_row, $likes_on_comments_row);
      
      $count += 1;
    } 
    return $row;
  }
  
  // This is a private function, used only within this class.
  // It basically computes and returns the SIN of likes on comments on a given post.
  private function getLieksSinOnComments($pageId, $postId) {
    $query = "select comment.id as CommentID, likedby.fb_id as LikeFromID, comment.fb_id as LikeToID from comment, likedby where likedby.page_id=" . $pageId . " AND likedby.post_id=" . $postId . " AND comment.id=likedby.comment_id AND comment.page_id=likedby.page_id";

    $query_result = $this->safe_mysql_query($query);
    $start_from =  0;
    $count = 0;
    $row = array();
    while($result = $query_result->fetch_array(MYSQLI_ASSOC)) {
      if($count < $start_from) {
       $count += 1;
       continue;
      }
      $row[$count]['page_id'] = $pageId;
      $row[$count]['post_id'] = $postId;
      $row[$count]['comment_id'] = $result['CommentID'];
      $row[$count]['from_id'] = $result['LikeFromID'];
      $row[$count]['to_id'] = $result['LikeToID'];
      $row[$count]['type'] = "like";
      $count += 1;
    }
    return $row;
  }
  
  // This function computes and returns SINs of likes on a given post.
  protected function getLikesOnPost($pageId, $postId, $limit, $poster=null) {
    $likes_query = "SELECT * FROM likedby WHERE page_id=$pageId AND post_id=$postId AND comment_id=0 LIMIT $limit";
    
    $likes_query_result = $this->safe_mysql_query($likes_query);
  
    $likes_count = 0;
    $likes_row = array();
    while($likes_result = $likes_query_result->fetch_array(MYSQLI_ASSOC)) {
      $likes_row[$likes_count]['type'] = 'like';
      $likes_row[$likes_count]['page_id'] = $pageId;
      $likes_row[$likes_count]['post_id'] = $postId;
      $likes_row[$likes_count]['comment_id'] = null;
      $likes_row[$likes_count]['to_id'] = $poster;
      if ($likes_result['created_time'] != null) {
        $likes_row[$likes_count]['created_time'] = $likes_result['created_time'];
        
      }
      if( $likes_result['fb_id'] != null) {
        $likes_row[$likes_count]['from_id'] = $likes_result['fb_id'];
        
      }
      $likes_count += 1;
    }
    return $likes_row;
  }
  
  // This function computes and returns the SINs of comments on a given post.
  protected function getCommentsOnPost($pageId, $postId, $limit, $poster=null) {
    $comments_query = "SELECT * FROM comment WHERE page_id=$pageId AND post_id=$postId LIMIT $limit";
    
    $comments_query_result = $this->safe_mysql_query($comments_query);
  
    $comment_count = 0;
    $comment_row = array();
    while($comment_result = $comments_query_result->fetch_array(MYSQLI_ASSOC)) {
      $comment_row[$comment_count]['type'] = 'comment';
      $comment_row[$comment_count]['page_id'] = $pageId;
      $comment_row[$comment_count]['post_id'] = $postId;
      $comment_row[$comment_count]['comment_id'] = $comment_result['id'];
      $comment_row[$comment_count]['to_id'] = $poster;
      $comment_row[$comment_count]['created_time'] = $comment_result['created_time'];
      if( $comment_result['fb_id'] != null) {
        $comment_row[$comment_count]['from_id'] = $comment_result['fb_id'];
      }
      $comment_count += 1;
    }   
    return $comment_row;
  }
}

$api = new api_sin($mysqli);
if(isset($_GET['offset'])) {
  $api->setLimit($_GET['offset']);
}
$type = "all";

if(isset($_GET['type'])) {
  $type = $_GET['type'];
}

if(isset($_GET['userId'])) {
  $userId = $_GET['userId'];
  print $api->RetrieveUserInformation($userId);
}
else if(isset($_GET['postId'])) {
  $postId = $_GET['postId'];
  print $api->RetrievePostInformation( $postId);
}
else if(isset($_GET['pageId'])) {
  $pageId = $_GET['pageId'];
  print $api->RetrieveCommunityInformation($pageId);
}
else {
  $response = array();
  $response['error'] = "Exception, invalid parameters";
  $row_json = json_encode($response);
  print $row_json;
}
 
?>
