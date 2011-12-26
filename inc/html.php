<?
/* Types of "galleries", mostly outdated */
// Normal gallery
define("DTYPE_NORMAL",0);
// Gallery in admin mode: Also displays folders and files as well as options to rename and delete files
define("DTYPE_ADMIN",1);
// 2,3,4 => Not used anymore
define("DTYPE_INSERTIMG",2);
define("DTYPE_INSERTGAL",3);
define("DTYPE_INSERTFILE",4);
// Normal gallery but displays folders
define("DTYPE_IMGANDFOLDERS",5);

/* Types of "files" */
define('IMAGE',1);
define('FOLDER',2);
define('ADMIN_IMAGE',3);
define('ADMIN_FOLDER',4);
define('ADD_IMAGE',5);
define('ADD_GALLERY',6);

/* Is used for the outdated gallery style using bbcode and for file management 
 * Creates a navigate-able list of files in a given folder (folders can be clicked to browse them)
*/
// Todo: Rewrite the whole file manager as class and throw away all this gallery code 
function Gallery($root="",$dtype=DTYPE_NORMAL, $rows=-1, $cols=-1, $width=-1, $height=-1) {
	global $site, $_SERVER, $c_uidx, $cfg;
	
	if($rows==-1) $rows = $cfg['galPublicCols'];
	if($cols==-1) $cols = $cfg['galPublicRows'];
	if($width==-1) $width = $cfg['galThumbnailWidth'];
	if($height==-1) $height = $cfg['galThumbnailHeight'];
	
	ob_start();
	
	if($dtype==DTYPE_ADMIN && !LOGINOK) $dtype=DTYPE_NORMAL;
	/*** Build correct URL for transparent folder browsing */
	
	$url=$_SERVER['REQUEST_URI'];
	
	
	if($cfg['fancyURLs']) $url=preg_replace('/pg([0-9]+)/','index.php?p=\\1',$url);
	
	$url=parse_url($url);
		
	if(isset($_GET['fgx']))  {
		$query = preg_replace("/fgx=([^&]*)&?/","",$url['query']);
		// Shitty regex, we need to check first wether there is a & in the end
		if(strlen($query)<=1) $query="";
		else 
			if($query[strlen($query)-1]!='&') $query.="&";
	} else {
		$query=$url['query'];
		if(strlen($url['query'])) $query.="&";
	}
	
	if(basename($url['path'])=='admin.php') $base_url = "http://".$_SERVER['HTTP_HOST'].$url['path']."?a=filad&fgx=";
	else {
		$base_url = "http://".$_SERVER['HTTP_HOST'].$url['path']."?".$query."fgx=";
	}
	
	if($dtype==DTYPE_INSERTIMG) $base_url = "add_img.php?fgx=";
	
	/*** Verify/Adjust path correctness ***/	
	if($root==".") $root="";
	if(!preg_match("#^".preg_quote(FILESROOT,'#').'#',$root)) $root = FILESROOT.$root;
	if(isset($_GET['fgx'])) {
		// Absolute paths are frowned upon
		if(strlen($_GET['fgx']) && $_GET['fgx'][0]=='/') $_GET['fgx']='';
		$path=$root."/".$_GET['fgx'];
	}
	else $path="";
	//echo "root: $root<br>";
	$path = SimplifyPath($path);
	//echo "<br>path is $path<Br>";
	if(!preg_match("#$root.*#",$path)) { $path = $root; $_GET['fgx']=""; }
	
	if(!is_dir($path))		// User error or admin error
		if(!is_dir($path = SimplifyPath($root)))	// only admin error
			return sprintf($GLOBALS['lng_dirnotexist'],$root);  // so show message only on admin error, as it contains path information
	
	/*** Read and display dir ***/
	
	//echo "<br>path is $path<Br>";
	$path=rtrim($path,'/');
//	echo "path is $path";
	$dir = opendir($path);
	$files = array();
	
	if(isset($_GET['site'])) {
		$site = intval($_GET['site']);
		if(!$site) $site=1;
	} else $site=1;

	
	$i=0;
	$j=0;
	while(($file=readdir($dir))==TRUE) {
		if(strpos($file,"_thumb")) continue;
		
		if($file!='.' && $file!='..') {
			if($dtype==DTYPE_ADMIN)
				$files[] = $file;
			else {
				$ending = strtolower(substr($file, strrpos($file,".")+1));
				if($ending == "png" || $ending == "gif" || $ending == "jpg" || (is_dir($path.'/'.$file) && ($dtype==DTYPE_INSERTGAL || $dtype==DTYPE_INSERTIMG || $dtype==DTYPE_IMGANDFOLDERS)))
						$files[] = $file;
			}
		}
	}
	
	if($dtype!=DTYPE_NORMAL && $dtype!=DTYPE_IMGANDFOLDERS) {
		echo '<div align="center">'.$GLOBALS['lng_youarehere'].' ';
				
		$folders = explode("/",$path);
		$f="";
		$i=0;
		foreach($folders as $folder) {
			if($i>0) echo " / ";
			if($folder.'/'!=FILESROOT) {
				if(strlen($f)) $f.='/';
				$f.=$folder;
			}
			echo "<a href=\"".$base_url.urlencode($f)."\">$folder</a>";
			$i++;
		}
		echo '</div><br>';
	}
	
	echo "\n\t\t\t".'<table align="center" cellpadding="0" cellspacing="0" class="gal">'."\n";

	if(!count($files)) {
		echo "<tr><td style=\"width:".$width."px; height:".$height."px;\">".$GLOBALS['lng_show']." </td>";
		
	} else {
		sort($files);
		$vars = array();
		$vars['width']=$width;
		$vars['height']=$height;
		$vars['base_url']=$base_url;
		$vars['path']=$path;
		
		//$galnumber = rand(0,1000);
		
		/* Display items */
		foreach($files as $file) {
			if(preg_match("#_r\.[a-zA-Z]+$#",$file))
				if(in_array(preg_replace("#_r(\.[a-zA-Z]+)$#","\\1",$file),$files))
					continue;
			if(($site-1)*$cols*$rows<=$i && $i<$site*$cols*$rows) {
				if(!($j%$cols)) {
					$j=0;
					echo "\t\t\t<tr>\n";
				}
				
				$vars['file']=$file;
				
				/* Item is a folder */
				if(is_dir($path."/".$file)) {
					$vars['p']=SimplifyPath($_GET['fgx']."/".$file);
					
					switch($dtype) {
						case DTYPE_INSERTIMG: // no break; here
						case DTYPE_INSERTGAL:
						case DTYPE_IMGANDFOLDERS:
						case DTYPE_NORMAL: DisplayItem(FOLDER,$vars); break;
						case DTYPE_ADMIN:  DisplayItem(ADMIN_FOLDER,$vars); break;
						//case DTYPE_INSERTGAL: DisplayItem(ADD_GALLERY,$vars); break;
					}

				/* Item is a picture / generic file */
				} else {
					// Create Thumbnail if none exist or outdated
					$mtime = filemtime("$path/$file");
					if(!file_exists($str=ThumbOf("$path/$file")) || filemtime($str)!=$mtime) {
						if(CopyResized("$path/$file",$width,$height)) {
							if(!@touch($str,$mtime))
								$GLOBALS['anego']->AddContent('Cannot touch() \''.$str.'\', please allow writing access (you might need to chown the file)');
						}
						
					}
					
					switch($dtype) {
						case DTYPE_INSERTGAL: // no break; here
						case DTYPE_IMGANDFOLDERS:
						case DTYPE_NORMAL: DisplayItem(IMAGE,$vars); break;
						case DTYPE_INSERTIMG: DisplayItem(ADD_IMAGE,$vars); break;
						case DTYPE_ADMIN:  DisplayItem(ADMIN_IMAGE,$vars); break;
					}
					
				}
				
				if($j==$cols-1) echo "\t</tr>\n";
				$j++;
			}
			$i++;
		}

		$sites=ceil($i/($cols*$rows));
		if($sites>1) {
			echo "<tr><td align=\"center\" colspan=\"$rows\">";
			$x=0;
			if($site>1) echo "<a href=\"".$base_url.urlencode($_GET['fgx'])."&site=".($site-1)."\">Last</a>&nbsp;";
			while($x++<$sites) {
				if($x==$site) echo "<b><a href=\"".$base_url.urlencode($_GET['fgx'])."&site=$x\">$x</a></b>&nbsp;";
					else echo "<a href=\"".$base_url.urlencode($_GET['fgx'])."&site=$x\">$x</a>&nbsp;";
			}
			if($site<$sites) echo "<a href=\"".$base_url.urlencode($_GET['fgx'])."&site=".($site+1)."\">Next</a>";
			echo "</td>";
		}
	}
	
	echo "</tr></table>";
	
	if($_GET['fgx'])  {
		$back = SimplifyPath($_GET['fgx'].'/..');
		echo "<div align=\"center\"><br><a href=\"".$base_url.urlencode($back)."\">back</a></div>";
	}
	
	$str=ob_get_contents();
	ob_end_clean();
	
	return $str;
}

// Displays a single file within a "gallery"
function DisplayItem($type, $vars) {
	/* Wondering about the weird identation of the HTML code? 
	 * Its just to keep the resulting html code more or less tidy 
	 */
	global $defIcons;

	switch($type) {
		case IMAGE: 
				?>
				<td style="width:<?=$vars['width']?>px; height:<?=$vars['height']?>px;">
					<a href="<?=$vars['path'].'/'.$vars['file']?>" title="<?=$vars['file']?>" rel="lightbox"><img border="0" title="<?=$vars['file']?>"  src="<?=ThumbOf($vars['path'].'/'.$vars['file'])?>" alt=""></a>
				</td>
		<?
		break;
		
		case FOLDER:
		?>
				<td style="width:<?=$vars['width']?>px; height:<?=$vars['height']?>px;">
					<a href="<?=$vars['base_url'].urlencode($vars['p'])?>"><img border="0" title="Folder" src="<?=$defIcons['folder']?>" alt="Folder"><br><?=$vars['file']?></a>
				</td>
<?			break;

		case ADMIN_IMAGE: 
			$vars['ending'] = strtolower(substr($vars['file'], strrpos($vars['file'],".")+1));
						?>
				<td style="width:<?=$vars['width']?>px; height:<?=$vars['height']?>px;">
					<div class="galleryImageAdmin">
					<span class="galleryEdit"><a href="javascript:RenameFile('<?=$vars['path'].'/'.$vars['file']?>','<?=$_GET['fgx']?>')"><img src="styles/default/img/cleardot.gif" class="smallIcon smallimgPencil" alt="Rename" title="Rename"></a> <a href="javascript:DelFile('<?=$vars['path'].'/'.$vars['file']?>','<?=$_GET['fgx']?>')"><img src="styles/default/img/cleardot.gif" class="smallIcon smallimgBin" alt="Delete" title="Delete"></a></span>
<?
			if($vars['ending'] == "png" || $vars['ending'] == "gif" || $vars['ending'] == "jpg") { 
				?><a href="<?=$vars['path'].'/'.$vars['file']?>" title="<?=$vars['file']?>" rel="lightbox"><img border="0" title="<?=$vars['file']?>" src="<?=ThumbOf($vars['path'].'/'.$vars['file'])?>"></a><? 
			} else {
			?><a href="<?=$vars['path'].'/'.$vars['file']?>"><img border="0" title="Non-picture file" style="margin-top:<?=round(($vars['height']-64-15)/2)?>px;" src="<?=$defIcons['file']?>"><br><?=$vars['file']?></a>
<? } ?>
					</div>
				</td>
<?			break;
		
		case ADMIN_FOLDER: 
?>				<td style="width:<?=$vars['width']?>px; height:<?=$vars['height']?>px;">
					<div class="galleryImageAdmin">
					<span class="galleryEdit"><a href="javascript:RenameFile('<?=$vars['path'].'/'.$vars['file']?>','<?=$_GET['fgx']?>')"><img src="styles/default/img/cleardot.gif" class="smallIcon smallimgPencil" alt="Rename" title="Rename"></a> <a href="javascript:DelFile('<?=$vars['path'].'/'.$vars['file']?>','<?=$_GET['fgx']?>',1)"><img src="styles/default/img/cleardot.gif" class="smallIcon smallimgBin" alt="Delete" title="Delete"></a></span>
					<a href="<?=$vars['base_url'].urlencode($vars['p'])?>"><img border="0" title="Folder" src="<?=$defIcons['folder']?>" style="margin-top:<?=round(($vars['height']-64-15)/2)?>px;" alt="Folder"><br><?=$vars['file']?></a></div>
				</td>
						
<?			break;

		case ADD_IMAGE: 
?>
				<td style="width:<?=$vars['width']?>px; height:<?=$vars['height']?>px;">
					<a href="javascript:AddImage('<?=$vars['path'].'/'.$vars['file']?>')"><img border="0" title="<?=$vars['file']?>"  src="<?=ThumbOf($vars['path'].'/'.$vars['file'])?>" alt=""></a>
				</td>
<?		
			break;
		
		case ADD_GALLERY:
			break;
		
		
	}
}


require(SMARTYPATH.'Smarty.class.php');

/* Main HTML Output class based on Smarty */
class Anego extends Smarty {
	var $smarty;
	var $header_general, $header_jspreload, $header_prependjs,$header_appendjs, $admin_links;
	var $header_jsmodules="";
	var $header_css;
	var $footer;
	var $content="";
	var $curPg=-1;
	var $curStyle;
	
	function Anego($style) {
		$this->template_dir = 'styles/'.$style.'/templates';
		$this->compile_dir = 'styles/'.$style.'/templates_c';
		$this->cache_dir = 'styles/'.$style.'/cache';
		$this->config_dir = 'styles/'.$style.'/configs';
		$this->curStyle = $style;

		$this->header_general = Array();
		$this->header_prependjs = Array();
		$this->header_appendjs = Array();
		$this->admin_links = Array();
		$this->header_css = Array();
	}
	
	// Adds a link to the admin bar
	function AddLink($link) {
		$this->admin_links[]=$link;
	}
	
	// Adds a js module as defined in jsld.php
	function AddJsModule($module) {
		$this->header_jsmodules .= '.'.$module;
	}
	
	// Adds a css file to the header
	function AddCSSFile($css) {
		$this->header_css[] = $css;
	}
	
	// Adds direct javascript CODE before any lib is loaded (make your constant definition here)
	function AddJsPreload($script) {
		$this->header_jspreload[] = $script;
	}

	// Adds javascript files before the main libs get loaded
	function prependJSFile($path) {
		$this->header_prependjs[] = $path;
	}
	
	// Adds javascript files after the main libs get loaded
	function appendJSFile($path) {
		$this->header_appendjs[] = $path;
	}

	// Appends given string to the <head> element
	function AddHeadHeader($str) {
		$this->header_general[] = $str;
	}
	
	// Appends given string to the div#footer element
	function AddFooter($str) {
		$this->footer[] = $str;
	}
	
	// Appends given string to the div#content element
	function AddContent($str) {
		$this->content.=$str;
	}
	
	// Displays given template and ends php execution
	function display($template, $cache_id = null, $compile_id = null) {
		/******* Custom style setup code *********/
		if(file_exists("styles/" . $this->curStyle . "/custom.php"))
			include("styles/" . $this->curStyle . "/custom.php");

		define('DISPLAY_ATTEMPTED',1);
		$this->prepare();
		
		$this->assign('content',$this->content);
		$this->assign('mainmenu',$this->MainMenu());
		$this->assign('menuadmin',$this->MenuAdmin());
		$this->assign('minormenu',$this->MinorMenu());
		
		parent::display($template, $cache_id,$compile_id);
	}
	
	function display_element($template) {
		parent::display($template);
	}
	
	// SQL-free display
	function bail($template) {
		$this->prepare();
		
		$this->assign('content',$this->content);
		parent::display($template);	
	}
	
	// Puts together all required header stuff
	function prepare() {
		// general header
		if(count($this->header_general))
			$header=implode("\n",$this->header_general);
		else $header='';
		// css
		$css = "";
		if(count($this->header_css)) 
			foreach($this->header_css as $cssfile)
				$css.="\t".'<link rel="stylesheet" href="'.$cssfile.'" type="text/css" media="screen">'."\n";
		// javascript
		$js = "";
		if(count($this->header_jspreload))
			foreach($this->header_jspreload as $script)
				$js.="$script\n";
		
		$jsfiles = "";
		if(count($this->header_prependjs))
			foreach($this->header_prependjs as $path)
				$jsfiles.="\t<script type=\"text/javascript\" src=\"$path\"></script>\n";
		if(strlen($this->header_jsmodules)) {
			if($GLOBALS['cfg']['fancyURLs'])
				$jsfiles.="\t<script type=\"text/javascript\" src=\"ld".$this->header_jsmodules."\"></script>\n";
			else 
				$jsfiles.="\t<script type=\"text/javascript\" src=\"jsld.php?g=".$this->header_jsmodules."\"></script>\n";
		}
		if(count($this->header_appendjs))
			foreach($this->header_appendjs as $path)
				$jsfiles.="\t<script type=\"text/javascript\" src=\"$path\"></script>\n";
		
		
		if(file_exists('styles/'.$this->curStyle.'/custom.js'))
			$jsfiles .= "\t<script type=\"text/javascript\" src=\"styles/".$this->curStyle."/custom.js\"></script>\n";
		
		
		$this->assign('header',$css."\t".'<script type="text/javascript">'."\n".$js."\t".'</script>'."\r\n".$jsfiles.$header);
		// footer
		$ft = "";
		if(count($this->footer))
			foreach($this->footer as $code)
				$ft.="$code\n";
		$this->assign('footer',$ft);
		// admin menu
		//if(count($this->admin_links))
		//	$this->assign('adminlinks',$this->admin_links);
		
		$this->assign('content',$this->content);
	}
	
	
	// Reload page
	function Reload($file="") {
		if(preg_match("/^http/",$file)) { header("Location: $file");  exit(); }
			
		if(!strlen($file)) $file=basename($_SERVER['PHP_SELF']);
		else if(strpos($file,$_SERVER['PHP_SELF'])!==FALSE) $file=basename($file);

		$dir = dirname($_SERVER['PHP_SELF']);
		if($dir{strlen($dir)-1}!='/' && $file{0}!='/') $dir.="/";

		header("Location: http://".$_SERVER['HTTP_HOST'].$dir.$file);
	  
		exit();
		return 0;
	}

	function Box($title) {
	//if($ret) ob_start();
		$title = "<div class=\"content_header\">\n<div class=\"add_shadow\"><div><div>\n<div class=\"content_header2\">\n<h2>$title</h2>\n</div>\n</div></div></div>\n</div>";
		return $title;
		/*
		if($ret) {
			$str=ob_get_contents();
			ob_end_clean();
			return $str;
		}*/	
	}
	
	// Builds the main menu array
	// TODO: Defenitely cache this. 3 boxed queries - horrible
	//		 and in the process make it endlessly boxable, not just 3 levels
	function MainMenu() {
		global $cfg;
		
		if($this->curPg > 0)  {
			// level 0,1,2 
			// Mark a level 1 item as selected when a level 2 item is the current page
			$q = "SELECT p1.parent_idx,p1.idx FROM ".PAGES." as p1, ".PAGES." as p2 WHERE p2.idx=".$this->curPg." AND p2.parent_idx=p1.idx";
			$res= @mysql_query($q) or
				BailSQL($GLOBALS['lng_failedchildermain'].$row['name'],$q);
			if(!mysql_affected_rows()) $curParents = array();
			else $curParents = mysql_fetch_row($res);
		} else $curParents = array();
		
		$items=array();
		
		$q="SELECT * FROM ".PAGES." WHERE parent_idx=0 AND (visibility&2)=2 ".(!LOGINOK?"AND (visibility&1)=1":"")." AND menu='MAIN' ORDER BY position";
		$res=@mysql_query($q) or
			BailSQL($GLOBALS['lng_failedmain'],$q);
		$i = mysql_affected_rows();
		$k=0;
		
		while($row=mysql_fetch_array($res)) {
			$i--;
			$item=$js=$repos='';
			$hasChildren = $childSelected = false;
		
			$q = "SELECT * FROM ".PAGES." WHERE parent_idx=".$row['idx']." AND (visibility&2)=2 ".(!LOGINOK?"AND (visibility&1)=1":"")." ORDER BY position";
			$res2 = @mysql_query($q) or
				BailSQL($GLOBALS['lng_failedchildermain'].$row['name'],$q);
			if(mysql_affected_rows()) {
				$k++;
				$hasChildren = true;
				
				$vis='';
				if($cfg['submenuStyle']=='auto' || $cfg['submenuStyle']=='onselect')  
					$vis = 'style="display:none;"';
				$item.='<div class="bothclear">'.$this->MenuItemLink($row,$k).'</div>';
				
				$sitem_div = '<div class="subnavbox" id="submenu'.$k.'" '.$js.' '.$vis.' '.$repos.'>';
				$sitem='<ul class="subnavlist">';
				$j=0;
				while($row2 = mysql_fetch_array($res2)) {
					if($row2['idx']==$this->curPg || in_array($row2['idx'],$curParents)) { $sitem.="<li class=\"subnavSelected\">"; $childSelected=true; }
					else $sitem.="<li>";
					//if($row2['file']) $link = $row2['file'];
					//	else 
					$sitem.=$this->MenuItemLink($row2);
					
					//if($cfg['submenuStyle'] != 'submenu onselect' || in_array($row2['idx'],$curParents) || $row2['idx']==$this->curPg) {
					$q = "SELECT * FROM ".PAGES." WHERE parent_idx=".$row2['idx']." AND (visibility&2)=2 ".(!LOGINOK?"AND (visibility&1)=1":"")." ORDER BY position";
					$res3 = @mysql_query($q) or
						BailSQL($GLOBALS['lng_failedchildermain'].$row['name'],$q);

					if(mysql_affected_rows()) {
						$sitem.='<div style="' . (($cfg['submenuStyle']=='visible')?'':'display:none;')  . '" class="subsubitems">';
						while($row3 = mysql_fetch_array($res3)) {
							if($row3['idx']==$this->curPg) $childSelected=true;
							$sitem.='<div class="subsubitem">'.$this->MenuItemLink($row3)."</div>";
						}
						$sitem.='</div>';
					}
					//}

					$j++;
					$sitem.="</li>\n\t\t\t\t";
				}
				
				$sitem.="\t\t\t\t".'</ul>';
				//if($cfg['submenuStyle']!='onselect')
				//$item .= $sitem.'</div>';
				//else if($childSelected || $row['idx']==$this->curPg)
				if($cfg['submenuStyle']!='auto' && ($childSelected || $row['idx']==$this->curPg))
					$item .= '<div class="subnavbox" id="submenu'.$k.'" '.$js.' '.$repos.'>'.$sitem.'</div>';				
				else 
					$item .= $sitem_div . $sitem;
				//else 
			} else {
				$item.=$this->MenuItemLink($row);
			}
			
			$selected = $this->curPg==$row['idx'] || in_array($row['idx'],$curParents);	
			
			$sugClass = $hasChildren?'navParent':'';
			$sugClass = ($selected)?('navSelected'.(strlen($sugClass)?' ':'').$sugClass):$sugClass;
			$sugClass .= $childSelected?((strlen($sugClass)?' ':'').'childSelected'):'';
			$items[]=array('id'=>$row['idx'],'link'=>$item,'selected'=>$selected,'hasChildren'=>$hasChildren, 'suggestedClass'=>$sugClass, 'lastNode'=>($i==0));
		}
		return $items;
	}
	
	// Generates one menu item link from a given database row.
	// $k == If not false: Children div element id
	function MenuItemLink($row,$k=false) {
		global $cfg;
		
		$file = $cfg['domain'];
		
		if(defined("ADMIN_MODE")) {
			if($cfg['fancyURLs']) $file.='admin-';
			else $file.='admin.php';
		}
		
		if($cfg['fancyURLs']) $href = $file.'pg'.$row['idx']; //index.php?p='.$row['idx'];
			else $href = $file.'?p='.$row['idx'];

		if($cfg['pageLoad']=='ajax')
			$onc = ' onclick="Core.loadPage(\'pg'.$row['idx'].'\')"';
		else $onc='';
		
		if($row['file']) { $onc=''; $href=$row['file']; }
		
		$name=$row['name'];
		if(isset($row['defImg']) && $row['defImg']==1) 
            $name='<img src="'.$row['defImg'].'" alt="'.$row['name'].'" title="'.htmlentities($row['info']).'">';
		if(isset($row['activeImg']) && $row['activeImg']==1 && $this->curPg==$row['idx']) 
            $name='<img src="'.$row['activeImg'].'" alt="'.$row['name'].'" title="'.htmlentities($row['info']).'">';
			
		if($row['nolink']) { if($cfg['submenuStyle']!='auto') return $name; $href='#'; $onc=''; }

		if($cfg['submenuStyle']=='auto' && $k) {
			if(isset($row['defImg']) && $row['defImg']==1 && $row['hoverImg']) $onc.=' onmouseover="OpenMenu('.$k.',\''.$row['image_selected'].'\'); TogImg(this,\''.$row['hoverImg'].'\')" onmouseout="CloseMenu('.$k.',\''.$row['image'].'\'); TogImg(this,\''.$row['defImg'].'\')"';
			else $onc.= ' OnMouseOver="OpenMenu('.$k.',\''.$row['image_selected'].'\')" OnMouseOut="CloseMenu('.$k.',\''.$row['image'].'\')"';
		} else  {
			$js = '';
			if(isset($row['defImg']) && $row['defImg']==1 && $row['hoverImg']) $onc.=' onmouseover="TogImg(this,\''.$row['hoverImg'].'\')" onmouseout="TogImg(this,\''.$row['defImg'].'\')"';
		}
		
		if(function_exists('CustomMenuItemLink'))
			return CustomMenuItemLink($row, '<a title="'.htmlentities($row['info']).'" href="'.$href.'"'.$onc.'>'.$name.'</a>',$this->curPg);
	
		return '<a title="'.htmlentities($row['info']).'" href="'.$href.'"'.$onc.'>'.$name.'</a>';
	}
	
	function MinorMenu() {
		ob_start();
		
		$q="SELECT * FROM ".PAGES." WHERE parent_idx=0 AND (visibility&2)=2 ".(!LOGINOK?"AND (visibility&1)=1":"")." AND menu='MINOR' ORDER BY position";
		$res=@mysql_query($q) or
			BailSQL($GLOBALS['lng_failedmain'],$q);
			
		while($row=mysql_fetch_array($res)) {
			if($row['file']) $link = $row['file'];
				else $link = "index.php?p=".$row['idx'];
				
			echo '<li>'.$this->MenuItemLink($row).'</li>';
		}
		$str = ob_get_contents();
		ob_end_clean();
		
		return $str;
	}
	
	function MenuAdmin() {
		ob_start();
		//if(!LOGINOK && GetConfig('show.adminlink')==1) 		
		//	echo '<li><a href="admin.php">Admin</a></li>';
		
		if(!LOGINOK) return;
		
		?>
		<ul class="nav adminnav">
		<?
			foreach($this->admin_links as $link)
				echo '<li>'.$link.'</li>';
		?>
		<li><a href="admin.php?a=lo"><?=__('Logout')?></a></li>
		</ul>
		<?		
		$str = ob_get_contents();
		ob_end_clean();
		return $str;		
	}
}