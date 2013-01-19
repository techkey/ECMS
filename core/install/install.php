<?php

include '../bootstrap.inc.php';

use core\modules\config\config;

/**  */
function p($str) {
  echo $str . '<br>';
}

/**  */
function install($module = FALSE) {
  $db_path = BASE_DIR . config::get_value('database.name', FALSE);
  if (!file_exists($db_path)) {
    exit('Cannot find db.');
  }

  $out  = '<table class="stupidtable"><thead><tr>';
  $out .= '<th data-sort="string">Module Name</th>';
  $out .= '<th data-sort="string">Info File</th>';
  $out .= '<th data-sort="string">Table Name</th>';
  $out .= '<th data-sort="string">Installed</th>';
  $out .= '<th>Actions</th>';
  $out .= '</tr></thead><tbody>';

  $modules = array();

  // Gather all core modules.
  $core_modules = glob(BASE_DIR . 'core/modules/*', GLOB_ONLYDIR);
  foreach ($core_modules as $core_module) {
    $name = basename($core_module);
    if ($name == 'config') {
      continue;
    }
    $modules[$name]['info'] = FALSE;

    if (file_exists($core_module . '/' . $name . '.ini')) {
      $modules[$name]['info'] = parse_ini_file($core_module . '/' . $name . '.ini');
    }
    $class = "\\core\\modules\\$name\\$name";
    $modules[$name]['class'] = new $class();
  }

  // Gather all user modules.
  $user_modules = glob(BASE_DIR . 'modules/*', GLOB_ONLYDIR);
  foreach ($user_modules as $user_module) {
    $name = basename($user_module);
    if ($name == 'config') {
      continue;
    }
    $modules[$name]['info'] = FALSE;

    if (file_exists($user_module . '/' . $name . '.ini')) {
      $modules[$name]['info'] = parse_ini_file($user_module . '/' . $name . '.ini');
    }
    $class = "\\modules\\$name\\$name";
    $modules[$name]['class'] = new $class();
  }

  foreach($modules as $name => $data) {
    if ($module == $name) {
      /** @noinspection PhpUndefinedMethodInspection */
      db_install_schema($data['class']->schema());
      if ($name == 'user') {
        $method = new ReflectionMethod($data['class'], 'add');
        $method->setAccessible(TRUE);
        $method->invoke($data['class'], 'admin', 'admin000', 'admin@localmail.com', 1, 'admin');
      }
    }

    $info = ($data['info'] !== FALSE) ? 'x' : '';
    $table_name = '';
    $installed = '';
    if (method_exists($data['class'], 'schema')) {
      /** @noinspection PhpUndefinedMethodInspection */
      $table_name = key($data['class']->schema());
      if (db_table_exists($table_name)) {
        $installed = 'x';
      }
    }
    $install_link = '';
    if ($table_name && !$installed) {
      $install_link = l('install', 'core/install/install.php?m=' . $name);
    }

    $out .= '<tr>';
    $out .= "<td>$name</td>";
    $out .= "<td>$info</td>";
    $out .= "<td>$table_name</td>";
    $out .= "<td>$installed</td>";
    $out .= "<td>$install_link</td>";
    $out .= '</tr>';
  }

  $out .= '</tbody></table>';

  return $out;
}

$module = isset($_GET['m']) ? $_GET['m'] : NULL;
$out = install($module);

?>
<!DOCTYPE html >
<html>
  <head>
    <meta charset = "UTF-8" />
    <title>Install ECMS</title>
    <link type="text/css" rel="stylesheet" href="<?php echo BASE_PATH; ?>library/stupidtable/css/table.css">
    <style type="text/css">
      body {font-family: sans-serif; font-size: 12px;}
    </style>
    <script type="text/javascript" src="<?php echo BASE_PATH; ?>library/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo BASE_PATH; ?>library/stupidtable/js/stupidtable.js"></script>
    <script type="text/javascript">
      <!--//--><![CDATA[//><!--
      $(function () {$('.stupidtable').stupidtable();});
      //--><!]]>
    </script>
  </head>
  <body>
    <div>
      <?php echo $out; ?>
    </div>
  </body>
</html>