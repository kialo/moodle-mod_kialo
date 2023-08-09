<?php

// Moodle configuration file.
// Will be copied to Moodle when starting the docker compose setup.

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'mariadb';
$CFG->dbname    = 'bitnami_moodle';
$CFG->dbuser    = 'bn_moodle';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
        'dbpersist' => 0,
        'dbport' => 3306,
        'dbsocket' => '',
        'dbcollation' => 'utf8mb4_unicode_ci',
);

if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
}
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $CFG->wwwroot   = 'https://' . $_SERVER['HTTP_HOST'];
} else {
    $CFG->wwwroot   = 'http://' . $_SERVER['HTTP_HOST'];
}
$CFG->dataroot  = '/bitnami/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02775;

//=========================================================================
// PHPUNIT SUPPORT
//=========================================================================
 $CFG->phpunit_prefix = 'phpu_';
 $CFG->phpunit_dataroot = '/bitnami/moodledata/phpunit';
 $CFG->phpunit_directorypermissions = 02777; // optional
 $CFG->phpunit_profilingenabled = true; // optional to profile PHPUnit runs.

//=========================================================================
// Custom settings for development
//=========================================================================

// Force a debugging mode regardless the settings in the site administration
 @error_reporting(E_ALL | E_STRICT);   // NOT FOR PRODUCTION SERVERS!
 @ini_set('display_errors', '1');         // NOT FOR PRODUCTION SERVERS!
 $CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
 $CFG->debugdisplay = 1;              // NOT FOR PRODUCTION SERVERS!

// disable some caching so that we don't constantly have to manually purge caches in the web UI
$CFG->cachetemplates = false;
$CFG->langstringcache = false;

// This setting is only used during the installation process. So once the Moodle site is installed, it is ignored.
$CFG->setsitepresetduringinstall = 'kialo-admin-preset.xml';

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
