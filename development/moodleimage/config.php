<?php

// phpcs:ignoreFile
// Custom Moodle configuration. Is appended to Moodle's own config.php.

global $CFG;

// Allow arbitrary hostname (by default it's hardcoded).
if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
}
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $CFG->wwwroot   = 'https://' . $_SERVER['HTTP_HOST'];
} else {
    $CFG->wwwroot   = 'http://' . $_SERVER['HTTP_HOST'];
}

//=========================================================================
// PHPUNIT SUPPORT
//=========================================================================
 $CFG->phpunit_prefix = 'phpu_';
 $CFG->phpunit_dataroot = '/var/www/moodledata/phpunit';
 $CFG->phpunit_directorypermissions = 02777; // optional
 $CFG->phpunit_profilingenabled = true; // optional to profile PHPUnit runs.

//=========================================================================
// Custom settings for development
//=========================================================================

// Force a debugging mode regardless the settings in the site administration
 @error_reporting(E_ALL);   // NOT FOR PRODUCTION SERVERS!
 @ini_set('display_errors', '1');         // NOT FOR PRODUCTION SERVERS!
 $CFG->debug = (E_ALL);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
 $CFG->debugdisplay = 1;              // NOT FOR PRODUCTION SERVERS!

// Make sure that the temp directories are not deleted during the backup process. Allows easier testing of the backup process.
$CFG->keeptempdirectoriesonbackup = true;

require_once(__DIR__ . '/lib/setup.php');
