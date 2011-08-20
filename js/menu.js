// Todo: Reduce this into 20 lines of code with jquery

// Maximum speed
var max_speed = 80;
// Accelerate in percantage of the advancement
var adv_acc = 33;
// Slow down in percantage of the advancement
var adv_break = 66;
// Height including 2px border and 8px padding
var div_height = 15;

var time = new Object();
var old_height = new Object();
var cur_height = new Object();
var pos = new Object();
var time = new Object();
var dir = new Object();
var start_height = new Object();

var opened;
var opening = new Array();
var closing = new Array();
var insubmenu=0;
var heights = new Object();

function LoadHeights() {
    var item;
	// Looser Browser IE doesnt know how getElementsByName works
    /*for(var i=0; i<document.getElementsByName("d_submenu").length; i++) {
        item = document.getElementsByName("d_submenu")[i];
        
        item.style.display = '';    
        heights[item.id] = item.offsetHeight - div_height;
		alert(heights[item.id]);
        item.style.display = 'none';
    }*/
	var i=0;
	while(true) {
		i++;
		item = document.getElementById("submenu"+i);
		if(!item) break;
		
		item.style.display = '';    
        heights[item.id] = item.offsetHeight - div_height;
        item.style.display = 'none';
	}
}

function OpenMenu(menu_id, submenu) {
	menu_id = 'submenu'+menu_id;
	
	if(anego.menu_scroll==0) {
		document.getElementById(menu_id).style.display = '';
		return;
	}
	//alert("open menu "+menu_id);
	//document.getElementById(menu_id).style.display = '';
	//document.getElementById('content').innerHTML += "<div align='right'>open "+menu_id+"</div>";
	
	if(closing.contains(menu_id)) {
		dir[menu_id]=1; 
		closing.remove(menu_id);
		time[menu_id]=0;
		start_height[menu_id] = cur_height[menu_id];
		opening.push(menu_id);
		old_height[menu_id] = heights[menu_id];
	}
	else if(menu_id!=opened) {
		if(opened && !closing.contains(opened)) CloseMenu(opened);
		
		if(submenu) document.getElementById(menu_id).style.display = 'inline';
		else document.getElementById(menu_id).style.display = '';
			
		//if(!heights[menu_id])
        // 	heights[menu_id] = document.getElementById(menu_id).offsetHeight - div_height;
        //    alert(heights[menu_id]);
		
		//alert(heights[menu_id]);
		
		
		old_height[menu_id] = heights[menu_id];
		//alert(heights[menu_id]);
		
		start_height[menu_id] = 0;
		cur_height[menu_id] = 0;
		time[menu_id]=0;
		
		document.getElementById(menu_id).style.height = '1px';
		
		opening.push(menu_id);
		dir[menu_id]=1;
		setTimeout("Resize('"+menu_id+"')",10)
	}
}

function CloseMenu(menu_id) {
	menu_id = 'submenu'+menu_id;
	
	if(anego.menu_scroll==0) {
		document.getElementById(menu_id).style.display = 'none';
		return;
	}

	//document.getElementById('content').innerHTML += "<div align='right'>close "+menu_id+"</div>";
	
	if(menu_id && !closing.contains(menu_id)) {
		if(opening.contains(menu_id)) {
			old_height[menu_id]=cur_height[menu_id];
			time[menu_id]=0;
			dir[menu_id]=-1;
			opening.remove(menu_id);
			closing.push(menu_id);
			
			
		}
		else if(menu_id==opened) {
			
			
			//document.getElementById(menu_id).style.height = heights[menu_id]+"px";
			
			closing.push(menu_id);
			dir[menu_id]=-1;
			time[menu_id]=0;
			opened = 0;
			old_height[menu_id]=heights[menu_id];
			setTimeout("Resize('"+menu_id+"')",200)
		}
	}
}

function Resize(element_id) {
	time[element_id]+=10;
	
	cur_height[element_id] = start_height[element_id] + Math.ceil(max_speed * adv_acc/100 * old_height[element_id] * time[element_id]/1000*time[element_id]/1000);
	if(dir[element_id]==1)
		cur_height[element_id] = cur_height[element_id] + (old_height[element_id]-cur_height[element_id])/10;
	else 
		cur_height[element_id] = old_height[element_id]-Math.floor(max_speed * adv_acc/100 * old_height[element_id] * time[element_id]/1000*time[element_id]/1000);
		
	if(cur_height[element_id]<0) cur_height[element_id]=1;
	if(cur_height[element_id]>heights[element_id]) cur_height[element_id]=heights[element_id];
	
	document.getElementById(element_id).style.height = cur_height[element_id]+"px";
	//document.getElementById("contenttext").innerHTML = cur_height[element_id]+"px";
	//document.getElementById('content').innerHTML = "<div align='right'>"+document.getElementById(element_id).style.height+" vs. "+document.getElementById(element_id).offsetHeight;+"</div>";
	//document.getElementById('footer_text').innerHTML = "<div align='center'>opening "+opening.tostring()+" | closing "+closing.tostring()+"</div>";
	
	if((dir[element_id]==1 && cur_height[element_id] < old_height[element_id]) || (dir[element_id]==-1 && cur_height[element_id]>1))
		setTimeout("Resize('"+element_id+"')",10)
	else {
		time[element_id]=0;		
		if(dir[element_id]==1) {
			opening.remove(element_id);
			opened = element_id;
			document.getElementById(element_id).style.height = '';
		} else {
			closing.remove(element_id);
			document.getElementById(element_id).style.display = 'none';
		}
	}
}