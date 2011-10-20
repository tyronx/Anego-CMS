/* Huge todo: When ajax call to create/delete element fails: undo everthing */

simpletext = function(page_id, element_id) {
	var module_id = 'simpletext'

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
		if (editing) {
			$('#newElem' + element_id).tinymce().hide();
			container.addClass('ceDraggable');
			container.removeClass('ceEditing');
			container.html(oldHTML);
			editing = false;
			hideMiniToolbarVar = false;
		} else {
			hideMiniToolbarVar = true;
			var buttons = '<button type="button" name="mew" id="btn_sendrte" style="min-width:150px">' + lng_savechanges + '</button> '+
							'<button type="button" name="mew2" id="btn_cancelrte" style="min-width:150px">' + lng_cancelchanges + '</button>';
						
			oldHTML = container.html();
			container.html('<textarea style="width:100%" id="newElem' + element_id + '">' + container.html() + '</textarea>' + buttons);
			container.removeClass('ceDraggable');
			container.addClass('ceEditing');
			this.tinyfy("newElem" + element_id);
			editing = true;
			
			$("#btn_sendrte").click(function() {
				var val = $("#newElem"+element_id).tinymce().getContent();
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
		var mcelang='en';
		if(anego.language=='ger') /* language var defined by Anego */
			mcelang='de';
			
		$('#'+el_id).tinymce({
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
}