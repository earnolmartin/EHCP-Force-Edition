var rowVisible = true;
var count = 0;
var colorLoad;
var colors=['green','blue','red','yellow','purple'];
var newColors =['Green','Blue','Red','Yellow','Purple'];
var i=0;
var check;
var found = 0;

function collapse(image, tbl){
	var tblRows = tbl.rows;
		for(i=0; i<tblRows.length; i++){
			if(tblRows[i].className != "display"){
					tblRows[i].style.display = (rowVisible) ? "none" : "";
			}
		}
		rowVisible = !rowVisible;
		count++;
		if(count % 2 ==0){
			image.src="images/collapse.gif";
		}else{
			image.src="images/expand.gif";
		}
}

function initialStyleSheet(){
	// Starting the creation of the select element.
	
	var td = document.getElementById('createSelectHere');
	var button = document.getElementById('createButtonHere');
	
	// Checking to see if the select already exists... if it does, we remove it!
	
	while(td.hasChildNodes()){
		td.removeChild(td.firstChild);
	}

	// Checking to see if button already exists... if it does, we remove it!
	
	while(button.hasChildNodes()){
		button.removeChild(button.firstChild);
	}

	// Create Select Element and Assign ID
	
	var se = document.createElement("select");
	se.id="selectedColor";

	// Create Button Element and Assign Functions

	var bu = document.createElement("input");
	bu.setAttribute("type","button");
	bu.setAttribute("value","Change");
	bu.setAttribute("id","color");
	bu.setAttribute("onclick", "changeColor(document.getElementById('selectedColor'));window.location.reload()");
	//bu.onclick = "changeColor(document.getElementById('selectedColor'))";
	//bu.onclick += 'window.location.reload()';
	
	if(document.cookie.length !=0){
		
		var cookieContents = document.cookie.split("; ");
		/*alert(cookieContents.toString());
		alert(cookieContents.length);*/
		var i = 0;
		while(i<cookieContents.length & found==0){
			var end = cookieContents[i].indexOf("=");
			check = cookieContents[i].substring(0, end);
			if(check == 'color'){
				found = 1;
				colorLoad = unescape(cookieContents[i].substring(end + 1, cookieContents[i].length));
			}
			i++;
			/*alert(found);
			alert(colorLoad);
			alert(check);*/
		}
	}
	
	if(found == 0){
		colorLoad = 'green';
		for(i=0; i<colors.length; i++){
			se.options[i] = new Option(newColors[i],colors[i]);
		}
	}else{
		
		//alert(colorLoad);
		// Pulls the color out of the array so we can create the select.
		for(i=0; i<colors.length; i++){
			if(colors[i] == colorLoad){
				//alert(colors[i]);
				colors.splice(i, 1);
				//alert(colors.toString());
				newColors.splice(i, 1);
				//alert(newColors.toString());
			}
		}
		var capitalize = (colorLoad.substring(0, 1).toUpperCase() + colorLoad.substring(1, colorLoad.length));
		//alert(capitalize);
		se.options[0] = new Option(capitalize, colorLoad);
	
		for(i=0; i<colors.length; i++){
			se.options[i+1] = new Option(newColors[i],colors[i]);
	    }
		
	}
	
	// Starts the stylesheet loading:
	
	
	switch(colorLoad){
	case 'green':
		document.getElementById('logo').src = 'images/logo.png';
		document.getElementById('stylesheet').href = 'css/test/progreen.css';
	break;
	case 'blue':
		document.getElementById('logo').src = 'images/logo_blue.png';
		document.getElementById('stylesheet').href = 'css/test/problue.css';
	break;
	case 'red':
		document.getElementById('logo').src = 'images/logo_red.png';
		document.getElementById('stylesheet').href = 'css/test/prored.css';
	break;
	case 'yellow':
		document.getElementById('logo').src = 'images/logo_yellow.png';
		document.getElementById('stylesheet').href = 'css/test/proyellow.css';
	break;
	case 'purple':
		document.getElementById('logo').src = 'images/logo_purple.png';
		document.getElementById('stylesheet').href = 'css/test/propurple.css';
	break;
	}
	
	td.appendChild(se);
	button.appendChild(bu);
	
	/* ReCreate Array:
	colors=['green','blue','red','yellow','purple'];
	newColors =['Green','Blue','Red','Yellow','Purple'];*/
}

function setCookie(value,expiredays){
//alert('wtf');
	if(document.cookie.length != 0){
		document.cookie="color" + '=; expires=Thu, 01-Jan-97 00:00:01 GMT;';
	}
	
			var exdate=new Date();
		exdate.setDate(exdate.getDate()+expiredays);
		document.cookie="color" + "=" +escape(value)+
		((expiredays==null) ? "" : ";expires="+exdate.toUTCString());
		//alert(document.cookie.value);

}



function changeColor(color){
	var colorPicked = color.value;
	switch(colorPicked){
	case 'green':
		colorLoad = 'green';
		color.options[0].selected = true;
	break;
	case 'blue':
		colorLoad = 'blue';
		color.options[1].selected = true;
	break;
	case 'red':
		colorLoad = 'red';
		color.options[2].selected = true;
	break;
	case 'yellow':
		colorLoad = 'yellow';
		color.options[3].selected = true;
	break;
	case 'purple':
		colorLoad = 'purple';
		color.options[4].selected = true;
	break;
	}
	setCookie(colorLoad, 30);
}