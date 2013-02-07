<?php
// darkstar.class.php

namespace core\themes\darkstar;

use core\engines\smarty\smarty;
use core\themes\theme;

/**
 *
 */
class darkstar extends theme
{
  private $smarty;

  private  $regions = array(
    'header' => 'Header',
    'page_top' => 'Page top',
    'page_bottom' => 'Page bottom',

    'content' => 'Content',
    'sidebar_first' => 'Sidebar first',
    'sidebar_second' => 'Sidebar second',

    'triptych_first' => 'Triptych first',
    'triptych_middle' => 'Triptych middle',
    'triptych_last' => 'Triptych last',

    'footer_top' => 'Footer top',
    'footer_firstcolumn' => 'Footer first column',
    'footer_secondcolumn' => 'Footer second column',
    'footer_thirdcolumn' => 'Footer third column',
    'footer_fourthcolumn' => 'Footer fourth column',
    'footer_fifthcolumn' => 'Footer fifth column',
    'footer_bottom' => 'Footer bottom',

//    'messages' => 'System messages',
  );

  /**
   *
   */
  public function __construct() {
    $this->smarty = new smarty();

    $this->smarty->get_smarty()->addPluginsDir(__DIR__);
    $this->smarty->get_smarty()->loadFilter('pre', 'whitespace_control');
  }

  /**
   * Get the regions that are available in this theme.
   *
   * @return array Associative array with regions, 'machine name' => 'human readable name'.
   */
  public function get_regions() {
    return $this->regions;
  }

  /**
   * Render page.
   *
   * @param string $name
   * @param array $context
   * @return string
   */
  public function render($name, $context = array()) {

//    $context['uid'] = get_user()->uid;
    $context['theme_path'] = $this->get_path() . 'core/themes/darkstar';

    $this->add_js(BASE_PATH . 'core/misc/jquery.js', array('weight' => -10));
//    library_load('jquery', -10);
    $this->add_js($this->get_path() . 'js/main.js');
    $this->add_css($this->get_path() . 'css/1200_15-20_col.css', array('weight' => -10));
    $this->add_css($this->get_path() . 'css/style.css');

    // Add scripts to the page.
    // Add css.
    $css = $this->get_css();
    if ($css) {
      $context['head']['css'] = $css;
    }
    $inline_css = $this->get_inline_css();
    if ($inline_css) {
      $context['head']['inline_css'] = $inline_css;
    }
    // Add js.
    $js = $this->get_js();
    if ($js) {
      $context['head']['js'] = $js;
    }
    $inline_js = $this->get_inline_js();
    if ($inline_js) {
      $context['head']['inline_js'] = $inline_js;
    }

    // Add messages to the page.
    $messages = get_module_session()->get_messages();
    if ($messages) {
      $context['messages'] = $messages;
    }
//    var_dump($context);

    return $this->smarty->render($name, $context);
  }

  /**
   * @param string $name template path without the extension
   * @param array $context
   * @param bool $nocache
   * @return string
   */
  public function fetch($name, $context = array(), $nocache = FALSE) {
    return $this->smarty->render($name, $context, $nocache);
  }

  public function clear_cache() {
    $this->smarty->clear_cache();
  }

}
