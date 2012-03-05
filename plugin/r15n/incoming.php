<?php
require_once('../../../wp-load.php');
R15NIncomming::init();

class R15NIncomming {
  public static function init() {
    self::setup();
  }

  public static function sms($to, $text) {
      $mail = Array(
        'user:',
        'password:',
        'api_id:3354237',
        'text:%s',
        'to:%s'
      );

      $body = sprintf(join($mail, "\r\n"), $text, $to);
      mail('sms@messaging.clickatell.com', 
        'R15N', $body,
        "From: Telekommunisten <r15n@telekommunisten.net>\r\nContent-Transfer-Encoding: 7bit\r\n"
      );
  }

  public static function setup() {
    global $wpdb;

    $caller = isset($_GET['caller']) ? $_GET['caller'] : '14169671111';
    $command = isset($_GET['command']) ? $_GET['command'] : 'setup';

    print "[* R15N ${command} ${caller} ]\r\n";
    // register user
    $sql = <<<SQL
SELECT user_id FROM wp_usermeta WHERE meta_value ="%s" and meta_key = "phone_number";
SQL;
 
    $user_id = $wpdb->get_var(sprintf($sql,$caller));
    $random_password = wp_generate_password( 6, false );

    if ( !$user_id ) {
      $user_id = $wpdb->get_var(sprintf($sql, "+" . $caller));
    }
    if ( !$user_id ) {
      $user_id = $wpdb->get_var(sprintf($sql, "00" . $caller));
    }
    if ( !$user_id ) {
      $username = $caller;
      $user_id = wp_create_user( $username, $random_password, $email );
      $email = $username . '@mailinator.com';
      update_user_option($user_id, 'default_password_nag', false);
      update_user_meta($user_id, 'phone_number', $username);
      update_user_meta($user_id, 'show_admin_bar_front', false);
      update_user_meta($user_id, 'active', 'yes');
      $message = "HTTP://R15N.NET - Username: ${username} - Password: ${random_password} - To reset password call +49308687035761 - To deactivate account +49308687035762";
      self::sms($caller, $message);
      print "[ #* R15N New User Created]";
    } else if ($command == "setup") {
      $user = new WP_User($user_id); 
      $username = $user->user_login;
      $message = "HTTP://R15N.NET - Username: ${username} - New Password: ${random_password} - To reset password call +49308687035761 - To deactivate call +49308687035762";
      wp_set_password( $random_password, $user_id );
      self::sms($caller, $message);
      print "[ #* R15N Reset Password For User " . $user_id ." ]";
    }
    if ($command != "setup") {
      $user = new WP_User($user_id); 
      $set_active = ($user->active == 'yes') ? 'no' : 'yes';
      update_user_meta($user->id, 'active', $set_active);
      if ($set_active == 'yes') {
        $status = "active";
        $change = "deactivate";
      } else {
        $status = "inactive";
        $change = "activate";
      }
      $message = "HTTP://R15N.NET - Your account is ${status} - To ${change} your account call +49308687035762";
      self::sms($caller, $message);
      print "[ #* R15N User " . $user_id . " is now " . $status . " ]";
    }
  }
}
