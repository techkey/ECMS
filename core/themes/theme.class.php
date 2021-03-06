<?php
// theme.class.php

namespace core\themes;

/**
 * @author Cornelis Brouwers <cornelis_brouwers@hotmail.com>
 */
class theme
{
  private $css = array();
  private $inline_css = array();
  private $js = array();
  private $inline_js = array();
  private $settings = array();

  /**
   * @return string
   */
  public function get_path() {
//    return BASE_PATH . str_replace('\\', '/', dirname(get_called_class()));
    return BASE_PATH . str_replace('\\', '/', dirname(str_replace('\\', '/', get_called_class()))) . '/';
  }

  /**
   * @return string
   */
  public function get_dir() {
    return __DIR__ . '/';
  }

  /**
   * Add css to the page.
   *
   * @param string       $data The file path or the inline js string.
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
  public function add_css($data, $options = NULL) {
    // Set default values.
    if (is_array($options)) {
      $options += array(
        'weight' => 0,
        'type' => 'file',
      );
    } else {
      $options = array(
        'weight' => 0,
        'type' => ($options) ? $options : 'file',
      );
    }

    // Check if the css must be included only on listed pages.
    if (isset($options['pages'])) {
      $request_path = request_path();
      $found = FALSE;
      foreach ($options['pages'] as $page) {
        $pattern = str_replace('#', '\#', $page);
        $pattern = '#^' . str_replace('*', '(.*)', $pattern) . '#';
        if (preg_match($pattern, $request_path)) {
          $found = TRUE;
          break;
        }
      }
      if (!$found) {
        return;
      }
    }

    switch ($options['type']) {
      case 'file':
        $options['weight'] = $options['weight'] + (count($this->css) / 1000);
        $this->css[] = array($options['weight'] . '' => $data);
        break;
      case 'inline':
        $this->inline_css[] = array($options['weight'] => $data);
        break;
    }
  }

  /**
   * Add js to the page.
   *
   * @param string|array $data The file path, the inline js string or a array of settings.
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
  public function add_js($data, $options = NULL) {
    if (is_array($options)) {
      $options += array(
        'weight' => 0,
        'type' => 'file',
      );
    } else {
      $options = array(
        'weight' => 0,
        'type' => ($options) ? $options : 'file',
      );
    }

    switch ($options['type']) {
      case 'file':
        $options['weight'] = $options['weight'] + (count($this->js) / 1000);
        $this->js[] = array($options['weight'] . '' => $data);
        break;
      case 'inline':
        $this->inline_js[] = array($options['weight'] => $data);
        break;
      case 'setting':
        $this->settings += $data;
        break;
    }
  }

  /**
   * Sort all stylesheets on weight and return them.
   *
   * @return array
   */
  public function get_css() {
    usort($this->css, function($a, $b) {
      if ($a == $b) {
        return 0;
      }
      return (key($a) < key($b)) ? -1 : 1;
    });
    $css = array();
    foreach ($this->css as $script) {
      list(, $css[]) = each($script);
    }
    return $css;
  }

  /**
   * Sort all inline stylesheets on weight and return them.
   *
   * @return string
   */
  public function get_inline_css() {
    usort($this->inline_css, function($a, $b) {
      if ($a == $b) {
        return 0;
      }
      return (key($a) < key($b)) ? -1 : 1;
    });
    $inline_css = array();
    foreach ($this->inline_css as $script) {
      list(, $inline_css[]) = each($script);
    }
    $inline_css = implode("\n", $inline_css);
    return $inline_css;
  }

  /**
   * Sort all javascript on weight and return them.
   *
   * @return array
   */
  public function get_js() {
    usort($this->js, function($a, $b) {
      if ($a == $b) {
        return 0;
      }
      return (key($a) < key($b)) ? -1 : 1;
    });
    $js = array();
    foreach ($this->js as $script) {
      list(, $js[]) = each($script);
    }
    return $js;
  }

  /**
   * Sort all inline javascript on weight and return them.
   *
   * @return string
   */
  public function get_inline_js() {
    $inline_js = array();
    // Add settings.
    if ($this->settings) {
      $settings = json_encode(array('settings' => $this->settings));
      $settings = 'var cms = ' . $settings . ';';
      $inline_js[] = $settings;
    }

    usort($this->inline_js, function($a, $b) {
      if ($a == $b) {
        return 0;
      }
      return (key($a) < key($b)) ? -1 : 1;
    });
    foreach ($this->inline_js as $script) {
      list(, $inline_js[]) = each($script);
    }

    $inline_js = implode("\n", $inline_js);
    return $inline_js;
  }

  /**
   * @return array
   */
  public function get_settings() {
    return $this->settings;
  }

  /**
   * @param array $ra
   * @return string
   */
  public function theme_table(array $ra) {
    // Preprocess.
    $ra['vars'] += array('attributes' => array());
    if (!$ra['vars']['attributes']) {
      $ra['vars']['attributes']['class'][] = 'list-table';
    };
    $ra['vars']['attributes'] = build_attribute_string($ra['vars']['attributes']);

    $header = array();
    foreach ($ra['vars']['header'] as $head) {
      if (is_array($head)) {
        $data = $head['data'];
        unset($head['data']);
        $header[] = array('data' => $data, 'attributes' => build_attribute_string($head));
      } else {
        $header[] = array('data' => $head, 'attributes' => '');
      }
    }
    $ra['vars']['header'] = $header;

    $rows = array();
    foreach ($ra['vars']['rows'] as $row) {
      $row_attributes = '';
      $cells = array();
      if (isset($row['data']) && (is_array($row['data']))) {
        $data = $row['data'];
        unset($row['data']);
        $row_attributes = build_attribute_string($row);
        $row = $data;
      }
      foreach ($row as $cell) {
        if (is_array($cell)) {
          $data = $cell['data'];
          unset($cell['data']);
          $cells[] = array('data' => $data, 'attributes' => build_attribute_string($cell));
        } else {
          $cells[] = array('data' => $cell, 'attributes' => '');
        }
      }
      $rows[] = array(
        'attributes' => $row_attributes,
        'data' => $cells,
      );
    }
    $ra['vars']['rows'] = $rows;

    return get_theme()->fetch($ra['template'], $ra['vars']);
  }

  /**
   * @return string
   */
  public function theme_pager() {
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    return get_theme()->fetch('pager', array('page' => $page));
  }

  /**
   * @internal
   *
   * @param array $data
   * @return string
   */
  private function _build_tree(array $data) {
    $out = '<ul>';
    foreach ($data as $path => $item) {
      $link = $item['#link'];
      $count = count($item) - 1;

      $out .= ($count) ? '<li class="has-subs">' : '<li>';
      $out .= l($link['title'], $path);

      if ($count) {
        unset($item['#link']);
        $out .= $this->_build_tree($item);
      }
      $out .= '</li>';
    }
    $out .= '</ul>';

    return $out;
  }

  /**
   * Build HTML of a menu.
   *
   * <pre>
   * The render array has the following keys:
   *  'attributes' => array(
   *    'class' => array('sf-menu'),
   *    ...
   *  ),
   *  'menu' => array(
   *    ...
   *  )
   * </pre>
   *
   * @see core\modules\menu\menu::$menus
   *
   * @param array $ra The render array to build.
   * @return string
   */
  public function theme_menu(array $ra) {
    if (isset($ra['attributes'])) {
      $out = '<ul ' . build_attribute_string($ra['attributes']) . '>';
    } else {
      $out = '<ul>';
    }
    foreach ($ra['menu'] as $path => $data) {
      $link = $data['#link'];
      $count = count($data) - 1;

      $out .= ($count) ? '<li class="has-subs">' : '<li>';
      $out .= l($link['title'], $path);

      if ($count) {
        unset($data['#link']);
        $out .= $this->_build_tree($data);
      }
      $out .= '</li>';
    }
    $out .= '</ul>';

    return $out;
  }
}

