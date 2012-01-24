<?php

R15NThemeFunctions::init();

class R15NThemeFunctions {

public static function init() {
  add_action('init', function() {
    wp_enqueue_script('jquery');
    register_nav_menu('site_nav', 'Site Navigation');
  });
  add_action('widgets_init', function() {
    register_sidebar(
      array(
        'id' => 'primary',
        'name' => __( 'Sidebar' ),
        'description' => __( 'Sidebar' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>'
      )
    );
  });

  add_filter('login_headertitle', function() {
    return "R15N";
  });

  add_filter('login_headerurl', function() {
    return home_url();
  });

  $style = self::ADMIN_STYLE;
  $dir = self::dir();
  add_action('login_head', function() use ($style, $dir) {
    printf($style,
      $dir,
      home_url() . '/images/r15n-logo.gif'
    );
  });
}
 
public static function lang() {
  return substr(get_locale(),0,2);
}

public static function dir() {
  return (self::lang() == 'he') ? 'rtl' : 'ltr';
}

const ADMIN_STYLE = <<< STYLE
<style type="text/css">
h1, h1 a, h1 img { height: 80px; width: 205px; margin: 0; padding: 0; border: 0;}
body {
  direction: %s;
 border:none;

}
h1 {
  position: absolute;
  top: 30px;
  align:center;
}
h1 a { 
  background-image:url(%s);
}
    </style>'
STYLE;

/* ENDCLASS */ };
