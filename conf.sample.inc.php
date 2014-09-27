<?
/******** Anego Website Style ********/
define('STYLE','anego');

/******** MySQL Database Connection Info ********/
// Host
define ("HOST","localhost");
// Username
define ("SQLUSER","user");
// Password
define ("SQLPASS","password");
// Databasename
define ("SQLDB","database");

/******** User accounts ********/

/* Example Syntax of user accounts, generate these with the setup.php script 
 * Use only lower case names! 
 *
 * $user_accounts = Array(
 *   'someuser' => 'somehash',
 *   'someotheruser' => 'someotherhash',
 * );

 * $user_roles = Array(
 *  'someuser' => Role::Admin,
 *  'someotheruser' => Role::ProMod
 * );
 */

/* Global Language setting (defaults to english)
 * eng: English
 * ger: German
 * auto: Autodetect language by browser/cookie (not fully implemented yet)
 */
$cfg['interfacelanguage'] = 'eng';
$cfg['websitelanguage'] = '';

/* Edit this line, if your anego installation is in a subfolder, always with trailing slash
 * e.g. /anego/
 */
$cfg['path'] = '/';

// Do not edit this
define("CONFIG_LOADED",true);
?>