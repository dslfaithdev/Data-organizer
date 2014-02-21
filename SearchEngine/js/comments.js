

function GetOpinionsInComments(pageId, postId, numOpinions) {
  var url = "//" + location.hostname + "/cgi-bin/comments.py" +
            "?page=" + pageId +
            "&post=" + postId +
            "&numOpinions=" + numOpinions;

  $.get(url, function(data) {
    ShowCommentOpinions(data);
  }, "json")
    .fail(function() {
      $('#'+pageId+'_'+postId+'>.comments').html("Error loading comments.").attr("status","error"); });
}

function ShowCommentOpinions(data) {
  var parentid = data['status']['id'];
  var jQueryParentId = '#'+ data['status']['id'];
  var placeholder = $('#'+parentid+'>.comments');
  placeholder.html("");
  if(data['status']['message'] != "Ok") {
    placeholder.html(data['status']['message']);
    placeholder.attr("status","error");
    return;
  }
  placeholder.attr("status","done");
  for (var i =0; i < data['comments'].length; i++) {
    var comments = data['comments'][i].sort(sort_by('timestamp', true, parseInt));
    $("<div/>", {
      id: "comment_group_" + parentid + "_" + i,
      class: "comment_group comment_group_" + i
    }).appendTo(placeholder);
    comments.forEach(function(comment) {/*
      $("<div/>", {
        id: "comment_" + comment['id'],
        class: "comment_item comment_group_item_" + i + " userMode_" + comment['user_mode'],
        text: new Date(comment['timestamp']*1000).toLocaleString() + " " + comment['message']
      }).appendTo("#comment_group_" + parentid + "_" + i);*/
    var str = "<div id=\"comment_" + comment['id'] +"\"" +
      "class=\"comment_item comment_group_item_" + i+ " userMode_" + comment['user_mode'] + "\">" +
      '<img style="float: left; " class="blur commment_img" src="http://graph.facebook.com/'+
      comment['fb_id']+'/picture" width="50" height="50"/>' +
      "<div class=\"name_link\"><a href=\"http://facebook.com/"+comment['fb_id']+"\">" + comment['name'] + "</a></div>" +
      comment['message'] +
      "<div class=\"date\"><a href=\"http://facebook.com/" + comment['page_id'] + "/posts/" + comment['post_id'] + "?comment_id=" + comment['id'] + "\">" +
      new Date(comment['timestamp']*1000).toLocaleString() +
      "</a></div></div>";
    $("#comment_group_" + parentid + "_" + i).append(str);
    });
  }
  $.farbtastic('#bias_color').linkTo('.userMode_-1');$.farbtastic('#delib_color').linkTo('.userMode_1');
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

function invert(rgb) {
      rgb = [].slice.call(arguments).join(",").replace(/rgb\(|\)|rgba\(|\)|\s/gi, '').split(',');
          for (var i = 0; i < rgb.length; i++) rgb[i] = (i === 3 ? 1 : 255) - rgb[i];
              return rgb.join(", ");
}

