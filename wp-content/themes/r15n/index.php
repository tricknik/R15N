<?php 
R15NTheme::render();

class R15NTheme {

const START_HTML = <<<HTML
<!doctype html>
<html>
  <head>
<link href='http://fonts.googleapis.com/css?family=PT+Serif:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400' rel='stylesheet' type='text/css'>

<meta charset="utf-8"/>
<title>%s</title>
<!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<link rel="stylesheet" media="all" href="%s"/>
HTML;

const PAGE_TOP_HTML = <<<HTML
</head>
<body lang="%s">
  <div id="header">
    <header id="masthead"> 
      <figure>
<a href="%s"><img src="%s/images/r15n-logo.gif" border="0" ></a>
      </figure>
      <nav id="pages">
HTML;

const HEADER_END_HTML = <<<HTML
      </nav>
    </header>
  </div>
HTML;

const CONTENT_START_HTML = <<<HTML
  <article id="page">
    <section class="content" dir="%s">
HTML;

const PAGE_BOTTOM_HTML = <<<HTML
    </section>
    <aside id="sidebar" dir="%s">
HTML;

const END_PAGE_HTML = <<<HTML
    </aside>
  </article>
HTML;

const OVERLAY_TOP_HTML = <<<HTML
  <div id="overlay">
    <div id="sash">
      <div class="logo">
<img src="/images/r15n.gif" />
      </div>
      <section>
<h3>%s</h3>
%s
HTML;

const OVERLAY_BOTTOM_HTML = <<<HTML
        <div id="countdown" />
      </section>
      <button onclick="jQuery('#overlay').hide();" type="submit" id="hide" name="hide">%s</button>
    </div>
  </div>
HTML;

const END_HTML = <<<HTML
</body>
</html>
HTML;

const COUNTDOWN_SCRIPT = <<<SCRIPT
<script type="text/javascript">
jQuery(function(){
  jQuery('#countdown').countdown({
    image: 'http://www.r15n.net/wp-content/plugins/simple-countdown-timer/images/digits-21-30.png',
    format: "dd:hh:mm:ss",
    startTime: "%s",
    digitWidth: 21,
    digitHeight: 30,
  });
});
</script>
SCRIPT;

public static function render() {
  if (have_posts()) {
    printf(self::START_HTML,
      get_bloginfo('name') . wp_title(':', false),
      get_stylesheet_uri()
    );
    wp_head();
    if (function_exists('sct_calcage')) {
      printf(self::COUNTDOWN_SCRIPT,
        self::time_until_open()
      );
    }
    printf(self::PAGE_TOP_HTML,
      self::lang(),
      home_url(),
      home_url()
    );
    wp_nav_menu();
    print self::HEADER_END_HTML;
    while (have_posts()) {
      the_post();
      printf(self::CONTENT_START_HTML,
        self::dir()
      );
      the_content();
    } 
  } else {
    _e('Sorry, no posts matched your criteria.');
  }
  printf(self::PAGE_BOTTOM_HTML,
    self::dir()
  );
  dynamic_sidebar('primary');
  print self::END_PAGE_HTML;
  printf(self::OVERLAY_TOP_HTML,
    __('Sorry, R15N is currently closed.'),
    __('Launching August 2nd!')
  );
  printf(self::OVERLAY_BOTTOM_HTML,
    __('hide this')
  );
  print self::END_HTML;
}

public static function lang() {
  return substr(get_locale(),0,2);
}

public static function dir() {
  return (self::lang() == 'he') ? 'rtl' : 'ltr';
}

public static function time_until_open () {
  $gmtOffset = (float) get_option('gmt_offset');
  if(empty($gmtOffset))
    $gmtOffset = wp_timezone_override_offset();
                        
  $secs = strtotime('2 August 2011 8am')-(time()+($gmtOffset*3600));
  if($secs < 0) $secs = 0;
        
  $d = sct_calcage($secs,86400,100000);
  $h = sct_calcage($secs,3600,24);
  $m = sct_calcage($secs,60,60);
  $s = sct_calcage($secs,1,60);
                        
  return $d.':'.$h.':'.$m.':'.$s;
}

/* ENDCLASS */ };

