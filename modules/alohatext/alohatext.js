 /* Huge todo: When ajax call to create/delete element fails: undo everthing */

alohatext = function(page_id, element_id) {
	var module_id = 'alohatext';
	var container=$("#" + module_id + "_" + element_id);
	var editing=false;
	var oldHTML='';
	
	this.createElement = function(cnt, position, callback) {
		$.get('index.php?a=cce&mid='+module_id+'&page_id='+page_id+'&pos='+position, function(data) {
			var aw;
			if(aw = GetAnswer(data))
				var data = jQuery.parseJSON(aw);
				cnt.html(data.html);
				cnt.attr('id',module_id + "_" + data.id);
				container = cnt;
				element_id = data.id;
				callback(module_id + "_" + data.id);
			});
	}
		
	this.editElement = function(element_id, placeholder) {
		alert('Aloha settings not implemented yet. To edit the text with aloha, please leave the edit mode');
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
	
}