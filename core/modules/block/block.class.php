<?php
/**
 * @file block.class.php
 */

namespace core\modules\block;

/**
 *
 */
class block {
  /**
   * <pre>
   * [region]
   *   [0]
   *     [name]
   *     [module]
   *     [content]
   *     [weight]
   *   ...
   * </pre>
   * @var array
   */
  private $blocks = array();

  /**
   * Define constants.
   */
  public function __construct() {
    /**
     * Shows this block on every page except the listed pages.
     */
    define('BLOCK_VISIBILITY_NOTLISTED', 0);

    /**
     * Shows this block on only the listed pages.
     */
    define('BLOCK_VISIBILITY_LISTED', 1);

    /**
     * Shows this block if the associated PHP code returns TRUE.
     */
    define('BLOCK_VISIBILITY_PHP', 2);
  }

  /**
   * Block structure:
   *  [name]
   *    [title]    => string
   *    [content]  => string|array If an array then use the template to render.
   *    [template] => string The template to use for rendering of the content.
   *    [vars]     => array The variables to use for the template.
   *    [region]   => string The region to put the block in.
   *    [weight]   => int
   *    [roles]    => array The roles that can see the block.
   */
  private function get_all_blocks() {
    // Collect all blocks.
    $results = invoke('block');

    $a = array();
    foreach ($results as $module => $blocks) {
      foreach ($blocks as $name => $block) {
        $block += array(
          'content'    => '',
          'weight'     => 0,
          'template'   => '',
          'vars'       => array(),
          'title'      => '',
          'roles'      => array(),
          'visibility' => BLOCK_VISIBILITY_NOTLISTED,
          'pages'      => array(),
        );

        if ($block['roles'] && !in_array(get_user()->role, $block['roles'])) {
          continue;
        }

        $request_path = request_path();
        if ($block['visibility'] == BLOCK_VISIBILITY_NOTLISTED) {
          foreach ($block['pages'] as $page) {
            $pattern = str_replace('#', '\#', $page);
            $pattern = '#' . str_replace('*', '(.*)', $pattern) . '#';
            if (preg_match($pattern, $request_path)) {
              continue 2;
            }
          }
        }

        if (!isset($a[$block['region']])) {
          $a[$block['region']] = array();
        };
        $b = array(
          'name'     => $name,
          'module'   => $module,
          'title'    => $block['title'],
          'content'  => $block['content'],
          'weight'   => $block['weight'] + (count($a[$block['region']]) / 1000),
          'template' => $block['template'],
          'vars'     => $block['vars'],
        );
        $a[$block['region']][] = $b;
      }
    }

    foreach ($a as &$blocks) {
      usort($blocks, function ($a, $b) {
        if ($a['weight'] == $b['weight']) {
            return 0;
        }
        return ($a['weight'] < $b['weight']) ? -1 : 1;
      });
    }
    unset($blocks);

    $this->blocks = $a;
  }


/* Hooks **********************************************************************/

  /**
   * Hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/blocks'] = array(
      'title'            => 'Blocks',
      'controller'       => 'block::blocks',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );

    return $menu;
  }

  /**
   * Hook page_build().
   */
  public function page_build(array &$page) {
    $this->get_all_blocks();

    foreach ($this->blocks as $region => $blocks) {
      foreach ($blocks as $block) {
        if ($block['content'] == '') {
          $content = get_theme()->fetch($block['template'], $block['vars']);
        } else {
          $content = $block['content'];
        }
        $context = array(
          'title'      => $block['title'],
          'content'    => $content,
          'attributes' => build_attribute_string(array(
            'id'    => 'block-' . $block['module'] . '-' . $block['name'],
            'class' => 'block',
          )),
        );
//        $vars[$region] .= get_theme()->fetch('block', $context);
        $page[$region][] = array(
          'name' => $block['name'],
          'template' => 'block',
          'vars' => $context,
        );
      }
    }
  }

  /**
   * Collect and render all blocks and merge them with the current render array..
   *
   * @param array $vars The current render array.
   */
  public function render(array &$vars) {
    $this->get_all_blocks();

    foreach ($this->blocks as $region => $blocks) {
      if (!isset($vars[$region])) {
        $vars[$region] = '';
      }
      foreach ($blocks as $block) {
        if ($block['content'] == '') {
          $content = get_theme()->fetch($block['template'], $block['vars']);
        } else {
          $content = $block['content'];
        }
        $context = array(
          'title'      => $block['title'],
          'content'    => $content,
          'attributes' => build_attribute_string(array(
            'id'    => 'block-' . $block['module'] . '-' . $block['name'],
            'class' => 'block',
          )),
        );
        $vars[$region] .= get_theme()->fetch('block', $context);
      }
    }
  }


/* Private route controllers **************************************************/

  /**
   * List all blocks.
   *
   * @return string
   */
  public function blocks() {
    library_load('stupidtable');
    add_js('$(function(){$(".stupidtable").stupidtable()});', 'inline');

    if (!$this->blocks) {
      $this->get_all_blocks();
    }

    $header = array(
      array('data' => 'Name',     'data-sort' => 'string'),
      array('data' => 'Module',   'data-sort' => 'string'),
      array('data' => 'Title',    'data-sort' => 'string'),
      array('data' => 'Region',   'data-sort' => 'string'),
      array('data' => 'Weight',   'data-sort' => 'int'),
      array('data' => 'Template', 'data-sort' => 'string'),
    );

    $count = 0;
    $rows = array();
    foreach ($this->blocks as $region => $blocks) {
      foreach ($blocks as $block) {
        $count++;
        $rows[] = array(
          $block['name'],
          $block['module'],
          $block['title'],
          $region,
          $block['weight'],
          $block['template'],
        );
      }
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'caption'    => $count . ' blocks',
//        'attributes' => array('class' => array('list-table blocks', 'stupidtable')),
        'attributes' => array('class' => array('stupidtable', 'sticky')),
        'header'     => $header,
        'rows'       => $rows,
      ),
    );

    return get_theme()->theme_table($ra);
  }

}

