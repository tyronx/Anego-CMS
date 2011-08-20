/* Huge todo: When ajax call to create/delete element fails: undo everthing */

alohatext = function( elid) {
	var module_id = 'alohatext';
	var element_id=elid;
	var container=$("#"+module_id+"_"+elid);
	var editing=false;
	var oldHTML='';
	var myself = this;
	
	this.createElement = function(page_id, cnt, position, callback) {
		$.get('index.php?a=cce&t='+module_id+'&page_id='+page_id+'&pos='+position, function(data) {
			var aw;
			if(aw=GetAnswer(data))
				var data = jQuery.parseJSON(aw);
				cnt.html(data.html);
				cnt.attr('id',module_id+"_"+data.id);
				container = cnt;
				element_id=data.id;
				callback(module_id+"_"+data.id);
			});
	}
		
	this.editElement = function(element_id, placeholder) {
		alert('Aloha settings not implemented yet. To edit the text with aloha, please leave the edit mode');
	}
	
	/* Return true if delection was successful */
	this.deleteElement = function (callback) {
		$.get('index.php?a=delce&t='+module_id+'&id='+element_id,
			function(data) {
				if(aw=GetAnswer(data)) {
					callback();
				}
			});
	}
	
}