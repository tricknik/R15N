#!/usr/bin/php -q
<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP';
require_once('/srv/http/r15n/wp-load.php');

SMSAll::init();

class SMSAll {

  public static function E164($number) {
    if (preg_match('/^\+?(?:00)?([1-9]\d{10,14})$/i', $number, $matches)) {
      return $matches[1];
    } else {
      return null;
    }
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
      print $body;
      print "\n";
  }

  public static function get_users() {
    global $wpdb;
    $sql = <<<SQL
SELECT ID FROM {$wpdb->users};
SQL;
    return $wpdb->get_results($sql);
  }

  public static function init() {
    $r = self::get_users();
    if ($r) {
      foreach($r as $row) {
        $user = new WP_User($row->ID); 
        if (isset($user->phone_number) && !empty($user->phone_number)) {
            $phonenumber = self::E164($user->phone_number);
            $change = ($user->active == 'yes') ? 'deactivate' : 'activate';
            $message = "HTTP://R15N.NET - Pay attention to incoming calls. R15N depends on your diligence. To ${change} account call +49308687035762";
            self::sms($phonenumber, $message);
        }
      }
    }
  }

}
