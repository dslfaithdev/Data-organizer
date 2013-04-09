<?php

// This is the abstract of the API class. Both GET and SIN API's are extended classes from this class.
abstract class apiClass {
  protected $limit = 25;

  // This is the main constructore of the class and extended classes which establishes the
  // database connection and creates a database object for this and extended class to access the db.
  public function __construct(mysqli $mysqli) {
    $this->db_con = $mysqli;
  }
  
  // Sets the $limit variable.
  public function setLimit($limit) {
    $this->limit = $limit;
  }
  
  // The following functions need to be implemented in the classes extended from this class directly.
  abstract protected function getLikesOnPost($pageId, $postId, $limit, $poster=null);
  
  abstract protected function getCommentsOnPost($pageId, $postId, $limit, $poster=null);
  
  abstract protected function ProcessPostQuery($query);
    
  // This public fuction retrieves GET or SIN information on $limited number of posts made by this
  // user. GET or SIN response depends on which subclass initiates the request.
  public function RetrieveUserInformation($userId) {
    $query = "SELECT * FROM post WHERE from_id IN ($userId) LIMIT " .$this->limit;
    $result['posts'] = $this->ProcessPostQuery($query);
    $json_result = json_encode($result);
    return $json_result;
  
  }
  
  
  public function RetrieveCommunityInformation($pageId) {
    $query = "SELECT * FROM page WHERE id IN ($pageId)";
  
    $query_result = $this->safe_mysql_query($query);
  
    $count = 0;
    $response = array();
    while($result = $query_result->fetch_array(MYSQLI_ASSOC)) {
      $pageId = $result['id'];
      $response[$count]['community_id'] = $result['id'];
      $response[$count]['name'] = $result['name'];
      $response[$count]['category'] = $result['category'];
  
      $post_query = "SELECT * FROM post WHERE page_id=$pageId LIMIT " . $this->limit;
    
      $response[$count]['posts'] = $this->ProcessPostQuery($post_query);
  
      $count += 1;
    }
  
    $row_json = json_encode($response);
    return $row_json;
  
  
  }
  
  // This function works in the same way independent of the subclass in was initiated from.
  public function RetrievePostInformation($postId) {
    $query = "select * from post WHERE id IN ($postId)";
  
    $result = $this->ProcessPostQuery($query);
    $json_result = json_encode($result);
    return $json_result;
  }
  
  // Each post row has the following information. We have to check to see whether the fields are
  // not null before setting the response values.
  protected function getPostRow($result) {
    $row['community_id'] = $result['page_id'];
    $row['post_id'] = $result['id'];
    $row['from'] = $result['from_id'];
    $row['created_time'] = $result['created_time'];
    if ($result['message'] != null) {
      $row['message'] = $result['message'];
    }
    if ($result['type'] != null) {
      $row['type'] = $result['type'];
    }
    if ($result['type'] != null) {
      $row['type'] = $result['type'];
    }
    if ($result['picture'] != null) {
      $row['picture'] = $result['picture'];
    }
    if ($result['story'] != null) {
      $row['story'] = $result['story'];
    }
    if ($result['link'] != null) {
      $row['link'] = $result['link'];
    }
    if ($result['icon'] != null) {
      $row['icon'] = $result['icon'];
    }
    if ($result['object_id'] != null) {
      $row['object_id'] = $result['object_id'];
    }
    if ($result['status_type'] != null) {
      $row['status_type'] = $result['status_type'];
    }
    if ($result['source'] != null) {
      $row['source'] = $result['source'];
    }
    if ($result['place_id'] != null) {
      $row['place_id'] = $result['place_id'];
    }
    
    return $row;
  }
  
  
  // This protected function takes care of the MySQL Injection attacks by escaping the query strings.
  // Please use this function instead of mysql_query or any other functions provided by PHP for
  // sending requests to the Database.
  protected function safe_mysql_query($query) {
    $result = $this->db_con->query($this->db_con->real_escape_string($query));
    if (!$result) {
  
    }
    return $result;
  }
}
 
?>
