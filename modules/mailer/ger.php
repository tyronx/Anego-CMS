<?php
$lang = array(
	'Failed getting form code' => 'Konnte form code nicht lesen',
	'Failed updating mailer element' => 'Konnte mailer element nicht updaten',
	'Mailer: No receiver E-Mail in DB found.' => 'Mailer: Kein Empfäer E-Mail in der Datenbank gefunden.',
	'Sorry, hourly contact request limit reached. Please try again later.' => 'Das Limit an Kontaktanfragen wurde erreicht, bitte versuche es später noch einmal.',
	'I\'m sorry, but I was unable to send out a mail. Something must be wrong with the server configuration' => 'I\'m sorry, but I was unable to send out a mail. Something must be wrong with the server configuration',
	'Sending...' => 'Sende Nachricht...',
	'Thank you for your message!' => 'Danke für deine Nachricht!',
	'From %s: A person used your e-mail form' => 'Von %s: Eine Person hat das Kontaktformular verwendet',
	'mailtemplate' => 'Hello
		
Eine Personen hat das Kontaktformular auf der Seite \'%s\' auf deiner Website verwendet. 

Name: {$name}
E-Mail/Telefon: {$email}

Anfrage:
{$request}

 -------------------------------------------------------
  - Anego CMS Mailer Module auf %s
',
	'formhtml' => '<p><label for="yourname">Name:</label><br>
<input type="text" id="yourname" name="name">
</p><p><label for="youremail">E-Mail oder Tel.:</label><br>
<input type="text" id="yourmail" name="email"><br>
</p><br>
<p><label for="yourrequest">Deine Anfrage:</label><br>
<textarea rows="10" cols="50" name="request" id="yourrequest"></textarea>
<br>
<p><input type="submit" name="send" value="Abschicken">'
	
);
