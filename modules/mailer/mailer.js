function sendFormMail(id) {
	if (!id) return false;
	
	$form = $('form[name="mailer' + id + '"]');
	
	var havemandatory = true;
	$form.find('input, textarea').each(function() {
		if ($(this).hasClass("mandatory")) {
			var val = $(this).val();
			if ($(this).prop("tagName").toLowerCase() == 'textarea')  val = $(this).text();
			
			if (val.length == 0) {
				havemandatory = false;
				$(this).focus();
				Core.blinkElements(this);
				Core.shakeElements($('input[type="submit"], button[type="submit"]', $form));
				return false;
			}
		}
	});
	
	if (!havemandatory) return false;
	
	$.post('modules/mailer/mail.php', $form.serialize(),
		function(data) {
			var aw;
			if(aw = GetAnswer(data)) {
				$form.html(aw);
			}
			$('.sending', $form).hide();
			$('input[type="submit"], button[type="submit"]', $form).removeAttr('disabled');
		}
	);
	
	$('.sending', $form).show();
	$('input[type="submit"], button[type="submit"]', $form).attr('disabled', 'disabled');
	
	
	return false;
}