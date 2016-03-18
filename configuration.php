<?php

/**
 * @file
 * configuration-sample.php
 *
 * Copy this file and rename it to configuration.php, then edit the
 * configuration array, below.
 *
 * This file is part of multisitemaker and should be placed alongside the
 * multisitemaker index.php file.
 *
 * This file provides the configuration settings for the multisite maker tool.
 */

if (!defined('MULTISITEMAKER_INITIALISED')) {
  multisitemaker_access_denied();
}

/**
 * Start a session.
 */
session_start();

/**
 * Edit the following array keys to set up the multisite maker.
 */
global $conf;

$conf = array(
  // Configuration for the database.
  'database' => array(
    // The location where the database server can be reached, e.g. "localhost",
    // 127.0.0.1, or a valid IP address or domain name.
    'hostname' => 'localhost',

    // The MySQL server's port. Default is 3306.
    'port' => 3306,

    // The database driver. Usually mysql.
    'driver' => 'mysql',

    // Database and username prefix: to prefix all database names and usernames
    // with, e.g. "4com_", set this to "_4com".
    'name_prefix' => '4com_',

    // The database username and password. This user MUST have grant
    // permissions, as they must be able to create new databases and users, and
    // assign permissions.
    'username' => 'root',
    'password' => 'password',
  ),

  // Security.
  'security' => array(
    // Whitelisted IP addresses.
    'whitelisted_ips' => array(
      // Add the IP addresses here which are allowed to access this service and
      // create new multisites.
      '127.0.0.1',
      // If you remove the above line, you may not be able to access this script
      // from your local machine.

      // Four Communications' London office IP address.
      '31.221.86.113',

      // Alex's VPN server. Just in case.
      '178.62.67.228',
    ),
  ),

  // Debug?
  'debug' => FALSE,
);

/**
 * Don't edit below this line please :)
 */

/**
 * Define a constant to let index.php know the config has been successfully
 * included.
 */
define('MULTISITEMAKER_CONFIGURED', TRUE);
