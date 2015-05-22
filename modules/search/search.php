<?
/*
Plugin Name: Page Search
Plugin Image: search.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Basic Search function for page contents. Place it on the page where you want the search results to show up. Search bar itself needs to be implemented manually
Version: 0.1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/

// How many chars left and right should be displayed in the search result
define("DISPLAYRANGE", 50);

class search extends ContentElement {
	var $pageidswithsearch = -1;

	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'elements_search'; }

	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}

	/* Generates some javascript loading code which in return loads the blog through ajax, 
	   degrades properly to a text link
	*/
	function generateContent() {
		global $cfg;
		
		if (!empty($_GET["searchtext"])) {
			$searchresults = $this->getSearchResults($_GET["searchtext"]);
			$str = "";
			
			foreach ($searchresults as $page) {
				$str .= '<div class="searchresult">' .
					'<div class="pagename"><a href="' . $page['url'] . '">' . $page['name'] . '</a></div>' .
					'<div class="surroundingtext">'. $page['searchresults'] . '</div>' .
				'</div>';
			}
			return $str ;
		} else {
			return "";
		}
	}


	function getSearchResults($searchtext) {
		$res = mysql_query("select * from ".PAGES);

		$resultweights = array();
		$surroundingtexts = array();

		$pages = array();
		while ($page = mysql_fetch_array($res)) {
			if ($this->containsSearchElement($page["idx"])) continue;
			
			$pages[$page["idx"]] = $page;
			
			$match = $this->getMatch($searchtext, $page["content_prepared"]);
			if (empty($match)) continue;
			
			$resultweights[$page["idx"]] = $match["weight"];
			$surroundingtexts[$page["idx"]] = $match["surroundingtexts"];
		}
		
		arsort($resultweights);
		
		$sortedpages = array();
		foreach ($resultweights as $pageid => $weight) {
		
			$sortedpages[$pageid] = array(
				"searchresults" => $surroundingtexts[$pageid],
				"idx" => $pageid,
				"name" => $pages[$pageid]["name"],
				"url" => $pages[$pageid]["url"]
			);
		}
		
		return $sortedpages;
	}
	
	
	function containsSearchElement($pageid) {
		if ($this->pageidswithsearch == -1) {
			$this->pageidswithsearch = array();
			$res = mysql_query("SELECT page_id from ".PAGE_ELEMENT." WHERE module_id='search'");
			while ($row = mysql_fetch_array($res)) {
				$this->pageidswithsearch[$row["page_id"]] = 1;
			}
		}
		
		return !empty($this->pageidswithsearch[$pageid]);
	}


	
	
	function getMatch($searchtext, $text) {
		$text = strip_tags($text);
		
		$weight = 9;
		$occurrences = $this->fullSearchMatches($searchtext, $text);
		
		if (empty($occurrences)) { 
			$occurrences = $this->wordMatches($searchtext, $text);
			$weight = 3;
		}
		
		if (empty($occurrences)) {
			$occurrences = $this->anyMatches($searchtext, $text);
			$weight = 1;
		}
		
		if (empty($occurrences)) {
			return null;
		}
		
		$match = array(
			"weight" => count($occurrences) * $weight,
			"surroundingtexts" => ""
		);
		
		foreach ($occurrences as $occurrence) {
			// [0] = found text
			// [1] == position
			
			$match["surroundingtexts"] .= 
				'<p>' . 
				(($occurrence[1] > 0) ? "..." : "") .
				substr($text, max(0, $occurrence[1] - DISPLAYRANGE), DISPLAYRANGE) .
				'<span class="searchmatch">' . $occurrence[0] . '</span>' .
				substr($text, min(strlen($text) - DISPLAYRANGE, $occurrence[1] + strlen($occurrence[0])), DISPLAYRANGE) .
				((strlen($text) > $occurrence[1]) ? "..." : "") .
				'</p>'
			;
			
		}
		
		return $match;
	}
	
	function fullSearchMatches($searchtext, $text) {
		$occurences = array();
		
		if (preg_match_all("/(^|\s){$searchtext}($|\s)/i", $text, $matches, PREG_OFFSET_CAPTURE) > 0) {
			foreach ($matches[0] as $match) {
				$occurences[] = $match;
			}
		}
		
		return $occurences;
	}
	
	function wordMatches($searchtext, $text) {
		$occurences = array();
		
		$words = explode(" ", $searchtext);
		foreach ($words as $word) {
			$occurences = array_merge($this->fullSearchMatches($word, $text), $occurences);
		}
		
		return $occurences;
	}
	
	function anyMatches($searchtext, $text) {
		$occurences = array();
		
		$words = explode(" ", $searchtext);
		foreach ($words as $word) {
			if (preg_match_all("/{$searchtext}/i", $text, $matches, PREG_OFFSET_CAPTURE) > 0) {
				foreach ($matches[0] as $match) {
					$occurences[] = $match;
				}
			}
		}
		return $occurences;
	}
	
	// Always regenerate this page
	public function contentValidUntil() {
		return time() - 5000;
	}

	
	public static function installModule() {
		return array(
			'js' => array(
				'pageEdit' => 'search.js'
			),
			'css' => array(
				'pageView' => 'search.css'
			)
		);
	}
}
?>