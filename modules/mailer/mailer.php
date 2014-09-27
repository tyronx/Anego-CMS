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
		global $cfg;
		
		$res = mysql_query('SELECT name FROM '. PAGES . ' WHERE idx=' . $this->pageId);
		list($pagename) = mysql_fetch_row($res);
		
		// Defaults
		$subject = sprintf(__('From %s: A person used your e-mail form'), $_SERVER['SERVER_NAME']);
		$template = sprintf(__('mailtemplate'), $pagename, $_SERVER['SERVER_NAME']);
		$formhtml = __('formhtml');
		$successMessage = __("Thank you for your message!");
		
		$q = "INSERT INTO " . $this->databaseTable() . " (subject, mailtemplate, formhtml, successmessage) VALUES (" .
			"'" . mysql_real_escape_string($subject) . "', '" . mysql_real_escape_string($template) . "'," .
			"'" . mysql_real_escape_string($formhtml) . "', '" . mysql_real_escape_string($successMessage) ."')";
		
		$res = mysql_query($q) or
			BailSQL(__("Failed inserting element"), $q);

		$this->elementId = mysql_insert_id();
		
		return Array(
			"id" => $this->elementId,
			"html" => $this->generateContent($this->elementId)
		);
	}
	
	function getData() {
		$q = 'SELECT * FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or BailSQL(__("Failed getting form code"), $q);
		$response = mysql_fetch_assoc($res);
		return "200\n" . json_encode($response);
	}
	
	function saveData($data) {
		$pairs = array();
		
		foreach($data as $pair) {
			if (get_magic_quotes_gpc()) {
				$pair['value'] = stripslashes($pair['value']);
			}
			$pairs[$pair['name']] = $pair['value'];
		}
		
		$q = "UPDATE " . $this->databaseTable() . " SET 
			subject='" . mysql_real_escape_string($pairs['subject']) . "',
			recipient='" . mysql_real_escape_string($pairs['recipient']) . "',
			mailtemplate='" . mysql_real_escape_string($pairs['mailtemplate']) . "',
			successmessage='" . mysql_real_escape_string($pairs['successmessage']) . "',
			hourlimit='" . intval($pairs['hourlimit']) . "',
			formhtml='" . mysql_real_escape_string($pairs['formhtml']) . "' WHERE idx=" . $this->elementId;
			
		$res = mysql_query($q) or 
			BailSQL(__("Failed updating mailer element"), $q);
		
		return "200\n" . $this->generateContent();
	}

	function generateContent() {
		global $cfg;
		
		$q = 'SELECT formhtml FROM ' . $this->databaseTable() .' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or 
			BailSQL(__("Failed form code"), $q);
		
		list($form) = mysql_fetch_row($res);
		
		$form = preg_replace("#(<(input|select|textarea).+name=('|\"))([^\\3]+)\\3#Usi", "\\1formdata[\\4]\\3", $form);
		
		$path = $cfg['path'];
		$js = <<<EOT
		<script type="text/javascript">
			sendFormMail = function(id) {
				\$form = \$('form[name="mailer' + id + '"]');
				$.post('modules/mailer/mail.php', \$form.serialize(),
					function(data) {
						var aw;
						if(aw = GetAnswer(data)) {
							\$form.html(aw);
						}
						$('.sending', \$form).hide();
						$('input[type="submit"], button[type="submit"]', \$form).removeAttr('disabled');
					}
				);
				
				$('.sending', \$form).show();
				$('input[type="submit"], button[type="submit"]', \$form).attr('disabled', 'disabled');
				
				
				return false;
			}
			</script>
EOT;
		
		return 
			'<form name="mailer' . $this->elementId . '" onsubmit="return sendFormMail(' . $this->elementId . ')">' .
			'<input type="hidden" name="mailerid" value="' . $this->elementId . '"> ' .
			$form . 
			'<span class="sending" style="display:none;"><img style="vertical-align: middle;" src="' . $cfg['path'] . 'styles/default/img/progress_active.gif" alt="Ajax ">' . __('Sending...') . '</span>' .
			'</form>' . $js;
	}
	

	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'mailer.js'
			)
		);
	}
}