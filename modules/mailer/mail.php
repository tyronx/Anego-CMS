<?
if (!isset($_POST['mailerid'])) {
	exit();
} else {
	chdir('../../');
	include('core.php');

	class MailTemplate extends Smarty {	
		function MailTemplate() {
			$this->template_dir = '';
			$this->compile_dir = 'tmp';
		}
	}


	sendMail();
}


function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_mailer'; }

function sendMail() {
	global $cfg;
	
	$mailerid = intval($_POST['mailerid']);
	
	
	$q = "SELECT * FROM " . databaseTable() . " WHERE idx=" . $mailerid;
	$res = mysql_query($q) or
		BailSQL('Failed getting Form Data from DB', $q);
		
	$row = mysql_fetch_assoc($res);
	
	if (!isset($row['recipient'])) {
		BailErr('Mailer: No receiver E-Mail in DB found. Missing entry or wrong id?');
	}
	
	$hrcount = $row['numsent_lasthour'];
	
	$currenthour = date('H') - date('i') - date('s');
	
	if($currenthour - $row['currenthour'] > 3600) {
		$hrcount = 0;
	}
	
	if ($hrcount >= $row['hourlimit']) {
		BailErr('Sorry, hourly contact request limit reached. Please try again later.');
	}
	
	
	$mail = new MailTemplate();

	foreach($_POST['formdata'] as $name=>$value) {
		$mail->assign($name,$value); 
	}
		
	$m = $mail->fetch('mail'.$id.'.tpl');
	
	$headers  =	'MIME-Version: 1.0' . "\n";
	$headers .=	'Content-type: text/plain; charset=iso-8859-1' . "\n";  
	$headers .=	'From: ' . $_SERVER['SERVER_NAME'] . ' Mailer <no-reply@'.$_SERVER['SERVER_NAME'].'>' . "\n" .
				'Reply-To: ' . $_SERVER['SERVER_NAME'] . ' Mailer <no-reply@'.$_SERVER['SERVER_NAME'].'>' . "\n" .
				'X-Mailer: PHP/' . phpversion()."\n";    

	
	if(! @mail($row['recipient'], $row['subject'], utf8_decode($m), $headers)) {
		BailErr('I\'m sorry, but I was unable to send out a mail. Something must be wrong with the server configuration');
	}
	
	
	mysql_query("UPDATE " . databaseTable() . " SET 
			numsent_lasthour=$hrcount
			numsent_total=numsent_total+1
			currenthour=$currenthour
		WHERE idx=$mailerid");
	
	exit("200\nThank you for your message!");
}

?>