<?
/*
Plugin Name: Product list
Plugin Image: productlist.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Module for easy listing of products. WARNING: This is an Alpha Version, not recommended for production use.
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
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or BailSQL('Couldn\'t read settings', $q);
		$settings = mysql_fetch_array($res);
		
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
		global $cfg;
		$products = $this->getProducts();
		
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or BailSQL('Couldn\'t read settings', $q);
		$settings = mysql_fetch_array($res);

		
		$str = '<div class="products"';
		if ($settings['productswidth']) $str .= ' style="width:'.$settings['productswidth'].'px"';
		$str .= '>';
		
		foreach($products as $product) {
			$str .= '<div class="product"'.$this->getCSS().'>';
			
			if ($product['page_idx'] > 0) {
				if ($product['pageurl']) {
					$link = $cfg['pageLoad'] == 'ajax' ? '#pages/' . $product['pageurl'] : $cfg['path'] . $product['pageurl'];
				} else {
					$link = ($cfg['pageLoad'] == 'ajax' ? '#pages/' . $product['page_idx'] : $cfg['path'] . 'pages/' . $product['page_idx']);
				}
				$str .= '<a href="' . $link . '">';
			}
			
			if ($product['filename'])
				$str .= '<div class="productpicture"><img src="' . $cfg['path'] . $this->path . $product['filename'] . '" alt="' . $product['title'] . '"></div>';
			
			$str .= '<div class="producttitle"><img src="' . $cfg['path'] . 'styles/sytech/img/pfeil.gif">'. $product['title'] . '</div>';
			
			if ($product['page_idx']) {
				$str .= '</a>';
			}
			
			$str .= '</div>';
		}
		
		return $str . '</div><div class="afterproductlist"></div>';
	}
	

	function loadProducts() {
		global $cfg;
		$products = $this->getProducts();
		$productsbyIndex = array();
		
		foreach($products as &$product) {
			$product['filename'] = $product['filename'] ? $cfg['path'] . $this->path . $product['filename'] : '';
		}
		
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or BailSQL('Couldn\'t read settings', $q);
		$settings = mysql_fetch_array($res);

	
		return "200\n" . json_encode(array ("products" => $products, 'css' => $this->getCSS(true), 'productswidth' => $settings['productswidth']));
	}
	
	function saveProduct() {
		if (get_magic_quotes_gpc()) {
			$_POST['title'] = stripslashes($_POST['title']);
			$_POST['description'] = stripslashes($_POST['description']);
		}
		
		$desc = mysql_real_escape_string(@$_POST['description']);
		$title = mysql_real_escape_string(@$_POST['title']);
		// TODO SECURITY RISC
		$filename = @$_POST['filename'];
		$createpage = intval(@$_POST['createpage']);
		$productid = intval(@$_POST['productid']);
		
		
		if ($filename) {
			$q = 'SELECT filename FROM ' . $this->productTable() . ' WHERE idx='.$productid;
			$res = mysql_query($q) or BailSQL(__('Couldn\'t get product info'), $q);
			list ($oldfilename) = mysql_fetch_row($res);
			
			if ($oldfilename) {
				@unlink($this->path . $oldfilename);
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
			
			$filename = mysql_real_escape_string($filename);
		}
		
		$pageidx = '';
		$elementidx = '';
		if ($createpage == 1) {
			// Add page to the bottom of the root tree
			$q = "SELECT MAX(position) as pos FROM ".PAGES." WHERE parent_idx=".$this->pageId;
			$res = mysql_query($q) or
				BailErr(__('Failed getting position for new page'),$q);
			$row = mysql_fetch_array($res);
			$pos = $row['pos'] + 1;
			
			$q = "INSERT INTO ". PAGES . " (name, date, parent_idx, visibility, position, menu)
				VALUES ('$title','".time()."','".$this->pageId."', 3, '$pos', 'MAIN')";
			
			mysql_query($q) or BailSQL(__('Couldn\'t insert page'), $q);
			
			$pageidx = mysql_insert_id();
			
			$q = "INSERT INTO ". $this->richtextTable() ." (value) VALUES('".$desc."')";
			mysql_query($q) or BailSQL(__('Couldn\'t insert richtext'), $q);
			
			$elementidx = mysql_insert_id();
			
			$q = "INSERT INTO ". PAGE_ELEMENT . " (page_id, element_id, module_id, position) VALUES ('$pageidx', '$elementidx', 'richtext', 0)";
			mysql_query($q) or BailSQL(__('Couldn\'t insert richtext into page'), $q);
		}

		if ($_POST['createnew']) {
			$q = "INSERT INTO " . $this->productTable() . " (products_idx, page_idx, element_idx, title, description, filename) VALUES
				('".$this->elementId."','$pageidx','$elementidx','$title','$desc','$filename')";
				
			mysql_query($q) or BailSQL(__('Couldn\'t insert product'), $q);
			
			return "200\n" . mysql_insert_id();
			
		} else {
			$q = 'UPDATE ' . $this->productTable() . ' SET 
				description=\'' . $desc . '\', '
				. (($createpage == 1) ? 'page_idx='.$pageidx.', ' : '')
				. ($filename ? "filename='$filename', " : '')
				. 'title=\'' . $title . '\' WHERE idx=' . $productid;
		
		
			mysql_query($q) or BailSQL(__('Couldn\'t update product info'), $q);
			
			$q = 'SELECT page_idx, element_idx FROM ' . $this->productTable() . ' WHERE idx='.$productid;
			$res = mysql_query($q) or BailSQL(__('Couldn\'t get product info'), $q);
			list ($pageidx, $elementidx) = mysql_fetch_row($res);
			
			if ($elementidx) {
				$q = 'UPDATE ' . $this->richtextTable() . ' SET 
					value=\'' . $desc . '\' 
					WHERE idx=' . $elementidx;
			
				mysql_query($q) or BailSQL(__('Couldn\'t update product page info'), $q);
				
				if (!mysql_affected_rows()) {
					// Now rows affeccted when desc doesnt change O.o
					//return "500\nDescription on page got deleted. Can't update page anymore. Please contact System Administrator";
				}
				
				$pmg = new PageManager();
				$pmg->generatePage($pageidx);
			}
		}
		
		return "200\nok";
	}
	
	function deleteProduct() {
		$q = 'DELETE FROM '. $this->productTable() .' WHERE idx=' . intval($_POST['productid']);
		mysql_query($q) or BailSQL(__('Couldn\'t delete product'), $q);
		return "200\nok";
	}
	
	function getProducts() {
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
			WHERE products_idx = '. $this->elementId;

		$res = mysql_query($q) or BailSQL('Couldn\'t retrieve product list', $q);
		
		while($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
			$products[] = $row;
		}
		
		return $products;
	}
	
	function getSettings() {
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or BailSQL('Couldn\'t read settings', $q);
		
		return "200\n" . json_encode(mysql_fetch_array($res, MYSQL_ASSOC));
	}
	
	function saveSettings() {
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
			
		$res = mysql_query($q) or BailSQL('Couldn\'t update settings', $q);
		
		return "200\nok";
	}
	
	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'productlist.js'
			)
		);
	}
}
?>