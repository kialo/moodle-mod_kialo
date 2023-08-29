<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// Moodle configuration file.
// Will be copied to Moodle when starting the docker compose setup.
//
// phpcs:disable

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

// Make sure that the temp directories are not deleted during the backup process. Allows easier testing of the backup process.
$CFG->keeptempdirectoriesonbackup = true;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
