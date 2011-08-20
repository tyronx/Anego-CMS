function alohafuncs(element_id) {
	var currentContent;
	
	this.init = function() {
		if(typeof GENTICS=="undefined"||!GENTICS) {
			GENTICS_Aloha_base='modules/alohatext/aloha/';
			
			Core.loadJavascript(GENTICS_Aloha_base+"aloha-nojq.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.Format/plugin.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.Table/plugin.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.List/plugin.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.Link/plugin.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.HighlightEditables/plugin.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.TOC/plugin.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.Link/delicious.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.Link/LinkList.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.Paste/plugin.js");
			Core.loadJavascript(GENTICS_Aloha_base+"plugins/com.gentics.aloha.plugins.Paste/wordpastehandler.js");
		}
		
		$(document).ready(function() {
			GENTICS.Aloha.settings = {
				logLevels: {'error': true, 'warn': false, 'info': false, 'debug': false},
				errorhandling: false,
				ribbon: false
			};
			$(function(){
				$('#alohatext_'+element_id+' .alohaContent').aloha();
				$('#alohatext_'+element_id+' .alohaContent').keypress(function() {
					if($('#alohatext_'+element_id+' .alohaSaveBtn').html()=='Saved.')
					$('#alohatext_'+element_id+' .alohaSaveBtn').html('Save');
				});
				$('#alohatext_'+element_id+' .alohaContent').after('<br><button class="alohaSaveBtn" style="margin:0px;" type="button" >Save text</button>');
				currentContent = $('#alohatext_'+element_id+' .alohaContent').html();
			});
		});
		
		GENTICS.Aloha.EventRegistry.subscribe(GENTICS.Aloha, "editableDeactivated", this.saveEditable);
		
	}
	
	this.saveEditable = function(event, eventProperties) {
		var elPrefix = 'alohatext_';
		var elId = eventProperties.editable.obj.parent().attr('id').substr(elPrefix.length);
		
		if(currentContent == eventProperties.editable.getContents()) return; 
		currentContent = eventProperties.editable.getContents();
		
		$('#'+elPrefix+elId+' .alohaSaveBtn').html('<img src="styles/default/img/cleardot.gif" class="loadingIcon" alt=""> Saving...');
		$('#'+elPrefix+elId+' .alohaSaveBtn').attr('disabled','disabled');
		
		$.post("modules/alohatext/aloha.php", { a: 'save', content: eventProperties.editable.getContents(), id: elId }, function(data) {
			if(GetAnswer(data)) 
				$('#'+elPrefix+elId+' .alohaSaveBtn').html('Saved.');
			 else 
				$('#'+elPrefix+elId+' .alohaSaveBtn').html('Save');
				
			$('#'+elPrefix+elId+' .alohaSaveBtn').removeAttr('disabled');
		});
	}
}