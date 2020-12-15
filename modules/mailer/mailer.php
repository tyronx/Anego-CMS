<?
/*
Plugin Name: Contact Form
Plugin Image: mailer.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: A simple contact form element
Version: 0.2
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/

addL10N('modules/mailer/lang/' . @$cfg['interfacelanguage'] . '.php');

class mailer extends ContentElement {
	var $config;

	static $methodMap = Array(
		'data'	=> 'getData',
		'save'	=> 'saveData'
	);

	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_mailer'; }
	
	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}
	
	public function createElement($position) {
		global $cfg, $sql_link;
		
		$res = mysqli_query($sql_link, 'SELECT name FROM '. PAGES . ' WHERE idx=' . $this->pageId);
		list($pagename) = mysqli_fetch_row($res);
		
		// Defaults
		$subject = sprintf(__('From %s: A person used your e-mail form'), $_SERVER['SERVER_NAME']);
		$template = sprintf(__('mailtemplate'), $pagename, $_SERVER['SERVER_NAME']);
		$formhtml = __('formhtml');
		$successMessage = __("Thank you for your message!");
		
		$q = "INSERT INTO " . $this->databaseTable() . " (subject, mailtemplate, formhtml, successmessage) VALUES (" .
			"'" . mysqli_real_escape_string($sql_link, $subject) . "', '" . mysqli_real_escape_string($sql_link, $template) . "'," .
			"'" . mysqli_real_escape_string($sql_link, $formhtml) . "', '" . mysqli_real_escape_string($sql_link, $successMessage) ."')";
		
		$res = mysqli_query($sql_link, $q) or
			BailSQL(__("Failed inserting element"), $q);

		$this->elementId = mysqli_insert_id($sql_link);
		
		return Array(
			"id" => $this->elementId,
			"html" => $this->generateContent($this->elementId)
		);
	}
	
	function getData() {
		global $sql_link;
		
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysqli_query($sql_link, $q) or BailSQL(__("Failed getting form code"), $q);
		$response = mysqli_fetch_assoc($res);
		return "200\n" . json_encode($response);
	}
	
	function saveData($data) {
		global $sql_link;
		
		$pairs = array();
		
		foreach($data as $pair) {
			if (get_magic_quotes_gpc()) {
				$pair['value'] = stripslashes($pair['value']);
			}
			$pairs[$pair['name']] = $pair['value'];
		}
		
		$q = "UPDATE " . $this->databaseTable() . " SET 
			subject='" . mysqli_real_escape_string($sql_link, $pairs['subject']) . "',
			recipient='" . mysqli_real_escape_string($sql_link, $pairs['recipient']) . "',
			mailtemplate='" . mysqli_real_escape_string($sql_link, $pairs['mailtemplate']) . "',
			successmessage='" . mysqli_real_escape_string($sql_link, $pairs['successmessage']) . "',
			hourlimit='" . intval($pairs['hourlimit']) . "',
			formhtml='" . mysqli_real_escape_string($sql_link, $pairs['formhtml']) . "' WHERE idx=" . $this->elementId;
			
		$res = mysqli_query($sql_link, $q) or 
			BailSQL(__("Failed updating mailer element"), $q);
		
		return "200\n" . $this->generateContent();
	}

	function generateContent() {
		global $cfg, $sql_link;
		
		$q = 'SELECT formhtml FROM ' . $this->databaseTable() .' WHERE idx=' . $this->elementId;
		$res = mysqli_query($sql_link, $q) or 
			BailSQL(__("Failed form code"), $q);
		
		list($form) = mysqli_fetch_row($res);
		
		$form = preg_replace("#(<(input|select|textarea).+name=('|\"))([^\\3]+)\\3#Usi", "\\1formdata[\\4]\\3", $form);
		
		$path = $cfg['path'];
		
		
		return 
			'<form name="mailer' . $this->elementId . '" onsubmit="return sendFormMail(' . $this->elementId . ')">' .
			'<input type="hidden" name="mailerid" value="' . $this->elementId . '"> ' .
			$form . 
			'<span class="sending" style="display:none;"><img style="vertical-align: middle;" src="' . $cfg['path'] . 'styles/default/img/progress_active.gif" alt="Ajax ">' . __('Sending...') . '</span>' .
			'</form>';
	}
	

	public static function installModule() {
		return array(
			'js' => array(
				'pageEdit'=>'maileredit.js',
				// js that should always load
				'load' => 'mailer.js',
			)
		);
	}
	
	public static function moduleInfos($language) {
		if ($language == "ger") {
			return array(
				"name" =>  "Kontaktformular",
				"description" => "Versand von Formulardaten an eine E-Mail Adresse"
			);
		} else {
			return array(
				"name" =>  "Contact form",
				"description" => "For sending form data to an email address"
			);
		}
	}
}