

function GetOpinionsInComments(pageId, postId, numOpinions) {
  var url = "//" + location.hostname + "/cgi-bin/comments.py" +
            "?page=" + pageId +
            "&post=" + postId +
            "&numOpinions=" + numOpinions;

  $.get(url, function(data) {
    ShowCommentOpinions(data);
  }, "json");
}

function ShowCommentOpinions(data) {
  if(data['comments'].length < 1) {
    return;
  }
  var parentid;
  for(key in data['comments'][0]) {
    parentid = data['comments'][0][key]['d']['page_id'] + "_" + data['comments'][0][key]['d']['post_id'];
    break;
  }
  var jQueryParentId = "#" + parentid;
  $(jQueryParentId + ">.comments").html("");
  $(jQueryParentId + ">.comments").attr("done",1);
  for (var i =0; i < data['comments'].length; i++) {
    var comments = new Array();
    var count = 0;
    for (key in data['comments'][i]) {
      comments.push(data['comments'][i][key]['d']);
      comments[count]['ranking'] = data['comments'][i][key]['ranking'];
      count += 1;
    }
    var comments = comments.sort(sort_by('ranking', true, parseInt));
    $("<div/>", {
      id: "comment_group_" + parentid + "_" + i,
      class: "comment_group_" + i
    }).appendTo(jQueryParentId+">.comments");
    var comment_group_div = "#comment_group_" + parentid + "_" + i;
    for (var j = 0; j < comments.length; j++) {
      $("<div/>", {
        id: "comment_" + j,
        class: "comment_group_item_" + i,
        text: comments[j]['message']
      }).appendTo(comment_group_div);
    }
  }
}

// Here's a more flexible version, which allows you to create
// reusable sort functions, and sort by any field

function sort_by(field, reverse, primer){

   var key = function (x) {return primer ? primer(x[field]) : x[field]};
   var order = (reverse == true) ? 1 : -1;
   return function (a,b) {
       var A = key(a), B = key(b);
       return (A < B ? -1 : (A > B ? 1 : 0)) * [1,-1][+!!reverse];
   }
}
