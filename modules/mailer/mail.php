<?
if (!isset($_POST['mailerid'])) {
	exit();
} else {
	chdir('../../');
	$cfg['interfacelanguage'] = '';
	include('core.php');
	addL10N('modules/mailer/lang/' . $cfg['interfacelanguage'] . '.php');

	class MailTemplate extends SmartyBC {	
		function __construct() {
			parent::__construct();
			
			$this->template_dir = '';
			$this->compile_dir = 'tmp';
			$this->register_resource('string', array(
				'string_get_template',
				'string_get_timestamp',
				'string_get_secure',
				'string_get_trusted'));
		}
	}


	sendMail();
}


/* String input for smarty templates */
function string_get_template($tpl_name, &$tpl_source, $smarty) {
    $tpl_source = $tpl_name;
    return true;
}

function string_get_timestamp($tpl_name, &$tpl_timestamp, $smarty) {
    $tpl_timestamp = time();
    return true;
}

function string_get_secure($tpl_name, $smarty) {
    return true;
}

function string_get_trusted($tpl_name, $smarty) {
    // not used for templates
}

function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_mailer'; }

function sendMail() {
	global $cfg, $sql_link;
	
	$mailerid = intval($_POST['mailerid']);
	
	
	$q = "SELECT * FROM " . databaseTable() . " WHERE idx=" . $mailerid;
	$res = mysqli_query($sql_link, $q) or
		BailSQL('Failed getting Form Data from DB', $q);
		
	$row = mysqli_fetch_assoc($res);
	
	if (!isset($row['recipient'])) {
		BailErr(__('Mailer: No receiver E-Mail in DB found. Missing entry or wrong id?'));
	}
	
	$hrcount = $row['numsent_lasthour'];
	
	$currenthour = time() - 60*date('i') - date('s');
	
	if ($currenthour - $row['currenthour'] > 3600) {
		$hrcount = 0;
	}
	
	if ($hrcount >= $row['hourlimit']) {
		BailErr(__('Sorry, hourly contact request limit reached. Please try again later.'));
	}
	
	
	$mail = new MailTemplate();

	foreach ($_POST['formdata'] as $name=>$value) {
		$mail->assign($name,$value); 
	}
	
	$m = $mail->fetch('string:' . $row['mailtemplate']);
	
	$replyto = $_SERVER['SERVER_NAME'] . ' Mailer <no-reply@'.$_SERVER['SERVER_NAME'].'>';
	if (strstr($_POST['formdata']['email'], "@")) {
		$replyto = $_POST['formdata']['email'];
	}
	
	$headers  =	'MIME-Version: 1.0' . "\n";
	$headers .=	'Content-type: text/plain; charset=iso-8859-1' . "\n";  
	$headers .=	'From: ' . $_SERVER['SERVER_NAME'] . ' Mailer <no-reply@'.$_SERVER['SERVER_NAME'].'>' . "\n" .
				'Reply-To: ' . $replyto . "\n" .
				'X-Mailer: PHP/' . phpversion()."\n";    

	
	if(! @mail($row['recipient'], $row['subject'], utf8_decode($m), $headers)) {
		BailErr(__('I\'m sorry, but I was unable to send out a mail. Something must be wrong with the server configuration'));
	}
	
	$hrcount++;
	
	$q = "UPDATE " . databaseTable() . " SET 
			numsent_lasthour=$hrcount,
			numsent_total=numsent_total+1,
			currenthour=$currenthour
		WHERE idx=$mailerid";
		
	mysqli_query($sql_link, $q);
	
	
	$successMessage = @$row['successmessage'];
	if (!strlen($successMessage)) {
		$successMessage = __("Thank you for your message!");
	}
	
	exit("200\n" . $successMessage);
}

?>