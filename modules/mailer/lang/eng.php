<?php
$lang = array(
	'Failed getting form code' => 'Failed getting form code',
	'Failed updating mailer element' => 'Failed updating mailer element',
	'Mailer: No receiver E-Mail in DB found. Missing entry or wrong id?' => 'Mailer: No receiver E-Mail in DB found. Missing entry or wrong id?',
	'Sorry, hourly contact request limit reached. Please try again later.' => 'Sorry, hourly contact request limit reached. Please try again later.',
	'I\'m sorry, but I was unable to send out a mail. Something must be wrong with the server configuration' => 'I\'m sorry, but I was unable to send out a mail. Something must be wrong with the server configuration',
	'Sending...' => 'Sending...',
	'Thank you for your message!' => 'Thank you for your message!',
	'From %s: A person used your e-mail form' => 'From %s: A person used your e-mail form',
	'mailtemplate' => 'Hello
		
A person has used the e-mail form on the \'%s\' Page of your website. 

Name: {$name}
E-Mail/Phone Number: {$email}

Request:
{$request}

 -------------------------------------------------------
  - Anego CMS Mailer Module on %s
',
	'formhtml' => '<p><label for="yourname">Your name:</label><br>
<input type="text" id="yourname" name="name" class="mandatory">
</p><p><label for="youremail">Your email or phone number:</label><br>
<input type="text" id="yourmail" name="email"><br>
</p><br>
<p><label for="yourrequest" class="mandatory">Your Request:</label><br>
<textarea rows="10" cols="50" name="request" id="yourrequest"></textarea>
<br>
<p><input type="submit" name="send" value="Submit form">'
);
