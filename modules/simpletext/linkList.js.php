<?
chdir('../../');
include("core.php");

header('Content-type: text/javascript');

if (! LOGINOK) {
	exit(');');
}

echo 'var tinyMCELinkList=new Array(';
$res2=mysql_query("SELECT idx,name FROM ".PAGES);
$i=0;
while($row2=mysql_fetch_array($res2)) {
	if($i>0) echo ',';
	$name=str_replace('"','\\"',$row2['name']);
	if($cfg['fancyURLs']) echo '["'.$name.'", "pg'.$row2['idx'].'"]';
	else echo '["'.$name.'", "index.php?p='.$row2['idx'].'"]';
	$i++;
}
echo ');';

?>