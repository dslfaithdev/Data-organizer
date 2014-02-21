var messageMap = new Array();
var pageNum;
var disableScroll = false;
function fadeTipbox(){ $('#tip_content').fadeOut(2000); }

function doSearch(keyword, page, retries){
  retries = (typeof retries !== 'undefined' ? retries : 1);
  if(retries>5)
    return;
  if(keyword== "")
    return;
  var orderBy = $("#ranking").val();
  if(orderBy != "")
    orderBy="&orderBy="+orderBy;
  var url = "//" + location.hostname + "/q.php?q=" + encodeURI(keyword + orderBy + "&page="+page);
  window.location.hash = "q=" + encodeURI(keyword + orderBy);
  if ($("#page_limit").val())
    url += "&page_id="+$("#page_limit").val();
  if (page == 1)
    messageMap.length = 0;
  //Disable the search button, show loader.
  $("#search_btn").attr("disabled", "disabled");
  $("#search_box").css("background", "url(image/ajax-loader.gif) no-repeat right center");
  var append = (page == 1) ? false : true;
  pageNum = page + 1;
  $.get(url, function(data) {
    if(data)
      searchResult(data, append);
    else
      doSearch(keyword, page);
  },"json")
  .fail( function() {
    console.log(url+" error");
    setTimeout(function(){doSearch(keyword, page, ++retries);}, 100);
  }).always( function () {
    $("#search_box").css("background", "");
    $("#search_btn").removeAttr("disabled");
  });
}

function searchResult(data, append) {
  disableScroll = true;
  if(!append)
    $('#results').empty();
  var results = data['results'];
  //      objs = data;

  if(append && results.length == 0)
    return;
  if($('#result_main').css('display') == 'none')
    $('#result_main').css('display', 'block');

  if(results.length==0){
    $('#results').html('<span style="font-size:28px">No results</span>');
    return;
  }

  for(var i=0; i<results.length; i++){
    messageMap[results[i]['post_id']] = new Array();
    messageMap[results[i]['post_id']]['comments'] = results[i]['comments'];
    messageMap[results[i]['post_id']]['likes'] = results[i]['likes'];
    messageMap[results[i]['post_id']]['shares'] = results[i]['shares'];
    if(results[i]['picture'] != null) {
      messageMap[results[i]['post_id']]['message'] = '<img src="'+results[i]['picture']+'"/>  \n'+linkify(results[i]['message']);
    } else
      messageMap[results[i]['post_id']]['message'] = linkify(results[i]['message']);
    var obj = $('<div class="result_wrapper" id="'+results[i]['page_id']+"_"+results[i]['post_id']+'" postid="'+results[i]['post_id']+'" pageid="'+results[i]['page_id']+'">' +
                '<div class="result_row1">' +
                '<span class="expand_icon" title="See content"></span><span class="group"><a href="http://facebook.com/'+results[i]['link']+'" target="_blank">'+results[i]['group']+'</a></span>' +
    '<span class="ctime"> '+results[i]['createdtime']+'</span>' +
      '</span>' +
      '</div>' +
      '<div class="result_row2">' +
      '<span class="like">'+results[i]['likes']+'</span> Likes · ' +
      '<span class="share">'+results[i]['shares']+'</span> Shares · ' +
      '<span class="comment">'+results[i]['comments']+'</span> Comments · ' +
      '<span class="entropy">'+results[i]['u_entropy']+'</span> User Entropy · ' +
      '<span class="entropy">'+results[i]['p_entropy']+'</span> Post Entropy · ' +
      '</div>' +
      '<div class="excerpt">' + results[i]['excerpt']+'</div>'+
      '<div class="result_content" flag="0">' +
      'This is the main content' +
      '</div>' +
      '<div class="comments"></div>' +
      '</div>'
               );
               $('#results').append(obj);
  }
  if((((pageNum-1)*25)+data['status']['matches']) >= data['status']['total']) {
    console.log("last page");
  } else
    disableScroll = false;
}

$(function(){
  $('#bias_color').farbtastic('.userMode_-1');$('#delib_color').farbtastic('.userMode_1');

  /* Parse hash */
  var h = $.parseQuery(decodeURI(window.location.hash.substring(1)));
  if(h.orderBy !== undefined) {
    $('#ranking').val(h.orderBy);
  }
  if(h.q !== undefined) {
    $('#search_box').val(h.q);
    doSearch(h.q, 1);
  }

  $('#tip_content').hide();
  $('#tip_icon').click(function(){
    $('#tip_content').fadeIn(1000, function(){
      setTimeout(fadeTipbox, 5000);
    });
  });
  $('#search_btn').click(function(){
    doSearch($('#search_box').val(), 1);
  });
  $('#search_box').keydown(function(event){
    if (!event)
      event = window.event;
    var keyCode = (event.keyCode);
    if (keyCode == 13){
      doSearch($('#search_box').val(), 1);
    }
  });
  $('#ranking').change(function () {
    doSearch($('#search_box').val(), 1);});

    $('#results').click(function(e){
      var target;
      if (!e) e = window.event;
      if (e.target) target = e.target;
      else if(e.srcElement) target = e.srcElement;
      if (target.nodeType == 3)
        target = target.parentNode;

      if($(target).hasClass('expand_icon')){
        var parent = $(target).parent().parent();
        var pid = parent.attr('postid');
        var pageid = parent.attr('pageid');
        var message = messageMap[pid]['message'];
        parent.find('.excerpt').toggle();
        if(parent.find('.result_content').attr('flag') == 0){
          var element = $('.result_wrapper[postid='+pid+']').find('.result_content');
          element.attr('flag', 1);
          element.css('display','block').html(message);
          $(target).css('background-position-x', '-9px');
          parent.find(".comments").show();
          if(messageMap[pid]['comments'] > 0
             && parent.find(".comments").attr('status') != 'loading'
           && parent.find(".comments").attr('status') != 'done') {
             parent.find(".comments").attr('status','loading');
             $("#"+pageid + "_"+pid+">.comments").html('<div id="' + pageid + "_" + pid + '_waiting" class="waiting">Analyzing comments, please wait...</div>');
             GetOpinionsInComments(pageid, pid, 2);
           }
        }else{
          var element = $('.result_wrapper[postid='+pid+']').find('.result_content');
          element.attr('flag', 0);
          element.css('display','').html();
          $(target).css('background-position-x', '');
          parent.find(".comments").hide();
        }
      }
    });
});

$(window).scroll(function() {
  if($(document).height() - 2 * $(window).height() <= $(window).scrollTop() && !disableScroll) {
    doSearch($('#search_box').val(), pageNum);
    disableScroll = true;
  }
});

/* doLinks script */

function linkify(inputText, options) {

  this.options = {linkClass: 'url', targetBlank: true};

  this.options = $.extend(this.options, options);

  inputText = inputText.replace(/\u200B/g, "");

  //URLs starting with http://, https://, or ftp://
  var replacePattern1 = /(src="|href="|">|\s>)?(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;Ã¯]*[-A-Z0-9+&@#\/%=~_|Ã¯]/gim;
  var replacedText = inputText.replace(replacePattern1, function($0,$1){ return $1?$0:'<a class="'+ this.options.linkClass + '" href="' + $0 + '"' + (this.options.targetBlank?'target="_blank"':'') + '>'+ $0+ '</a>';});

  //URLS starting with www and not the above
  var replacePattern2 = /(src="|href="|">|\s>|https?:\/\/|ftp:\/\/)?www\.[-A-Z0-9+&@#\/%?=~_|!:,.;Ã¯]*[-A-Z0-9+&@#\/%=~_|Ã¯]/gim;
  var replacedText = replacedText.replace(replacePattern2, function($0,$1){ return $1?$0:'<a class="'+ this.options.linkClass + '" href="http://' + $0 + '"' + (this.options.targetBlank?'target="_blank"':'') + '>'+ $0+ '</a>';});

  //Change email addresses to mailto:: links
  var replacePattern3 = /([\.\w]+@[a-zA-Z_]+?\.[a-zA-Z]{2,6})/gim;
  var replacedText = replacedText.replace(replacePattern3, '<a class="' + this.options.linkClass + '" href="mailto:$1">$1</a>');

  return replacedText;
}

$.fn.doLinks = function(){
  this.each(function(){
    $(this).html(linkify($(this).html()));
  });
}

function pageDelib(userList) {
  //userList = ' {"Bias":[ 1140153280, 100002988813528, 100001565006736, 100000403640024, 100002207002605, 1543735750, 1366838071, 641316629, 631650176, 508344985, 639976758, 100000071575196, 100000254314629, 100000409532416, 1664659690], "Deliberation":[ 100002135796868, 1283578506, 208115509223180, 698675914, 100000281994815, 135188709920622, 541353848, 1796097322, 1298858058, 100002279155105, 100000396581051, 1233695230, 100000602055906, 1607928306, 631786909]}';
  //  userList = JSON.parse(json);
  var str = ""; var i=1;
  for (type in userList) {
    //Create div to place first type.
    str += '<div id="'+type+'" class="comment_group '+type+'"';
    (Object.keys(userList).length >= i++) ? str+='style="width:'+100/Object.keys(userList).length+'%; float: left">' : str+='">';
    str += '<span class="type_head">'+type+'</span>';
    for (i in userList[type]) {
      str += '<div id="'+i+'" class="comment_item" style="border: 0px !important; margin-bottom: 20px;">'
      + '<img style="float: left; " class="blur" src="http://graph.facebook.com/'
      + i+'/picture?redirect=1&height=100&width=100" width="100" height="100"/>';
      str += '<div id="name_'
      + i+'" class="name_link">'
      + '<a href=""">' + userList[type][i]['name'] + '</a></div>'
      + '<div style="height: 150px;overflow: auto;">';
      for (cid in userList[type][i]['comments']) {
        str += '<div class="user_comment">'
        + userList[type][i]['comments'][cid]['message']
        + '</div>';
      }
      //    str += 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed ut nibh sed augue tincidunt volutpat. Sed hendrerit pharetra vulputate. Aliquam mollis elit id ante rutrum, a commodo neque sagittis. Sed sit amet ante id metus scelerisque laoreet eu pulvinar elit. Aenean porttitor tempus nunc eget rhoncus. Nullam condimentum ornare mauris, eu blandit felis volutpat vel. Aliquam sit amet consectetur magna. Curabitur lacinia fermentum porttitor. Vestibulum odio dui, aliquam eget interdum at, ultrices nec urna. Mauris in placerat nibh. Phasellus scelerisque risus id fringilla blandit. Quisque fringilla orci a ante dictum pellentesque.';
      str += '</div>';
      str += '</div>';
    }
    str += '</div>';
  }
  $('#results').html(str);
}
$(function(){
  $.ajaxSetup({timeout: 60000});
  $('#page_limit').change(function () {
    if(!$(this).val()) {
      $('#results').empty();
      return;
    }
    $.getJSON( "delib.php?pageId="+$(this).val())
    .done(function( json ) {pageDelib(json);});
    console.log($(this).val());});

});
