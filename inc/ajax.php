<?
if(isset($_GET['a']))
	$ac = $_GET['a'];
else $ac = $_POST['a'];

// Initialize PageManager where needed
if(in_array($ac,Array('rp','gce','cce','edce','callce','delce','mce','gcec'))) {
	include('inc/modules.php');
	$pmg = new PageManager();
}

switch($ac) {
	// Helper to check if url_rewrite is enabled
	case 'rw':
		exit('yes');
	
	// Rebuild page contents
	case 'rp':
		$page = intval($_GET['page']);
		$pmg->generatePage($page);
		exit("200\nok");
		break;
	
	// Get content elements. This is data required once the user presses "Edit page"
	case 'gce':
		if(!LOGINOK) exit("300\nYou need to log on first.");
		
		$p = intval($_GET['fgx']);
		
		// Delievers ce modules, page content and page elements of this page
		$json = $pmg->contentElementModules($p);
		// required js files
		$json['js'] = pageEditJs($p);
		
		exit("200\n".json_encode($json));
	
	// Create Content Element
	case 'cce':
		if(!LOGINOK) exit("300\nYou need to log on first.");
		
		$mid = trim($_GET['mid']);
		$page = intval($_GET['page_id']);
		$position = intval($_GET['pos']);
		
		if(!$page) exit("300\nInvalid page id");
		
		if(!preg_match("#[a-zA-Z0-9_-]+#",$mid))
			exit("300\nInvalid module id");
		
		$m = $pmg->getModules();
		if(!isset($m[$mid]))
			exit("300\nCan't find module of type '".$mid."'");
		
		include_once('modules/'.$mid.'/'.$mid.'.php');

		// Yay, PHP5.3 goodness <3
		$ce = new $mid($page); //eval("return new ".$mid."();");
		
		if($json = $ce->createElement($position)) {
			/*$res=mysql_query("SELECT MAX(position) FROM ".PAGE_ELEMENT." WHERE page_id=$page");
			list($maxPos) = mysql_fetch_row($res);*/
			
			mysql_query("UPDATE ".PAGE_ELEMENT." SET position=position+1 WHERE page_id=$page AND position>=$position");
			
			mysql_query("INSERT INTO ".PAGE_ELEMENT." (page_id,element_id,module_id,position) VALUES ('$page','".$json['id']."','$mid','$position')") or
				exit("500\nFailed inserting page_element");
				
			//list($maxPos) = mysql_fetch_row($res);
			
			$pmg->generatePage($page);
			
			exit("200\n".json_encode($json));
		}
		exit("500\nContent Element creation failed");
	
	// Call generateContent() of a module - e.g used by html element to retrieve all the content (<script> tags are stripped when using $('sdf').html())
	case 'gcec':
		if(!LOGINOK) exit("300\nYou need to log on first.");
		
		$mid = trim($_POST['mid']);
		$elid = intval($_POST['elid']);
		$pid = intval($_POST['pid']);
		
		if(!preg_match("#[a-zA-Z0-9_-]+#",$mid))
			exit("300\nInvalid module id");
		
		include_once('modules/'.$mid.'/'.$mid.'.php');
		
		$ce = new $mid($pid, $elid);
		
		echo "200\n";
		echo $ce->generateContent($elid);
		exit();
		
		break;
		
	// Call Content element function. Parameters:
	// mid: module id (e.g. 'blog')
	// elid: element id
	// fn: function to call
	// params[]: function parameters, every parameter is treated as a string
	// (optional) recache: wether or not the content has changed. If yes, the page cache will be refreshed
	case 'callce':
		if(!LOGINOK) exit("300\nYou need to log on first.");
		
		$mid = trim(@$_POST['mid']);
		$elid = intval(@$_POST['elid']);
		$pid = intval(@$_POST['pid']);
		
		if(!$elid) exit("300\nInvalid element id");

		if(preg_match("#[^a-zA-Z0-9_-]+#",$mid))
			exit("300\nInvalid module id");
		
		$m = $pmg->getModules();
		
		if(! isset($m[$mid]))
			exit("300\nCan't find module of type '".($mid)."'");
		
		include_once('modules/'.$mid.'/'.$mid.'.php');

		$ce = new $mid($pid, $elid);

		// $funcName = $mid::$methodMap[$_POST['fn']]; // this syntax only works in php5.3 
		// ...and eval() is always a potential security hole :/
		eval('$methodMap= '.$mid.'::$methodMap;');
		$method_name = $methodMap[$_POST['fn']];
		
		if(!strlen($method_name)) exit("500\nFunction does not exist");
		
		if(isset($_POST['params']) && !is_array($_POST['params'])) exit("500\nCoding Error: Parameters in wrong format (must be array)");
		
		$params = isset($_POST['params']) ? $_POST['params'] : array();
		
		// Dynamically call method
		$response = call_user_func_array(array($ce, $method_name), $params);
		
		if(isset($_POST['recache']) && $_POST['recache']) {
			$pmg->generatePage($pid);
		}

		exit($response);
	
	// Move a placed content element from one position to another
	// Step 1: SQL Query to cut out element (all elements below: pos--)
	// Step 2: Re-Insert element (all elements below: pos++)
	case 'mce':
		if(!LOGINOK) exit("You need to log on first.");
	
		$mid = trim($_GET['mid']);
		$elid = intval($_GET['elid']);
		$newpos = intval($_GET['newpos']);
		
		if(!$elid) exit("300\nInvalid element id");

		if(preg_match("#[^a-zA-Z0-9_-]+#",$mid))
			exit("300\nInvalid module id");
		
		// Get old position and page id
		$q = "SELECT page_id,position FROM ".PAGE_ELEMENT." WHERE element_id=$elid AND module_id='$mid'";
		$res=mysql_query($q) or
			BailErr("Failed getting page",$q);
		list($page_id,$oldpos) = mysql_fetch_array($res);
		
		// Cut it out
		$q = "UPDATE ".PAGE_ELEMENT." SET position=position-1 WHERE page_id=$page_id AND position>$oldpos";
		mysql_query($q) or
			BailErr("Failed cutting out element",$q);

		// Re-Insert (make space)
		$q = "UPDATE ".PAGE_ELEMENT." SET position=position+1 WHERE page_id=$page_id AND position>=$newpos";
		mysql_query($q) or
			BailErr("Failed making space for element",$q);

		// Finally set the new position on the element
		$q = "UPDATE ".PAGE_ELEMENT." SET position=$newpos WHERE element_id=$elid AND module_id='$mid'";
		mysql_query($q) or
			BailErr("Failed setting element position",$q);

		$pmg->generatePage($page_id);

		exit("200\nok.");
	
	case 'delce':
		if(!LOGINOK) exit("You need to log on first.");
		
		$mid = trim($_GET['mid']);
		$elid = intval($_GET['elid']);
		$pid = intval($_GET['pid']);
		
		if(!$elid) exit("300\nInvalid element id");
		
		if(preg_match("#[^a-zA-Z0-9_-]+#",$mid))
			exit("300\nInvalid module id");
		
		$m = $pmg->getModules();
		if(!isset($m[$mid]))
			exit("300\nCan't find module of type '".$mid."'");
			
		include_once('modules/'.$mid.'/'.$mid.'.php');
					
		$ce = new $mid($pid);
		if($ce->deleteElement($elid)) {
			$q = "SELECT page_id, position FROM ".PAGE_ELEMENT." WHERE element_id=$elid AND module_id='$mid'";
			$res=mysql_query($q) or 
				BailErr("Failed getting page id",$q);
			list($page_id, $elpos) = mysql_fetch_array($res);


			$q = "DELETE FROM ".PAGE_ELEMENT." WHERE element_id=$elid AND module_id='$mid'";
			$res=mysql_query($q) or
				BailErr("Failed deleting content element table",$q);
				
			$q = "UPDATE ".PAGE_ELEMENT." SET position=position-1 WHERE page_id=$page_id AND position>$elpos";
			$res=mysql_query($q) or
				BailErr("Failed decreasing position",$q);
			
			$pmg->generatePage($page_id);
			
			exit("200\nok");
		}
		exit("300\nmodule denied delete");
	
	// Print main menu
	case 'mainmenu': 
		$anego->assign('mainmenu',$anego->MainMenu());
		$anego->display_element('menu.tpl');
		exit();
	
	// Print minor menu
	case 'minormenu':
		// Minor menu doesn't have a template :(
		echo $anego->MinorMenu();
		//$anego->assign('minormenu',$anego->MinorMenu());
		//$anego->display_element('menu.tpl');
		exit();
	
	// ajax page loading
	case 'p':
		$p=intval($_GET['p']);
		
		if($p == -1) $p = CurrentPage();
		
		$json = Array();
		
		if($p < 1) {
			$json['content'] = 'Invalid page';
			exit("200\n".json_encode($json)); 
		}
		
		
		$q = "SELECT name, file, content, content_prepared FROM ".PAGES." WHERE idx=$p ".(!LOGINOK?"AND (visibility&1)=1":"")."";
		if(! ($res = mysql_query($q)))	 {
			$json['content'] = "Failed getting page data for page $p";
			logError("Failed getting page data for page $p<br>",$q);
			exit("200\n".json_encode($json)); 
		}
		$row = mysql_fetch_array($res);

		if(!mysql_affected_rows()) {
			$json['content'] = $lng_permission;
			exit("200\n".json_encode($json)); 
		}
		
		if(!strlen($row['content'])) $row['content']="<i id=\"hasnoContent\">$lng_content</i>";
		if(!strlen($row['content_prepared'] && strlen($row['content']))) $row['content_prepared'] = $row['content'];
		
		/* Also deliever what js files to load */
		$json['js']=pageLoadJs($p);

		$lng_pagetitle = str_replace(array('&lt;','&gt;'),array('<','>'),$lng_pagetitle);
		
		$json['title'] = $lng_pagetitle." - ".$row['name'];
		$json['content'] = $row['content_prepared'];
		
		echo "200\n".json_encode($json)."\r\n";
		
		exit();
		
	default:
		exit("400\nwrong commmand");
}