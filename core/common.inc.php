<?php

/**************************** helpers for convenience *************************/

/**
 * @param $class
 * @return string
 */
function get_class_name($class) {
  $name = get_class($class);
  $pos = strrpos($name, '\\');
  if ($pos !== FALSE) {
    return substr($name, $pos + 1);
  } else {
    return $name;
  }
}

/**
 * @param string $type
 * @param string|array $data
 * @return bool
 */
function watchdog_add($type, $data) {
  return get_module('watchdog')->add($type, $data);
}

/**
 *
 */
function log_add() {
  get_module('log')->add();
}

/**
 * @return \core\modules\system\system Returns the object or FALSE.
 */
function get_module_system() {
  return get_module('system');
}

/**
 * @return \core\modules\session\session Returns the object or FALSE.
 */
function get_module_session() {
  return get_module('session');
}

/**
 * @return \core\modules\menu\menu Returns the menu instance.
 */
function get_module_menu() {
  return get_module('menu');
}

/**
 * @return \core\modules\router\router Returns the object or FALSE.
 */
function get_module_router() {
  return get_module('router');
}

/**
 * @return \core\modules\form\form Returns the form instance.
 */
function get_module_form() {
  return get_module('form');
}

/**
 * @return \core\modules\library\library Returns the object or FALSE.
 */
function get_module_library() {
  return get_module('library');
}

/**
 * @return \core\modules\user\user Returns the object or FALSE.
 */
function get_module_user() {
  return get_module('user');
}

/**
 * @return \core\modules\block\block Returns the object or FALSE.
 */
function get_module_block() {
  return get_module('block');
}

/**
 * @return \core\modules\comment\comment Returns the object or FALSE.
 */
function get_module_comment() {
  return get_module('comment');
}

/**
 * @return \core\modules\node\node Returns the object or FALSE.
 */
function get_module_node() {
  return get_module('node');
}

/**
 * @return \core\modules\content\content Returns the object or FALSE.
 */
function get_module_content() {
  return get_module('content');
}

/**
 * @param $name
 * @param array $info
 * @return array
 */
function library_add($name, array $info) {
  return get_module_library()->add($name, $info);
}

/**
 * @param string $name
 * @return array|bool
 */
function library_get($name) {
  return get_module_library()->get($name);
}

/**
 * @param string $name
 * @return string|bool
 */
function library_get_dir($name) {
  return get_module_library()->get_dir($name);
}

/**
 * @param string $name
 * @return string|bool
 */
function library_get_path($name) {
  return get_module_library()->get_path($name);
}

/**
 * @param string $name
 * @param int $weight
 * @return array|bool
 */
function library_load($name, $weight = 0) {
  $module = get_module_library();
  if ($module) {
    return $module->load($name, $weight);
  } else {
    return FALSE;
  }
}

/**
 * @param string $message
 * @param string $status
 */
function set_message($message, $status = 'status') {
  get_module('session')->set_message($message, $status);
}

/**
 * Add js to the page.
 *
 * @param string|array $data The file path or the inline js string.
 * @param string|array $options An string or array of options:
 * <pre>
 * string:
 *  'file': Adds a reference to a JavaScript file to the page.
 *  'inline': Executes a piece of JavaScript code on the current page by placing the code directly in the page.
 *  'setting': Adds settings to the global storage of JavaScript settings.
 * </pre>
 * <pre>
 * array:
 *  'weight': Default is 0.
 *  'type': Can be 'file', 'inline' or 'setting'. Default is 'file'.
 * </pre>
 */
function add_js($data, $options = NULL) {
  get_theme()->add_js($data, $options);
}


/**
 * Add css to the page.
 *
 * @param string|array $data The file path or the inline js string.
 * @param string|array $options An string or array of options:
 * <pre>
 * string:
 *  'file': Adds a reference to a stylesheet file to the page.
 *  'inline': Executes a piece of stylesheet code on the current page by placing the code directly in the page.
 * </pre>
 * <pre>
 * array:
 *  'weight': Default is 0.
 *  'type': Can be 'file' or 'setting'. Default is 'file'.
 * </pre>
 */
function add_css($data, $options = NULL) {
  get_theme()->add_css($data, $options);
}


/**
 * Create a V4 UUID.
 *
 * @link http://tools.ietf.org/html/rfc4122
 *
 * @return string
 */
function create_uuid() {
  return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    // 32 bits for "time_low"
    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

    // 16 bits for "time_mid"
    mt_rand( 0, 0xffff ),

    // 16 bits for "time_hi_and_version",
    // four most significant bits holds version number 4
    mt_rand( 0, 0x0fff ) | 0x4000,

    // 16 bits, 8 bits for "clk_seq_hi_res",
    // 8 bits for "clk_seq_low",
    // two most significant bits holds zero and one for variant DCE1.1
    mt_rand( 0, 0x3fff ) | 0x8000,

    // 48 bits for "node"
    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
  );
}


/**
 * Builds a string from a associative array.
 * The 'class' attribute may be a string or a associative array.
 *
 * @param array $attributes
 * @return string
 */
function build_attribute_string(array $attributes) {
  $attribute_array = array();
  foreach ($attributes as $name => $value) {
    if ($name == 'class') {
      if (!is_array($value)) {
        $value = array($value);
      }
      $value = implode(' ', $value);
    }
    $attribute_array[] = sprintf('%s="%s"', $name, $value);
  }
  return implode(' ', $attribute_array);
}

/**
 * @param array $array
 * @return array
 */
function make_array_assoc(array $array) {
  $a = array();
  foreach ($array as $value) {
    $a[$value] = $value;
  }
  return $a;
}

/**
 * @param string $text
 * @return string
 */
function check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * @param string $text
 * @param string $path
 * @return string
 */
function l($text, $path) {
  if ((substr($path, 0, 7) != 'http://') && (substr($path, 0, 8) != 'https://')) {
    $path = BASE_PATH . $path;
  }
  return '<' . "a href='$path'>$text</a>";
}

/**
 * @param string $path
 */
function go_to($path) {
  // Run hook shutdown().
  $array = array(
    'status_code' => 302,
    'content_length' => 0,
  );
  invoke('shutdown', $array);

  $path = BASE_PATH . $path;
  header("Location: $path");
  exit;
}

/**
 * Get the currently loggedin user.
 *
 * @return \USER|null Returns a user object of the current loggedin user, the guest account or NULL if user module is not installed.
 */
function get_user() {
  /** \USER $user */
  static $user = NULL;

  if (!$user) {
    $module_user = get_module_user();
    if ($module_user) {
      $user = $module_user->get_loggedin_user();
    } else {
      $user = new stdClass();
      $user->uid = 0;
      $user->role = '';
    }
  }
  return $user;
}

/**
 * @param $access_arguments
 * @return bool
 */
function user_has_access($access_arguments) {
  $user = get_user();
  if (($access_arguments == '') || ($user->role == $access_arguments) || ($user->uid == 1)) {
    return TRUE;
  }
  return FALSE;
}

/**
 * @param $mixed
 * @param bool $return
 * @return string|null
 */
function _vardump($mixed, $return = FALSE) {
  if ($return) {
    ob_start();
    var_dump($mixed);
    return ob_get_clean();
  } else {
    var_dump($mixed);
    return NULL;
  }
}

/**
 * Custom pretty vardump for 1 mixed variable.
 *
 * @param mixed $var
 * @param bool $return
 * @param bool $pre
 * @param string $font_size
 * @return null|string
 */
function vardump($var, $return = FALSE, $pre = TRUE, $font_size = '') {
  static $out = '';
  static $spaces = 0;

  if ($spaces == 0) $out = '';

  if ($pre) {
    $n = "\n";
    $sp = ' ';
  }
  else {
    $n = '<br />';
    $sp = '&nbsp;';
  }

  //echo "++ $spaces ++";
//	if (!$spaces) echo "<pre>";

  $t = str_repeat($sp, $spaces);

  if (is_array($var)) {
    if ($spaces) {
      $out .= "$n";
    }
    $spaces += 4;
    $out .= "$t<strong>array</strong>$n";
    $t .= $sp . $sp;
    /** @var array $var */
    if (count($var)) {
      foreach ($var as $key => $val) {
        if (is_string($key)) $key = "'$key'";
        $out .= "$t{$key} <span style='color: #888a85'>=&gt;</span> ";
        $fn = __FUNCTION__;
        $fn($val);
      }
    } else {
      $out .= "$t<em><span style='color: #888a85'>empty</span></em>$n";
    }
    $spaces -= 4;
  }
  elseif (is_object($var)) {
    if ($spaces) {
      $out .= "$n";
    }
    $spaces += 4;
    /** @var stdClass $var */
    $out .= "$t<strong>object(<i>" . get_class($var) . "</i>)</strong>$n";
    $t .= $sp . $sp;
/* working
    $avar = (array)$var;
    if (count($avar)) {
      foreach ($avar as $key => $val) {
        if (is_string($key)) $key = "'$key'";
        $out .= "$t{$key} <span style='color: #888a85'>=&gt;</span> ";
        $fn = __FUNCTION__;
        $fn($val);
      }
    } else {
      $out .= "$t<em><span style='color: #888a85'>empty</span></em>$n";
    }
//*/
    $reflect = new ReflectionObject($var);
    $props = $reflect->getProperties();
    if (count($props)) {
      foreach ($props as $prop) {
        $type = array();
        if ($prop->isPrivate()) {
          $type[] = 'private';
        }
        if ($prop->isProtected()) {
          $type[] .= 'protected';
        }
        if ($prop->isPublic()) {
          $type[] = 'public';
        }
        if ($prop->isStatic()) {
          $type = 'static';
        }
        $type = '<i>' . implode('</i> <i>', $type) . '</i>';
        $name = $prop->name;
        if (is_string($name)) $name = "'$name'";
        $out .= "$t$type {$name} <span style='color: #888a85'>=&gt;</span> ";
        $fn = __FUNCTION__;
        $fn($prop->getValue($var));
      }
    } else {
      $out .= "$t<em><span style='color: #888a85'>empty</span></em>$n";
    }


    $spaces -= 4;
  }
  elseif (is_string($var)) {
    $len = strlen($var);
    $var = htmlentities($var);
    $var = str_replace(' ', '&nbsp;', $var);
    $out .= "<" . "small>string</small> <span style='color: #cc0000'>'$var'</span> <i>(length=" . $len . ")</i>$n";
  }
  elseif (is_int($var)) {
    $out .= "<" . "small>int</small> <span style='color: #4e9a06'>$var</span>$n";
  }
  elseif (is_float($var)) {
    $out .= "<" . "small>float</small> <span style='color: #f57900'>$var</span>$n";
  }
  elseif (is_bool($var)) {
    $var = $var ? 'true' : 'false';
    $out .= "<" . "small>boolean</small> <span style='color: #75507b'>$var</span>$n";
  }
  elseif (is_null($var)) {
//		$var = gettype($var);
    $out .= "<" . "small>null</small> <span style='color: #75507b'>null</span>$n";
  }
//    elseif (is_object($var)) {
//      $var = get_class($var);
//      $out .= "<" . "small>object</small> <span style='color: #75507b'>$var</span>$n";
//    }
  elseif (is_resource($var)) {
    /** @var resource $var */
    $var = get_resource_type($var);
    $out .= "<" . "small>resource</small> <span style='color: #75507b'>$var</span>$n";
  }
  else {
    $type = gettype($var);
    $out .= "<" . "small>$type</small> <span style='color: #75507b'>???</span>$n";
  }

  if (!$spaces) {
    $tmp = $out;
    $out = '';
    if ($pre) {
      if ($font_size) {
        $tmp = sprintf('<' . 'pre style="font-size: %s">%s</pre>', $font_size, $tmp);
      }
      else {
        $tmp = "<pre>$tmp</pre>";
      }
    }
    return ($return) ? $tmp : print $tmp;
  }
  else {
    return NULL;
  }
}

/**
 * @return string
 */
function generate_password() {
  $s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  $s = str_shuffle($s);
  $count = mt_rand(8, 12);
  return substr($s, mt_rand(0, 62 - $count - 1), $count);
}

/**
 * @return bool
 */
function is_node_page() {
  return (substr($_SERVER['REQUEST_URI'], 0, 5) == '/node');
}

/**
 * @return bool
 */
function is_admin_page() {
  return (substr($_SERVER['REQUEST_URI'], 0, 6) == '/admin');
}

/**
 * @param string $name
 * @param null   $default
 * @return mixed
 */
function variable_get($name, $default = NULL) {
  return get_module_system()->variable_get($name, $default);
}

/**
 * @param string $name
 * @param mixed  $value
 */
function variable_set($name, $value) {
  get_module_system()->variable_set($name, $value);
}

/**
 * @param string $name
 */
function variable_del($name) {
  get_module_system()->variable_del($name);
}
