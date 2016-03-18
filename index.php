<?php
/**
 * @file
 * multisitemaker.php
 *
 * This file should be installed at the webroot of the root domain under which
 * you want to create multi-sites.
 */

/**
 * Constant definitions.
 */
// Define our INITIALISED constant.
define('MULTISITEMAKER_INITIALISED', TRUE);

/**
 * Start output buffering.
 */
ob_start();

/**
 * Load functions.
 */
require 'functions.php';

// The database connection name.
define('MULTISITEMAKER_MYSQL_SERVER', 'multisitemakermysqlserver');

/**
 * Load configuration.
 */
multisitemaker_load_configuration();

/**
 * Access check.
 */
multisitemaker_access_check();

/**
 * If we've got this far, we're configured and access has been granted.
 *
 * Let's check the database...
 */
multisitemaker_connect_db();

/**
 * Debug code.
 */
if (multisitemaker_debug()) {
  $output .= '<p>Success: A proper connection to MySQL was made! The MySQL server at ' . $conf['database']['hostname'] . ' was found.</p>';
  echo '<p>Host information: ' . mysqli_get_host_info(multisitemaker_db()) . '</p>';
}

/**
 * Decide what we're doing: are we showing the "create a new multisite" form,
 * or processing submitted form data?
 */
if (multisitemaker_debug()) {
  dpm($_SESSION, '$_SESSION');
}

switch ($_GET['q']) {
  case 'createmultisite':
    if (isset($_POST) && !empty($_POST)) {
      if (multisitemaker_validate_post()) {
        // Valid form data received.
        multisitemaker_process_new_multisite_form();
      }
      else {
        // Invalid form data received. Show error.
        echo '<h3>Error</h3>
      <p>Sorry, the information you sent appears to be invalid. <a href="/multisitemaker.php">Please click here to try again</a>.';

        multisitemaker_access_denied();
      }
    }
    else {
      multisitemaker_not_found();
    }
    break;

  case 'redirecttodrupal':
    multisitemaker_redirecttodrupal();
    break;

  default:
    echo multisitemaker_get_multisite_form();
    break;
}

/**
 *
 */
multisitemaker_exit();
