<?
/*
Plugin Name: Formmailer
Plugin URI: http://www.anego.at
Plugin Type: General
Configurable: yes
Description: Simple formmailer, which has a version number of 0.2 in order to stand out from the others
Version: 0.2
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/

/* Mail contact form tool */
/* Required (hidden) fields to use this mailer */

// m = 1
// id = form id
// res = response text after mail has been sent


if(isset($_GET['a']) || isset($_POST['a'])) {
	chdir('../../');
	include("core.php");
	
	if(isset($_GET['a'])) $a=$_GET['a'];
	else $a=$_POST['a'];
	
	$config=Array();
	$config['mailer_hrlimit'] = 5;
	$config['mailer_subject'] = 'Contact request from '.$_SERVER['SERVER_NAME'];
	$config['mailer_to']='';
	$config['mailer_template']= 'Hello

A person has used the contact form on '.$_SERVER['SERVER_NAME'].'

Name: {$reName}
E-Mail/Phone Number: {$email}

Request:
{$request}

 -------------------------------------------------------------
 
 - Anego CMS Mailer on '.$_SERVER['SERVER_NAME'].'
';
	
	$q="SELECT * FROM ".SETTINGS." WHERE name LIKE 'mailer_%'";
	$res=mysql_query($q)
		or BailErr('Cannot get settings :/',$q);
		
	while($row=mysql_fetch_array($res))
		$config[$row['name']]=$row['value'];
		
	switch($a) {
		case 'getconf':
			echo "200\n";
?>
		<form action="#">
		<p><label for="recip">Send to address:</label><br>
		<input type="text" name="recip" size="30" value="<?=$config['mailer_to']?>"></p>
		<p><label for="subject">Send with subject:</label><br>
		<input type="text" name="subject" size="60" value="<?=$config['mailer_subject']?>"></p>
		<p><label for="template">Mail template:</label><br>
		<textarea cols="80" rows="15" name="template"><?=$config['mailer_template']?></textarea></p><br>
		<label for="limit">Limit mail sending to </label> <input type="text" name="hrlimit" size="2" value="<?=$config['mailer_hrlimit']?>"> per Hour
		</form>
<?
			break;
			
		case 'saveconf':
			if(get_magic_quotes_gpc()) {
				$_POST['subject']=stripslashes($_POST['subject']);
				$_POST['template']=stripslashes($_POST['template']);
			}
		
			$q='REPLACE INTO '.SETTINGS.' (name,value) VALUES '.
				"('mailer_to', '".$_POST['recip']."'), ".
				"('mailer_subject', '".mysql_real_escape_string($_POST['subject'])."'), ".
				"('mailer_template', '".mysql_real_escape_string($_POST['template'])."'), ".
				"('mailer_hrlimit', '".intval($_POST['hrlimit'])."')";
			
			mysql_query($q) or
				BailErr('Couldn\'t save settings',$q);
				
			echo "200\n";
			
			break;
	
	}
	exit();
}

if(isset($_POST['m'])) {
	chdir('../../');
	include("core.php");

	class MailTemplate extends Smarty {	
		function MailTemplate() {
			$this->template_dir = '';
			$this->compile_dir = 'tmp';
		}
	}
	
	/*$id=intval(@$_POST['id']);
	if(!$id) Bail('Mailer: No id set or not valid. Please fix your form');*/
	
	// Default value
	$row['mailer_hrlimit'] = 5;
	
	$q="SELECT * FROM ".SETTINGS." WHERE name LIKE 'mailer_%' OR name='pagetitle'";
	$res=mysql_query($q) or
		BailSQL('Failed getting Form Data from DB',$q);
		
	while($row=mysql_fetch_array($res))
		$config[$row['name']]=$row['value'];
	
	if(!isset($config['mailer_to'])) Bail('Mailer: No receiver E-Mail in DB found. Missing entry or wrong id?');
	
	if(@$config['mailer_hrcount']>=$config['mailer_hrlimit']) Bail('Sorry, hourly contact request limit reached. Please try again later.');
	
	if(time()-@$config['mailer_lastmail'] > 3600) {
		$q='REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'mailer_lastmail\',\''.time().'\')';
		$res=mysql_query($q) or
			BailSQL('Failed getting Form Data from DB',$q);
			
		$config['mailer_hrcount']=0;
	}
	
	$q='REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'mailer_hrcount\',\''.($config['mailer_hrcount']+1).'\''; 
	$res=mysql_query($q) or
		BailSQL('Failed getting Form Data from DB',$q);
	
	$mail = new MailTemplate();

	foreach($_POST as $name=>$value)
		$mail->assign($name,$value);
		
	$m=$mail->fetch('mail'.$id.'.tpl');
	
	$headers  =	'MIME-Version: 1.0' . "\n";
	$headers .=	'Content-type: text/plain; charset=iso-8859-1' . "\n";  
	$headers .=	'From: '.$config['pagetitle'].' Mailer <no-reply@'.$_SERVER['SERVER_NAME'].'>' . "\n" .
				'Reply-To: '.$config['pagetitle'].' Mailer <no-reply@'.$_SERVER['SERVER_NAME'].'>' . "\n" .
				'X-Mailer: PHP/' . phpversion()."\n";    

	
	mail($config['mailer_to'],$config['mailer_subject'], utf8_decode($m),$headers);
	
	$anego->AddContent('<h3>'.$lng_send_success.'</h3>'.htmlentities(utf8_decode($_POST['res'])).'<hr><pre>');
	$anego->display('index.tpl');
}
?>