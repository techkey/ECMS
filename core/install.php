<?php
/**
 * @file install.php
 */

include 'bootstrap.inc.php';

use core\modules\config\config;

$db_path = BASE_DIR . config::get_value('database.name', FALSE);
if ($db_path) {
  $db_path = dirname($db_path);
}
$db_path_exists = file_exists($db_path);

$system_result = FALSE;
$system_module = get_module_system();
$user_result = FALSE;
$user_module = get_module_user();

if (isset($_POST['install'])) {
  $schema = $system_module->schema();
  $table = key($schema);
  if (!db_table_exists($table)) {
    $system_result = db_install_schema($schema);
  } else {
    $system_result = TRUE;
  }

  $schema = $user_module->schema();
  $table = key($schema);
  db_query('DROP TABLE IF EXISTS ' . $table);
  $user_result = db_install_schema($schema);
  if ($user_result) {
    $method = new ReflectionMethod($user_module, 'add');
    $method->setAccessible(TRUE);
    $method->invoke($user_module, 'admin', 'admin000', 'admin@localmail.com', 1, 'admin');
  }
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Install</title>
  </head>
  <body>
    <?php if (!$db_path_exists) { ?>
      <p>Path to the database doesn't exists. Please make path <?php echo $db_path; ?></p>
    <?php } elseif (!$system_module) { ?>
      <p>Cannot find the system module, please copy the system module to /core/modules.</p>
    <?php } elseif (!$user_module) { ?>
      <p>Cannot find the user module, please copy the user module to /core/modules.</p>
    <?php } else { ?>
      <?php if (!isset($_POST['install'])) { ?>
        <form action="" method="post">
          <p>This will install the system module if not installed and (re)install the user module. The user table will contain only a admin user. Are you sure?</p>
          <p><input type="submit" name="install" value="Install"></p>
        </form>
      <?php } else { ?>
        <?php if ($system_result && $user_result) { ?>
          <p>Modules are installed. Admin login is admin/admin000</p>
          <p>Goto the <a href="<?php echo BASE_PATH; ?>">home</a> page.</p>
          <p>Goto the <a href="<?php echo BASE_PATH . 'user/login'; ?>">login</a> page.</p>
        <?php } else { ?>
          <?php if (!$system_result) { ?>
            <p>Installation of the system module failed.</p>
          <?php } ?>
          <?php if (!$user_result) { ?>
            <p>Installation of the user module failed.</p>
          <?php } ?>
        <?php } ?>
      <?php } ?>
    <?php } ?>
  </body>
</html>
