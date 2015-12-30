<?
chdir('../../');
include("core.php");

header('Content-type: text/javascript');


echo 'var tinyMCEImageList = new Array(';

if (! LOGINOK) {
	exit(');');
}

$imgpath = 'files/content';
// Get all page which are not just for structuring (nolink)
$dir = opendir($imgpath);


$files = array();

while ($file = readdir($dir)) {	
	if (preg_match("/(?<!_r)\.(jpg|png|gif)$/i", $file)) {
		$files[] = $file;
	}
}

natcasesort ($files);

$i=0;
foreach ($files as $file) {
	if ($i > 0) echo ',';
	echo '["' . $file . '", "' . $cfg['path'] . $imgpath . '/' . $file . '"]';
	$i++;
}

echo ');';

?>