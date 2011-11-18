/* R15N */

console_log("console"," [ #* R15N Call Initiated ]\n");

var message_id = argv[0];
var caller_id = argv[1];
var caller_number = argv[2];
var callee_id = argv[3];
var callee_number = argv[4];

var caller_prefix = "{ignore_early_media=ring_ready,origination_caller_id_name='R15N',origination_caller_id_number=491632866163}";
var callee_prefix = "{ignore_early_media=false,origination_caller_id_name='R15N',origination_caller_id_number=491632866163}";

//var RE_DOMESTIC = /^1[2-9][0-9]+$/;
//caller_dial = (RE_DOMESTIC.test(caller_number)) ? caller_number : "011" + caller_number;
//callee_dial = (RE_DOMESTIC.test(callee_number)) ? callee_number : "011" + callee_number;

var caller_dial = caller_prefix + "sofia/gateway/sip.rapidvox.com/00" + caller_number;
var callee_dial = callee_prefix + "sofia/gateway/sip.rapidvox.com/00" + callee_number;

var duration = 0;
var acause = 'UNINITIATED';
var bcause = 'UNINITIATED';

var caller = new Session(caller_dial);
while(caller.ready()) {
  console_log('console', "caller " + caller.state);
  caller.streamFile('jessycom.wav');
  caller.execute("sched_hangup", "+300 ALLOTTED_TIMEOUT");
  caller.setVariable("campon", true);
  caller.setVariable("campon_retries", 0);
  caller.setVariable("bridge_early_media", true);
  caller.setVariable("bypass_media_after_bridge", true);
  caller.setVariable("bridge_generate_comfort_noise", true);
  //caller.setVariable("bridge_pre_execute_bleg_app", "playback r15n.wav");
  caller.setVariable("hangup_after_bridge", true);
  var start = Date.now();
  var callee = new Session(callee_dial);
  while(callee.ready()) {
    console_log('console', "callee " + callee.state);
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

