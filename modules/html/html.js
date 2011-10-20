/* Huge todo: When ajax call to create/delete element fails: undo everthing */

html = function(page_id, element_id) {
	var module_id = 'html'
	var container = $("#" + module_id + "_" + element_id);
	var editing = false;
	var oldHTML = '';
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
			container.addClass('ceDraggable');
			container.removeClass('ceEditing');
			container.html(oldHTML);
			editing=false;
			hideMiniToolbarVar=false;
		} else {
			hideMiniToolbarVar=true;
			var buttons = '<button type="button" name="mew" id="btn_sendrte" style="min-width:150px">'+lng_savechanges+'</button> '+
							'<button type="button" name="mew2" id="btn_cancelrte" style="min-width:150px">'+lng_cancelchanges+'</button>';
			
			container.removeClass('ceDraggable');
			container.addClass('ceEditing');
			editing=true;
			
			/* Loaded contents from server because .html() strips <script> tags */
			$.get('index.php',{a:'gcec', t:module_id,elid:element_id},function(data) {
				if((aw=GetAnswer(data))!=null) {
					oldHTML = aw;
					
					container.html('<textarea style="width:100%" rows="10" id="newElem'+element_id+'">'+aw.replace(/>/g,'&gt;').replace(/</g,'&lt;')+'</textarea>'+buttons);
					
					$("#btn_sendrte").click(function() {
						var val=$("#newElem"+element_id).val();
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
								// alerts any errors that might have happened
								GetAnswer(data);
								hideMiniToolbarVar=false;
							}					
						});
					});
					$("#btn_cancelrte").click(function() {
						container.addClass('ceDraggable');
						container.removeClass('ceEditing');
						container.html(oldHTML);
						editing=false;
						hideMiniToolbarVar=false;
					});
			
				}
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
	
}