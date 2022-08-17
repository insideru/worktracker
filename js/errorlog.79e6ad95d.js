js_error = 0;
window.onerror = function(msg, url, lineNo, columnNo, error) { 
    js_error++;
    if (js_error<10) {
        var req = new XMLHttpRequest();
        var params = "msg=" + encodeURIComponent(msg) + '&url=' + encodeURIComponent(url) + "&lineNo=" + lineNo;
        var message = [
            'Message: ' + msg,
            'URL: ' + url,
            'Line: ' + lineNo,
            'Column: ' + columnNo,
            'Error object: ' + JSON.stringify(error)
          ].join(' - ');
        req.open("POST", "/errorlog.php");
        req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        req.send(message); 
    }
};