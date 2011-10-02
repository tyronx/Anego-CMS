<?
/* Javascript loader code - packs all required js files into one neat little package 
 * Opens up a range of possibilties to easily improve js loading times:
 * - (already implemented) send a last-modified with the http header, so next request we get a if-modified-since date and return a 304 not-modified when the files are up-to-date
 * - minify(.php) then (implemented) gzip compress
 * - (implemented) then cache a range of required files
 * - define a production mode where we set an expire header so the browser doesn't even request the file 
 */

include('inc/auth.php'); // Also includes config

$jsFiles = array(
/* Default js files to load */
'de' => array('lib/jquery-1.6.2.min.js','lib/jquery.fancybox-1.3.4.pack.js', 'lib/jquery.livequery.js', 'js/core.js'), //'js/menu.js', //, 'lib/jquery.mousewheel-3.0.4.pack.js',
/* Admin language files*/
'adger' => array('lang/admin_ger.js'),
'adeng' => array('lang/admin_eng.js'),
/* Admin settings */
'as' => array('js/admSettings.js',),
'jui' => array('lib/jquery-ui-1.8.11.custom.min.js'),
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

if(!$_GET['g']) return '';
$k=explode('.',$_GET['g']);

// Minify sux with path resolution on windows also its too slow to do every time
/*if(preg_match('/Win32/',$_SERVER['SERVER_SOFTWARE']))
	define('LOCALUSE',1);
else 
	define('LOCALUSE',0);

$minifyJS = new Minify(TYPE_JS);
//$minifyCSS = new Minify(TYPE_CSS);

foreach($k as $group) {
	if(isset($jsFiles[$group]))
		$minifyJS->addFile($jsFiles[$group]);
}

echo $minifyJS->combine();
*/


/* Output compression mostly taken from tinymce compressor */

// Check if it supports gzip
$zlibOn = ini_get('zlib.output_compression') || (ini_set('zlib.output_compression', 0) === false);
$encodings = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? strtolower($_SERVER['HTTP_ACCEPT_ENCODING']) : "";
$encoding = preg_match( '/\b(x-gzip|gzip)\b/', $encodings, $match) ? $match[1] : "";

// norton antivirus header
if (isset($_SERVER['---------------']))
	$encoding = "x-gzip";

$supportsGzip = !empty($encoding) && !$zlibOn && function_exists('gzencode');

/* Only update if one of the js files is updated */
$allFiles='';
$newestFileDate=0;
foreach($k as $group)
	if(isset($jsFiles[$group]))
		foreach($jsFiles[$group] as $file) {
			$newestFileDate=max($newestFileDate,filemtime($file));
			$allFiles.=$file;
		}


// Generate hash for all files
$hash = md5($allFiles);

// Set cache file name
$cacheFile = "tmp/" . $hash . ($supportsGzip ? ".gz" : ".js");

// Set headers
//header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expiresOffset) . " GMT");
//header("Cache-Control: public, max-age=" . $expiresOffset);

if (file_exists($cacheFile) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $newestFileDate) { 
	header("HTTP/1.1 304 Not Modified"); 
	exit; 
}

header("Content-type: text/javascript");
header("Vary: Accept-Encoding");  // Handle proxies
header("Cache-Control: no-cache, must-revalidate");
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $newestFileDate ) . ' GMT');

if ($supportsGzip)
	header("Content-Encoding: " . $encoding);

// Use cached file
if (file_exists($cacheFile) && filemtime($cacheFile) >= $newestFileDate) {
	readfile($cacheFile);
	return;
}

// Set base URL for where tinymce is loaded from
$buffer = "";

/* Javascript output*/

foreach($k as $group) {
	if(isset($jsFiles[$group]))
		foreach($jsFiles[$group] as $file) 
			$buffer.=file_get_contents($file)."\r\n";
			
}

/* Some init code */
if(in_array('de',$k)) {
	if(file_exists('var/installed_modules'))
		$modules = unserialize(file_get_contents('var/installed_modules'));
	else $modules = Array();
	
	$directLoad = Array();
	foreach($modules as $mid=>$mod) 
		if(isset($mod['config']['allowdirectLoad']) && $mod['config']['allowdirectLoad'])
			$directLoad[]=$mid;
	
	$init = '';
	if(count($directLoad))
		$init.= "\nanego.directLoad=Array('".implode("','",$directLoad)."');\n";
		
	// Javascript site loading code - let loadPage handle invalid anchor 
	$buffer.= '$(document).ready(function() {
	if(anego.error) return;
	$.history.init(Core.historyChange, { unescape: ",/" });
	Core.loadPage(window.location.hash.substr(1));'."\n\t".$init.'});';
}

// Compress data
if ($supportsGzip)
	$buffer = gzencode($buffer, 9, FORCE_GZIP);

// Write cached file
@file_put_contents($cacheFile, $buffer);

// Stream contents to client
echo $buffer;