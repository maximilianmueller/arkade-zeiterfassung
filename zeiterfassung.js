
var jq = (typeof jQuery != "undefined") ? jQuery : null;

function show_time()
  {
  var now = new Date();
  var hours = now.getHours();
  var minutes = now.getMinutes();
  var seconds = now.getSeconds();
  var hours0 = ((hours < 10) ? "0" : ""); 
  var minutes0 = ((minutes < 10) ? ":0" : ":");
  var seconds0 = ((seconds < 10) ? ":0" : ":");
  var timestr = hours0 + hours + minutes0 + minutes + seconds0 + seconds;
  document.getElementById("acttime").innerHTML = timestr;
  window.setTimeout("show_time()", 1000);
  }