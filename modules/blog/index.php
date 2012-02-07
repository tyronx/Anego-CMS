<?
if (isset($_GET['a']) || isset($_POST['a'])) {
	$cdir = getcwd();
	// Get the includes right
	chdir('../../');
	include('core.php');
	// Now set it back
	chdir($cdir);
	/* load language file */
	include($language . '.php');
	
	new BlogManager();
}


class BlogManager {
	var $blogTable = 'elements_blog_entry';
	var $cmtTable = 'comments_blog';
	var $readonly = false;
	
	function BlogManager() {
		global $cfg, $lng, $cdir, $anego, $language;
		
		$this->blogTable = $cfg['tablePrefix'].$this->blogTable;
		$this->cmtTable = $cfg['tablePrefix'].$this->cmtTable;
		$this->readonly = @$_GET['editmode'] == 'true';
		
		if (isset($_GET['a'])) {
			$a = $_GET['a'];
		} else {
			$a = $_POST['a'];
		}
		
		switch($a) {
			// get blog entries
			case 'g':
				$id = intval($_GET['id']);
				
				$blogs = $this->blogEntries($id);
				$nav = $this->navigation($id);
				
				echo "200\n";
				echo json_encode(array(
					'blogs' => $blogs,
					'navigation' => $nav));
				
				break;
				
			// load entry (ajax call)
			case 'le':
				$id = intval($_GET['id']);
				
				echo "200\n";
				echo $this->blogEntry($id,true);
				
				break;
				
			// rc = root call. The file has been requested from the root dir
			case 'rc':
				// l == Load entry
				if($_GET['data'][0] == 'l') {
					$id = intval(substr($_GET['data'],1));
					
					chdir('../../');
					
					$res = mysql_query("SELECT blog_id FROM " . $this->blogTable . " WHERE idx=$id");
					list($blogId) = mysql_fetch_array($res);
					$res = mysql_query("SELECT page_id FROM " . PAGE_ELEMENT . " WHERE module_id='blog' AND element_id=$blogId");
					list($pageId) = mysql_fetch_row($res);
					$anego->curPg = $pageId;
					
					AdminBar(-1);
					
					$anego->AddContent($this->blogEntry($id, true));
					$anego->appendJSFile('modules/blog/' . $language . '.js');
					$anego->appendJSFile('modules/blog/admin.js');
					$anego->display('index.tpl');
				}
				
				// v == view blog
				if($_GET['data'][0] == 'v') {
					$id = intval(substr($_GET['data'], 1));
					
					chdir('../../');
					
					$res = mysql_query("SELECT page_id FROM " . PAGE_ELEMENT . " WHERE module_id='blog' AND element_id=$id");
					list($pageId) = mysql_fetch_row($res);
					$anego->curPg = $pageId;
					
					$anego->AddContent($this->blogEntries($id));
					$anego->appendJSFile('modules/blog/' . $language . '.js');
					$anego->appendJSFile('modules/blog/admin.js');
					$anego->display('index.tpl');
				}
				break;
				
			// get blog entry comments
			case 'gc':
				$id = intval($_GET['id']);
				
				$cmts = entryComments($id);
				
				echo "200\n";
				echo $cmts;
				
				break;
				
			// write comment
			case 'wc':
				$id = intval($_POST['id']);
				
				$q = "UPDATE " . $this->blogTable . " SET comments=comments+1 WHERE idx=$id";
				mysql_query($q) or
					BailErr($lng['blog']['cmtaddfail'], $q);
					
				if (! mysql_affected_rows()) {
					exit("400\n" . $lng['blog']['cmtaddfailmore']);
				}
				
				if (! strlen($_POST['name'])) {
					$_POST['name'] = 'Anonymous';
				}
					
				$q = "INSERT INTO " . $this->cmtTable . " (element_id,user,date,comment) VALUES 
					($id,'" . mysql_real_escape_string(htmlentities($_POST['name'])) . "',
					'" . time() . "','" . mysql_real_escape_string(htmlentities($_POST['comment'])) . "')";
				
				mysql_query($q) or
					BailErr($lng['blog']['cmtaddfail'], $q);
					
				$cmt_id=mysql_insert_id();
				
				$q = "SELECT comments FROM " . $this->blogTable . " WHERE idx=$id";
				$res = mysql_query($q) or
					BailErr($lng['blog']['cmtcntreadfail'], $q);
				list($cmts) = mysql_fetch_array($res);
				$c = $cmts;
				if ($cmts == 0) $c = '0';
				
				
				echo "200\n";
				echo $c . ' ' . (($cmts == 1)?$lng['blog']['blog_comment']:$lng['blog']['blog_comments']) . "\n";
				
				echo '<div class="blogComment" id="blogCmt'.$cmt_id.'"><p>';
				if(LOGINOK) echo '<img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="smallIcon smallimgBin" onclick="blogfuncs.deleteComment('.$cmt_id.','.$id.')">';
				echo '<b>' . sprintf($lng['blog']['said'], htmlentities($_POST['name'])) . '</b></p><p>' . htmlentities($_POST['comment']) . '</p>';
				echo '<p><span class="blogCommentDate">' . date('F d Y H:i', time()) . '</span></p></div><br>';
				
				break;
			
			// delete comment
			case 'dc':
				if(!LOGINOK) exit("300\n".$lng['blog']['accessfail']);
				
				$cmt_id = intval($_GET['cmt_id']);
				$blog_id = intval($_GET['blog_id']);
				
				$q = 'DELETE FROM ' . $this->cmtTable . ' WHERE idx=' . $cmt_id . ' AND element_id=' . $blog_id;
				mysql_query($q) or
					BailErr($lng['blog']['cmtdelfail'], $q);
					
				if (! mysql_affected_rows()) exit("400\n" . $lng['blog']['nothingtodelete']);
					
				$q = "UPDATE " . $this->blogTable . " SET comments=comments-1 WHERE idx=$blog_id";
				mysql_query($q) or
					BailErr($lng['blog']['cmtdelfail'], $q);
					
				$q = "SELECT comments FROM " . $this->blogTable . " WHERE idx=$blog_id";
				$res = mysql_query($q) or
					BailErr($lng['blog']['cmtdelfail'],$q);
				list($cmts) = mysql_fetch_array($res);
				$c = $cmts;
				if ($cmts == 0) $c = '0';
				
				echo "200\n";
				echo $c . ' ' . (($cmts == 1)?$lng['blog']['blog_comment']:$lng['blog']['blog_comments']);
				
				break;
			
				
			// Create blog entry
			case 'cb':
				if (!LOGINOK) exit("300\n" . $lng['blog']['accessfail']);
				
				$id = intval($_POST['id']);
				
				if (get_magic_quotes_gpc()) {
					$_POST['title'] = stripslashes($_POST['title']);
					$_POST['content'] = stripslashes($_POST['content']);
				}

				
				$q = "INSERT INTO " . $this->blogTable . " (blog_id,user_id,date,title,entry,comments) VALUES ($id,0," . time() . ",
					'" . mysql_real_escape_string($_POST['title']) . "','" . mysql_real_escape_string($_POST['content']) . "',0)";
					
				mysql_query($q) or
					BailErr($lng['blog']['blogaddfail'],$q);
				
				$t = $this->blogEntry(mysql_insert_id());
				echo "200\n";
				echo $t;
				break;
				
			// Update blog entry
			case 'ub':
				if (!LOGINOK) exit("300\n" . $lng['blog']['accessfail']);
				
				$id = intval($_POST['id']);
				
				if (get_magic_quotes_gpc()) {
					$_POST['title'] = stripslashes($_POST['title']);
					$_POST['content'] = stripslashes($_POST['content']);
				}
				
				$q = "UPDATE " . $this->blogTable . " SET title='" . mysql_real_escape_string($_POST['title']) . "', 
					entry='" . mysql_real_escape_string($_POST['content']) . "' WHERE idx=$id";
				
				mysql_query($q) or
					BailErr($lng['blog']['blogeditfail'], $q);
					
				echo "200\nok";
				break;
				
			// Delete blog entry
			case 'db':
				if (!LOGINOK) exit("300\n" . $lng['blog']['accessfail']);
				$id = intval($_GET['id']);
				
				$q = "DELETE FROM " . $this->blogTable . " WHERE idx=$id";
				mysql_query($q) or
					BailErr($lng['blog']['blogdelfail'], $q);
				echo "200\nok";
				break;
				
			default: break;
		}
	}
	
	/*** Display a single blog entry ****/
	function blogEntry($entry_id, $fullview=false) {
		global $lng, $cfg;
		
		$text = '';
		$q = 'SELECT * FROM ' . $this->blogTable . ' WHERE idx=' . $entry_id;
		$res = mysql_query($q) or
			BailErr($lng['blog']['bloggetfail'], $q);
			
		if (! mysql_affected_rows()) {
			return $lng['blog']['nosuchblog'];
		}
				
		$row = mysql_fetch_array($res);
		
		if ($cfg['fancyURLs']) {
			$link = 'mdblog-l'.$row['idx'];
		} else {
			$link = '?a=lm&id='.$row['idx'];
		}
		
		$text .= '<div class="blogElement" id="blogElement_'.$row['idx'].'"><span class="blogDate">'.date('d.m.Y H:i',$row['date']).'</span>';
		$text .= '<a class="blogLink" href="'.$link.'" onclick="blogfuncs.loadEntry(this,'.$row['idx'].')"><h1 class="blogTitle">'.$row['title'].'</h1></a><div class="blogContent">'.$row['entry'].'</div>'; 
		
		if ($fullview) {
			if(LOGINOK && !$this->readonly) $text.='<div class="blogSummary"><div><img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="imgEdit icon" onclick="blogfuncs.editEntry('.$row['idx'].',true)"><img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="imgBin icon" onclick="blogfuncs.deleteEntry('.$row['idx'].',true)"></div></div>';
			$text.='<h1 style="padding-top:20px; clear:both;">'.$lng['blog']['post_comment'].'</h1>';			
			$text.='<textarea rows="6" cols="50" id="commentBody"></textarea><br><p>'.$lng['blog']['comment_as'].'<input id="commentName" type="text"><input type="hidden" name="email" value="" id="commentMail"></p>';
			//$text.='<p><table border="0"><tr><td><button id="commentButton" type="button" onclick="blogfuncs.postComment('.$entry_id.')">'.$lng['blog']['submit_comment'].'</button></td><td id="loadingIconSlot"></td></tr></table></p>';
			$text.='<p><button id="commentButton" type="button" onclick="blogfuncs.postComment('.$entry_id.')">'.$lng['blog']['submit_comment'].'</button> <img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" id="loadingIconSlot"></p>';
			
			$text.='<h3 class="commentCounter" style="padding-top:20px; clear:both;">'.$row['comments']." ".(($row['comments']==1)?$lng['blog']['blog_comment']:$lng['blog']['blog_comments'])."</h3>";
			if ($row['comments']>0) {
				$text.=$this->entryComments($entry_id);
			} else {
				$text.='<div class="commentSection"></div>';
			}
		} else {
			$text.='<div class="blogSummary"><p><small><a href="'.$link.'" onclick="blogfuncs.loadEntry('.$entry_id.')">'.$row['comments'].' '.(($row['comments']==1)?$lng['blog']['blog_comment']:$lng['blog']['blog_comments']).'</a></small></p>';		
			if(LOGINOK && !$this->readonly) $text.='<div><img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="imgEdit icon" onclick="blogfuncs.editEntry('.$row['idx'].',true)"><img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="imgBin icon" onclick="blogfuncs.deleteEntry('.$row['idx'].',true)"></div>';
			$text.='</div>';
		}
		$text.='<hr></div>';
		
		if ($fullview) {
			$text = '<div class="contentElement"><div class="blog" id="blogc_' . $row['blog_id'] . '">' . $text . '</div>';
			$text .= '<div class="blogNaviBox" id="blognav_' . $row['blog_id'] . '">' . $this->navigation($row['blog_id']) . '</div></div>';
		}
		
		return $text;
	}
	
	function entryComments($entry_id) {
		global $lng, $cfg;
		
		$q = "SELECT * FROM ".$this->cmtTable." WHERE element_id=$entry_id";
		$res = mysql_query($q) or
			BailErr($lng['blog']['cmtreadfail'],$q);
			
		if (! mysql_affected_rows()) return '<div class="commentSection"></div>';
			
		$cmts='<div class="commentSection">';
		while ($row = mysql_fetch_array($res)) {
			$cmts.='<div class="blogComment" id="blogCmt'.$row['idx'].'"><p>';
			if (LOGINOK) $cmts.='<img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="smallIcon smallimgBin" onclick="blogfuncs.deleteComment('.$row['idx'].','.$entry_id.')">';
			$cmts.='<b>'.sprintf($lng['blog']['said'],$row['user']).'</b></p><p>'.$row['comment'].'</p>';
			$cmts.='<p><span class="blogCommentDate">'.date('F d Y H:i',$row['date']).'</span></p><br></div>';
		}
		
		return $cmts.'</div>';
	}
	
	function navigation($blog_id) {
		global $cfg;
		
		if ($cfg['fancyURLs']) {
			$linkBase = 'mdblog-l';
		} else {
			$linkBase = '?a=lm&id=';
		}

		$q = 'SELECT * FROM '.$this->blogTable.' WHERE blog_id='.$blog_id.' ORDER BY date DESC';
		$res = mysql_query($q) or
			BailErr($lng['blog']['bloggetfail'],$q);
			
		$text = '<div class="blogNavigation">';

		while ($row = mysql_fetch_array($res)) {
			$text .= '<p><a href="' . $linkBase . $row['idx'] . '">' . $row['title'] . '</a></p>';
		}
		
		$text .= '</div>';
		
		return $text;
	}

	function blogEntries($blog_id) {
		global $lng, $cfg;
		$text = '';
		
		if (LOGINOK && !$this->readonly) {
			$text='<div id="blogadminbar_'.$blog_id.'" align="right"><a href="#" onclick="blogfuncs.newEntry('.$blog_id.'); return false;">'.$lng['blog']['new_entry'].'</a></div>';
		}
		
		$q = 'SELECT * FROM '.$this->blogTable.' WHERE blog_id='.$blog_id.' ORDER BY date DESC';
		$res = mysql_query($q) or
			BailErr($lng['blog']['bloggetfail'],$q);
			
			
		if (! mysql_affected_rows()) {
			return $text . '<i>' . $lng['blog']['noblogentries'] . '</i>';
		}
		
		$i = mysql_affected_rows();
		
		$text.='<div class="blogElements">';
		
		while ($row = mysql_fetch_array($res)) {
			
			if ($cfg['fancyURLs']) {
				$link = 'mdblog-l'.$row['idx'];
			} else {
				$link = '?a=lm&id='.$row['idx'];
			}
		
			$text.='<div class="blogElement" id="blogElement_'.$row['idx'].'"><span class="blogDate">'.date('d.m.Y H:i',$row['date']).'</span>';
			$text.='<a class="blogLink" name="blog'.$i.'" href="'.$link.'" onclick="blogfuncs.loadEntry(this,'.$row['idx'].','.$blog_id.')"><h1 class="blogTitle">'.$row['title'].'</h1></a><div class="blogContent">'.$row['entry'].'</div>';
			$text.='<div class="blogSummary"><p><small><a href="'.$link.'" onclick="blogfuncs.loadEntry(this,'.$row['idx'].','.$blog_id.')">'.$row['comments'].' '.(($row['comments']==1)?$lng['blog']['blog_comment']:$lng['blog']['blog_comments']).'</a></small></p>';
			if (LOGINOK && !$this->readonly) $text.='<div><img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="imgEdit icon" onclick="blogfuncs.editEntry('.$row['idx'].')"><img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="imgBin icon" onclick="blogfuncs.deleteEntry('.$row['idx'].')"></div>';
			$text.='<div class="blogComments"></div>';
			$text.='</div><hr></div>';
			$i--;
		}
		
		$text.='</div>';
		
		return $text;
	}
}
?>
