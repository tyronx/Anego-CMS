/* Huge todo: When ajax call to create/delete element fails: undo everthing */

seperator = function (elid) {
	var module_id = 'seperator';
	var element_id=elid;
	var container=$("#"+module_id+"_"+elid);
	
	this.createElement=function(page_id, cnt, position, callback) {
			$.get('index.php?a=cce&t='+module_id+'&page_id='+page_id+'&pos='+position, function(data) {
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
		$.get('index.php?a=delce&t='+module_id+'&id='+element_id,
			function(data) {
				if(aw=GetAnswer(data)) {
					callback();
				}
			});
	}
}