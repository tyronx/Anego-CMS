gallery = function(elid) {
	var module_id = 'gallery'
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
				myself.editElement();
			});
	}
	
	this.editElement = function() {
		if(editing) {
			container.addClass('ceDraggable');
			container.removeClass('ceEditing');
			container.html(oldHTML);
			editing=false;
		} else {
			container.removeClass('ceDraggable');
			container.addClass('ceEditing');
			editing=true;
			
			/* Loaded contents from server because .html() strips <script> tags */
			$.get('index.php',{a:'gcec', t:module_id,elid:element_id},function(data) {
				if((aw=GetAnswer(data))!=null) {
					oldHTML = aw;
					container.html('stuff');
				}
			});
			
		}
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