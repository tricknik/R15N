<?php

require_once('../../../wp-load.php');
R15NReport::init();

class R15NReport {

  public static function add_call_detail($anumber, $bnumber, $duration, $acause, $bcause) {
    global $wpdb;

    $call_details_table = "{$wpdb->prefix}call_details";
    $values['anumber'] = $anumber;
    $values['bnumber'] = $bnumber;
    $values['duration'] = $duration;
    $values['reported'] = time();
    $values['acause'] = $acause;
    $values['bcause'] = $bcause;
    $wpdb->insert($call_details_table,
      $values,
      Array('%d','%d','%d', '%s', '%s', '%s')
    );
    $call_detail_id = $wpdb->insert_id;
    return $call_detail_id;
  }

  public static function add_call($call_detail_id, $message_id, $caller, $callee) {
    global $wpdb;

    $calls_table = "{$wpdb->prefix}calls";
    $values['call_detail_id'] = $call_detail_id;
    $values['message_id'] = $message_id;
    $values['caller_user_id'] = $caller;
    $values['callee_user_id'] = $callee;
    $wpdb->insert($calls_table,
      $values,
      Array('%d','%d','%d','%d')
    );
    $call_id = $wpdb->insert_id;
    return $call_id;
  }

  public static function init() {
    if (isset($_GET['anumber'])) {
      $anumber = $_GET['anumber'];
      $bnumber = $_GET['bnumber'];
      $duration = $_GET['duration'];
      $acause = $_GET['acause'];
      $bcause = $_GET['bcause'];
      $call_detail_id = self::add_call_detail($anumber, $bnumber, $duration, $acause, $bcause);
      $valid = array('NONE' => true, 'ALLOTTED_TIMEOUT' => true, 
          'CALL_REJECTED' => true, 'NORMAL_CLEARING' => true,
          'UNALLOCATED_NUMBER' => true, 'NORMAL_TEMPORARY_FAILURE' => true,
          'FACILITY_REJECTED' => true, 'INCOMPATIBLE_DESTINATION' => true
      );
      $caller_id = $_GET['caller'];
      $callee_id = $_GET['callee'];
      R15N::update_user_scoreboard($caller_id, 0, $call_detail_id);
      R15N::update_user_scoreboard($callee_id, 1, $call_detail_id);
      if (isset($valid[$acause])) { 
        $message_id = $_GET['message'];
        if ($bcause != 'NORMAL_CLEARING' || $duration < 5000) {
          $callee_id = 0;
        }
        self::add_call($call_detail_id, $message_id, $caller_id, $callee_id);
      }
      print "[ #* R15N Call:" . " caller " . $caller_id;
      print " callee " . $callee_id;
      print " message " . $message_id . ']';
    } else {
      print "INVALID";
    }
  }
}
