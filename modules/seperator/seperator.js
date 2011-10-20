/* Huge todo: When ajax call to create/delete element fails: undo everthing */

seperator = function (page_id, element_id) {
	var module_id = 'seperator';

	var container=$("#" + module_id + "_" + element_id);
	
	this.createElement=function(cnt, position, callback) {
			$.get('index.php?a=cce&mid='+module_id+'&page_id='+page_id+'&pos='+position, function(data) {
				if(aw=GetAnswer(data))
					var data = jQuery.parseJSON(aw);
					cnt.html(data.html);
					cnt.attr('id',module_id+"_"+data.id);
					container = cnt;
					element_id=data.id;
					callback(module_id+"_"+data.id);
					//myself.editElement();
				});
	}
		
	this.editElement=function(element_id, placeholder) {
			
	}
		
	/* Return true if delection was successful */
	this.deleteElement=function(callback) {
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