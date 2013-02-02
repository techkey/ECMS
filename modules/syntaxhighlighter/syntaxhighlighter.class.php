<?php
/**
 * @file syntaxhighlighter.class.php
 */

namespace modules\syntaxhighlighter;

/**
 * Class syntaxhighlighter
 *
 * @package modules\syntaxhighlighter
 */
class syntaxhighlighter {

  /**
   * Hook page_alter().
   *
   * @api page_alter()
   *
   * @param array $page
   */
  public function page_alter(array $page) {
    if (!isset($page['content'])) {
      return;
    }

    // Check if syntaxhighlighter is used.
    $pos = strpos($page['content'], 'class="brush:');
    if ($pos !== FALSE) {
      library_load('syntaxhighlighter');
      // todo Load the needed syntax highlight brushes automaticly
      add_js(library_get_path('syntaxhighlighter') . 'scripts/shBrushPhp.js');
      add_js(library_get_path('syntaxhighlighter') . 'scripts/shBrushJScript.js');
      // Check if mixed html is used.
      if (strpos($page['content'], 'html-script:', $pos)) {
        add_js(library_get_path('syntaxhighlighter') . 'scripts/shBrushXml.js');
      }
      add_js('SyntaxHighlighter.all()', array('type' => 'inline'));
    }
  }

}