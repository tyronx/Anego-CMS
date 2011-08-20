/* Functions for Edit mode */
blog = function (elid) {
	var element_id=elid;
	var module_id = 'blog';
	var container=$("#"+module_id+"_"+elid);

	
	this.createElement=function(page_id, cnt, position, callback) {			
			$.get('index.php?a=cce&t='+module_id+'&page_id='+page_id+'&pos='+position, function(data) {
				var aw;
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
		alert("Blog settings not implemented yet sorry :(\nTo add/edit blog entries, leave the edit mode");
	}
		
	/* Return true if delection was successful */
	this.deleteElement=function(callback) {
		$.get('index.php?a=delce&t='+module_id+'&id='+element_id,
			function(data) {
				var aw;
				if(aw=GetAnswer(data)) {
					callback();
				}
			});
	}
	
}