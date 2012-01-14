<?
/*
Plugin Name: Blog / News
Plugin Image: blog.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Simple blog / news content element. Write/edit/delete blog entries and allow users to comment on them.
Version: 0.1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/
class blog extends ContentElement {
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'elements_blog'; }

	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}

	/* Generates some javascript loading code which in return loads the blog through ajax, 
	   degrades properly to a text link
	*/
	function generateContent() {
		global $cfg,$language;
		
		include('modules/blog/'.$language.'.php');
		
		if($cfg['fancyURLs']) {
			$noscript = '<h1><a href="mdblog-v' . $this->elementId . '">' . $lng['blog']['gotomyblog'] . '</a></h1>';
		} else {
			// Todo: Build something that allows no-fancy-urls no-javascript-browser/crawlers to open the blog
			$noscript = '';
		}
		
		$elemId = $this->elementId;
	
		return <<<EOT
		<script type="text/javascript">
		$(function() {
			blogfuncs.initBlog($elemId);
		});
		</script>
		<noscript>$noscript</noscript>
		<div class="blog" id="blogc_$elemId"></div>
		<div class="blogNaviBox" id="blognav_$elemId"></div>
EOT;
	}
	
	public static function installModule() {
		return Array(
/*			'hooks'=>Array(
				'jspreLoad'=>function() {
					return 
				}
			)*/
			// Determines wether requests in the form of #[installed module name][number] should be forwarded to the module script
			'allowdirectLoad' => true,
			'js' => Array(
				// js that should always load
				'load' => Array('%lng.js'),
				// js to be loaded when page is being edited
				'pageEdit' => Array('blog.js'),
				// js to be loaded when page is viewed & a mod is logged on
				'pageMod' => Array('admin.js'),
				// js to be loaded when a visitor views the page
				'pageView' => Array('admin.js')
			)
		);
	}
}
?>