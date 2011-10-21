gallery = ContentElement.extend({
	onStartEdit: function() {
		var self = this;
		// Will be loaded only once (loadJavascript() takes care of that)
		Core.loadJavascript('ld.jui');
		Core.loadJavascript('modules/gallery/jquery.filedrop.js');
			
		/* Loaded contents from server because .html() strips <script> tags */
		// Done by custom-ce call 
		$.post('index.php',{
			a:'callce',
			fn:'lp',
			mid: self.module_id,
			pid: self.page_id,
			elid: self.element_id
		},function(data) {
			if((aw=GetAnswer(data))!=null) {
				self.html = aw;
				$('#' + self.containerId).html('<div style="text-align:left;"><a id="addFilesLink" href="#">Add files</a></div><div id="files">No </div>');
				$('#' + self.containerId).find('#addFilesLink').click(self.addFiles);
				
				
			}
		});
		return true;
	},
	
	onEndEdit: function() {
		return true;
	},
	
	addFiles: function() {
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
	
});