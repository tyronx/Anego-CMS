plainhtml = ContentElement.extend({
	onStartEdit: function() {
		var self = this;
		var $container = $('#'+self.containerId);
		
		this.editorId = "tm" + self.module_id + "_" + self.element_id;
		
		var buttons = '<button type="button" name="mew" class="btn_sendrte" style="min-width:150px">' + lng_savechanges + '</button> '+
					'<button type="button" name="mew2" class="btn_cancelrte" style="min-width:150px">' + lng_cancelchanges + '</button>';
		
		/* Loaded contents from server because .html() strips <script> tags */
		$.get('index.php', {
			a:'gcec', 
			t: self.module_id,
			elid: self.element_id
		}, function(data) {
			var aw = GetAnswer(data);
			if(aw != null) {
				self.html = aw;
				$container.html('<textarea style="width:100%" rows="10" id="' + self.editorId + '">' + aw.replace(/>/g,'&gt;').replace(/</g,'&lt;') + '</textarea>' + buttons);
				
				$container.find('.btn_sendrte').click(function() {
					self.html = $('#' + self.editorId).val();
					self.endEdit();
					
					$.ajax({
						type : 'POST',
						url : 'index.php',
						data: { 
							a: 'callce',
							mid: self.module_id,
							elid: self.element_id,
							pid: self.page_id,
							fn: 'save',
							recache: true,
							'params[]': [self.html]  // Function parameters
						},
						success: function(data) {
							// alerts any errors that might have happened
							GetAnswer(data);
						}
					});
				});

				$container.find('.btn_cancelrte').click(function() {
					self.endEdit();
				});
			} else {
				self.endEdit();
			}
		});
		
		return true;
	},
	
	onEndEdit: function() {
		if(this.html)
			$('#' + this.containerId).html(this.html);
		
		return true;
	}
});