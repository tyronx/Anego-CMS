/* Huge todo: When ajax call to create/delete element fails: undo everthing */

richtext = function(page_id, element_id) {
	var module_id = 'richtext';

	var container=$("#" + module_id + "_" + element_id);
	var editing=false;
	var oldHTML='';
	var myself = this;
	var hideMiniToolbarVar = false;
	
	this.createElement = function(cnt, position, callback) {
		$.get('index.php?a=cce&mid='+module_id+'&page_id='+page_id+'&pos='+position, function(data) {
			var aw;
			if(aw=GetAnswer(data))
				var data = jQuery.parseJSON(aw);
				cnt.html(data.html);
				cnt.attr('id',module_id+"_"+data.id);
				container = cnt;
				element_id=data.id;
				callback(module_id+"_"+data.id);
				myself.editElement();
			});
	}
	
	this.hideMiniToolbar = function() {
		return hideMiniToolbarVar;
	}
		
	this.editElement = function() {
		if(editing) {
			//tinyMCE.execCommand('mceRemoveControl', false, "newElem"+element_id);
			$('#newElem'+element_id).tinymce().hide();
			container.addClass('ceDraggable');
			container.removeClass('ceEditing');
			container.html(oldHTML);
			editing=false;
			hideMiniToolbarVar=false;
		} else {
			hideMiniToolbarVar=true;
			var buttons = '<button type="button" name="mew" id="btn_sendrte" style="min-width:150px">'+lng_savechanges+'</button> '+
							'<button type="button" name="mew2" id="btn_cancelrte" style="min-width:150px">'+lng_cancelchanges+'</button>';
						
			oldHTML=container.html();
			
			container.html('<textarea style="width:100%" id="newElem'+element_id+'">'+container.html()+'</textarea>'+buttons);
			container.removeClass('ceDraggable');
			container.addClass('ceEditing');
			this.tinyfy("newElem"+element_id);
			editing=true;
			$("#btn_sendrte").click(function() {
				var val=$("#newElem"+element_id).tinymce().getContent();
				$('#newElem'+element_id).tinymce().hide();
				container.addClass('ceDraggable');
				container.removeClass('ceEditing');
				container.html(val);
				editing=false;

				$.ajax({
					type : 'POST',
					url : 'index.php',
					data: { 
						a: 'callce',
						mid: module_id,
						elid: element_id,
						pid: page_id,
						fn: 'save',
						recache: true,
						'params[]': [val]	// Function parameters
					},
					success: function(data) {
						//var aw;
						// alerts any errors that might have happened
						GetAnswer(data);
						hideMiniToolbarVar=false;
					}
				});
			});
			$("#btn_cancelrte").click(function() {
				$('#newElem'+element_id).tinymce().hide();
				container.addClass('ceDraggable');
				container.removeClass('ceEditing');
				container.html(oldHTML);
				editing=false;
				hideMiniToolbarVar=false;
			});
		}
	}
	
	/* Return true if delection was successful */
	this.deleteElement = function (callback) {
		$.get('index.php', {
			a: 'delce',
			pid: page_id,
			mid: module_id,
			elid: element_id
		}, function(data) {
			if(aw=GetAnswer(data)) {
				callback();
			}
		});
	}
	
	this.tinyfy = function(el_id) {
		var templates = [
			{
				title : "Two columns",
				src : "lib/tiny_mce/templates/2column.htm",
				description : "A template that defines two colums, each one with a title, and some text."
			},
			{
				title : "Left side bar",
				src : "lib/tiny_mce/templates/leftsidebar.htm",
				description : "A 2 column template with a bar on the left side."
			},
			{
				title : "Right side bar",
				src : "lib/tiny_mce/templates/rightsidebar.htm",
				description : "A 2 column template with a bar on the right side."
			},
			{
				title : "Text and Table",
				src : "lib/tiny_mce/templates/texttable.htm",
				description : "A title with some text and a table."
			}
		]
			
		var mcelang='en';
		if(anego.language=='ger') /* language var defined by Anego */
			mcelang='de';
			
		$('#'+el_id).tinymce({
			script_url : 'lib/tiny_mce/tiny_mce_gzip.php',
			mode : 'none',
			theme : "advanced",	
			plugins : "advimagescale,table,tablegrid,advlink,preview,media,searchreplace,contextmenu,paste,fullscreen,xhtmlxtras,inlinepopups,phpimage,template",
			height : 350,
			theme_advanced_buttons1 : "bold,italic,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,forecolor,backcolor",
			theme_advanced_buttons2 : "pastetext,|,search,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,|,charmap,template,media,phpimage,|,hr,removeformat,|,sub,sup",
			theme_advanced_buttons3 : "tablegrid,|,row_props,cell_props,|,row_before,row_after,delete_row,|,col_before,col_after,delete_col,|,split_cells,merge_cells,|,preview,code,fullscreen",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			theme_advanced_font_sizes : "7pt,8pt,9pt,10pt,11pt,12pt,13pt,14pt,15pt,17pt,19pt,21pt,23pt,25pt",
			language : mcelang, 
			advlink_styles: "Spam Protected E-Mail Address=hiddenEmail",
			paste_text_use_dialog: true,
			accessibility_warnings : false,
			advimagescale_noresize_all: true,
			extended_valid_elements: "form[name|id|action|method|enctype|accept-charset|onsubmit|onreset|target],input[id|name|type|value|size|maxlength|checked|accept|src|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],textarea[id|name|rows|cols|maxlength|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],option[name|id|value|selected|style],select[id|name|type|value|size|maxlength|checked|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|multiple|style]",	
			button_tile_map : true,
			content_css : "styles/"+anego.style+"/text.css", /* style var defined by Anego */
			external_link_list_url : "modules/richtext/linkList.js.php",
			convert_urls : false,
			template_templates : templates
		});
	}
}