<?php

/**
 * @file
 * functions.php
 *
 * Functions for the multisite maker.
 */

if (!defined('MULTISITEMAKER_INITIALISED')) {
  multisitemaker_access_denied();
}

/**
 * Constant definitions.
 */

/**
 * Time of the current request in seconds elapsed since the Unix Epoch.
 *
 * This differs from $_SERVER['REQUEST_TIME'], which is stored as a float
 * since PHP 5.4.0. Float timestamps confuse most PHP functions
 * (including date_create()).
 *
 * @see http://php.net/manual/reserved.variables.server.php
 * @see http://php.net/manual/function.time.php
 */
define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);

/**
 * Load the configuration.
 */
function multisitemaker_load_configuration() {
  if (is_readable('configuration.php')) {
    include 'configuration.php';
  }
  else {
    multisitemaker_exit('configuration.php not found. Have you copied the configuration-sample.php file and set the configuration values?');
  }

  if (!defined('MULTISITEMAKER_CONFIGURED')) {
    multisitemaker_exit('configuration.php didn\'t finish loading successfully. Have you copied the configuration-sample.php file and set the configuration values?');
  }
}

/**
 * Connect to the MySQL server and return a database link object.
 *
 * @param string $databasename
 *
 * @return \mysqli
 */
function multisitemaker_connect_db($databasename = '') {
  global $conf;

  if ($database_connection = mysqli_connect($conf['database']['hostname'], $conf['database']['username'], $conf['database']['password'], $databasename, $conf['database']['port'])) {
    multisitemaker_db($database_connection);
    return TRUE;
  }
  else {
    $error_text = 'Unable to connect to MySQL ' . (empty($databasename) ? 'server' : 'database "' . $databasename . '"') . ' at ' . $conf['database']['hostname'] . ', port ' . $conf['database']['port'] . ' with username "' . $conf['database']['username'] . '". Password supplied: ' . (!empty($conf['database']['password']) ? 'yes' : 'no');
    multisitemaker_db_error($error_text, TRUE, 'connect');
  }
}

/**
 * Display an error and optionally die when a database error occurs.
 *
 * @param string $message
 */
function multisitemaker_db_error($message = NULL, $die = FALSE, $type = 'normal') {
  if ($type == 'connect') {
    $message .= '<p>Error ' . mysqli_connect_errno() . ': ' . mysqli_connect_error() . '</p>';
  }
  else {
    $message .= '<p>Error ' . mysqli_errno(multisitemaker_db()) . ': ' . mysqli_error(multisitemaker_db()) . '</p>';
  }

  // What error class should we use?
  $class = ($die ? 'danger' : 'warning');

  // Write a message to the screen.
  multisitemaker_message($message, $class);

  // Are we exiting here?
  if ($die) {
    multisitemaker_exit();
  }
}

/**
 * Writes a status message to the screen. Set $type to one of 'success', 'info',
 * 'warning' or 'danger'.
 *
 * @param        $text
 * @param string $type
 */
function multisitemaker_message($text, $type = 'info') {
  echo '<div class="alert alert-' . $type . '">' . $text . '</div>';
}

/**
 * Creates a database and database user with full privileges on the new
 * database.
 *
 * @param $options             array An array of options with the following keys:
 *                             - new_database
 *                             - new_username
 *                             - new_password
 *                             We assume this data is unsanitised, so it will be
 *                             passed through mysqli_real_escape_string() and the
 *                             database name and username will be trimmed and
 *                             lowercased.
 *
 *
 * @return bool
 */
function multisitemaker_create_db_and_user($options = array()) {
  global $conf;

//  // Process the db and usernames.
//  foreach ($options as $key => &$value) {
//    // If this is a database name, trim to 64 chars, lowercase and replace
//    // special chars with underscores.
//    if ($key == 'new_database') {
//      $value = multisitemaker_make_alphanumeric($value, 64);
//    }
//
//    // If this is a username, trim to 32 chars, lowercase and replace special
//    // chars with underscores.
//    if ($key == 'new_username') {
//      $value = multisitemaker_make_alphanumeric($value, 16);
//    }
//  }

  // E.g. CREATE USER '[username]'@'localhost' IDENTIFIED BY '[password]';
  $query = "CREATE USER '%s'@'%s' IDENTIFIED BY '%s';";
  multisitemaker_db_query($query, array(
    $options['new_username'],
    $conf['database']['hostname'],
    $options['new_password'],
  ));

  // E.g. CREATE DATABASE [username];
  $query = "CREATE DATABASE %s;";
  multisitemaker_db_query($query, array($options['new_database']));

  // E.g. GRANT USAGE ON *.* TO '[username]'@'localhost' IDENTIFIED BY '[password]';
  $query = "GRANT USAGE ON *.* TO '%s'@'%s' IDENTIFIED BY '%s';";
  multisitemaker_db_query($query, array(
    $options['new_username'],
    $conf['database']['hostname'],
    $options['new_password'],
  ));

  // E.g. GRANT ALL PRIVILEGES ON [username].* TO '[username]'@localhost;
  $query = "GRANT ALL PRIVILEGES ON %s.* TO '%s'@%s;";
  multisitemaker_db_query($query, array(
    $options['new_database'],
    $options['new_username'],
    $conf['database']['hostname'],
  ));

  // E.g. FLUSH PRIVILEGES;
  $query = "FLUSH PRIVILEGES;";
  multisitemaker_db_query($query, array());

  // Set a flag to indicate it's all been successful.
  $options['result'] = TRUE;

  return $options;
}

/**
 * Remove all non-alphanumerics from a string, optionally replacing them with
 * a placeholder character such as an underscore.
 *
 * @param string $string          The string to sanitise.
 * @param int    $maximum_length  The maximum length of the string.
 * @param string $allow_also      Any additional characters to allow through the
 *                                regex.
 * @param string $replace_with    What to replace removed characters with;
 *                                defaults to nothing.
 *
 * @return string
 */
function multisitemaker_make_alphanumeric($string, $maximum_length = 0, $allow_also = '', $replace_with = '') {
  $string = check_plain(strtolower(preg_replace('/[^a-zA-Z0-9' . $allow_also . ']+/', $replace_with, $string)));

  if ($maximum_length > 0) {
    $string = substr($string, 0, $maximum_length);
  }

  return $string;
}

/**
 * Returns TRUE if $conf['debug'] is set and true.
 *
 * @return bool
 */
function multisitemaker_debug() {
  global $conf;

  return isset($conf['debug']) && $conf['debug'];
}

/**
 * Run a database query and return the result.
 *
 * @param            $query_string
 * @param            $query_arguments
 * @param bool|FALSE $debug
 *
 * @return bool|\mysqli_result
 */
function multisitemaker_db_query($query_string, $query_arguments = array()) {
  // Sanitise the options.
  foreach ($query_arguments as &$value) {
    $value = mysqli_real_escape_string(multisitemaker_db(), $value);
  }

  // Unshift the query string onto the query arguments.
  array_unshift($query_arguments, $query_string);

  // Create the query string.
  $query = call_user_func_array('sprintf', $query_arguments);

  // Debug? Print the query.
  if (multisitemaker_debug()) {
    dpm($query_arguments, '$query_arguments');
  }

  // Run the query.
  if (!($mysqli_result = mysqli_query(multisitemaker_db(), $query))) {
    multisitemaker_db_error(NULL, TRUE);

    if (multisitemaker_debug()) {
      dpm($mysqli_result, '$mysqli_result');
    }
  }

  return $mysqli_result;
}

/**
 * Check that the user has access to this script.
 */
function multisitemaker_access_check($return_result = FALSE) {
  global $conf;

  // Set an access flag. We default to "allowed" until/if a test fails, when we
  // return FALSE.
  $access = TRUE;

  // Check IP addresses.
  if (!in_array($_SERVER['REMOTE_ADDR'], $conf['security']['whitelisted_ips'])) {
    $access = FALSE;
  }

  // If the user is not allowed, and we aren't returning a boolean, send a 403
  // and die.
  if (!$return_result && !$access) {
    multisitemaker_access_denied();
  }

  return $access;
}

/**
 * Return a 403 Access Denied response.
 */
function multisitemaker_access_denied() {
  header('HTTP/1.0 403 Forbidden');
  multisitemaker_exit('<h1>403 Access Denied</h1>');
}

/**
 * Return a 404 Not Found response.
 */
function multisitemaker_not_found() {
  header('HTTP/1.0 404 Not Found');
  multisitemaker_exit('<h1>404 Not Found</h1>');
}

/**
 * Dump out a variable.
 *
 * @param      $variable
 * @param null $message
 */
function dpm($variable, $message = NULL) {
  echo '<div class="well"><pre>' . (!is_null($message) ? $message . ': ' : '') . print_r($variable, TRUE) . '</pre></div>';
}

/**
 * Creates the hidden fields which need to be added to a form for it to
 * validate on submission.
 *
 * @param $form_id
 *
 * @return string
 */
function multisitemaker_get_form_validation_fields($form_id) {
  $hidden_fields = array();

  $form_id = check_plain($form_id);
  $form_build_id = md5($form_id . ':' . $_REQUEST['REMOTE_ADDR'] . ':' . REQUEST_TIME);

  $hidden_fields['multisitemaker_form_post'] = 'multisitemaker_form_post';
  $hidden_fields['form_id'] = $form_id;
  $hidden_fields['form_build_id'] = $form_build_id;

  global $_SESSION;

  if (!is_array($_SESSION['forms'])) {
    $_SESSION['forms'] = array();
  }

  $_SESSION['forms'][$form_id] = $form_build_id;

  $output = '';
  foreach ($hidden_fields as $field_name => $field_value) {
    $output .= '<input type="hidden" name="' . $field_name . '" value="' . $field_value . '" /> ';
  }

  return $output;
}

/**
 * Checks POST data to check that it is a valid request.
 *
 * All forms should have a
 *
 * @return bool
 */
function multisitemaker_validate_post() {
  global $_SESSION;

  if (multisitemaker_debug()) {
    dpm($_SESSION['forms'], '$_SESSION[forms]');
    dpm($_POST, '$_POST');
  }

  // Assume it's valid until proven otherwise.
  $valid = TRUE;

  // Is there a 'form_build_id' POST variable?
  if (isset($_POST['form_id'], $_POST['form_build_id'])) {
    // Check $_SESSION for a form_build_id against the form_id array key.
    if (!isset($_SESSION['forms'])) {
      $valid = FALSE;
    }
    else {
      if (array_key_exists($_POST['form_id'], $_SESSION['forms']) &&
        ($_SESSION['forms'][$_POST['form_id']] == $_POST['form_build_id'])
      ) {
        // Valid - do nothing.
      }
      else {
        // Not found, or didn't match.
        $valid = FALSE;
      }
    }
  }

  // Return the result.
  return $valid;
}

/**
 * Encodes special characters in a plain-text string for display as HTML.
 *
 * Also validates strings as UTF-8 to prevent cross site scripting attacks on
 * Internet Explorer 6.
 *
 * @param string $text
 *   The text to be checked or processed.
 *
 * @return string
 *   An HTML safe version of $text. If $text is not valid UTF-8, an empty string
 *   is returned and, on PHP < 5.4, a warning may be issued depending on server
 *   configuration (see @link https://bugs.php.net/bug.php?id=47494 @endlink).
 *
 * @see     drupal_validate_utf8()
 * @ingroup sanitization
 */
function check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Gets an HTML form to create a new multisite.
 *
 * @return string
 */
function multisitemaker_get_multisite_form() {
  // Set the form ID so we can get the form_build_id later.
  $form_id = 'new_multisite_form';

  $output = '<h3>What\'s this?</h3>
    <p>This page allows you to create your own Drupal website using the Greyhead-flavoured Drupal 7 build.</p>
    <p>Your site will have a URL like <code><em>monkey</em>' . multisitemaker_get_root_domain_name() . '</code></p>
    <p>You can choose whatever you like for the part before the first dot, as long as it\'s made up of lower-case letters, numbers, and hyphens; if you enter other characters, we will remove them or change them to underscored.</p>
    <p>Please bear in mind that this is an experimental service. It may be slow, or even completely offline sometimes, and is only provided so you can try out building a Drupal website.</p>

    <h3>Important things</h3>
    <ol>
      <li>Remember the URL of your site! Otherwise you won\'t be able to get back to it, will you?</li>
      <li>When asked to provide an e-mail address, make sure you use your real e-mail address - otherwise, if you forget your password, you won\'t be able to recover it and you\'ll lose access to your site.</li>
      <li>Use a good password that you won\'t forget - <em>sevenhairygorillas</em> is more secure than <em>m0nk3y!1!</em>, and more memorable, too.</li>
      <li>Lastly, this service is provided as-is and comes with no guarantees. You should back-up your database using Backup/Migrate (under Admin - Config - System - Backup Migrate) on a regular basis.</li>
    </ol>

    <h3>Problems?</h3>
    <p>Please send an e-mail to <a href="mailto:alex@greyhead.co.uk?subject=Drupal Multisite Maker">Alex at Greyhead dot co dot yookay</a> if you have any questions, problems, requests, complaints, or monkeys.</p>

    <hr />

  ';

  // Create an array which we can implode into an HTML form.
  $form_elements = array();

  $form_elements[] = '<div class="bd-example">';
  $form_elements[] = '<form id="' . $form_id . '" action="/multisitemaker/?q=createmultisite" method="post" class="">';
  $form_elements[] = '<div class="row">';

  $element_id = $element_name = 'subdomain_name';
  $element_label = 'Subdomain name';
  $form_elements[] = '<div class="form-group form-inline clearfix"><div class="col-sm-9"><label for="' . $element_id . '">
    ' . $element_label . ':</label> <input class="form-control" type="text"
    maxlength="20" name="' . $element_id . '" id="' . $element_id . '" value="' .
    multisitemaker_form_default_value($element_id) . '" /><code>' .
    multisitemaker_get_root_domain_name() . '</code></div>';

  $form_elements[] = '<div class="col-sm-3 text-right"><button class="btn btn-md btn-primary" type="submit" id="submit" name="op">Create</button></div></div>';

  // Check which install profile to use. If more than one, show options.
  $install_profiles_to_use = array();

  // Check for Four's Features.
  if (is_dir('../features/four_communications_base_modules')) {
    $install_profiles_to_use['fourprofile'] = 'Four Communications';
  }

  // Check for Greyhead's Features.
  if (is_dir('../features/common_base_modules')) {
    $install_profiles_to_use['greyheadprofile'] = 'Greyhead';
  }

  // Assemble the HTML for the profiles.
  $install_profiles_elements = '';
  if (count($install_profiles_to_use) == 1) {
    // Exactly one profile available. Create a hidden field.
    reset($install_profiles_to_use);
    $install_profile_id = key($install_profiles_to_use);
    $install_profiles_elements .= '<input type="hidden" name="profile" value="' . $install_profile_id . '" />';
  }
  elseif (count($install_profiles_to_use) > 1) {
    $install_profiles_elements .= '
      <div class="col-sm-12 advanced">
        <div class="form-element">
          <label for="profile">
            Which install profile should we use? Select "Greyhead" if this Drupal
            build contains the alexharries version of the drupal7_sites_common
            repository; choose "Four" if you have the
            fourcommunications/drupal7_sites_common repository.
          </label>';

    foreach ($install_profiles_to_use as $install_profile_id => $install_profile_name) {
      $install_profiles_elements .= '<input type="radio" name="profile" value="' . $install_profile_id . '" id="profile-' . $install_profile_id . '" checked /> <label for="profile-' . $install_profile_id . '">' . $install_profile_name . '</label>';
    }

    $install_profiles_elements .= '
        </div>
      </div>';
  }
  else {
    multisitemaker_message('<h3>Uh-oh</h3>
      <p>I couldn\'t find either a Greyhead or Four profile directory, so the multisitemaker can\'t continue. Sad face.</p>', 'danger');

    multisitemaker_exit();
  }

  // Add in advanced options.
  $form_elements[] = '
    <div class="form-group clearfix">
      <div class="col-sm-12 advanced">
        <div class="form-element">
          <label for="multisitesymlink">Advanced: include modules, themes and
            libraries from a multisite directory:
          </label>

          <input class="form-control" type="text" maxlength="100" id="multisitesymlink"
          name="multisitesymlink" value="' .
        multisitemaker_form_default_value('multisitesymlink') . '" />
        </div>
      </div>

      <div class="col-sm-12 advanced">
        <div class="form-element">
          <label for="settingssiteurls">Advanced: also serve this site on the
            following URLs:
          </label>

          <input class="form-control" type="text" maxlength="255" id="settingssiteurls"
          name="settingssiteurls" value="' .
        multisitemaker_form_default_value('settingssiteurls') . '" />

          <div class="description">
            <small>
              <p>
                Enter domain names without "http://", separated by commas,
                for example "www.example.com,www.monkey.co.uk", etc.
              </p>
              <p>
                You can use this, for example, if you want to point
                www.myawesomewebsite.com to this server. You cannot use domain
                names ending in <em>' . multisitemaker_get_root_domain_name() . '</em>
              </p>

              <p>
                You should check with your line manager before directing
                external web traffic to this server.
              </p>
            </small>
          </div>
        </div>
      </div>

    ' . $install_profiles_elements . '
    </dvi>';

  // Add in the form protection fields.
  $form_elements[] = multisitemaker_get_form_validation_fields($form_id);

  $form_elements[] = '</div>';
  $form_elements[] = '</form>';
  $form_elements[] = '</div>';

  $output .= implode("\r\n", $form_elements);

  return $output;
}

/**
 * Process the new multisite form data.
 *
 * This function:
 *
 * - Sanitises the subdomain name by removing all non-alphanumerics and
 *    converting to lower case.
 *
 * - Creates a database name and username by taking the sanitised subdomain
 *    name and appending multisitemaker_get_root_domain_name().
 *
 * - Creates a database password, which is the first 20 characters of the MD5
 *    of the database name, request IP, and request time.
 *
 * - Checks that a directory doesn't already exist in sites/ with the
 *    subdomain's name.
 *
 * - Creates the subdomain's directory in /sites-projects.
 *
 * - Creates the database, user and password.
 *
 * - Redirects the user back to this script but on the subdomain's URL, where
 *    we then set the database details in $_SESSION, and redirect to
 *    install.php with the Greyhead install profile pre-selected (because we
 *    need that profile to pre-populate the database details from $_SESSION).
 */
function multisitemaker_process_new_multisite_form() {
  global $conf;

  $subdomain_name = multisitemaker_make_alphanumeric($_POST['subdomain_name'], 0, '-');

  if (empty($subdomain_name)) {
    multisitemaker_exit('The subdomain name cannot be empty.');
  }

  $domain_name = $subdomain_name . multisitemaker_get_root_domain_name();
  $domain_name_sanitised = multisitemaker_make_alphanumeric($domain_name);
  $new_database = $new_username = multisitemaker_make_alphanumeric($conf['database']['name_prefix'] . $domain_name_sanitised, 16, '');
  $password = substr(md5($domain_name_sanitised . ':' . $_SERVER['REQUEST_IP'] . ':' . REQUEST_TIME), 0, 20);

  // Check that the domain name directory doesn't already exist.
  if (file_exists('../sites-projects/' . $domain_name)) {
    multisitemaker_message('<h3>Error</h3>
      <p>A multisite with the domain name ' . $subdomain_name . ' already exists.</p>', 'danger');

    multisitemaker_exit();
  }

  // Try to create the directory.
  @mkdir('../sites-projects');

  if (!mkdir('../sites-projects/' . $domain_name, 0777)) {
    multisitemaker_message('<h3>Error</h3>
      <p>Unable to create the directory sites/' . $domain_name . '; does this script have write permissions on the sites/ directory?</p>', 'danger');

    multisitemaker_exit();
  }

  // multisitesymlink
  $multisitesymlink = check_plain(trim($_POST['multisitesymlink']));

  if (!empty($multisitesymlink)) {
    if (!(file_exists('../sites-projects/' . $multisitesymlink) && is_dir('../sites-projects/' . $multisitesymlink))) {
      multisitemaker_exit('Sorry, the multisite directory name entered either doesn\'t exist or isn\'t accessible.');
    }

    // Symlink into the sites-common directory.
    symlink('../sites-projects/' . $multisitesymlink, '../sites-common/' . $multisitesymlink);
  }

  // Create symlinks now, if required.
  if (!empty($multisitesymlink)) {
    $destination_root = '../sites-projects/' . $multisitesymlink . '/';
    $source_root = '../sites-projects/' . $domain_name . '/';

    $symlinks_to_create = array(
      'modules',
      'themes',
      'libraries',
    );

    foreach ($symlinks_to_create as $symlink_to_create) {
      if (file_exists($destination_root . $symlink_to_create) && is_dir($destination_root . $symlink_to_create)) {
        symlink('../' . $multisitesymlink . '/' . $symlink_to_create, $source_root . $symlink_to_create);
      }
    }
  }

  // settingssiteurls
  $settingssiteurls = (array)explode(',', check_plain(trim($_POST['settingssiteurls'])));

  if (!empty($settingssiteurls)) {
    $siteurls_file_contents = '';

    foreach ($settingssiteurls as $row => &$settingssiteurl) {
      $settingssiteurl = trim($settingssiteurl);

      // Remove any URLs which end in multisitemaker_get_root_domain_name().
      if (substr($settingssiteurl, 0 - strlen(multisitemaker_get_root_domain_name())) == multisitemaker_get_root_domain_name()) {
        multisitemaker_message('<h3>Error</h3>
          <p>You cannot specify that this multisite should be shown on URLs ending in <em>' . multisitemaker_get_root_domain_name() . '</em>. Sorry about that.</p>', 'danger');

        multisitemaker_exit();
      }

      $siteurls_file_contents .= 'SETTINGS_SITE_URLS[] = ' . $settingssiteurl . "\r\n";
    }

    // Now create the file.
    $siteurls_file_path_root = '../sites-projects/' . $domain_name . '/settings.site_urls.info';

    $siteurls_file = fopen($siteurls_file_path_root, "w") or die("Unable to create file at $siteurls_file_path_root.");
    fwrite($siteurls_file, $siteurls_file_contents);
    fclose($siteurls_file);
  }

  //profile
  $profile = check_plain(trim($_POST['profile']));

  // Make sure $profile is a directory in the profiles directory;
  // otherwise, die. Remember our script location is /multisite-maker
  if (!is_dir('../core/profiles/' . $profile)) {
    multisitemaker_message('<h3>Error</h3>
      <p>The specified install profile <em>"' . $profile . '"</em> isn\'t valid, or its directory wasn\'t found at <em>"../core/profiles/' . $profile . '"</em> in the multisite maker. Please try a different profile.</p>', 'danger');

    multisitemaker_exit();
  }

  // Create the database and user, and set permissions.
  $options = array(
    'new_database' => $new_database,
    'new_username' => $new_username,
    'new_password' => $password,
  );

  if ($options = multisitemaker_create_db_and_user($options)) {
    if (multisitemaker_debug()) {
      dpm($options, '$result');
    }

    // Redirect the user back to this script but on the subdomain's URL.
    $redirect_url = multisitemaker_get_protocol() . '://' . $domain_name . '/multisitemaker/?';
    $redirect_options = array(
      'q=redirecttodrupal',
      'profile=' . $profile,
      'new_database=' . $options['new_database'],
      'new_username=' . $options['new_username'],
      'new_password=' . $options['new_password'],
      'hostname=' . $conf['database']['hostname'],
      'port=' . $conf['database']['port'],
      'driver=' . $conf['database']['driver'],
    );

    $redirect_url .= implode('&', $redirect_options);

    header('Location: ' . $redirect_url, TRUE, 302);
    multisitemaker_exit();
  }
  else {
    multisitemaker_message('<h3>Error</h3>
      <p>Unable to create the database and/or user.</p>', 'danger');

    multisitemaker_exit();
  }
}

/**
 * Gets the protocol of this page request.
 *
 * @return string
 */
function multisitemaker_get_protocol() {
  if (isset($_SERVER['HTTPS'])) {
    if ($_SERVER['HTTPS'] == 'on') {
      return 'https';
    }
  }

  return 'http';
}

/**
 * Check $_POST to see if a value with the name $variable_name has been posted.
 *
 * @param $variable_name
 *
 * @return string
 */
function multisitemaker_form_default_value($variable_name) {
  if (isset($_REQUEST[$variable_name])) {
    return check_plain($_REQUEST[$variable_name]);
  }
}

/**
 * Gets the root domain name, prefixed with a full stop.
 *
 * @return string The root domain name prefixed with a full stop, e.g.
 *                ".multisitemaker.4com.local"
 */
function multisitemaker_get_root_domain_name() {
  return '.' . strtolower($_SERVER['HTTP_HOST']);
}

/**
 * Get or set the database connection object.
 *
 * @param null $database_connection
 *
 * @return mixed
 */
function multisitemaker_db($database_connection = NULL) {
  global $_SESSION;

  if (!is_null($database_connection)) {
    $_SESSION['database_connection'] = $database_connection;
  }

  return $_SESSION['database_connection'];
}

/**
 * Close any DB connection and exit.
 */
function multisitemaker_exit($message = NULL) {
  // Print any message.
  echo $message;

  // Close any db connection.
  if ($db = multisitemaker_db()) {
    mysqli_close($db);
  }

  // Write output.
  $output = ob_get_clean();

  // Write the page output.
  multisitemaker_page($output);

  exit();
}

/**
 * Set the database details in $_SESSION, and redirect to install.php with
 * the Greyhead install profile pre-selected (because we need that profile
 * to pre-populate the database details from $_SESSION).
 */
function multisitemaker_redirecttodrupal() {
  global $_SESSION;

  // Redirect the user back to this script but on the subdomain's URL.
  $redirect_url = multisitemaker_get_protocol() . '://' . $_SERVER['HTTP_HOST'] . '/install.php?';

  $redirect_options = array(
    'profile=' . $_GET['profile'],
    'locale=en',
    'new_database=' . $_GET['new_database'],
    'new_username=' . $_GET['new_username'],
    'new_password=' . $_GET['new_password'],
    'hostname=' . $_GET['hostname'],
    'port=' . $_GET['port'],
    'driver=' . $_GET['driver'],
  );

  $redirect_url .= implode('&', $redirect_options);

  header('Location: ' . $redirect_url, TRUE, 302);
  multisitemaker_exit();
}

/**
 * Writes a page to the screen.
 *
 * @param $output
 */
function multisitemaker_page($output) {
  include 'page.tpl.php';
}
