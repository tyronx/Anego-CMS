<?
/* Global Language setting (defaults to english)
 * eng: English
 * ger: German
 * auto: Autodetect language by browser/cookie (not fully implemented yet)
 */
$cfg['interfacelanguage'] = 'eng';
$cfg['websitelanguage'] = '';

/* Salt for storing passwords */
$cfg['hash_salt']="Vw_0Q3Z,e;y_!xyGo+tI";

/* Table prefix in database for all anego tables */
$cfg['tablePrefix'] = 'anego_';

/* Forbidden files for uploading in the manage file section */
$cfg['ForbiddenFiles'] = array("php","html","htm","cgi","pl","xhtml","js","exe","htaccess",'pif','com');
/* Allowed picture formats */
$cfg['allowedPictureFiles'] = array('jpg','png','gif');

/* Cookie duration in hours */
$cfg['cookieTime'] = 10*24;

/* Name of cookie */
$cfg['cookieName'] = 'ancms_auth';

/**
 * Gallery sizes
 */

/* How many image columns in the admin manage file section */
$cfg['galAdminCols'] = 5;
/* How many image rows in the admin manage file section */
$cfg['galAdminRows'] = 5;

/* How many image columns for public galleries */
$cfg['galPublicCols'] = 4;
/* How many image rows for public galleries */
$cfg['galPublicRows'] = 5;

/* How many image columns for the insert gallery dialog*/
$cfg['galInsertCols'] = 4;
/* How many image rows for the insert gallery dialog*/
$cfg['galInsertRows'] = 4;

/* Gallery thumbnail image width */
$cfg['galThumbnailWidth'] = 160;

/* Gallery thumbnail image height */
$cfg['galThumbnailHeight'] = 120;


/******** Directory to your uploaded files with trailing slash ********/
define("FILESROOT",'files/');

/**** Smarty path ****/
define("SMARTYPATH",'lib/Smarty/');


/* How show submenus be displayed, default is visible
 * visible: always visible
 * auto: appear when user moves over mouse (not correctly implemented yet)
 * onselect: appears when the parent-menu is being clicked
 * submenu onselect: 2 level deep submenus appear when clicked, 1 level deep always visible
*/
$cfg['submenuStyle'] = 'onselect';

/**
 * Performance Settings
 */

/* If pageload is set to ajax, this defines each fade-in, fade-out duration in ms when loading a new page
 * Set to 0 to disabled fading
 */
$cfg['ajaxloadFadeTimer'] = 150;

/* Defines what HTML Code will be loaded when editing pages
 * load: HTML Code will be loaded from server database (slow)
 * direct: HTML Code on the page will be used directly (this will cause problems when there is javascript code, that alters the HTML code on the client) 
*/
$cfg['editMode']='load';

$cfg['pageLoad']='ajax';


/* Wether or not to enable the aloah editor (in addition to tinymce) */
$cfg['useAloha'] = false;

/* Whether or not to use fancy urls (pg1234 instead of index.php?p=1234) */
/* Warning: If this setting is turned off, all the javascript files will probably be always uncached by the browser hence loading of the site will be slow */
/* Warning2: Turning this off may worsen your search engine rank too, as crawlers don't like dynamic urls (index.php?p=234) */
$cfg['fancyURLs'] = true;

/* Wether or not to display the secondary menu */
$cfg['minorMenu'] = true;

/* Domain of the website you want to use, set in $cfg['domain']if you don't want automatic detection. */
$cfg['default_domain'] = 'http://'.$_SERVER['SERVER_NAME'].(($_SERVER['SERVER_PORT']!=80)?':'.$_SERVER['SERVER_PORT']:'') . '/';
$cfg['domain'] = '';

/* If your site is not at the root of a domain, you can set a path here. e.g. www.domain.com/path */

$cfg['default_path'] = '/';
$cfg['path'] = '/';

/* User roles */

// A "Enum"
class Role {
	const Admin = 16;
	const ProMod = 12;			// Cannot access Site settings
	const SimpleMod = 8;		// Cannot access Page edit, Manage files, Manage Menu
	const User = 4;
	const Nothing = 0;
}