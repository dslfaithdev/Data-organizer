function requestQuery() {
  var query = $("#query_input").val();
  var url = "./" + query;
  $("#loading_spinner").show(200);
  $("#loading_text").html("Waiting for Response...");
  $.get(url, function(data) {
    $("#loading_text").html("Pretifying the response...");
    data = JSON.stringify(JSON.parse(data), undefined, 4);
    
    var prettified_json = syntaxHighlight(data);
    //alert(data);
    $("#loading_spinner").hide();
    $("#json_results_section").html("<pre>" + prettified_json + "</pre>");
  });
}

function syntaxHighlight(json) {
    if (typeof json != 'string') {
         json = JSON.stringify(json, undefined, 2);
    }
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

