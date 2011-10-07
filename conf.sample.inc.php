<?
/******** User accounts ********/

$cfg['tablePrefix'] = 'anego_';

// generate user accounts with setup.php
// Insert only lower case names here
$user_accounts = Array(
	'someuser' => 'somehash',
	'someotheruser' => 'someotherhash',
);

$user_roles = Array(
	'someuser' => Role::Admin,
	'someotheruser' => Role::ProMod
);

/******** MySQL Database Connection Info ********/
// Host
define ("HOST","localhost");
// Username
define ("SQLUSER","user");
// Password
define ("SQLPASS","password");
// Databasename
define ("SQLDB","database");

/******** Anego Website Style ********/
define('STYLE','anego');

/* Global Language setting (defaults to english)
 * eng: English
 * ger: German
 * auto: Autodetect language by browser/cookie (not fully implemented yet)
 */
$language = 'end';

/* Uncomment & edit this line, if your anego installation is in a subfolder */
//$cfg['domain'].='anego/';

/******** Directory to your uploaded files ********/
define("FILESROOT",'files/');

/**** Smarty path ****/
define("SMARTYPATH",'lib/Smarty-2.6/');

// Do not edit this
define("CONFIG_LOADED",true);

?>