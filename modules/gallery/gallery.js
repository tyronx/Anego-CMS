gallery = ContentElement.extend({
	onInit: function() {
		// Will be loaded only once (loadJavascript() takes care of that)
		Core.loadJavascript('ld.jui');
		Core.loadJavascript('modules/gallery/jquery.filedrop.js');
	},
	imgTemplate: '<div class="pic">'+
				'<div class="imageHolder">'+
					'<a href="#">'+
					'<img />' + 
					'</a>'+
					'<span class="uploading"></span>'+
				'</div>'+
				'<div class="progressHolder">'+
					'<div class="progress"></div>'+
				'</div>'+
			'</div>',
	
	onStartEdit: function() {
		var self = this;
		var $container = $('#' + self.containerId);
		var $imageDlg;
		
		self.html = $('#' + self.containerId).html();
			
		// Done by custom-ce call 
		$.post('index.php',{
			a: 'callce',
			fn: 'lp',
			mid: self.module_id,
			pid: self.page_id,
			elid: self.element_id
		},function(data) {
			if ((aw = GetAnswer(data)) != null) {
				var response = $.parseJSON(aw);
				var $galleryEditor = $('<div class="galleryEditor"></div>');
				var $imageGrid = $('<div class="gallery pictureGrid"></div>');
				
				
				$container.html('<div style="text-align:left;"><a class="GalAddFilesLink" href="#">Add files</a> | <a class="GalSettingsLink" href="#">Settings</a></div>');
				$container.append($galleryEditor);
				
				$container.find('.GalAddFilesLink').click(self.addFiles);
				$container.find('.GalSettingsLink').click(self.settings);

				$galleryEditor.append($imageGrid);
				
				var $button = $('<button type="button" name="mew" class="btn_cancelrte" style="min-width:150px">' + lng_close + ' & Update page</button>');
				
				$container.append($button);
				$button.click(function() {
					self.endEdit();
					// Update the page
					$.get('index.php', { a: 'rp', page: self.page_id});
				});
				
				var $image;
				$.each(response.pictures, function(index, pic) {
					$image = $(self.imgTemplate);
					$('img', $image)
						.attr('src',response.path + '/' + pic.preview)
						.attr('alt', pic.shortdescription);
					
					$('.progressHolder', $image).remove();
					$('.uploading', $image).remove();
					$image.data('idx', pic.idx);
					console.log(pic.preview + '/' + pic.idx);
					
					$('a', $image)
						.attr('title', pic.longdescription)
						.click(function() {
							var str = '<p>Short description (for <a href="http://en.wikipedia.org/wiki/Search_engine_optimization">SEO</a>)<br>' +
								'<input type="text" name="shortdescription"></p><p>Long description<br><input type="text" name="longdescription"></p>';
							
							$image = $(this).parent().parent();
							
							if($imageDlg) {
								$imageDlg.closeDialog();
								$imageDlg = null;
							}
							
							$imageDlg = OpenDialog({
								title: 'Image settings',
								content: str,
								buttons: BTN_SAVECANCEL,
								ok_callback: function() {
									var $self = this;
									$self.waitResponse();
									console.log('edit ' + $('img',$image).attr('src') + '/' + $image.data('idx'));
									
									$.post('index.php', {
										a: 'callce',
										fn: 'save',
										mid: self.module_id,
										pid: self.page_id,
										elid: self.element_id,
										picid: $image.data('idx'),
										longdescription: $('input[name="longdescription"]', $imageDlg).val(),
										shortdescription: $('input[name="shortdescription"]', $imageDlg).val() 
									}, function(data) {
										if(GetAnswer(data)) {
											$('a', $image).attr('title', $('input[name="longdescription"]', $imageDlg).val());
											$('img', $image).attr('alt', $('input[name="shortdescription"]', $imageDlg).val());
											
											$self.closeDialog();
										} else {
											$self.endWait();
										}
									});
								}
							});
							
							$('input[name="longdescription"]', $imageDlg).val($(this).attr('title'));
							$('input[name="shortdescription"]', $imageDlg).val($(this).find('img').attr('alt'));
							
							return false;
						});
					
					$imageGrid.append($image);
				});
				
				$galleryEditor.append('<div class="bothclear"></div>');
				
				$('.pictureGrid', $container).sortable({
					containment: 'parent'
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
				$img = $.data(file, 'preview');
				
				$img.addClass('done');
				$('.progressHolder', $img).fadeOut();
				$('.uploading', $img).fadeOut();
				
				// response is the JSON object that post_file.php returns
				if(GetAnswer(response.status)) {
					$img.find('img').attr('src', response.preview);
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
			var $preview = $(self.imgTemplate), $image = $('img', $preview);
			var reader = new FileReader();
			
			$image.attr('width', 160);
			$image.attr('height', 120);
			
			reader.onload = function(e) {
				// e.target.result holds the DataURL which
				// can be used as a source of the image:
				$image.attr('src', e.target.result);
			};
			
			// Reading the file as a DataURL. When finished,
			// this will trigger the onload function above:
			reader.readAsDataURL(file);
			
			$('#' + self.containerId + ' .pictureGrid').append($preview);
			
			// Associating a preview container
			// with the file, using jQuery's $.data():
			$.data(file, 'preview', $preview);
		}

		return true;
	},
	
	onEndEdit: function() {
		var self = this;
		
		$.post('index.php',{
			a: 'gcec',
			mid: self.module_id,
			pid: self.page_id,
			elid: self.element_id
		}, function(data) {
			var aw;
			if(aw = GetAnswer(data)) 
				$('#' + self.containerId).html(aw);
		});
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