/* R15N */

console_log("console"," [ #* R15N Call Initiated ]\n");

var message_id = argv[0];
var caller_id = argv[1];
var caller_number = argv[2];
var callee_id = argv[3];
var callee_number = argv[4];

var caller_prefix = "{ignore_early_media=ring_ready,origination_caller_id_name='R15N',origination_caller_id_number=" + callee_number + "}";
var callee_prefix = "{ignore_early_media=false,origination_caller_id_name='R15N',origination_caller_id_number=" + caller_number + "}";

//var RE_DOMESTIC = /^1[2-9][0-9]{9}+$/;
//caller_number = (RE_DOMESTIC.test(caller_number)) ? caller_number : "011" + caller_number;
//callee_number = (RE_DOMESTIC.test(callee_number)) ? callee_number : "011" + callee_number;
//var path = "sofia/gateway/sip.voicenetwork.ca/";

var path = "sofia/gateway/sip.rapidvox.com/";

var caller_dial = caller_prefix + path + caller_number;
var callee_dial = callee_prefix + path + callee_number;

var duration = 0;
var acause = 'UNINITIATED';
var bcause = 'UNINITIATED';

var caller = new Session(caller_dial);
while(caller.ready()) {
  console_log('console', "caller " + caller.state);
  caller.execute("sched_hangup", "+300 ALLOTTED_TIMEOUT");
  caller.setVariable("bridge_early_media", true);
  caller.setVariable("bypass_media_after_bridge", true);
  caller.setVariable("bridge_generate_comfort_noise", true);
  caller.setVariable("hangup_after_bridge", true);
  caller.streamFile('r15nsting.wav');
  var start = Date.now();
  var callee = new Session(callee_dial);
  while(callee.ready()) {
    bridge(caller, callee);
    bcause = caller.getVariable('bridge_hangup_cause');
  }
  var duration = Date.now() - start;
}
acause = caller.cause;
var path = "http://r15n.net/wp-content/plugins/r15n/callreport.php?";
query = [];
query.push('message=' + message_id);
query.push('duration=' + duration);
query.push('caller=' + caller_id);
query.push('callee=' + callee_id);
query.push('anumber=' + caller_number);
query.push('bnumber=' + callee_number);
query.push('acause=' + acause);
query.push('bcause=' + bcause);
var url = path + query.join('&');
console_log("console", url + "\n");
var result = fetchUrl(url);
if (result == false) {
  console_log("console", "[ #* R15N Failed To Report Call ]\n"); 
} else {
  console_log("console", result); 
}

