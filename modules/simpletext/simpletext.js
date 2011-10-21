simpletext = ContentElement.extend({
	onStartEdit: function() {
		var self = this;
		var $container = $('#'+self.containerId);
		
		this.editorId = "tm" + this.module_id + "_" + this.element_id;
		
		var buttons = '<button type="button" name="mew" class="btn_sendrte" style="min-width:150px">' + lng_savechanges + '</button> '+
					'<button type="button" name="mew2" class="btn_cancelrte" style="min-width:150px">' + lng_cancelchanges + '</button>';
		
		self.html = $container.html();
		$container.html('<textarea style="width:100%" id="' + self.editorId + '">' + self.html + '</textarea>' + buttons);
		self.tinyfy();
		
		$container.find('.btn_sendrte').click(function() {
			self.html = $('#' + self.editorId).tinymce().getContent();
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
		
		return true;
	},
	
	onEndEdit: function() {
		$('#' + this.editorId).tinymce().hide();
		$('#' + this.containerId).html(this.html);
		
		return true;
	},
	
	tinyfy: function() {
		var mcelang='en';
		if(anego.language=='ger') /* language var defined by Anego */
			mcelang='de';
			
		$('#' + this.editorId).tinymce({
			script_url : 'lib/tiny_mce/tiny_mce_gzip.php',
			mode : 'none',
			theme : "advanced",	
			plugins : "advimagescale,advlink,contextmenu,paste,inlinepopups,phpimage",
			height : 300,
			theme_advanced_buttons1 : "bold,italic,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,forecolor,backcolor",
			theme_advanced_buttons2 : "pastetext,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,|,charmap,|,hr,removeformat,|,sub,sup,|,phpimage,|,code",
			theme_advanced_buttons3 : "",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			theme_advanced_font_sizes : "7pt,8pt,9pt,10pt,11pt,12pt,13pt,14pt,15pt,17pt,19pt,21pt,23pt,25pt",
			language : mcelang,
			advlink_styles: "Spam Protected E-Mail Address=hiddenEmail",
			paste_text_use_dialog: true,
			accessibility_warnings : false,
			button_tile_map : true,
			content_css : "styles/"+anego.style+"/text.css", /* style var defined by Anego */
			external_link_list_url : "modules/simpletext/linkList.js.php",
			convert_urls : false
		});
	}
});