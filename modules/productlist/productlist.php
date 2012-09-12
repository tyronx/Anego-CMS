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
		'dp'	=> 'deleteProduct'
	);	
	
	function __construct($pageId, $elementId = 0) {
		$this->path = FILESROOT . 'products/' . $elementId . '/';
		
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}

	function generateContent() {
		global $cfg;
		$products = $this->getProducts();
		
		$str = '<div class="products">';
		
		foreach($products as $product) {
			$str .= '<div class="product">';
			
			if ($product['page_idx'] > 0) {
				if ($product['pageurl']) {
					$link = $cfg['pageLoad'] == 'ajax' ? '#pages/' . $product['pageurl'] : $cfg['path'] . $product['pageurl'];
				} else {
					$link = ($cfg['pageLoad'] == 'ajax' ? '#pages/' . $product['page_idx'] : $cfg['path'] . 'pages/' . $product['page_idx']);
				}
				$str .= '<a href="' . $link . '">';
			}
			
			if ($product['filename'])
				$str .= '<div class="productpicture"><img src="' . $this->path . $product['filename'] . '" alt="' . $product['title'] . '"></div>';
			
			$str .= '<div class="producttitle"><img src="' . $cfg['path'] . 'styles/sytech/img/pfeil.gif">'. $product['title'] . '</div>';
			
			if ($product['page_idx']) {
				$str .= '</a>';
			}
			
			$str .= '</div>';
		}
		
		return $str . '</div><div class="bothclear"></div>';
	}
	

	function loadProducts() {
		$products = $this->getProducts();
		$productsbyIndex = array();
		
		foreach($products as &$product) {
			$product['filename'] = $product['filename'] ? $this->path . $product['filename'] : '';
		}
	
		return "200\n" . json_encode(array ("products" => $products));
	}
	
	function saveProduct() {
		$desc = mysql_real_escape_string(@$_POST['description']);
		$title = mysql_real_escape_string(@$_POST['title']);
		// TODO SECURITY RISC
		$filename = mysql_real_escape_string(@$_POST['filename']);
		$createpage = intval(@$_POST['createpage']);
		$productid = intval(@$_POST['productid']);
		
		
		if ($filename) {
			if (!is_dir(FILESROOT . 'products/')) {
				mkdir(FILESROOT . 'products/');
			}
			if (!is_dir($this->path)) {
				mkdir($this->path);
			}
			$fp = fopen($this->path . $_POST['filename'], 'w');
			fwrite($fp, base64_decode(substr($_POST['filedata'], strpos($_POST['filedata'], 'base64') + 6)));
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
	}
	
	function getProducts() {
		$products = array();
		
		$q = 'SELECT product.*, page.url as pageurl, page.idx as pageidx FROM ' . $this->productTable() . ' product LEFT JOIN ' . PAGES . ' page ON (product.page_idx = page.idx) WHERE products_idx = '. $this->elementId;
		$res = mysql_query($q) or BailSQL('Couldn\'t retrieve product list', $q);
		
		while($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
			$products[] = $row;
		}
		
		return $products;
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