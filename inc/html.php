<?
require(SMARTYPATH.'SmartyBC.class.php');

define('MAXDEPTH', 6);

/* Main HTML Output class based on Smarty */
class Anego extends SmartyBC {
	var $smarty;
	var $header_general, $header_jspreload, $header_prependjs,$header_appendjs, $admin_links;
	var $header_jsmodules="";
	var $header_css;
	var $footer;
	var $content="";
	var $curPg=-1;
	var $curStyle;
	
	var $customphpincluded = false;
	
	function __construct($style) {
		parent::__construct();
	
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
		
		$this->register_prefilter('i10n_smarty');
	}
	
	function _addCustomCode() {
		/******* Custom style setup code *******/
		if (!$this->customphpincluded && file_exists("styles/" . $this->curStyle . "/custom.php")) {
			include("styles/" . $this->curStyle . "/custom.php");
			$this->customphpincluded = true;
		}
	}
	
	// Displays given template and ends php execution
	function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		$this->prepare();
		
		if (!file_exists($this->template_dir . '/'. $template)) {
			$template = '../../default/templates/' . $template;
		}
		
		parent::display($template, $cache_id, $compile_id, $parent);
	}
	
	function fetchContent($template, $cache_id = null, $compile_id = null, $parent = null) {
		if (!file_exists($this->template_dir . '/'. $template)) {
			$template = '../../default/templates/' . $template;
		}
		
		return parent::fetch($template, $cache_id, $compile_id, $parent = null);
	}
	
	function display_element($template) {
		parent::display($template);
	}
	
	// SQL-free display
	function bail($template) {
		$this->prepare(true);
		
		$this->assign('content', $this->content);
		parent::display($template);	
	}
	
	// Puts together all required header stuff
	function prepare($skipsql = false) {
		global $cfg;
		
		if (!$skipsql) {
			$this->_addCustomCode();
		}
		
		if (LOGINOK) {
			$this->AddCSSFile('styles/default/admin.css');
		}
		
		/*if (!class_exists('PageManager')) include('inc/modules.php');
		
		// Loop content through all page output filters
		// TODO: FilterModules should use Closures to pass their filters, and store them in
		// installed_modules via closure serialization lib
		$pmg = new PageManager();
		$filterModules = getModules('FilterModule');
		if(count($filterModules)) {
			foreach($filterModules as $module) {
				if($return = $module->onContentOutputPage($this->content)) {
					$this->content = $return;
				}
			}
		}*/
		
		if (!defined('DISPLAY_ATTEMPTED')) define('DISPLAY_ATTEMPTED', 1);
		
		if (!$skipsql) {
			$pages = array(
				'major' => $this->pageTreeByMenu('MAIN'),
				'minor' => $this->pageTreeByMenu('MINOR')
			);
			
			$this->assign('pages', $pages);
			$this->assign('menuadmin',$this->MenuAdmin());
		}
		
		
		$this->assign('content', $this->content);
		
		// general header
		if (count($this->header_general)) {
			$header = implode("\n", $this->header_general);
		} else {
			$header = '';
		}
		
		// css
		$css = "";
		if (count($this->header_css)) {
			foreach($this->header_css as $cssfile)
				$css.="\t".'<link rel="stylesheet" href="' . $cfg['domain'] . $cssfile . '" type="text/css" media="screen">'."\n";
		}
		// javascript
		$js = "";
		if (count($this->header_jspreload)) {
			foreach($this->header_jspreload as $script)
				$js.="$script\n";
		}
		$jsfiles = "";
		if (count($this->header_prependjs)) {
			foreach ($this->header_prependjs as $path) {
				$jsfiles.="\t<script type=\"text/javascript\" src=\"" . $cfg['domain'] . "$path\"></script>\n";
			}
		}
		if (strlen($this->header_jsmodules)) {
			if($GLOBALS['cfg']['fancyURLs']) {
				$jsfiles.="\t<script type=\"text/javascript\" src=\"" . $cfg['domain'] . "ld".$this->header_jsmodules."\"></script>\n";
			} else {
				$jsfiles.="\t<script type=\"text/javascript\" src=\"" . $cfg['domain'] . "jsld.php?g=".$this->header_jsmodules."\"></script>\n";
			}
		}
		
		if(count($this->header_appendjs)) {
			foreach($this->header_appendjs as $path)
				$jsfiles.="\t<script type=\"text/javascript\" src=\"" . $cfg['domain'] . "$path\"></script>\n";
		}
		
		if (file_exists('styles/'.$this->curStyle.'/custom.js')) {
			$jsfiles .= "\t<script type=\"text/javascript\" src=\"" . $cfg['domain'] . "styles/".$this->curStyle."/custom.js\"></script>\n";
		}
		
		$this->assign('header',$css."\t".'<script type="text/javascript">'."\n".$js."\t".'</script>'."\r\n".$jsfiles.$header);
		// footer
		$ft = "";
		if (!empty($this->footer)) {
			foreach($this->footer as $code)
				$ft.="$code\n";
		}
		
		$this->assign('footer', $ft);
		$this->assign('content',$this->content);
		
		/******* Custom style setup code *******/
		if (file_exists("styles/" . $this->curStyle . "/postfilter.php"))
			include("styles/" . $this->curStyle . "/postfilter.php");
	}
	
	
	// Reload page
	function Reload($file="") {
		if (preg_match("/^http/", $file)) { 
			header("Location: $file");
			exit(); 
		}
			
		if (!strlen($file)) {
			$file = basename($_SERVER['PHP_SELF']);
		} else {
			if (strpos($file, $_SERVER['PHP_SELF'])!==FALSE) $file = basename($file);
		}

		$dir = dirname($_SERVER['PHP_SELF']);
		if ($dir{strlen($dir)-1} != '/' && $file{0} != '/') {
			$dir.="/";
		}

		header("Location: http://".$_SERVER['HTTP_HOST'].$dir.$file);
	  
		exit();
		return 0;
	}

	// Builds the main menu array
	function pageTreeByMenu($menuid) {
		global $cfg, $sql_link;
		
		$this->_addCustomCode();
	
		// Get Flat array of page
		$q = "SELECT idx, parent_idx, name, url, info, file, nolink, defImg, hoverImg, activeImg FROM ". PAGES . " WHERE menu='$menuid' AND (visibility&2)=2 ".(!LOGINOK?"AND (visibility&1)=1":"") . " ORDER BY parent_idx, position";
		
		$res = @mysqli_query($sql_link, $q) or
			BailSQL(__('Failed retrieving the main menu'), $q);
		
		$pages_flat = array();
		while ($row = mysqli_fetch_assoc($res)) {
			$pages_flat[$row['idx']] = $this->createPageLink($row);
		}
		
		if ($this->curPg > 0) {
			// Create "page selected trail in the page tree"
			if (isset($pages_flat[$this->curPg])) {
				$pages_flat[$this->curPg]['selected'] = true;
			}
			
			$idx = $this->curPg;
			while (($idx = @$pages_flat[$idx]['parent_idx']) != 0) {
				if (@$pages_flat[$idx]) {
					$pages_flat[$idx]['childselected'] = true;
				}
			}
		}
		
		// Build the pagetree 
		return $this->addChildren(0, $pages_flat);
	}
	
	// Todo: Make this algorithm faster by slicing out elements we've already added
	function addChildren($parentidx, $pages, $depth=0) {
		global $cfg;
		
		$children = array();

		foreach ($pages as $page) {
			if ($page['parent_idx'] == $parentidx) {
			
				if ($depth < MAXDEPTH) {
					$page['children'] = $this->addChildren($page['idx'], $pages, $depth+1);
				}
				
				$page['itemclasses'] = 
					(count($page['children']) 					? 'navParent ' : '') .
					($cfg['submenuStyle'] == 'dropdown'? 'canHover ' : '') .
					(@$page['selected']							? 'navSelected ' : '') .
					(@$page['childselected']					? 'childSelected ' : '') .
					($page['nolink']								? 'textitem ' : 'menuitem');
				
				/*
					Submenu is hidden when
						- submenuStyle set to 'auto' and page is not selected
						- submenuStyle set to 'onselect' and page or childpages is not selected
						- submenuStyle set to 'submenu onselect' and 2 level deep submenus appear when clicked, 1 level deep always visible
				*/
				$page['childcontainerclasses'] = 
						(
							$cfg['submenuStyle'] == 'dropdown' 
							//&& !@$page['selected']
						) ? 'hidden ' : '' 
					.
						(
							$cfg['submenuStyle'] == 'onselect' 
							&& (!@$page['selected'] && !@$page['childselected'])
							&& $depth > 0
						) ? 'hidden ' : ''
					.
						(
							$cfg['submenuStyle'] == 'submenu onselect' 
							&& (!$page['selected'] && !$page['childselected'])
							&& $depth > 1
						) ? 'hidden ' : ''
					;
				
				$children[$page['idx']] = $page;
			}
		}
		
		return $children;
	}
	
	
	// Generates one menu item link from a given database row.
	function createPageLink($row) {
		global $cfg;
		
		$row['link'] = $cfg['path'];
		$row['linkclass'] = '';
		
		if (strlen($row['url']) && $row['url']{0} == '/') $row['url'] = substr($row['url'], 1);
		
		if($cfg['fancyURLs']) {
			if (@$row['url']) {
				$row['link'] .= $row['url'];
				$row['linkclass'] = 'urlalias';
			} else {
				$row['link'] .= 'pages/' . $row['idx'];
			}
		} else {
			$row['link'] .= '?p=' . $row['idx'];
		}
		
		if($row['file']) {
			if (preg_match("/^http/", $row['file'])) {
				$row['link'] = $row['file'];
			} else {
				$row['link'] = ($row['file']{0} != '#' ?$cfg['path'] : "") . $row['file'];
			}
			
		}
		
		if (function_exists('CustomMenuItemLink'))
			return CustomMenuItemLink($row, $this->curPg);
	
		return $row;
	}
	
	function MenuAdmin() {
		global $cfg;
		
		ob_start();

		if(!LOGINOK) return;
		
		$logouturl = $cfg['path'] . ($cfg['fancyURLs'] ? 'admin/logout' : 'admin.php?a=logout');

		?>
		<ul class="nav adminnav">
		<?
			foreach($this->admin_links as $link)
				echo '<li>'.$link.'</li>';
		?>
		<li><a href="<?=$logouturl?>"><?=__('Logout')?></a></li>
		</ul>
		<?		
		$str = ob_get_contents();
		ob_end_clean();
		return $str;
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
}
