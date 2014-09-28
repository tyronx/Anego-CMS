<?

abstract class BasicModule { 
}

abstract class FilterModule extends BasicModule {
	
	public function onContentOutputPage($text) {
		return $text;
	}

	public function onContentOutputAjax($text) {
		return $text;
	}

	public function __call($method, $args) {
		return '';
	}
	
}

/* Implement this Interface if you want your module to be a draggable content element when editing pages 
 * Also includes basic module behaviour. Overwrite where required.
 */
abstract class ContentElement extends BasicModule {
	var $pageId;
	var $moduleId;
	var $elementId;
	// Map of methods that your js-module requires
	static $methodMap = Array('save'=>'saveElement');
	
	// Class Constructor
	function __construct($moduleId, $pageId, $elementId = 0) {
		$this->moduleId = $moduleId;
		$this->pageId = $pageId;
		$this->elementId = $elementId;
	}
	
	function databaseTable() { return 'undefined'; }
	
	/* Once the user drops a content element, this function will be called.
	 * Builds a content element and returns the generated content
	 * parameter: id of the content element to be edited or -1 if a new element should be created
	 * returns: String of the HTML content
	 */
	// does not make use of pageID/position. This information is stored in the anego_page_element table
	public function createElement($position) {
		global $cfg;
		
		$q = "INSERT INTO " . $this->databaseTable() . " (value) VALUES ('')";
		$res = mysql_query($q) or
			BailSQL("Failed inserting element", $q);

		$this->elementId = mysql_insert_id();
		
		return Array("id" => $this->elementId, "html" => $this->generateContent($this->elementId));
	}
	
	/* Called when the user presses Save when editing that content element */
	public function saveElement($html) {
		if (! $this->elementId) return "300\nMissing element id";
		
		if (get_magic_quotes_gpc())
			$html = stripslashes($html);
				
		$q = "UPDATE " . $this->databaseTable() . " SET value='" . mysql_real_escape_string($html) . "' WHERE idx=" . $this->elementId;
		mysql_query($q) or
			BailErr("Failed saving element ", mysql_error());
		
		return "200\nok";
	}
	
	/* Should generate the HTML content when called.
	 * parameter: id of the content element to be generated	
	 * returns: String of the HTML content
	 * $elementId is cleaned via intval() so you don't have to worry about SQL injection 
	 */
	public function generateContent() {
		global $cfg;
		$q = "SELECT value FROM ".$this->databaseTable()." WHERE idx=" . $this->elementId;
		$res = mysql_query($q) or
			BailErr("Failed deleting element", $q);
		list($str) = mysql_fetch_array($res);

		return $str;
	}
	
	/* $elementId is cleaned via intval() so you don't have to worry about SQL injection */
	public function deleteElement() {
		global $cfg;
		$q = "DELETE FROM ".$this->databaseTable()." WHERE idx=" . $this->elementId;
		mysql_query($q) or
			BailErr("Failed deleting element", $q);
		return true;
	}
	
	/* Function that should return all required hooks and install settings of the module */
	public static function installModule() {
		return Array();
	}
	
	public static function moduleInfos($language) {
		return array(
			"name" => "",
			"description" => ""
		);
	}
	
}

class PageManager {
	// path WITH trailing slash - the path in the js code is static though (and maybe other places), so you cant really change this :p
	private $modulePath = 'modules/';
	
	/* Array of loaded and/or existing modules */
	/* Structure:
	$mid = Module ID (string) - name of the directory and .php file
	
	$this->loadedModules[$mid] = array('name'=>$header['Plugin Name'], 
									 'image'=>$header['Plugin Image'],
									 'type'=>$header['Plugin Type'],
									 'plugin_uri'=>$header['Plugin URI'],
									 'description'=>$header['Description'],
									 'version'=>$header['Version'],
									 'author'=>$header['Author'],
									 'cnfig'=>plugin configuration from installModule(),
									 'installed'=>true|false);

	*/
	private $loadedModules = false;
		
	function PageManager() {
		$this->loadModules();
	}
	
	// Returns array of loaded modules (false when modules aren't loaded)
	function getModules($type = null) {
		$modules = array();
		
		if ($type) {
			foreach($this->loadedModules as $module) {
				if(trim($module['type']) == $type) {
					$modules[] = $module;
				}
			}
		} else {
			$modules = $this->loadedModules;
		}
		
		return $modules;
	}
	
	function moduleClassnameById($mid) {
		foreach($this->loadedModules['info'] as $module)
			if($module['moduleId']==$mid)
				return $modules['classname'];
	}
	
	
	/* Retrieves general information about the content element modules as well as information of 
	   the meaning of the individual content elements that are on the page of given id */
	function contentElementModules($page_id) {
		global $cfg;
		
		$page_id = intval($page_id);
		
		$ret = array('modules' => array());
		foreach ($this->loadedModules as $mID => $module) {
			if (trim($module['type']) == 'ContentElement')
				$ret['modules'][] = array(
					'mid' => $mID,
					'name' => $module['name'],
					'image' => $this->modulePath . $mID . '/' . $module['image']
				);
		}


		// Send the source too since its not much more overhead
		$q = "SELECT content_prepared, name  FROM ".PAGES." WHERE idx=$page_id";
		$res = mysql_query($q) or
			BailErr("Failed getting page content for editing",$q);
		$row = mysql_fetch_array($res);
		
		
		$ret['title'] = $row['name'];
		
		return $ret;
	}
	
	/* Rebuilds a page's HTML by calling each individual module generateContent() method and stitching that together */
	function generatePage($page_id) {
		$page_id = intval($page_id);
		
		$q = "SELECT * FROM ".PAGE_ELEMENT." WHERE page_id=$page_id ORDER BY position";
		$res = mysql_query($q) or
			BailErr("Failed getting page elements for page generation", $q);
		
		$txt = '';
		while ($row = mysql_fetch_array($res)) {
			$mid = $row['module_id'];
			if(!isset($this->loadedModules[$mid])) {
				BailErr("Cannot recreate page as there is a module missing!");
			}
			
			include_once($this->modulePath . $mid . '/' . $mid . '.php');
			
			$ce = new $mid($page_id, $row['element_id']);
			$txt.= '<div id="' . $mid.'_' . $row['element_id'] . '" class="contentElement ceDraggable">' . $ce->generateContent() . '</div>';
		}
		
		$q = "UPDATE ".PAGES." SET content_prepared='" . mysql_real_escape_string($txt) . "' WHERE idx=$page_id";
		$res = mysql_query($q) or
			BailErr("Failed generating page",$q);
			
		return $txt;
	}
	
	// Installs a module
	function installModule($f) {
		if (file_exists($this->modulePath . $f . '/' . $f . '.php')) {
			
			
			// Load the file and get install settings
			include_once($this->modulePath . $f . '/' . $f . '.php');
			
			// Todo: use SuperClosure.class.php here to serialize the anonymous function hooks
			//$install_config=$f::installModule(); // this syntax only works in php5.3 
			// ...and eval() is always a potential security hole :/
			eval('$install_config = ' . $f . '::installModule();');
			
			include_once('lib/jsmin.php');
			
			// Todo: New module install system with anonymous functions for hooks as well as better js loading code over the js loader
			$this->loadModule($f, array(
				'config'		=> $install_config,
				'installed'		=> true
			));
			
			$fp = @fopen('var/installed_modules','w') or
				exit("400\nCannot open var/installed_modules for writing");
			
			fwrite($fp, serialize($this->loadedModules));
			fclose($fp);
			
			return true;
		} else {
			return false;
		}
	}
	
	
	function loadModule($name, $moredata) {
		global $cfg;
		$lng = $cfg["interfacelanguage"];
		
		$header = $this->parseHeader(file_get_contents($this->modulePath . $name. '/' . $name . '.php'));
		
		if (empty($header['Plugin Name'])) {
			echo "error loading module {$name} - plugin name not found!";
		}
		
		include_once($this->modulePath . $name . '/' . $name . '.php');
		
		eval('$moduleinfos = ' . $name . "::moduleInfos('{$lng}');");
		
		$this->loadedModules[$name] = array_merge(array(
			'name'			=> !empty($moduleinfos['name']) ? $moduleinfos['name'] : $header['Plugin Name'], 
			'image'			=> @$header['Plugin Image'],
			'type'			=> trim($header['Plugin Type']),
			'configurable'	=> @$header['Configurable'],
			'plugin_uri'	=> @$header['Plugin URI'],
			'description'	=> !empty($moduleinfos['description']) ? $moduleinfos['description'] : @$header['Description'],
			'version'		=> @$header['Version'],
			'author'		=> @$header['Author'],
		), $moredata);
	}
	
	// Uninstalls a module
	function uninstallModule($f) {
		if (!isset($this->loadedModules[$f])) {
			return true;
		}
		
		unset($this->loadedModules[$f]);
		
		$fp = @fopen('var/installed_modules', 'w') or
			exit("400\nCannot open var/installed_modules for writing");
				
		fwrite($fp,serialize($this->loadedModules));
		fclose($fp);
		
		return true;
	}
	
	// Gets all installed modules - code duplicated in ajax.php when reading pages as well as the javascript loader
	function loadModules() {
		if (file_exists('var/installed_modules')) {
			$this->loadedModules = unserialize(file_get_contents('var/installed_modules'));
		} else {
			$this->loadedModules = Array();
		}
	}

	// Populates the loadedModules array with all existing modules
	// This includes non-installed modules as well. (a installed flag is being set appropietly)
	function findModules() {
		$d = opendir($this->modulePath);
		while ($f = readdir($d)) {	
			if (is_dir($this->modulePath . $f) && !preg_match("#[^\w_0-9]+#", $f)) {
				$this->loadModule($f, array("installed" => isset($this->loadedModules[$f]['installed'])));
			}
		}
	}
	
	// Parses the Meta-Data that is contained in the comments at beginning of the module main php file
	function parseHeader($file) {
		if (preg_match("#/\*(.*)\*/#sU", $file, $cmt)) {
			if (preg_match_all("#^\s*([\w\s]*):\s*(.*)$#m", $cmt[1], $pairs)>0) {
				return array_combine($pairs[1], $pairs[2]);
			}
		}
		return Array();
	}
}