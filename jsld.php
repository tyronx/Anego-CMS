<?
/* Javascript loader code - packs all required js files into one neat little package 
 * Opens up a range of possibilties to easily improve js loading times:
 * - sends a last-modified with the http header, so next request we get a if-modified-since date and return a 304 not-modified when the files are up-to-date
 * - Uses jsmin to minify the js code, then gzip compresses the result
 * - caches the minified, gzip compressed file
 * - adds a Expires-Http-Header for 24 hours
 */
 

require "inc/auth.php"; // Also includes config
require "inc/functions.php";
require "inc/db_init.php";
require "inc/lng_init.php";

// Developer mode?
$res = mysqli_query($sql_link, "SELECT value FROM ".SETTINGS." WHERE name='developermode'");
list($developermode) = mysqli_fetch_array($res);


$jsFiles = array(
/* Default js files to load */
'de' => array('lib/jquery-1.7.min.js','lib/jquery.fancybox-1.3.4.pack.js', 'lib/jquery.livequery.js', 'js/core.js'), //'js/menu.js', //, 'lib/jquery.mousewheel-3.0.4.pack.js',
/* Admin language files*/
'adger' => array('lang/ger.js'),
'adeng' => array('lang/eng.js'),
/* Admin settings */
'as' => array('js/admSettings.js',),
'jui' => array('lib/jquery-ui-1.8.16.custom.min.js'),
/* Admin menu */
'am' => array('js/jquery.sortableTree.js','js/admMenu.js'),
/* Admin files */
'af' => array('js/admFiles.js'),
/* Admin pages + TinyMCE */
'ap' => array('lib/tiny_mce/jquery.tinymce.js','js/admPage.js'),
/* Login */
'lo' => array('lib/sha256.js'));

/* We need history support for ajax loading */
$jsFiles['de'][]='lib/jquery.history.js';
//$jsFiles['de'][]='lib/jquery.json-2.2.min.js';

if(!$_GET['g']) {
	return '';
}
$k = explode('.',$_GET['g']);

/* Output compression mostly taken from tinymce compressor */

if ($developermode) {
	$supportsGzip = false;
} else {
	// Check if it supports gzip
	$zlibOn = ini_get('zlib.output_compression') || (ini_set('zlib.output_compression', 0) === false);
	$encodings = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? strtolower($_SERVER['HTTP_ACCEPT_ENCODING']) : "";
	$encoding = preg_match( '/\b(x-gzip|gzip)\b/', $encodings, $match) ? $match[1] : "";

	// norton antivirus header
	if (isset($_SERVER['---------------']))
		$encoding = "x-gzip";

	$supportsGzip = !empty($encoding) && !$zlibOn && function_exists('gzencode');
}

// Only update if one of the js files is updated
$allFiles='';
$newestFileDate=0;
foreach ($k as $group) {
	if (isset($jsFiles[$group])) {
		foreach ($jsFiles[$group] as $file) {
			$newestFileDate = max($newestFileDate,filemtime($file));
			$allFiles .= $file;
		}
	}
}

// Generate hash for all files
$hash = md5($allFiles);

// Set cache file name
$cacheFile = "tmp/" . $hash . ($developermode ? "-dev" : "-prod") . ($supportsGzip ? ".gz" : ".js");

// Browser Caching im Produktivmodus
if (!$developermode) {
	$expiresOffset = 24 * 3600;
	
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expiresOffset) . " GMT");
	header("Cache-Control: public, max-age=" . $expiresOffset);
}

if (file_exists($cacheFile) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $newestFileDate) { 
	header("HTTP/1.1 304 Not Modified"); 
	exit; 
}

header("Content-type: text/javascript");
header("Vary: Accept-Encoding");  // Handle proxies
header("Cache-Control: no-cache, must-revalidate");
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $newestFileDate ) . ' GMT');

if ($supportsGzip) {
	header("Content-Encoding: " . $encoding);
}

// Use cached file
if (file_exists($cacheFile) && filemtime($cacheFile) >= $newestFileDate) {
	readfile($cacheFile);
	return;
}

// Set base URL for where tinymce is loaded from
$buffer = "";




/* Generate Javascript output */

foreach ($k as $group) {
	if (isset($jsFiles[$group])) {
		foreach ($jsFiles[$group] as $file) {
			$buffer .= file_get_contents($file)."\r\n";
		}
	}
}

if (!$developermode) {
	require "lib/jsmin.php";
	
	$buffer = JSMin::minify($buffer);
}

/* Some init code */
if (in_array('de',$k)) {
	if (file_exists('var/installed_modules')) {
		$modules = unserialize(file_get_contents('var/installed_modules'));
	} else {
		$modules = Array();
	}
	
	$directLoad = Array();
	if (is_array($modules) && count($modules)) {
		foreach ($modules as $mid=>$mod) {
			if (isset($mod['config']['allowdirectLoad']) && $mod['config']['allowdirectLoad']) {
				$directLoad[]=$mid;
			}
		}
	}
	
	$init = '';
	if (count($directLoad)) {
		$init.= "\nanego.directLoad=Array('".implode("','",$directLoad)."');\n";
	}
		
	// Javascript site loading code - let loadPage handle invalid anchor 
	$buffer.= '$(document).ready(function() {
	if(anego.error) return;
	$.history.init(Core.historyChange, { unescape: ",/" });
	Core.loadPage(window.location.hash.substr(1));'."\n\t".$init.'});';
}

// Compress data
if ($supportsGzip) {
	$buffer = gzencode($buffer, 9, FORCE_GZIP);
}

// Write cached file
@file_put_contents($cacheFile, $buffer);

// Stream contents to client
echo $buffer;