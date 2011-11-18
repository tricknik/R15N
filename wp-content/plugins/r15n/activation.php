<?php
require_once('../../../wp-load.php');
R15NActivation::init();

class R15NActivation {
  public static function init() {
    if (is_user_logged_in()) {
      $set_active = (R15N::is_active()) ? 'no' : 'yes';
      $user = wp_get_current_user();
      update_user_meta($user->id, 'active', $set_active);
    }
    wp_redirect(R15N::account_activation_url());
  }
}
