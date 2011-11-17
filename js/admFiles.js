var response=0;
var iframes=0;
var fileentered=0;
var curpath;

var $addFileDlg;

function FileEntered() {
	fileentered=1;
}

function AutoUpload() {
	if(fileentered==1) {
		SubmitFile();
		fileentered=0;
	}
	window.setTimeout(AutoUpload,250);	
}

function CheckUpload(iframes) {
//alert(iframes);
	var el = document.getElementById('iframe'+iframes);
	var doc = el.contentDocument;
	if (doc == undefined || doc == null)
		doc = el.contentWindow.document;
	
	var upl,aw;
	if(upl=doc.getElementById('result')) {
		if(aw=GetAnswer(upl.innerHTML)) 
			document.getElementById('upload'+iframes).innerHTML = aw.substr(aw.lastIndexOf('/')+1) + ' '+lng_completed;
		else
			document.getElementById('upload'+iframes).innerHTML = lng_failed;
	} else window.setTimeout(function(){CheckUpload(iframes);},500);
}

function SubmitFile(path) {
	if(!path) path=curpath;
	
	var el = document.getElementById('iframe'+iframes);
	var doc = el.contentDocument;
	if (doc == undefined || doc == null)
		doc = el.contentWindow.document;
		
	if(doc.fileupload.fiupl.value.length==0) {  
		return; 
	}
	
	doc.fileupload.submit();
	document.getElementById('upload'+iframes).innerHTML = lng_uploading+' <img src="styles/default/img/cleardot.gif" class="loadingIcon">';
	//document.getElementById('upload'+iframes).style.cssFloat='left';
	
	var moo = iframes;
	window.setTimeout(function(){CheckUpload(moo); moo=null;},500);	
	
	el.style.display='none';
	
	//document.getElementById('dlgBox').style.height = (100 + iframes*17)+'px';
	iframes++;
	
	
	var str = '<iframe id="iframe'+iframes+'" scrolling="no" class="upload" marginWidth="0" marginheight="0" frameborder="0"></iframe><div class="uploadline" id="upload'+iframes+'"></div>';
	
	// this deletes the content of the prebvious iframe. No idea why
	//document.getElementById('dlgContent').innerHTML += str;
	// alternate solution
	var $prg = $('<span>'+str+'</span>');
	
	$('.dlgBtnContainer', $addFileDlg).prepend($prg);
	
	str = '<html><body><form method="POST" enctype="multipart/form-data" name="fileupload" action="admin.php?a=af" accept-charset="UTF-8"  onSubmit="return false">';
	str += '<input type="file" onchange="parent.FileEntered()" id="fiupl" name="fiupl">'
	str += '<input type="hidden" name="path" value="'+path+'"></form></body></html>';

	var el = document.getElementById('iframe'+iframes);
	var doc2 = el.contentDocument;
	if (doc2 == undefined || doc == null)
		doc2 = el.contentWindow.document;
	doc2.open();
	doc2.write(str);
	doc2.close();
}

function AddFile(path) {
	iframes=1;
	curpath=path;
	
	var str = '<iframe id="iframe'+iframes+'" style="width:300px;" scrolling="no" class="upload" marginWidth="0" marginheight="0" frameborder="0"></iframe><div class="uploadline" id="upload'+iframes+'"></div>';
	$addFileDlg = OpenDialog({
		title: lng_addfile + ' <span style="font-size:10pt;">('+lng_maxfilesize+' '+anego.maxmb+' MB)</span>',
		content: str,
		buttons: BTN_CLOSE,
		close_callback: 
			function() {
				$.get("admin.php", {
					a: 'files',
					fgx: Core.GETvar('fgx'),
					r: response
				}, function(data) { 
					$('#content').html(data);
					Core.lightbox('a[rel=lightbox]');
				});
			}
		}
	);
	
	var str = '<html><body><form method="POST" enctype="multipart/form-data" name="fileupload" action="admin.php?a=af" accept-charset="UTF-8"  onSubmit="return false">';
	str += '<input type="file" size="30" onchange="parent.FileEntered()" id="fiupl" name="fiupl">'
	str += '<input type="hidden" name="path" value="'+path+'"></form></body></html>';

	var el = document.getElementById('iframe'+iframes);
	var doc = el.contentDocument;
	if (doc == undefined || doc == null)
		doc = el.contentWindow.document;
	doc.open();
	doc.write(str);
	doc.close();
	
	window.setTimeout(AutoUpload,250);	
}

function AddFolder(path) {
	OpenDialog({
		title:lng_addfolder,
		content: '<form name="pagedata" onSubmit="return false">' + lng_foldername + ':<br><input type="text" size="25" name="folder" onSubmit="return false"></form>',
		ok_callback: function() { 
			if (document.pagedata.folder.value.length==0) {
				alert(lng_enterfolder); 
				return;
			}
			if(document.pagedata.folder.value.match(/[^A-Za-z0-9_\-]+/)) { 
				alert(lng_invalidfolder);
				return; 
			}
			
			var $dlg = this;
			$dlg.waitResponse();

			$.get('admin.php', {
					a: 'cfol',
					fgx: path,
					r: response,
					path: path,
					nfolder: document.pagedata.folder.value
				}, function(data) {
					$('#content').html(data);
					Core.lightbox('a[rel=lightbox]');
					$dlg.closeDialog();
				}
			);
		}
	});
}

function RenameFile(path, fgx) {
	var filename = path.substr(path.lastIndexOf('/')+1);
	
	OpenDialog({
		title: lng_renamefile,
		content: '<form name="pagedata" onSubmit="return false">File name:<br><input type="text" size="25" name="filen" value="'+filename+'" onSubmit="return false"></form>',
		ok_callback: function() {
			if(document.pagedata.filen.value.length=0) { 
				alert(lng_enterfolder); 
				return; 
			}
			if(document.pagedata.filen.value.match(/[^A-Za-z0-9_\-\.]+/)) { 
				alert(lng_invalidfolder);
				return; 
			}
			
			var $dlg = this;
			
			$dlg.waitResponse();
			
			$.post('admin.php?a=renf&fgx='+urlencode(anego.curPg)+'&r='+response,{
					path: path,
					renfile: document.pagedata.filen.value
				}, function(data) {
					var aw;
					if (aw = GetAnswer(data)) {
						document.getElementById("content").innerHTML = aw;
						Core.lightbox('a[rel=lightbox]');
						$dlg.closeDialog();
					}
				}
			);
		}
	});
}

function DelFile(file,fgx,isfolder) {
	var resp;
	if(isfolder) resp=confirm(lng_folderconfirm);
		else resp=confirm(lng_fileconfirm);
	if(resp)
		Core.postData('file='+urlencode(file),"admin.php?a=delf&fgx="+urlencode(anego.curPg)+"&r="+response,PrintPics);
}

function PrintPics(req) {
	document.getElementById("content").innerHTML = req.responseText;
	Core.lightbox('a[rel=lightbox]');
}