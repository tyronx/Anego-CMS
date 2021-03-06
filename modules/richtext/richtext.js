richtext = ContentElement.extend({
	onStartEdit: function() {
		var self = this;
		var $container = $('#'+self.containerId);
		
		this.editorId = "tm" + this.module_id + "_" + this.element_id;
		
		var buttons = '<button type="button" name="mew" class="btn_sendrte" style="min-width:150px">' + lng_savechanges + '</button> '+
					'<button type="button" name="mew2" class="btn_cancelrte" style="min-width:150px">' + lng_cancelchanges + '</button>';
		
		
		var loadnew = true;
		var height = $container.height();

		self.html = $container.html();
		// Takes care that escaped html tags stay escaped
		var escapedHTML = $("<div/>").text(self.html).html();
		$container.html('<textarea style="width:100%" id="' + self.editorId + '">' + escapedHTML + '</textarea>' + buttons);
		self.tinyfy(height < 300 ? 300 : height + 100);
		
		if(loadnew) {
			$.post('index.php', {
				a: 'gcec',
				mid: self.module_id,
				elid: self.element_id,
				pid: self.page_id
			}, function(data) {
				var aw;
				if(aw = GetAnswer(data)) {
					self.html = aw;
					$('#' + self.editorId).tinymce().setContent(aw);
					Core.callHooks('afterContentElementEditLoad', { contentElement: self });
				}
			});
		} else {
			Core.callHooks('afterContentElementEditLoad', { contentElement: self });
		}
		
		$container.find('.btn_sendrte').click(function() {
			self.html = $('#' + self.editorId).tinymce().getContent();
			$container.html(self.html);
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
	
	tinyfy: function(height) {
		if (!height) height = 300;
		
		var templates = [
			{
				title : "Two columns",
				src : "lib/tiny_mce/templates/2column.htm",
				description : "A template that defines two colums, each one with a title, and some text."
			}, {
				title : "Left side bar",
				src : "lib/tiny_mce/templates/leftsidebar.htm",
				description : "A 2 column template with a bar on the left side."
			}, {
				title : "Right side bar",
				src : "lib/tiny_mce/templates/rightsidebar.htm",
				description : "A 2 column template with a bar on the right side."
			}, {
				title : "Text and Table",
				src : "lib/tiny_mce/templates/texttable.htm",
				description : "A title with some text and a table."
			}
		];
		
		// Per style defined templates
		if(typeof tinymceTemplates != 'undefined') {
			templates = templates.concat(tinymceTemplates);
		}
		
		if(typeof tinymceAdvancedStyles!= 'undefined') {
			templates = templates.concat(tinymceTemplates);
		}
		
		var mcelang='en';
		if(anego.language=='ger') /* language var defined by Anego */
			mcelang='de';
		
		var settings = {
			script_url : anego.path + 'lib/tiny_mce/tiny_mce_gzip.php',
			mode : 'none',
			theme : "advanced",	
			plugins : "advimagescale,table,tablegrid,advlink,preview,media,searchreplace,contextmenu,paste,fullscreen,xhtmlxtras,inlinepopups,phpimage,anegofileupload,template",
			height : height,
			theme_advanced_buttons1 : "bold,italic,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,forecolor,backcolor",
			theme_advanced_buttons2 : "pastetext,|,search,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,|,charmap,template,media,phpimage,anegofileupload,|,hr,removeformat,|,sub,sup",
			theme_advanced_buttons3 : "tablegrid,|,row_props,cell_props,|,row_before,row_after,delete_row,|,col_before,col_after,delete_col,|,split_cells,merge_cells,|,preview,code,fullscreen",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			theme_advanced_fonts: "Andale Mono=andale mono,times;"+
                    "Arial=arial,helvetica,sans-serif;"+
                    "Arial Black=arial black,avant garde;"+
                    "Book Antiqua=book_antiquaregular,palatino;"+
                    "Calibri=calibri;"+
					"Corda Light=CordaLight,sans-serif;"+
                    "Courier New=courier_newregular,courier;"+
                    "Georgia=georgia,palatino;"+
                    "Helvetica=helvetica;"+
                    "Impact=impactregular,chicago;"+
                    "Symbol=webfontregular;"+
                    "Tahoma=tahoma,arial,helvetica,sans-serif;"+
                    "Terminal=terminal,monaco;"+
                    "Times New Roman=times new roman,times;"+
                    "Trebuchet MS=trebuchet ms,geneva;"+
                    "Verdana=verdana,geneva;"+
                    "Webdings=webdings;"+
                    "Wingdings=wingdings,zapf dingbats",
			theme_advanced_font_sizes : "7pt,8pt,9pt,10pt,11pt,12pt,13pt,14pt,15pt,17pt,19pt,21pt,23pt,25pt",
			theme_advanced_styles : "Volle Breite=fullwidth;Gray Frame=grayframe;Small padding right=smallpadright;Medium padding right=mediumpadright;Small padding left=smallpadleft;Small padding left+right+bottom=smallpadu;Small padding all sides=smallpadding;Large padding all sides=largepadding; No padding=nopadding",
			language : mcelang, 
			advlink_styles: "Spam Protected E-Mail Address=hiddenEmail",
			paste_text_use_dialog: true,
			accessibility_warnings : false,
			advimagescale_noresize_all: true,
			extended_valid_elements: "form[name|id|action|method|enctype|accept-charset|onsubmit|onreset|target],input[id|name|type|value|size|maxlength|checked|accept|src|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],textarea[id|name|rows|cols|maxlength|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],option[name|id|value|selected|style],select[id|name|type|value|size|maxlength|checked|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|multiple|style]",	
			button_tile_map : true,
			content_css : anego.path + "styles/default/default-text.css" + "," + anego.path + "styles/" + anego.style + "/text.css",   /* style var defined by Anego */
			external_link_list_url : anego.path + "modules/richtext/linkList.js.php",
			external_image_list_url : anego.path + "modules/richtext/imageList.js.php",
			convert_urls : false,
			template_templates : templates
		};
		
		if (typeof tinymceRichtextSettings == "object")
			settings = $.extend(settings, tinymceRichtextSettings);
		
		$('#' + this.editorId).tinymce(settings);
	}
});