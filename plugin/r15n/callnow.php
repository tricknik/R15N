<?php

require_once('../../../wp-load.php');
R15NCall::init();

class R15NCall {

  public static function get_community_id($community) {
    global $wpdb;
    $communities_table = "{$wpdb->prefix}communities";
    $sql = <<<SQL
SELECT ID from $communities_table
  WHERE community="%s";
SQL;
  
    $q = $wpdb->prepare($sql, $community);
    $r = $wpdb->get_row($q);
    $community_id = $r->ID;
    if (!$community_id) {
      $wpdb->insert($communities_table, array('community' => $community));
      $community_id = $wpdb->insert_id;
    }
    return $community_id;
  }

  public static function add_message($community_id, $user_id, $open, $close) {
    global $wpdb;
    $messages_table = "{$wpdb->prefix}messages";
    $values['community_id'] = $community_id;
    $values['user_id'] = $user_id;
    $values['open_utc'] = $open;
    $values['close_utc'] = $close;
    $wpdb->insert($messages_table,
      $values,
      Array('%d','%d','%s','%s', '%s')
    );
    $message_id = $wpdb->insert_id;
    return $message_id;
  }

  public static function add_call($message_id, $callee) {
    global $wpdb;
    $calls_table = "{$wpdb->prefix}calls";
    $values['message_id'] = $message_id;
    $values['callee_user_id'] = $callee;
    $wpdb->insert($calls_table,
      $values,
      Array('%d','%d')
    );
    $call_id = $wpdb->insert_id;
    return $call_id;
  }

  public static function init() {
    wp_redirect(R15N::call_initiated_url($call_id));
    return false;
    $cap = (isset($_REQUEST['capability'])) ? $_REQUEST['capability'] : 'call_test';
    $open = (isset($_REQUEST['open'])) ? $_REQUEST['open'] : '18:00';
    $close = (isset($_REQUEST['close'])) ? $_REQUEST['close'] : '21:00';
    $user = wp_get_current_user();
    $community_id = self::get_community_id($cap);
    $message_id = self::add_message($community_id, $user->ID, $open, $close);
    $call_id = self::add_call($message_id, $user->ID);
    wp_redirect(R15N::call_initiated_url($call_id));
  }
}
