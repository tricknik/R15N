session.answer()
while(session.ready()) {
  var target_number = session.getVariable('sip_to_user');
  caller_id = session.getVariable('caller_id_number');

  var command = (target_number == "0049308687035761") ? "setup" : "activation";
  console_log("console", "[ #* R15N " + command + " " + caller_id + " ]\n");
  var RE_LOCAL = /^0[1-9][0-9]+$/;
  var RE_INTERNATIONAL = /^00[1-9][0-9]+$/;
  caller_id = (RE_LOCAL.test(caller_id)) ? '49' + caller_id.substr(1) : caller_id;
  caller_id = (RE_INTERNATIONAL.test(caller_id)) ? + caller_id.substr(2) : caller_id;
  
  session.streamFile("r15nwelcome.wav"); 
  query = [];
  var path = "http://r15n.net/wp-content/plugins/r15n/incoming.php?";
  query.push('command=' + command);
  query.push('caller=' + caller_id);
  var url = path + query.join('&');
  console_log("console", url + "\n");
  var result = fetchUrl(url);
  if (result == false) {
    console_log("console", "[ #* R15N Failed To Notify Incoming Call ]\n"); 
  } else {
    console_log("console", "[ #* R15N Notified Incoming Call ]\n"); 
  }
  console_log("console", result);
  session.hangup();
}
