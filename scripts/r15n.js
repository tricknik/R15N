/* R15N */

console_log("console"," [ #* R15N Call Initiated ]\n");

var message_id = argv[0];
var caller_id = argv[1];
var caller_number =  argv[2];
var callee_id = argv[3];
var callee_number = argv[4];

var r15n_inbound = "49308687035761";
var path = "sofia/default/0011103";

var caller_prefix = "{originate_timeout=12,ignore_early_media=true,origination_caller_id_name='R15N',origination_caller_id_number=" + r15n_inbound + "}";
var callee_prefix = "{ignore_early_media=false,origination_caller_id_name='R15N',origination_caller_id_number=" + r15n_inbound + "}";

var caller_dial = caller_prefix + path + caller_number + "@sbc.voxbeam.com";
var callee_dial = callee_prefix + path + callee_number + "@sbc.voxbeam.com";

var duration = 0;
var acause = 'UNINITIATED';
var bcause = 'UNINITIATED';

console_log('console', caller_dial);
var caller = new Session(caller_dial);
while(caller.ready()) {
  console_log('console', "caller " + caller.state);
  //caller.setVariable("bypass_media_after_bridge", true);
  caller.setVariable("bridge_generate_comfort_noise", true);
  caller.setVariable("call_timeout", 15);
  caller.setVariable("ringback", "/var/lib/freeswitch/sounds/r15nring.wav");
  caller.setVariable("instant_ringback", true);
  caller.setVariable("hangup_after_bridge", true);
  var start = Date.now();
  //var callee = new Session(callee_dial);
  while (caller.ready()) {
    caller.execute("sched_hangup", "+300 ALLOTTED_TIMEOUT");
    caller.execute("bridge", callee_dial);
    bcause = caller.getVariable('bridge_hangup_cause');
  }
  if (caller.ready()) {
    caller.hangup();
  }
  var duration = Date.now() - start;
}
acause = caller.cause;
var path = "XXXXX?";
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
  console_log("console", "[ #* R15N Reported Call ]\n"); 
  console_log("console", result);
}

