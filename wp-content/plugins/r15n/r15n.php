<?php
/*
Plugin Name: R15N
Plugin URI: http://r15n.co.il
Description: R15N Community Telephone System
Version: 0.1
AuthorDmytri Kleiner
Author URI: http://dmytri.info
*/

R15N::init();

class R15N {

  /* INIT */

  static public function init() {
    add_action('init', function() {
      if (isset($_GET['action']) && '/wp-login.php' == $_SERVER['SCRIPT_NAME'] && 'register' == $_GET['action']) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('utils', admin_url('/js/utils.js'));
        wp_enqueue_script('user-profile', admin_url('/js/user-profile.js'));
      }
    });
    $home_url = (isset($_REQUEST['redirect_to'])) ? $_REQUEST['redirect_to'] : self::home_url();
    add_filter('login_redirect', function() use ($home_url) {
      return $home_url;
    });
    add_filter('registration_redirect', function() use ($home_url) {
      return $home_url;
    });
    add_filter('user_contactmethods', function() {
      return Array(
        'phone_number' => __('Phone Number'),
        'active' => __('Active')
      );
    });
    add_action('personal_options_update', function($user_id) {
      update_user_meta($user_id, 'show_admin_bar_front', false);
    });
    add_action('admin_head', function() {
      global $_wp_admin_css_colors;
      $_wp_admin_css_colors = 0;
    });
    $register_form_html = self::REGISTER_FORM_HTML;
    add_action('register_form', function() use ($register_form_html) {
      printf($register_form_html,
        __('Phone number'),
        __('New password'),
        __('Confirm new password'),
        __('Strength indicator'),
        __('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).')
      );
    });
    add_filter('user_registration_email', function($email) {
      if (!$email) {
        $phonenumber = $_POST['user_phonenumber'];
        $mail_user = ($phonenumber) ? $phonenumber : 'r15n';
        $email = $mail_user . '@mailinator.com';
      }
      return $email;
    }); 
    add_filter('registration_errors', function($errors) {
      if (!isset($_POST['pass1']) || $_POST['pass1'] != $_POST['pass2']) {
        $errors->add('password_mismatch', __('The passwords do not match.'));
      }
      $user_phonenumber = $_POST['user_phonenumber'];
      if (empty($user_phonenumber)) {
        $errors->add('phonenumber_missing',
          __('<strong>ERROR</strong>: Please enter your phone number'));
      } else if (!preg_match('/^\+?(?:00)?([1-9]\d{10,14})$/i', $user_phonenumber)) {
        $errors->add('phonenumber_invalid',
          __('<strong>ERROR</strong>: Please enter your complete phone number starting with the country code. i.e. +49305555555'));
      }
      if (!$errors) {
        wp_signon(array('user_login' => $_POST['user_name'],
        'user_password' => $_POST['pass1']), false);
      }
      return $errors;
    });
    add_action('sanitize_user', function($username) {
      return $username;
    });
    add_filter('validate_username', function() {
      return true;
    });
    /* Register Widgets */

    add_action('widgets_init', array('R15NAuthLinksWidget', 'register'));
    add_action('widgets_init', array('R15NCallButtonWidget', 'register'));
    add_action('widgets_init', array('R15NLoginWidget', 'register'));
    add_action('widgets_init', array('R15NLanguagewidget', 'register'));

    /* Activation */

    register_activation_hook(__FILE__, Array('R15NSetup','setup'));

  }

  /* ELEMENTS */

  static public function login_form () {
    return sprintf(self::LOGIN_FORM_HTML,
      __('Username'),
      __('Password'),
      __('Remember Me'),
      __('Log In'),
      self::current_url()
    );
  }

  static public function register_form() {
    return sprintf(self::REGISTER_FORM_HTML,
      __('Phone number'),
      __('New password'),
      __('Confirm new password')
    );
  }

  static public function call_button($att, $content) {
    $cap = (isset($att['capability'])) ? $att['capability'] : 'call_r15n';
    if (self::is_active() && current_user_can($cap)) {
      return sprintf(self::CALL_BUTTON_HTML,
        add_query_arg('capability', $cap, content_url() . '/plugins/r15n/callnow.php'),
        __('Call Now!')
      );
    }
  }

  static public function account_activation() {
    if (is_user_logged_in()) {
      return sprintf(self::ACCOUNT_ACTIVATION_HTML,
        self::toggle_activation_url(),
        (self::is_active()) ? __('Deactivate') : __('Activate')
      );
    }
    
  }

  static public function auth_links() {
    if (is_user_logged_in()) {
      $html[] = sprintf(self::LOGGED_IN_HTML,
       self::auth_url('logout'), __('Log Out'),
       self::account_activation_url(), (self::is_active()) ? __('Stop Calling Me!') : __('Activate')
      );
      if (current_user_can('administrator') || current_user_can('edit_pages')) {
        $html[] = sprintf(self::ADMIN_HTML,
          admin_url(),
          __('Dashboard')
        );
      } 
      return join("\n", $html);
    } else {
      return sprintf(self::NOT_LOGGED_IN_HTML,
       self::auth_url('register'), __('Register'),
       self::auth_url('login'), __('Log In')
      );
    }
  }

  static public function language_selector() {
    if (isset($GLOBALS['q_config']['language_name'])) {
      $items = Array();
      foreach(qtrans_getSortedLanguages() as $language) {
        $items[] = sprintf(self::LANGUAGE_SELECTOR_ITEM_HTML,
          add_query_arg('lang', $language, get_permalink()),
          $GLOBALS['q_config']['language_name'][$language]
        );
      }
      return sprintf(self::LANGUAGE_SELECTOR_LIST_HTML, join("\n",$items));
    }
  }

  static public function random_callee($att, $content) { 
    global $wpdb;
    $capability = $att['capability'];
    $number = $att['number'];
    $communities_table = "{$wpdb->prefix}communities";
    $calls_table = "{$wpdb->prefix}calls";
    $messages_table = "{$wpdb->prefix}messages";
    $sql = <<< SQL
SELECT c.callee_user_id FROM {$calls_table} AS c
  JOIN {$messages_table} AS m ON
   c.message_id = m.ID 
  JOIN {$communities_table} as p ON
    m.community_id = p.ID
    AND p.community = %s
  WHERE c.callee_user_id > 0
    AND initiated > CONCAT(CURDATE(), ' 00:00:00')
SQL;
    $q = $wpdb->prepare($sql, $capability);
    $r = $wpdb->get_results($q);
    if ($number > count($r)) {
      $number = count($r);
    }
    $ugly = array();
    $ugly[] = "<pre>";
    if ($number) {
      $win = array_rand($r, $number);
      shuffle($win);
      foreach($win as $winner) {
      $u = new WP_User($r[$winner]->callee_user_id);
      $ugly[] = " - {$u->display_name} {$u->phone_number}";
      } 
    } 
    $ugly[] = "</pre>";
    return join("\n",$ugly);
  }

 /* HELPERS */

  static public function lang() { 
    return substr(get_locale(), 0, 2);
  }

  static public function current_url() {
    return add_query_arg('lang', self::lang());
  }

  static public function home_url($type='login') {
    return add_query_arg('lang', self::lang(), site_url());
  }

  static public function call_initiated_url($cap='') {
    $attr['lang'] = self::lang();
    if (!empty($cap)) {
      $attr['capability'] = $cap;
    }
    return add_query_arg($attr, site_url('/call-initiated'));
  }

  static public function toggle_activation_url() {
    return content_url('/plugins/r15n/activation.php');
  }

  static public function is_active() {
      $active = false;
      if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $active = ($user->active == 'yes') ? true : false;
      }
      return $active;
  }

  static public function account_activation_url($cap='') {
    $attr['lang'] = self::lang();
    if (!empty($cap)) {
      $attr['capability'] = $cap;
    }
    return add_query_arg($attr, site_url('/account-activation'));
  }

  static public function show_member_content($att) {
      $cap = (isset($att['capability'])) ? $att['capability'] : null;
      $check = ($cap) ? R15N::is_active() && current_user_can($cap) : is_user_logged_in();
      return ($check && !is_feed());
  }

  static public function auth_url($type='login') {
    $attr['lang'] = self::lang();
    $attr['redirect_to'] = urlencode(self::current_url());
    switch($type) {
      case 'register': 
        $attr['action'] = 'register';
      break;
      case 'logout': 
        $attr['action'] = 'logout';
        $attr['_wpnonce'] = wp_create_nonce('log-out');
      break;
    }
    return add_query_arg($attr, wp_login_url());
  }

 static public function update_user_scoreboard($user_id, $bleg=false, $call_detail_id=null) {
    global $wpdb;
    $user_scoreboard_table = "{$wpdb->prefix}user_scoreboard";
    $sql = <<<SQL
SELECT * FROM $user_scoreboard_table
  WHERE user_id=%d;
SQL;
  
    $q = $wpdb->prepare($sql, $user_id);
    $user_scoreboard = $wpdb->get_row($q);

    $values = array();
    $format = array();
    if ($call_detail_id) {
      $values['locked'] = 0;
      array_push($format, '%d');
      $call_detail_table = "{$wpdb->prefix}call_details";
      $sql = <<<SQL
SELECT * FROM $call_detail_table
  WHERE ID=%d
SQL;
      $q = $wpdb->prepare($sql, $call_detail_id);
      $call_detail = $wpdb->get_row($q);
      file_put_contents('/tmp/r15nsql', $q);
      if ($call_detail) {
        if ($call_detail->bcause == "NORMAL_CLEARING") {
            $values['score'] = 2000 + (($user_scoreboard->score * 3) + $call_detail->duration) / 4;
            array_push($format, '%d');
            $values['failures'] = 0;
            array_push($format, '%d');
        } else {
          $score_failure = false;
          if (empty($call_detail->bcause) || $call_detail->bcause == "NONE" || $call_detail->bcause == "UNINITIATED") {
              switch ($call_detail->acause) {
                case "INVALID_NUMBER_FORMAT":
                case "NORMAL_TEMPORARY_FAILURE":
                  $score_failure = false;
                break;
                case "RECOVERY_ON_TIMER_EXPIRE":
                case "USER_BUSY":
                case "NO_ANSWER":
                  $score_failure = false;
                  $values['score'] = $user_scoreboard->score * 0.75;
                  array_push($format, '%d');
                break;
                case "ALLOTTED_TIMEOUT":
                case "NORMAL_CLEARING":
                  if (!$bleg) {
                    $values['score'] = $user_scoreboard->score + 1000;
                    array_push($format, '%d');
                  }
                  $score_failure = $bleg;
                break;
                case "CALL_REJECTED":
                default:
                  $score_failure = !$bleg;
                break;
              }
          } else {
             $score_failure = $bleg;
          }
          if ($score_failure) {
            if ($user_scoreboard->failures < 5) {
              $values['score'] = $user_scoreboard->score * 0.5;
              array_push($format, '%d');
              $values['failures'] = $user_scoreboard->failures + 1;
              array_push($format, '%d');
            } else {
              update_user_meta($user_id, 'active', 'no');
              $values['score'] = 0;
              array_push($format, '%d');
              $values['failures'] = 2;
              array_push($format, '%d');
            }
          } 
        }
      }
    } else {
      $values['locked'] = 1;
      array_push($format, '%d');
    }

    if ($user_scoreboard) {
      $wpdb->update($user_scoreboard_table, $values, array('user_id' => $user_id), $format, array('%d'));
    } else {
      $values['user_id'] = $user_id;
      array_push($format, '%d');
      $wpdb->insert($user_scoreboard_table, $values, $format);
    }
  }

 /* TEMPLATES */

  const NOT_LOGGED_IN_HTML = <<<HTML
<nav class="sidebar">
<ul>
  <li><a href="%s">%s</a></li>
  <li><a href="%s">%s</a></li>
</ul>
</nav>
HTML;

  const LOGGED_IN_HTML = <<<HTML
<nav class="sidebar">
<ul>
  <li><a href="%s">%s</a></li>
  <li><a href="%s">%s</a></li>
</ul>
</nav>
HTML;

  const ADMIN_HTML = <<<HTML
<nav class="sidebar">
<ul>
  <li><a href="%s">%s</a></li>
</ul>
</nav>
HTML;

  const CALL_BUTTON_HTML = <<<HTML
<script>
jQuery(document).ready(function() {
  jQuery('#overlay').hide();
});
</script>
<div class="call">
  <a class="button" href="%s">%s</a>
</div>
HTML;

  const REGISTER_FORM_HTML = <<<HTML
  <p>
    <label>%s<br /> 
      <input type="text" name="user_phonenumber" id="user_phonenumber" class="input" value="" size="25" tabindex="20" />
    </label> 
  </p> 
  <p>
    <label>%s<br />
      <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
    </label>
  </p>
  <p>
     <label>%s<br /> 
       <input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
     </label>
  </p>
     <div id="pass-strength-result" class="hide-if-no-js">%s</div>
     <p class="description indicator-hint">%s</p>
  <p> 
HTML;

  const LOGIN_FORM_HTML = <<<HTML
<form name="loginform" id="loginform" action="/wp-login.php" method="post"> 
  <p> 
    <label>%s<br /> 
    <input type="text" name="log" id="user_login" class="input" size="20" tabindex="10" /></label> 
  </p> 
  <p> 
    <label>%s<br /> 
    <input type="password" name="pwd" id="user_pass" class="input" size="20" tabindex="20" /></label> 
  </p> 
  <p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> %s</label></p> 
  <p class="submit"> 
    <input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="%s" tabindex="100" /> 
    <input type="hidden" name="redirect_to" value="%s" /> 
    <input type="hidden" name="testcookie" value="1" /> 
  </p> 
</form>
HTML;

  const LANGUAGE_SELECTOR_LIST_HTML = <<<HTML
<nav class="language">
  <ul>
%s
  </ul>
</nav>
HTML;

  const LANGUAGE_SELECTOR_ITEM_HTML = <<<HTML
    <li><a href="%s">%s</a></li>
HTML;

  const ACCOUNT_ACTIVATION_HTML = <<<HTML
<div class="activation">
  <a class="button" href="%s">%s</a>
</div>
HTML;

}

class R15NAuthLinksWidget extends WP_Widget {
  public function __construct() {
    parent::__construct('r15n_auth_links', 'R15N Auth Links');
  }
  public function register() {
    register_widget(__class__);
    add_shortcode('member', function($att, $content) {
      return ($content && R15N::show_member_content($att)) ? $content : '';
    });
    add_shortcode('nonmember', function($att, $content) {
      return ($content && !R15N::show_member_content($att)) ? $content : '';
    });
    add_shortcode('active', function($att, $content) {
      return ($content && R15N::is_active()) ? $content : '';
    });
    add_shortcode('inactive', function($att, $content) {
      return ($content && is_user_logged_in() && !R15N::is_active()) ? $content : '';
    });
    add_shortcode('account-activation', function($att, $content) {
      return R15N::account_activation();
    });
    add_shortcode('random-callee', function($att, $content) {
      return R15N::random_callee($att, $content);
    });
  }
  public function form() {
    print 'R15N Auth Links';
  }
  public function widget($args) {
    print $args['before_widget'];
    print R15N::auth_links();
    print $args['after_widget'];
  }
}


class R15NCallButtonWidget extends WP_Widget {
  public function __construct() {
    parent::__construct('r15n_callbutton', 'R15N Call Button');
  }
  public function register() {
    register_widget(__class__);
    add_shortcode('call', function($att, $content) {
      return R15N::call_button($att, $content);
    });
  }
  public function form() {
    print 'R15N Call Button';
  }
  public function widget($args) {
    if (is_user_logged_in()) {
      print $args['before_widget'];
      print R15N::call_button();
      print $args['after_widget'];
    }
  }
}

class R15NLoginWidget extends WP_Widget {
  public function __construct() {
    parent::__construct('r15n_login', 'R15N Login Widget');
  }
  public function register() {
    register_widget(__class__);
  }
  public function form() {
    print 'R15N Login Widget';
  }
  public function widget($args) {
    if (!is_user_logged_in()) {
      print $args['before_widget'];
      print R15N::login_form();
      print $args['after_widget'];
    }
  }
}

class R15NLanguageWidget extends WP_Widget {
  public function __construct() {
    parent::__construct('r15n_language', 'R15N Language Widget');
  }
  public function register() {
    register_widget(__class__);
  }
  public function form() {
    print 'R15N Language Selector Widget';
  }
  public function widget($args) {
    print $args['before_widget'];
    print R15N::language_selector();
    print $args['after_widget'];
  }
}

class R15NSetup {

  const COMMUNITIES_TABLE_SQL = <<<SQL
CREATE TABLE %s (
  ID smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  community VARCHAR(55) NOT NULL,
  PRIMARY KEY  (ID),
  UNIQUE KEY community (community)
);
SQL;

  const MESSAGES_TABLE_SQL = <<<SQL
CREATE TABLE %s (
  ID bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  community_id smallint UNSIGNED NOT NULL,
  user_id bigint UNSIGNED NOT NULL,
  open_utc time NOT NULL,
  close_utc time NOT NULL,
  initiated timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY  (ID),
  KEY community_id (community_id)
);
SQL;

  const CALLS_TABLE_SQL = <<<SQL
CREATE TABLE %s (
  ID bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  call_detail_id bigint UNSIGNED NOT NULL,
  message_id bigint UNSIGNED NOT NULL,
  caller_user_id bigint UNSIGNED DEFAULT NULL,
  callee_user_id bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY  (ID),
  KEY message_id (message_id),
  KEY message_callee (message_id, callee_user_id),
  KEY caller_user_id (caller_user_id),
  KEY callee_user_id (callee_user_id)
);
SQL;

  const CALL_DETAILS_TABLE_SQL = <<<SQL
CREATE TABLE %s (
  ID bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  initiated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reported timestamp,
  duration int(11) DEFAULT NULL,
  acause char(40) DEFAULT NULL,
  bcause char(40) DEFAULT NULL,
  anumber char(40) DEFAULT NULL,
  bnumber char(40) DEFAULT NULL,
  PRIMARY KEY  (id)
);
SQL;

  const USER_SCOREBOARD_TABLE_SQL = <<<SQL
CREATE TABLE %s (
  user_id bigint UNSIGNED NOT NULL,
  locked boolean DEFAULT 0 NOT NULL, 
  score int(11) UNSIGNED DEFAULT 0 NOT NULL,
  failures tinyint(3) UNSIGNED DEFAULT 0 NOT NULL,
  updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (user_id),
  KEY score (score),
  KEY updated (updated)
);
SQL;

  public static function create_table($sql, $name) {
    global $wpdb;
    $query = sprintf($sql,
      $wpdb->prefix . $name
    );
    dbDelta($query); 
  }

  public static function setup() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    self::create_table(self::COMMUNITIES_TABLE_SQL, 'communities');
    self::create_table(self::MESSAGES_TABLE_SQL, 'messages');
    self::create_table(self::CALLS_TABLE_SQL, 'calls');
    self::create_table(self::CALL_DETAILS_TABLE_SQL, 'call_details');
    self::create_table(self::USER_SCOREBOARD_TABLE_SQL, 'user_scoreboard');
  }
}

/* PLUGGABLES */

if ( !function_exists('wp_new_user_notification') ) :
function wp_new_user_notification($user_id, $plaintext_pass = '') {
  $user = new WP_User($user_id);
  $password = $_POST['pass1'];
  $phonenumber = $_POST['user_phonenumber'];
  wp_set_password($password, $user_id);
  update_user_option($user_id, 'default_password_nag', false);
  update_user_meta($user_id, 'phone_number', $phonenumber);
  update_user_meta($user_id, 'show_admin_bar_front', false);
  update_user_meta($user_id, 'active', 'yes');
  $user_login = stripslashes($user->user_login);
  $user_email = stripslashes($user->user_email);
  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
  $message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
  $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
  $message .= sprintf(__('E-mail: %s'), $user_email) . "\r\n";
  @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);
}
endif;
