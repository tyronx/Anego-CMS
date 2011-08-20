<?

abstract class BasicModule { 
}

/* Implement this Interface if you want your plugin to be a draggable content element when editing pages */
abstract class ContentElement extends BasicModule {
	// Map of methods that your js-module requires
	static $methodMap = Array('save'=>'saveElement');
	
	function databaseTable() { return 'undefined'; }
	
	/* Once the user drops a content element or edits an existing one, this function will be called.
	 * Builds a content element and returns either the generated content or an interface to edit the element 
	 * parameter: id of the content element to be edited or -1 if a new element should be created
	 * returns: String of the HTML content
	 */
	public function createElement($pageId, $position) { // does not take use of pageID/position
		global $cfg;
		$q="INSERT INTO ".$this->databaseTable()." (value) VALUES ('')";
		$res=mysql_query($q) or
			BailAjax("Failed inserting element",$q);
			
		$id=mysql_insert_id();
		
		return Array("id"=>$id,"html"=>$this->generateContent($id));
	}
	
	function saveElement() {
		$id=intval($_POST['elid']);
		if(!$id) exit("500\nMissing id");
				
		if(get_magic_quotes_gpc ())
			$_POST['html']=stripslashes($_POST['html']);
				
		$q="UPDATE ".$this->databaseTable()." SET value='".mysql_real_escape_string($_POST['html'])."' WHERE idx=$id";
		mysql_query($q) or
			BailAjax("Failed saving element ",mysql_error());
		
		echo "200\nok";
		return true;
	}		
	
	/* Should generate the HTML content when called.
	 * parameter: id of the content element to be generated	
	 * returns: String of the HTML content
	 * $elementId is cleaned via intval() so you don't have to worry about SQL injection 
	 */
	public function generateContent($elementId) {
		global $cfg;
		$q = "SELECT value FROM ".$this->databaseTable()." WHERE idx=$elementId";
		$res=mysql_query($q) or
			BailAjax("Failed deleting element",$q);
		list($str)=mysql_fetch_array($res);
		return $str;
	}
	
	/* $elementId is cleaned via intval() so you don't have to worry about SQL injection */
	public function deleteElement($elementId) {
		global $cfg;
		$q = "DELETE FROM ".$this->databaseTable()." WHERE idx=$elementId";
		mysql_query($q) or
			BailAjax("Failed deleting element",$q);
		return true;
	}
	
	/* Function that should return all required hooks and install settings of the module */
	public static function installModule() {
		return Array();
	}
	
}

class PageManager {
	// path WITH trailing slash - the path in the js code is static though (and maybe other places), so you cant really change this :p
	private $modulePath = 'modules/';
	
	/* Array of loaded and/or existing modules */
	/* Structure:
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
	private $loadedModules;
		
	function PageManager() {
		$this->loadModules();
	}
	
	function modules() {
		return $this->loadedModules;
	}
	
	function moduleClassnameById($mid) {
		foreach($this->loadedModules['info'] as $module)
			if($module['moduleId']==$mid)
				return $modules['classname'];
	}
	
	
	/* Retrieves general information about the content element modules as well as information of the meaning of the individual content elements
	   that are on the page of given id */
	function contentElementModules($page_id) {
		global $cfg;
		
		$page_id=intval($page_id);
		//if(!$this->modulesPopulated) return '500'."\n".'Coding error, modules array not initialised yet';
		$ret=array('modules'=>array());
		
		foreach($this->loadedModules as $mID=>$module) {
			if(trim($module['type'])=='ContentElement')
				$ret['modules'][] = array('mid'=>$mID,
										  'name'=>$module['name'],
										  'image'=>$this->modulePath.$mID.'/'.$module['image']
										 );
		}

		// Send the source too since its not much more overhead
		$q="SELECT content_prepared, name  FROM ".PAGES." WHERE idx=$page_id";
		$res=mysql_query($q) or
			BailAjax("Failed getting page content for editing",$q);
		$row=mysql_fetch_array($res);
		if(isset($row['content_prepared'][0])) // faster than strlen()
			$ret['content'] = $row['content_prepared'];
		else 
			$ret['content'] = $this->generatePage($page_id);
		
		
		$ret['title'] = $row['name'];
		
		// Does not look like this is needed, so commenting it out
		/*$q="SELECT * FROM ".PAGE_ELEMENT." WHERE page_id=$page_id";
		$res=mysql_query($q) or
			BailAjax("Failed getting page content for editing",$q);
			
		while($row=mysql_fetch_array($res))
			$ret['elements'][] = array('pos'=>$row['position'],'module'=>$row['module_id'],'element_id'=>$row['element_id']);
		*/
		
		return $ret;
	}
	

	function generatePage($page_id) {
		$page_id=intval($page_id);
		
		$q="SELECT * FROM ".PAGE_ELEMENT." WHERE page_id=$page_id ORDER BY position";
		$res=mysql_query($q) or
			BailAjax("Failed getting page elements for page generation",$q);
		
		$txt = '';
		while($row=mysql_fetch_array($res)) {
			$mid=$row['module_id'];
			if(!isset($this->loadedModules[$mid])) BailAjax("Cannot recreate page as there is a module missing!");
			
			include_once($this->modulePath.$mid.'/'.$mid.'.php');
			
			// Yay, PHP5.3 goodness <3
			$ce = new $mid(); //eval("return new ".$mid."();");
			$txt.= '<div id="'.$mid.'_'.$row['element_id'].'" class="contentElement ceDraggable">'.$ce->generateContent($row['element_id'],$page_id).'</div>';
		}
		
		$q="UPDATE ".PAGES." SET content_prepared='".mysql_real_escape_string($txt)."' WHERE idx=$page_id";
		$res=mysql_query($q) or
			BailAjax("Failed generating page",$q);
			
		return $txt;
	}

	// Gets all installed modules - code duplicated in ajax.php when reading pages as well as the javascript loader
	function loadModules() {
		if(file_exists('var/installed_modules'))
			$this->loadedModules = unserialize(file_get_contents('var/installed_modules'));
		else $this->loadedModules = Array();			
	}
	
	function installModule($f) {
		if(file_exists($this->modulePath.$f.'/'.$f.'.php')) {
			$header = $this->parseHeader(file_get_contents($this->modulePath.$f.'/'.$f.'.php'));
			
			// Load the file and get install settings
			include_once($this->modulePath.$f.'/'.$f.'.php');
			
			// use SuperClosure.class.php here to serialize the anonymous function hooks
			$install_config=$f::installModule();
						
			include_once('lib/jsmin.php');
			
			// Todo: New module install system with anonymous functions for hooks as well as better js loading code over the js loader
			$this->loadedModules[$f] = array('name'=>$header['Plugin Name'], 
											 'image'=>@$header['Plugin Image'],
											 'type'=>trim($header['Plugin Type']),
											 'configurable'=>@$header['Configurable'],
											 'plugin_uri'=>@$header['Plugin URI'],
											 'description'=>@$header['Description'],
											 'version'=>@$header['Version'],
											 'author'=>@$header['Author'],
											 //'jscode'=>JSMin::minify(file_get_contents($this->modulePath.$f.'/'.$f.'.js')),
											 'config'=>$install_config,
											 'installed'=>true);
											 
											 
												
			$fp=@fopen('var/installed_modules','w') or
				exit("400\nCannot open var/installed_modules for writing");
			fwrite($fp,serialize($this->loadedModules));
			fclose($fp);
			return true;
		} else return false;
	}
	
	function uninstallModule($f) {
		if(!isset($this->loadedModules[$f])) return true;
		
		unset($this->loadedModules[$f]);
		
		$fp=@fopen('var/installed_modules','w') or
				exit("400\nCannot open var/installed_modules for writing");
				
		fwrite($fp,serialize($this->loadedModules));
		fclose($fp);
		
		return true;
	}

	// Finds all existing modules
	function findModules() {
		$d = opendir($this->modulePath);
		while($f=readdir($d)) {	
			if(is_dir($this->modulePath.$f) && !preg_match("#[^\w_0-9]+#",$f)) {
				$header = $this->parseHeader(file_get_contents($this->modulePath.$f.'/'.$f.'.php'));
				if(!isset($header['Plugin Name'])) continue;
				
				if(isset($this->loadedModules[$f])) $this->loadedModules[$f]['installed']=true;				
				else
					$this->loadedModules[$f] = array('name'=>$header['Plugin Name'], 
													 'image'=>@$header['Plugin Image'],
													 'type'=>trim($header['Plugin Type']),
													 'configurable'=>@$header['Configurable'],
													 'plugin_uri'=>@$header['Plugin URI'],
													 'description'=>@$header['Description'],
													 'version'=>@$header['Version'],
													 'author'=>@$header['Author'],
													 'installed'=>false);
													 
			}
		}
	}
	
	function parseHeader($file) {
		if(preg_match("#/\*(.*)\*/#sU",$file,$cmt))
			if(preg_match_all("#^\s*([\w\s]*):\s*(.*)$#m",$cmt[1],$pairs)>0)		
				return array_combine($pairs[1],$pairs[2]);
		
		return Array();
	}


}
?>