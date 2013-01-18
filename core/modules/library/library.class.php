<?php

namespace core\modules\library;


/**
 * Handles external scrips.
 */
class library {

  private $libraries = array();

  /**
   * Search for the version.
   *
   * @param string $name
   * @return string Returns the version.
   */
  private function get_version($name) {
    $version = '-';
    $info = $this->libraries[$name];
    if (isset($info['version_info']['file'])) {
      $f = fopen(LIBRARY_DIR . $name . '/' . $info['version_info']['file'], 'r');
      for ($i = 0; $i < $info['version_info']['num_lines']; $i++) {
        $line = fgets($f, $info['version_info']['num_chars']);
        if ($line === FALSE) {
          break;
        }
        if (preg_match($info['version_info']['pattern'], $line, $matches)) {
          $version = $matches[1];
          break;
        }
      }
      fclose($f);
    }

    return $version;
  }

  /**
   * <pre>
   * name: name of the library
   * file: the file to include when loaded
   * version: if not set then the function tries to find it using the info below
   *  version_file: the file to look at for the version
   *  version_pattern: the pattern to use to get the version
   *  version_num_lines: maximum lines to scan for version
   *  version_num_chars: maximum characters per line to scan for version
   *
   * Filled in by the class:
   * fname: full directory + filename of the file to be included (usefull for .php files)
   * fpath: full path + filename of the file to be included (usefull for .js files)
   * loaded: TRUE if loaded, else FALSE
   * </pre>
   * @param string $name
   * @param array $info
   * @return mixed
   */
  public function add($name, array $info) {
    if (isset($this->libraries[$name])) {
      return $this->libraries[$name];
    }

    $info += array(
      'weight' => 0,
      'home_url' => '-',
      'download_url' => '-',
    );

    $this->libraries[$name] = $info;
    $this->libraries[$name]['loaded'] = FALSE;

    if (!isset($info['version'])) {
      $this->libraries[$name]['version'] = $this->get_version($name);
    }

    return $this->libraries[$name];
  }

  /**
   * @param string $name
   * @param int $weight
   * @return bool|array
   */
  public function load($name, $weight = 0) {
    if (!isset($this->libraries[$name])) {
      return FALSE;
    }

    if (isset($this->libraries[$name]['loaded']) && $this->libraries[$name]['loaded']) {
      return TRUE;
    }

    $info = $this->libraries[$name];

    $weight = ($weight) ? $weight : $info['weight'];

    if (isset($info['file_dependency'])) {
      foreach ($info['file_dependency'] as $type => $value) {
        switch ($type) {
          case 'file':
            $b = file_exists(BASE_DIR . 'library/' . $name . '/' . $value);
            if (!$b) {
              return FALSE;
            }
            break;
        }
      }
    }

    // Load dependencies.
    if (isset($info['dependency'])) {
      foreach ($info['dependency'] as $dependency) {
        $b = $this->load($dependency);
        if (!$b) {
          return FALSE;
        }
      }
    }

    // Load css.
    if (isset($info['css'])) {
      $weight_offset = 0;
      foreach ($info['css'] as $css) {
        $fname = BASE_DIR . 'library/' . $name . '/' . $css;
        if (!file_exists($fname)) {
          watchdog_add('error', 'Library: cannot find ' . $fname);
          return FALSE;
        }
        $fpath = BASE_PATH . 'library/' . $name . '/' . $css;
        get_theme()->add_css($fpath, array('weight' => $weight + $weight_offset++));
      }
    }

    // Load js.
    if (isset($info['js'])) {
      $weight_offset = 0;
      foreach ($info['js'] as $js) {
        $fname = BASE_DIR . 'library/' . $name . '/' . $js;
        if (!file_exists($fname)) {
          watchdog_add('error', 'Library: cannot find ' . $fname);
          return FALSE;
        }
        $fpath = BASE_PATH . 'library/' . $name . '/' . $js;
        get_theme()->add_js($fpath, array('weight' => $weight + $weight_offset++));
      }
    }

    // Load php files.
    if (isset($info['php'])) {
      foreach ($info['php'] as $php) {
        $fname = BASE_DIR . 'library/' . $name . '/' . $php;
        if (!file_exists($fname)) {
          watchdog_add('error', 'Library: cannot find ' . $fname);
          return FALSE;
        }
        /** @noinspection PhpIncludeInspection */
        require_once $fname;
      }
    }

    $this->libraries[$name]['loaded'] = TRUE;

    return $this->libraries[$name];
  }

  /**
   * Get library information.
   *
   * @param string $name
   * @return bool|array Return a associative array with the library information or FALSE if the library doesn't exists.
   */
  public function get($name) {
    if (isset($this->libraries[$name])) {
      return $this->libraries[$name];
    } else {
      return FALSE;
    }
  }

  /**
   * Get the directory of a library.
   *
   * @param string $name
   * @return bool|string Returns the directory of a library or FALSE if the library doesn't exists.
   */
  public function get_dir($name) {
    $dir = BASE_DIR . 'library/' . $name;
    if (is_dir($dir)) {
      return $dir;
    } else {
      return FALSE;
    }
  }

  /**
   * Get the path of a library.
   *
   * @param string $name
   * @return bool|string Returns the path of a library or FALSE if the library doesn't exists.
   */
  public function get_path($name) {
    if (isset($this->libraries[$name])) {
      $path = BASE_PATH . 'library/' . $name . '/';
      return $path;
    } else {
      return FALSE;
    }
  }

  /* Hooks ********************************************************************/

  /**
   * Hook init();
   *
   * Register libraries.
   */
  public function init() {
    $libs = glob(LIBRARY_DIR . '*', GLOB_ONLYDIR);
    if ($libs) {
      foreach ($libs as $lib) {
        $fname = $lib . '/' . basename($lib) . '.lib.ini';
        if (file_exists($fname)) {
          $info = parse_ini_file($fname, TRUE);
          $this->add(basename($lib), $info);
        }
      }
    }
  }

  /**
   * Hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/libraries'] = array(
      'title' => 'Libraries',
      'controller' => 'library:libraries',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );

    return $menu;
  }

  /* Private routings *********************************************************/

  /**
   * @return string
   */
  public function libraries() {
    library_load('stupidtable');
    add_js('$(function(){$(".stupidtable").stupidtable()});', 'inline');

    $header = array(
      array('data' => 'Name', 'data-sort' => 'string'),
      'Version',
      'Home URL',
      'Download URL');

    $rows = array();
    foreach ($this->libraries as $name => $library) {
      $rows[] = array(
        $name,
        $library['version'],
        l($library['home_url'], $library['home_url']),
        l($library['download_url'], $library['download_url']),
      );
    }

    $ra = array(
      'template' => 'table',
      'vars' => array(
        'caption' => count($this->libraries) . ' libraries',
        'header'  => $header,
        'rows'    => $rows,
        'attributes' => array('class' => array('stupidtable')),
      ),
    );

    return get_theme()->theme_table($ra);
  }
}

