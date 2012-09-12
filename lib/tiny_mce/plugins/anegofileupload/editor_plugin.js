(function() {
	var strPluginURL;
	tinymce.create('tinymce.plugins.anegofileuploadPlugin', {
		init: function(ed, url)  {
			// store the URL for future use..
			strPluginURL = url;
			ed.addCommand('mceanegofileupload', function() {
				anegofileupload(url);   
			});
			
			ed.addButton('anegofileupload', {
				title: 'Add a downloadable file',
				cmd: 'mceanegofileupload',
				image: url + '/img/anegofileupload.png'
			});
		},
		
		createControl: function(n, cm) {
			return null;
		},
		
		getPluginURL: function() {
			return strPluginURL;
		},
		
		getInfo: function() {
			return {
				longname: 'Anego File Upload plugin'
			};
		}
	});
	tinymce.PluginManager.add('anegofileupload', tinymce.plugins.anegofileuploadPlugin);
})();


// this function can get called from the plugin init (above) or from the callback on advlink/advimg plugins..
// in the latter case, win and type will be set.. In the rist case, we will just update the main editor window
// with the path of the uploaded file
function anegofileupload(url, type, win) {

	// open the plugin popup
	tinyMCE.activeEditor.windowManager.open({
		file            : url + '/upload.php',
		title           : 'Add file download',
		width           : 350,
		height          : 210,
		resizable       : "no",
		inline          : 1,
		close_previous  : "no"
	}, {
		plugin_url : url
	});
}

// This function will get called when the upload is done uploading the file and ready to update
// calling dialog and close the upload popup
// strReturnURL should be the string with the path to the uploaded file
function ClosePluginPopup (actualURL, strReturnURL, imageUrl, filename) {
	var win = tinyMCEPopup.getWindowArg("window");
	
	if (!win) {
		tinyMCE.activeEditor.selection.setContent(
			tinyMCE.activeEditor.dom.createHTML('a', { href : actualURL, class : 'download' }, '<img src="'+imageUrl+'"/>') + '&nbsp;' +
			tinyMCE.activeEditor.dom.createHTML('a', { href : actualURL, class : 'download' }, filename)
		);
	} else {
		win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = strReturnURL;
	}
	
	tinyMCEPopup.close();
}
