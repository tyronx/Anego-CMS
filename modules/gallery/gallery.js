gallery = ContentElement.extend({
	template: '<div class="preview">'+
				'<span class="imageHolder">'+
					'<img />'+
					'<span class="uploaded"></span>'+
				'</span>'+
				'<div class="progressHolder">'+
					'<div class="progress"></div>'+
				'</div>'+
			'</div>',

	onStartEdit: function() {
		var self = this;
		var $container = $('#' + self.containerId);
		// Will be loaded only once (loadJavascript() takes care of that)
		Core.loadJavascript('ld.jui');
		Core.loadJavascript('modules/gallery/jquery.filedrop.js');
		
		self.html = $('#' + self.containerId).html();
			
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
				var images = $.parseJSON(aw);
				var $imageGrid = $('<div class="pictureGrid"></div>');
				
				$container.html('<div style="text-align:left;"><a class="GalAddFilesLink" href="#">Add files</a> | <a class="GalSettingsLink" href="#">Settings</a></div>');
				$container.append($imageGrid);
				
				$container.find('.GalAddFilesLink').click(self.addFiles);
				$container.find('.GalSettingsLink').click(self.settings);
				
				var $button = $('<button type="button" name="mew" class="btn_cancelrte" style="min-width:150px">' + lng_cancelchanges + '</button>');
				
				$container.append($button);
				$button.click(function() {
					self.endEdit();
				});
				
				var $image;
				$.each(images.preview, function(index, value) {
					$image = $(self.template), 
					$('img', $image).attr('src',images.path + '/' + value);
					$imageGrid.append($image);
				});
			}
		});
		
		$(document).filedrop({
			// The name of the $_FILES entry:
			paramname:'pic',
			data: { 
				a: 'callce',           // send POST variables
				fn: 'up',
				mid: self.module_id,
				pid: self.page_id,
				elid: self.element_id
			},
			maxfiles: 20,
			maxfilesize: 4,
			url: 'index.php',
			
			uploadFinished:function(i,file,response){
				$.data(file, 'preview').addClass('done');
				// response is the JSON object that post_file.php returns
				if(GetAnswer(response.status)) {
					$.data(file, 'preview').find('img').attr('src', response.preview);
				}
			},
			
			error: function(err, file) {
				switch(err) {
					case 'BrowserNotSupported':
						alert('Your browser does not support HTML5 file uploads!');
						break;
					case 'TooManyFiles':
						alert('Too many files! Please select 20 at most! (configurable)');
						break;
					case 'FileTooLarge':
						alert(file.name+' is too large! Please upload files up to 4mb (configurable).');
						break;
					default:
						break;
				}
			},
			
			// Called before each upload is started
			beforeEach: function(file) {
				if(!file.type.match(/^image\//)){
					alert('Only images are allowed!');
					
					// Returning false will cause the
					// file to be rejected
					return false;
				}
			},
			
			uploadStarted:function(i, file, len){
				createImage(file);
			},
			
			progressUpdated: function(i, file, progress) {
				$.data(file, 'preview').find('.progress').width(progress);
			}
			 
		});
		
		function createImage(file){
			var $preview = $(self.template), $image = $('img', $preview);
				
			var reader = new FileReader();
			
			$image.attr('width',100);
			$image.attr('height',100);
			
			reader.onload = function(e){
				// e.target.result holds the DataURL which
				// can be used as a source of the image:
				$image.attr('src',e.target.result);
			};
			
			// Reading the file as a DataURL. When finished,
			// this will trigger the onload function above:
			reader.readAsDataURL(file);
			
			$preview.appendTo($('#' + self.containerId + ' .pictureGrid'));
			
			// Associating a preview container
			// with the file, using jQuery's $.data():
			
			$.data(file, 'preview', $preview);
		}

		return true;
	},
	
	onEndEdit: function() {
		return true;
	},
	
	settings: function() {
		OpenDialog({
			title: "Settings",
			content: "Some lots of settings"
		});
		return false;
	},
	
	addFiles: function() {
		OpenDialog({
			title: "Add files",
			content: '<b>Hint:</b> You can just Drag&Drop Images into your browser window!<br><br><input type="file" name="picupload" multiple /> tbd'
		});
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
		return false;
	}
	
});