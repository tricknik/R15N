/* R15N */

console_log("console"," [ #* R15N Call On Site Phone ]\n");

var phone = argv[0];

var r15n_red = "493022487480";
var r15n_orange = "493022487254";

var path = "sofia/default/0011103";

var caller_prefix = "{jitterbuffer_msec=120,originate_timeout=16,ignore_early_media=true}";

if (phone == 'red') {
  caller_number = r15n_red;
} else {
  caller_number = r15n_orange;
}

var caller_dial = caller_prefix + path + caller_number + "@sbc.voxbeam.com";

console_log('console', caller_dial);
var caller = new Session(caller_dial);
while(caller.ready()) {
  caller.streamFile('r15ntestcall.wav');
  caller.hangup();
}

