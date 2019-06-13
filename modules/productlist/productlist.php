<?
/*
Plugin Name: Product list
Plugin Image: productlist.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Module for easy listing of products. 
Version: 0.1alpha
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/

class productlist extends ContentElement {
	var $path;
	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_products'; }
	function productTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_product'; }
	function richtextTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_richtext'; }
	
	static $methodMap = Array(
		'lp'	=> 'loadProducts',
		'sp'	=> 'saveProduct',
		'dp'	=> 'deleteProduct',
		'ss'	=> 'saveSettings',
		'gs'	=> 'getSettings'
	);	
	
	function __construct($pageId, $elementId = 0) {
		$this->path = FILESROOT . 'products/' . $elementId . '/';
		
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}
	
	function getCSS($nostyletag = false) {
		global $sql_link;
		
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysqli_query($sql_link, $q) or BailSQL('Couldn\'t read settings', $q);
		$settings = mysqli_fetch_array($res);
		
		$css = array();
		if ($settings['productwidth'] > 0) 
			$css[] = 'width: ' . $settings['productwidth'] . 'px;';
		if ($settings['productheight'] > 0) 
			$css[] = 'height: ' . $settings['productheight'] . 'px;';
		if ($settings['producthorispacing'] > 0) 
			$css[] = 'margin-right: ' . $settings['producthorispacing'] . 'px;';
		if ($settings['productvertispacing'] > 0) 
			$css[] = 'margin-bottom: ' . $settings['productvertispacing'] . 'px;';
		
		if (count($css)) {
			$css = implode(' ', $css);
			if (!$nostyletag) $css = 'style="' . $css . '"';
		} else {
			$css = '';
		}
		return $css;
	}

	function generateContent() {
		global $cfg, $sql_link;
		
		$products = $this->getProducts();
		
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysqli_query($sql_link, $q) or BailSQL('Couldn\'t read settings', $q);
		$settings = mysqli_fetch_array($res);

		
		$str = '<div class="products"';
		if ($settings['productswidth']) $str .= ' style="width:'.$settings['productswidth'].'px"';
		$str .= '>';
		
		foreach ($products as $product) {
			$str .= '<div class="product"'.$this->getCSS().'>';
			
			if ($product["targeturl"]) {
				
				$str .= '<a href="' . $product["targeturl"] . '">';
				
			} else {
			
				if ($product['page_idx'] > 0) {
					if ($product['pageurl']) {
						$link = $cfg['pageLoad'] == 'ajax' ? '#pages/' . $product['pageurl'] : $cfg['path'] . $product['pageurl'];
					} else {
						$link = ($cfg['pageLoad'] == 'ajax' ? '#pages/' . $product['page_idx'] : $cfg['path'] . 'pages/' . $product['page_idx']);
					}
					$str .= '<a href="' . $link . '">';
				}
			}
				
			if ($product['filename'])
				$str .= '<div class="productpicture"><img src="' . $cfg['path'] . $this->path . $product['filename'] . '" alt="' . $product['title'] . '"></div>';
			
			$str .= '<div class="producttitle">'. str_replace(array("  ", "\n"), array("&nbsp;&nbsp;", "<br>"), $product['title']) . '</div>';
			
			if ($product['page_idx'] || $product["targeturl"]) {
				$str .= '</a>';
			}
			
			$str .= '</div>';
		}
		
		return $str . '</div><div class="afterproductlist"></div>';
	}
	

	function loadProducts() {
		global $cfg, $sql_link;
		
		$products = $this->getProducts();
		$productsbyIndex = array();
		
		foreach($products as &$product) {
			$product['filename'] = $product['filename'] ? $cfg['path'] . $this->path . $product['filename'] : '';
		}
		
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysqli_query($sql_link, $q) or BailSQL('Couldn\'t read settings', $q);
		$settings = mysqli_fetch_array($res);

	
		$q = "SELECT idx,name,url FROM ".PAGES." WHERE nolink=0 order by name asc";
		$res = mysqli_query($sql_link, $q) or BailSQL('Couldn\'t read pages', $q);
		
		$pages = array();
		while ($row = mysqli_fetch_array($res)) {
			$pages[] = $row;
		}
	
		return "200\n" . json_encode(array ("pages" => $pages, "products" => $products, 'css' => $this->getCSS(true), 'productswidth' => $settings['productswidth']));
	}
	
	function saveProduct() {
		global $sql_link;
		
		if (get_magic_quotes_gpc()) {
			$_POST['title'] = stripslashes($_POST['title']);
			$_POST['description'] = stripslashes($_POST['description']);
		}
		
		$desc = mysqli_real_escape_string($sql_link, @$_POST['description']);
		$title = mysqli_real_escape_string($sql_link, @$_POST['title']);
		// TODO SECURITY RISC
		$filename = @$_POST['filename'];
		$target = intval(@$_POST['target']);
		$targeturl = mysqli_real_escape_string($sql_link, @$_POST['targeturl']);
		$productid = intval(@$_POST['productid']);
		$pageidx = intval(@$_POST['pageidx']);
		
		$oldproduct = null;
		$elementidx = 'null';
		if ($productid) {
			$q = 'SELECT * FROM ' . $this->productTable() . ' WHERE idx='.$productid;
			$res = mysqli_query($sql_link, $q) or BailSQL(__('Couldn\'t get product info'), $q);
			$oldproduct  = mysqli_fetch_row($res);
			
			$elementidx = $oldproduct["element_idx"];
		}
		
		if ($filename) {
			if (!empty($oldproduct['filename'])) {
				@unlink($this->path . $oldproduct['filename']);
			}
			
			$filename = prettyName($filename, $this->path);

			if (!is_dir(FILESROOT . 'products/')) {
				mkdir(FILESROOT . 'products/');
			}
			if (!is_dir($this->path)) {
				mkdir($this->path);
			}
			$fp = fopen($this->path . $filename, 'w');
			fwrite($fp, base64_decode(substr($_POST['filedata'], strpos($_POST['filedata'], 'base64') + 6)));
			
			$filename = mysqli_real_escape_string($sql_link, $filename);
		}
		
		if ($target == 0 || $target == 3) {
			$pageidx = 'null';
			$elementidx = 'null';
		}
		
		if ($target == 1) {
			$elementidx = 'null';
		}

		
		if ($target == 2) {
			// Create new page
			if(!$productid || empty($oldproduct["page_idx"])) {
				// Add page to the bottom of the root tree
				$q = "SELECT MAX(position) as pos FROM ".PAGES." WHERE parent_idx=".$this->pageId;
				$res = mysqli_query($sql_link, $q) or
					BailErr(__('Failed getting position for new page'),$q);
				$row = mysqli_fetch_array($res);
				$pos = $row['pos'] + 1;
				
				$q = "INSERT INTO ". PAGES . " (name, date, parent_idx, visibility, position, menu)
					VALUES ('$title','".time()."','".$this->pageId."', 3, '$pos', 'MAIN')";
				
				mysqli_query($sql_link, $q) or BailSQL(__('Couldn\'t insert page'), $q);
				
				$pageidx = mysqli_insert_id($sql_link);
				
				$q = "INSERT INTO ". $this->richtextTable() ." (value) VALUES('".$desc."')";
				mysqli_query($sql_link, $q) or BailSQL(__('Couldn\'t insert richtext'), $q);
				
				$elementidx = mysqli_insert_id($sql_link);
				
				$q = "INSERT INTO ". PAGE_ELEMENT . " (page_id, element_id, module_id, position,style,padding,margin,alignment) VALUES ('$pageidx', '$elementidx', 'richtext', 0, '', '', '', '')";
				mysqli_query($sql_link, $q) or BailSQL(__('Couldn\'t insert richtext into page'), $q);
			
			// Update existing page
			} else {
				if ($elementidx) {
					$q = 'UPDATE ' . $this->richtextTable() . ' SET 
						value=\'' . $desc . '\' 
						WHERE idx=' . $elementidx;
				
					mysqli_query($sql_link, $q) or BailSQL(__('Couldn\'t update product page info'), $q);
					
					$pmg = new PageManager();
					$pmg->generatePage($pageidx);
				}
			}
		}
		
		if ($target != 3) {
			$targeturl = "";
		}
		
		
		// Insert/Update product itself
		if ($productid) {
			$q = 'UPDATE ' . $this->productTable() . ' SET 
				description=\'' . $desc . '\', '
				. 'page_idx='.$pageidx.', '
				. 'targeturl=\''.$targeturl.'\', '
				. 'element_idx='.$elementidx.', '
				. ($filename ? "filename='$filename', " : '')
				. 'title=\'' . $title . '\' WHERE idx=' . $productid;
				
			mysqli_query($sql_link, $q) or BailSQL(__('Couldn\'t insert product'), $q);
			
			return "200\n" . mysqli_insert_id($sql_link);
			
		} else {
			$q = "INSERT INTO " . $this->productTable() . " (products_idx, page_idx, element_idx, title, description, filename, targeturl) VALUES
				('".$this->elementId."',$pageidx,$elementidx,'$title','$desc','$filename', '$targeturl')";
			
			mysqli_query($sql_link, $q) or BailSQL(__('Couldn\'t insert product'), $q);
			
			return "200\nok";
		}
	}
	
	function deleteProduct() {
		global $sql_link;
		
		$q = 'DELETE FROM '. $this->productTable() .' WHERE idx=' . intval($_POST['productid']);
		mysqli_query($sql_link, $q) or BailSQL(__('Couldn\'t delete product'), $q);
		return "200\nok";
	}
	
	function getProducts() {
		global $sql_link;
		
		$products = array();
		
		$q = '
			SELECT 
				product.*,
				COALESCE(richtext.value, description) as syncdescription,
				page.url as pageurl, 
				page.idx as pageidx 
			FROM ' . $this->productTable() . ' product 
			LEFT JOIN ' . PAGES . ' page ON (product.page_idx = page.idx) 
			LEFT JOIN ' . $this->richtextTable() . ' richtext ON (product.element_idx = richtext.idx) 
			WHERE products_idx = '. $this->elementId . '
			ORDER BY products_idx';
			
		$res = mysqli_query($sql_link, $q) or BailSQL('Couldn\'t retrieve product list', $q);
		
		while($row = mysqli_fetch_assoc($res)) {
			$products[] = $row;
		}
		
		return $products;
	}
	
	function getSettings() {
		global $sql_link;
		
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysqli_query($sql_link, $q) or BailSQL('Couldn\'t read settings', $q);
		
		return "200\n" . json_encode(mysqli_fetch_assoc($res));
	}
	
	function saveSettings() {
		global $sql_link;
		
		$productswidth = intval($_POST['productswidth']);
		$productwidth = intval($_POST['productwidth']);
		$productheight = intval($_POST['productheight']);
		$producthorispacing = intval($_POST['producthorispacing']);
		$productvertispacing = intval($_POST['productvertispacing']);
		
		$q = 'UPDATE '. $this->databaseTable() . " SET
			productswidth = $productswidth,
			productwidth = $productwidth,
			productheight = $productheight,
			producthorispacing = $producthorispacing,
			productvertispacing = $productvertispacing
			WHERE idx=" . $this->elementId;
			
		$res = mysqli_query($sql_link, $q) or BailSQL('Couldn\'t update settings', $q);
		
		return "200\nok";
	}
	
	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>array('lang/%lng.js', 'productlist.js')
			)
		);
	}
	
	
	public static function moduleInfos($language) {
		if ($language == "ger") {
			return array(
				"name" =>  "Produkte",
				"description" => "Erlaubt das erstellen einfacher Produktlisten"
			);
		} else {
			return array(
				"name" =>  "Products",
				"description" => "Allows creation of simple Product lists"
			);
		}
	}
}
?>