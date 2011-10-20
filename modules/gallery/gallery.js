gallery = function(page_id, element_id) {
	var module_id = 'gallery'
	var container=$("#" + module_id + "_" + element_id);
	var editing=false;
	var oldHTML='';
	var myself = this;
	
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
	
	this.editElement = function() {
		// Will be loaded only once (loadJavascript() takes care of that)
		Core.loadJavascript('ld.jui');
		Core.loadJavascript('modules/gallery/jquery.filedrop.js');
			
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
			// Done by custom ce call 
			$.post('index.php',{
				a:'callce',
				fn:'loadpictures',
				mid: module_id,
				pid: page_id,
				elid: element_id
			},function(data) {
				if((aw=GetAnswer(data))!=null) {
					oldHTML = aw;
					container.html('<div style="text-align:left;"><a id="addFilesLink" href="#">Add files</a></div><div id="files">No </div>');
					container.find('#addFilesLink').click(myself.addFiles);
					
					
				}
			});
			
		}
	}
	
	this.addFiles = function() {
		Core.loadJavascript('modules/gallery/filedrop.js');
		//OpenDialog("<div id)
		/*function makeFileList() {
			var input = document.getElementById("filesToUpload");
			var ul = document.getElementById("fileList");
			while (ul.hasChildNodes()) {
				ul.removeChild(ul.firstChild);
			}
			for (var i = 0; i < input.files.length; i++) {
				var li = document.createElement("li");
				li.innerHTML = input.files[i].name;
				ul.appendChild(li);
			}
			if(!ul.hasChildNodes()) {
				var li = document.createElement("li");
				li.innerHTML = 'No Files Selected';
				ul.appendChild(li);
			}*/
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