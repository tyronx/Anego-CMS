mailer = ContentElement.extend({
	editorTemplate:
		'<form action="#"> ' +
		'<p><label for="recipient">' + __('Send to address:') + '</label><br> ' +
		'<input type="text" id="recipient" name="recipient" size="30" value=""></p> ' +
		'<p><label for="subject">' + __('Send with subject:') + '</label><br> ' +
		'<input type="text" id="subject" name="subject" size="60" value=""></p><br> ' +
		'<label for="hourlimit">' + ('Limit mail sending to') + '</label> ' +
		'<input type="text" id="hourlimit" name="hourlimit" size="2" value=""> ' + __('per Hour') + '<br><br>' +
		'<p><label for="mailtemplate">' + __('Mail template:') + '</label><br> ' +
		'<textarea style="width:99%" rows="9" id="mailtemplate" name="mailtemplate"></textarea></p><br> ' +
		'<p><label for="formhtml">' + ('Form HTML (only &lt;inputs&gt;, etc.):') + '</label><br> ' +
		'<textarea style="width:99%" rows="9" id="formhtml" name="formhtml"></textarea></p><br> ' +
		'<button name="save" type="button" style="min-width:150px">' + __('Save & Update form') + '</button> ' +
		'</form>',
	
	onStartEdit: function(newlyCreated) {
		var self = this;
		var $container = $('#' + self.containerId);

		$.post('index.php', {
				a: 'callce',
				fn: 'data',
				mid: self.module_id,
				elid: self.element_id,
				pid: self.page_id
			}, function(data) {
				var aw;
				
				if(aw = GetAnswer(data)) {
					data = $.parseJSON(aw);
					var $editor = $(self.editorTemplate);
					
					$('input[name="recipient"]', $editor).val(data.recipient);
					$('input[name="subject"]', $editor).val(data.subject);
					$('input[name="hourlimit"]', $editor).val(data.hourlimit);
					
					$('textarea[name="mailtemplate"]', $editor).text(data.mailtemplate);
					$('textarea[name="formhtml"]', $editor).text(data.formhtml);
					
					$container.html($editor);
			
					$('button[name="save"]', $container).click(function() {
						$.post('index.php', {
							a: 'callce',
							fn: 'save',
							params: { 'data': $('form', $container).serializeArray() },
							mid: self.module_id,
							elid: self.element_id,
							pid: self.page_id
						}, function(data) {
							var aw;
							if(aw = GetAnswer(data)) {
								self.endEdit();
								$container.html(aw);
				
								// Update the page server side
								$.get('index.php', { a: 'rp', page: self.page_id} );

							}
						});
					});
				}
			}
		);
			
		
		return true;
	},
	
	onEndEdit: function() {
		return true;
	},
	
	
});