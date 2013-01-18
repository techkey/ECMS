<?php
/**
 * @file error.php
 */

$status_code = isset($_SERVER['REDIRECT_STATUS']) ? $_SERVER['REDIRECT_STATUS'] : 0;

switch ($status_code) {
  case 403:
    $status_header = '403 Forbidden';
    $status_message = 'Access forbidden!';
    break;

  case 404:
    $status_header = '404 Not Found';
    $status_message = 'The requested URL was not found on this server.';
    break;

  default:
    $status_header = '000';
    $status_message = '000';
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title><?php echo $status_code; ?></title>
  </head>
  <body>
    <h1><?php echo $status_header; ?></h1>
    <p><?php echo $status_message; ?></p>
  </body>
</html>