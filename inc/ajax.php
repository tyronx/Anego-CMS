<?
if(isset($_GET['a']))
	$ac = $_GET['a'];
else $ac = $_POST['a'];

// Initialize PageManager where needed
if(in_array($ac,Array('rp','gce','cce','edce','callce','delce','mce','gcec'))) {
	include('inc/modules.php');
	$pmg = new PageManager();
}

/* CSRF Protection */
$allowed = array('rp', 'p', 'rw');
if (! in_array($ac, $allowed) && !IS_AJAX) {
	exit("300\n" . __('This action may only be called via AJAX'));
}

switch($ac) {
	// Helper to check if url_rewrite is enabled
	case 'rw':
		exit('yes');
	
	// Rebuild page contents
	case 'rp':
		$page = intval(@$_GET['page']);
		if (LOGINOK) {
			if (! $page) exit("500\n" . __('Missing page'));
			$result = $pmg->generatePage($page);
		}
		
		exit("200\n" . $result);
		break;
	
	// Get content elements. This is data required once the user presses "Edit page"
	case 'gce':
		if(!LOGINOK) exit("300\n" . __('You need to log on first.'));
		
		$p = intval(@$_GET['fgx']);
		
		// Delievers ce modules, page content and page elements of this page
		$json = $pmg->contentElementModules($p);
		// required js files
		$json['js'] = pageEditJs();
		
		exit("200\n".json_encode($json));
	
	// Create Content Element
	case 'cce':
		if(!LOGINOK) exit("300\n" . __('You need to log on first.'));
		
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

		// Create new instance of this module
		$ce = new $mid($page); //eval("return new ".$mid."();");
		
		if($json = $ce->createElement($position)) {
			/*$res=mysqli_query($sql_link, "SELECT MAX(position) FROM ".PAGE_ELEMENT." WHERE page_id=$page");
			list($maxPos) = mysqli_fetch_row($res);*/
			
			mysqli_query($sql_link, "UPDATE ".PAGE_ELEMENT." SET position=position+1 WHERE page_id=$page AND position>=$position");
			
			$q = "INSERT INTO ".PAGE_ELEMENT." (page_id,element_id,module_id,position,style,padding,margin,alignment) VALUES ('$page','" . $json['id'] . "','$mid','$position', '', '', '', '')";
			mysqli_query($sql_link, $q) or
				BailSQL("500\nFailed inserting page_element", $q);
				
			//list($maxPos) = mysqli_fetch_row($res);
			
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
		
		if(preg_match("#[^a-zA-Z0-9_-]+#",$mid))
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
		
		//if(!$elid) exit("300\nInvalid element id");

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
		
		if(isset($_POST['params']) && !is_array($_POST['params'])) {
			exit("500\nCoding Error: Parameters in wrong format (must be array)");
		}
		
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
		$res=mysqli_query($sql_link, $q) or
			BailErr("Failed getting page",$q);
		list($page_id,$oldpos) = mysqli_fetch_array($res);
		
		// Cut it out
		$q = "UPDATE ".PAGE_ELEMENT." SET position=position-1 WHERE page_id=$page_id AND position>$oldpos";
		mysqli_query($sql_link, $q) or
			BailErr("Failed cutting out element",$q);

		// Re-Insert (make space)
		$q = "UPDATE ".PAGE_ELEMENT." SET position=position+1 WHERE page_id=$page_id AND position>=$newpos";
		mysqli_query($sql_link, $q) or
			BailErr("Failed making space for element",$q);

		// Finally set the new position on the element
		$q = "UPDATE ".PAGE_ELEMENT." SET position=$newpos WHERE element_id=$elid AND module_id='$mid'";
		mysqli_query($sql_link, $q) or
			BailErr("Failed setting element position",$q);

		$pmg->generatePage($page_id);

		exit("200\nok.");
	
	case 'delce':
		if (!LOGINOK) exit("You need to log on first.");
		
		$mid = trim($_GET['mid']);
		$elid = intval($_GET['elid']);
		$pid = intval($_GET['pid']);
		
		if (!$elid) {
			exit("300\nInvalid element id");
		}
		
		if (preg_match("#[^a-zA-Z0-9_-]+#",$mid)) {
			exit("300\nInvalid module id");
		}
		
		$m = $pmg->getModules();
		if (!isset($m[$mid]))
			exit("300\nCan't find module of type '".$mid."'");
			
		include_once('modules/'.$mid.'/'.$mid.'.php');
					
		$ce = new $mid($pid);
		if ($ce->deleteElement($elid)) {
			$q = "SELECT page_id, position FROM ".PAGE_ELEMENT." WHERE element_id=$elid AND module_id='$mid'";
			$res = mysqli_query($sql_link, $q) or
				BailSQL("Failed getting page id", $q);

			list($page_id, $elpos) = mysqli_fetch_array($res);

			$q = "DELETE FROM " . PAGE_ELEMENT . " WHERE element_id=$elid AND module_id='$mid'";
			$res = mysqli_query($sql_link, $q) or
				BailSQL("Failed deleting content element table", $q);
				
			if (mysqli_affected_rows($sql_link)) {
				$q = "UPDATE " . PAGE_ELEMENT . " SET position=position-1 WHERE page_id=$page_id AND position>$elpos";
				$res = mysqli_query($sql_link, $q) or
					BailSQL("Failed decreasing position", $q);
			}
			
			$pmg->generatePage($page_id);
			
			exit("200\nok");
		}
		
		exit("300\nmodule denied delete");
	
	// Print main menu
	case 'mainmenu': 
		$anego->assign('pagetree', $anego->pageTreeByMenu('MAIN'));
		$anego->assign('menuname', 'mainnav');
		$anego->display_element('menu.tpl');
		exit();
	
	// Print minor menu
	case 'minormenu':
		$anego->assign('pagetree', $anego->pageTreeByMenu('MINOR'));
		$anego->assign('menuname', 'minornav');
		$anego->display_element('menu.tpl');
		exit();
	
	// ajax page loading
	case 'p':
		$p = CurrentPage();
		
		$json = Array();
		
		$selection = '';
		if (is_numeric($p)) {
			if ($p < 1) {
				$json['content'] = 'Invalid page';
				exit("404\n" . json_encode($json)); 
			}

			$selection = "idx='$p'";
		} else {
			$selection = "(url='" . mysqli_real_escape_string($sql_link, $p) . "' AND nolink=0 AND file='')";
		}
		
		
		$q = "SELECT idx, name, file, content, content_prepared FROM ".PAGES." WHERE " . $selection . ' ' . (!LOGINOK?"AND (visibility&1)=1":"");
		if (! ($res = mysqli_query($sql_link, $q)))	 {
			$json['content'] = "Failed getting page data for page $p";
			logError("Failed getting page data for page $p<br>", $q);
			exit("500\n" . json_encode($json)); 
		}
		$row = mysqli_fetch_array($res);
		
		if (!mysqli_affected_rows($sql_link)) {
			$json['content'] = __('Page does not exist or no permission to see it');
			exit("404\n" . json_encode($json)); 
		}
		
		if(@$_GET['updatePage'] && LOGINOK) {
			include('inc/modules.php');
			$pmg = new PageManager();
			$row['content'] = $pmg->generatePage($row['idx']);
			$json['pageUpdated'] = true;
		}

		if (! strlen($row['content'])) {
			$row['content'] = 
				"<i id=\"hasnoContent\">" . 
					__("This page has not been filled with content yet. Please use the 'Edit this page' Link to enter your text") . 
				"</i>";
		} else {
			if (! strlen($row['content_prepared'])) {
				$row['content_prepared'] = $row['content'];
			}
		}
		
		$anego->curPg = $p;
		$anego->content = $row['content_prepared'];
		$anego->prepare();

		/* Also deliever what js files to load */
		$json = array_merge($json, pageLoadFiles($row['idx']));

		$pagetitle = str_replace(array('&lt;', '&gt;'), array('<','>'), getSetting('pagetitle'));
		
		$json['pageId'] = $row['idx'];
		$json['title'] = $pagetitle . " - " . $row['name'];
		$json['content'] = $anego->get_template_vars('content');
				
		
		echo "200\n" . json_encode($json) . "\r\n";
		
		exit();
		
	default:
		exit("400\nwrong commmand");
}