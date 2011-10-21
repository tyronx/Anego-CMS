<?

if($_POST['a']=='save') {
	chdir('../../');
	include_once('core.php');
	
	if(!LOGINOK) exit("500\nNo permission to edit this text (are you logged in?");
	
	$id=intval($_POST['id']);
	
	if(get_magic_quotes_gpc ())
		$_POST['content']=stripslashes($_POST['content']);
	
	$q='UPDATE '.$cfg['tablePrefix']."pages_aloha SET value='".mysql_real_escape_string($_POST['content'])."' WHERE idx=$id";
	mysql_query($q) or
		BailErr("Failed saving element ",$q);
		
	// affected_rows returns 0 if new value == old value :(
	//if(!mysql_affected_rows()) exit("300\nCouldn't save content, aloha element not found. Has it been deleted?");
	
	// Regenerate the page
	$q = 'SELECT page_id FROM '.PAGE_ELEMENT.' WHERE element_id='.$id.' AND module_id=\'alohatext\'';
	$res=mysql_query($q) or
		BailErr("Failed regenerating page",$q);
	list($pageId)=mysql_fetch_row($res);
	include_once('inc/modules.php');
	$pmg = new PageManager();		
	$pmg->generatePage($pageId);
	
	exit("200\nok");
}

?>