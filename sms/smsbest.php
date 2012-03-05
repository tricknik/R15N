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
SELECT u.ID, s.score, count(c.caller_user_id) as cnt
  FROM wp_user_scoreboard as s 
  JOIN wp_users as u ON s.user_id  = u.ID
  JOIN wp_calls as c ON u.ID = c.caller_user_id
  GROUP BY c.caller_user_id
  HAVING s.score > 30000 
    AND cnt > 2
  ORDER BY s.score desc
  LIMIT 20;
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
        if (isset($user->phone_number) && !empty($user->phone_number)) {
            $phonenumber = self::E164($user->phone_number);
            $change = ($user->active == 'yes') ? 'deactivate' : 'activate';
            $name = $user->user_nicename;
            $message = "HTTP://R15N.NET - Thank You ${name} for being among the most diligent R15N users! R15N depends on you. To ${change} account call +49308687035762";
            self::sms($phonenumber, $message);
        }
      }
    }
  }

}
