#!/usr/bin/php -q
<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP';
require_once('/srv/http/r15n/wp-load.php');

SMSAll::init();

class SMSAll {

  public static function E164($number) {
    if (preg_match('/^\+?(?:00)?([1-9]\d{8,14})$/i', $number, $matches)) {
      return $matches[1];
    } else {
      return null;
    }
  }

  public static function sms($to, $text) {
      #$to = "491632866163";
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
      print "\n----\n";
      #die();
  }

  public static function get_users() {
    global $wpdb;
    $sql = <<<SQL
SELECT m.user_id AS ID, d.anumber, count(d.anumber) AS cnt
  FROM wp_call_details AS d
  JOIN wp_usermeta AS m
    ON m.meta_key = "phone_number"
      AND m.meta_value LIKE CONCAT("%",d.anumber)
  WHERE d.acause != "NORMAL_CLEARING"
  GROUP BY d.anumber
  ORDER BY cnt DESC
  LIMIT 50;
SQL;
    $r = $wpdb->get_results($sql);
    print_r($r);
    return $r;
  }

  public static function init() {
    $r = self::get_users();
    if ($r) {
      foreach($r as $row) {
        $user = new WP_User($row->ID); 
        if ($user->active == "yes") {
          update_user_meta($user->id, 'active', 'no');
          $phonenumber = self::E164($user->phone_number);
          $name = $user->user_nicename;
          $message = "HTTP://R15N.NET -  ${name}, Your acount has been deactivated for lack of diligence. To reactivate your account call +49308687035762";
          self::sms($phonenumber, $message);
        }
      }
    }
  }

}
