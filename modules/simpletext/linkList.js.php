<?
chdir('../../');
include("core.php");

header('Content-type: text/javascript');


echo 'var tinyMCELinkList=new Array(';

if (! LOGINOK) {
	exit(');');
}

// Get all page which are not just for structuring (nolink)
$res2 = mysql_query("SELECT idx,name,url FROM ".PAGES." WHERE nolink=0");
$i = 0;

while ($row2 = mysql_fetch_array($res2)) {
	if ($i>0) echo ',';
	$name = str_replace('"','\\"', $row2['name']);
	if ($cfg['fancyURLs']) {
		$filename = 'pages/' . $row2['idx'];
		if (strlen($row2['url'])) {
			$filename = $row2['url'];
		}
		
		echo '["' . $name . '", "' . $cfg['path']  . $filename . '"]';
	} else {
		echo '["' . $name . '", "' . $cfg['path']  . 'index.php?p=' . $row2['idx'] . '"]';
	}
	$i++;
}

echo ');';

?>