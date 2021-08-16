 
  // Gen variables
  var rowVisible = true;
  var count = 0;
 
  // Color Variables
  if(defaultColor == null){
	var defaultColor = '#0000ff';
  }
  
  if(contrastStyle == null){
	var contrastStyle = 'light';
  }
  
  var darkColor;
  var superDarkColor;
  var lightColor;
  var superLightColor;
  var myDomainData;
  
  $(document).ready(function() {    
    // Change component colors based on default, dark, and lighter colors
    changeComponentColors();
    
    // Set initial contrast style
    $('#themeContrastSelect').val(contrastStyle);
    
    $('#themeContrastSelect').on('change', function() {
		var optionPicked = $(this).val();
		if(optionPicked == "light"){
			updateUserContrastThemeOption("light");
		}else if(optionPicked == "dark"){
			updateUserContrastThemeOption("dark");
		}
		contrastStyle = optionPicked;
		changeComponentColors();
	});    
    
    // Color Picker Loader
    $('#colorpicker').ColorPicker({
    
       color: defaultColor,
  	   onShow: function (colpkr) {
  		    $(colpkr).fadeIn(500);
  		    return false;
  	   },
  	   onHide: function (colpkr) {
  		    $(colpkr).fadeOut(500);
  		    
  		    // Send ajax call and store setting in DB
			updateUserThemeColorOption();
			
  		    return false;
  	   },
  	   onChange: function (hsb, hex, rgb) {
		   
		  // Get the selected color
          var newColor = '#' + hex;
		  defaultColor = newColor;
		 
          changeComponentColors();
         
  	   }
    }); 
    
    // Could be done a popup, but I've come up with a better idea!
    /*
    $(".selectDomainNameLink").click(function(e){
		e.preventDefault();
		var ahrefSrc = $(this).attr('href');
		
		if(ahrefSrc != null && ahrefSrc != ""){
			var htmlForPopup = '<div class="white-popup"><div class="magnificContentsDiv">';
			htmlForPopup += '<iframe class="magnificIframe" src="' + ahrefSrc + '"></iframe>';
			htmlForPopup += "</div></div>";
			// Open directly via API
			$.magnificPopup.open({
				items: {
					src: htmlForPopup,
					type: 'inline'
				},
				callbacks: {
					open: function() {
						modifyMagnificThemeColors();
					},
					close: function() {
						deselectDomain();
					}
				}
			});	
		}
	});
	*/
	
	$("div.header").click(function(e){
		location.href="index.php?op=deselectdomain";
	});
	
	// Events for dropdown menus
	$(document).on("mouseenter", ".menuItem", function(){
		$(this).find('.ddMenu').slideDown(200);
	});
	$(document).on("mouseleave", ".menuItem", function(){
		$(this).find('.ddMenu').hide();
	});
	$(document).on("click", ".fakeLink", function(){
		e.preventDefault();
	});
	
	// Process drop down menu item clicks 
	$(document).on("click", ".ddMenuLink, .openInWindowLink", function(e){
		var goToURL = $(this).find('a').attr("href");
		if(!$(this).hasClass("sameWindow")){
			if(goToURL != null && goToURL != ""){
				window.open(goToURL);
			}
		}else{
			if(goToURL != null && goToURL != ""){
				location.href=goToURL;
			}
		}
	});
	
	// Check if domain has been selected and run domain selected function
	checkDomainSelected();
	
	// Go and add check all and uncheck all to genericList tables
	addSelectAllAndDeselectAll();
  });
  
  function getColor(action, iterations){
	  var colorToReturn = defaultColor;
	  
	  if(action == "light"){
		  for (var i = 0; i < iterations; i++) {
			colorToReturn = $.xcolor.lighten(colorToReturn).getHex();
		  }
	  }else if(action == "dark"){
		  for (var i = 0; i < iterations; i++) {
			colorToReturn = $.xcolor.darken(colorToReturn).getHex();
		  }
	  }
	  
	  return colorToReturn; 
	  
  }
  
  function setControls(){   
	  
	if(contrastStyle == "light"){
		// Set background colors
		$('.darkBG').css('backgroundColor', darkColor);
		$('.lightBG').css('backgroundColor', lightColor);
		$('.colorBG').css('backgroundColor', defaultColor);
		$('.superDarkBG').css('backgroundColor', superDarkColor);
		$('.superLightBG').css('backgroundColor', superLightColor);
		$('body').css('backgroundColor', lightColor);
		$('a').css('color', superDarkColor);
		$('p').css('color', '#000000');
		$('div.footer p').css('color', '#FFFFFF');
		$('input').css('color', '#000000');
		$('input').css('backgroundColor', '#FFFFFF');
		$('textarea').css('color', '#000000');
		$('textarea').css('backgroundColor', '#FFFFFF');
		$('input[type=submit], button, input[type=button]').css('backgroundColor', lightColor);
		$('input[type=submit], button, input[type=button]').css('color', '#000000');
		$('.ehcp_content').css('backgroundColor', '#FFFFFF');
		$('.sessionBar').css('backgroundColor', '#FFFFFF');
		$('tr.itemRow').css('backgroundColor', '#FFFFFF');
		$('div.similarFunctions a').css('backgroundColor', lightColor);
		$('.success').css('color', '#69C657');
		$('.error').css('color', '#FF0000');

		
		// Set text colors
		$('.darkText').css('color', darkColor);
		$('.lightText').css('color', lightColor);
		$('.colorText').css('color', defaultColor);
		$('.menuItem').css('color', '#FFFFFF');
		$('.changeColor').css('color', '#000000');
		$('td.category').css('color', '#000000');
		$('.titleDiv').css('color', '#FFFFFF');
		$('.inWidth').css('color', '#000000');
		
		// Set borders
		$('.darkBorderBottom').css('border-bottom', 'solid 4px ' + darkColor);
		$('input[type=text]').css('border', 'solid 2px ' + darkColor);
		$('input[type=password]').css('border', 'solid 2px ' + darkColor);
		$('input[type=submit], button, input[type=button]').css('border', 'solid 2px ' + darkColor);
		$('textarea').css('border', 'solid 2px ' + darkColor);
		$('td.list, th.list').css('border', 'solid 1px black');
		$('table.genericList td, table.genericList th').css('border', 'solid 1px black');
		
		// Update color picker control div
		$('#colorpicker').css('backgroundColor', defaultColor);
		$('#colorpicker').css('border-color', superDarkColor); 
		
		// Set hover events to modify hover attributes
		$('.menuItem').hover(function(){
			$(this).css('backgroundColor', lightColor);
			$(this).css('color', superDarkColor);
		},function() {
			$(this).css('backgroundColor', darkColor);
			$(this).css('color', '#FFFFFF');
		});
		
		$('div.items').hover(function(){
			$(this).css('backgroundColor', darkColor);
			$(this).css('color', '#FFFFFF');
		},function() {
			$(this).css('backgroundColor', '');
			$(this).css('color', darkColor);
		});
		
		$('tr.itemRow').hover(function(){
			$(this).css('backgroundColor', lightColor);
			$(this).find('td.category').css('font-weight', 'bold');
		},function() {
			$(this).css('backgroundColor', '');
			$(this).find('td.category').css('font-weight', 'normal');
		});
		
		$('a').hover(function(){
			$(this).css('color', darkColor);
		},function() {
			$(this).css('color', superDarkColor);
		});
		
		$('div.similarFunctions a').hover(function(){
			$(this).css('backgroundColor', darkColor);
			$(this).css('color', lightColor);
		},function() {
			$(this).css('backgroundColor', lightColor);
			$(this).css('color', superDarkColor);
		});
		
		$('input[type=submit], button, input[type=button]').hover(function(){
			$(this).css('border', '2px solid ' + superDarkColor);
		},function(){	
			$(this).css('border', '2px solid ' + darkColor);
		});
		
		$('input[type=text]').focus(function(){
			$(this).css('border', '2px solid ' + defaultColor);
			$(this).css('box-shadow', '0 0 10px ' + defaultColor);
		});
		
		$('input[type=text]').blur(function(){
			$(this).css('border', '2px solid ' + darkColor);
			$(this).css('box-shadow', '');
		});
		
		$('input[type=password]').focus(function(){
			$(this).css('border', '2px solid ' + defaultColor);
			$(this).css('box-shadow', '0 0 10px ' + defaultColor);
		});
		
		$('input[type=password]').blur(function(){
			$(this).css('border', '2px solid ' + darkColor);
			$(this).css('box-shadow', '');
		});
		
		$('textarea').focus(function(){
			$(this).css('border', '2px solid ' + defaultColor);
			$(this).css('box-shadow', '0 0 10px ' + defaultColor);
		});
		
		$('textarea').blur(function(){
			$(this).css('border', '2px solid ' + darkColor);
			$(this).css('box-shadow', '');
		});
		
		// Drop down items:
		$(document).off("mouseenter", ".ddMenuLink").on("mouseenter", ".ddMenuLink", function(){
			$(this).css('backgroundColor', lightColor);
			$(this).css('color', superDarkColor);
			$(this).find('.fakeLink').css('color', superDarkColor);
		});
		$(document).off("mouseleave", ".ddMenuLink").on("mouseleave", ".ddMenuLink", function(){
			$(this).css('backgroundColor', darkColor);
			$(this).css('color', '#FFFFFF');
			$(this).find('.fakeLink').css('color', '#FFFFFF');
		});		
		
	}else if(contrastStyle == "dark"){
		// Set background colors
		$('.darkBG').css('backgroundColor', lightColor);
		$('.lightBG').css('backgroundColor', darkColor);
		$('.colorBG').css('backgroundColor', defaultColor);
		$('.superDarkBG').css('backgroundColor', superLightColor);
		$('.superLightBG').css('backgroundColor', superDarkColor);
		$('body').css('backgroundColor', darkColor);
		$('a').css('color', superLightColor);
		$('p').css('color', '#FFFFFF');
		$('div.footer p').css('color', '#FFFFFF');
		$('input').css('color', '#FFFFFF');
		$('input').css('backgroundColor', '#000000');
		$('textarea').css('color', '#FFFFFF');
		$('textarea').css('backgroundColor', '#000000');
		$('input[type=submit], button, input[type=button]').css('backgroundColor', superLightColor);
		$('input[type=submit], button, input[type=button]').css('color', '#000000');
		$('.ehcp_content').css('backgroundColor', superDarkColor);
		$('.sessionBar').css('backgroundColor', superDarkColor);
		$('tr.itemRow').css('backgroundColor', darkColor);
		$('div.similarFunctions a').css('backgroundColor', darkColor);
		$('.success').css('color', '#58ff53');
		$('.error').css('color', '#fc83ff');
		

		// Set text colors
		$('.darkText').css('color', lightColor);
		$('.lightText').css('color', darkColor);
		$('.colorText').css('color', defaultColor);
		$('.menuItem').css('color', darkColor);
		$('.changeColor').css('color', '#FFFFFF');
		$('td.category').css('color', '#FFFFFF');
		$('.titleDiv').css('color', '#000000');
		$('.inWidth').css('color', '#FFFFFF');
		
		// Set borders
		$('.darkBorderBottom').css('border-bottom', 'solid 4px ' + lightColor);
		$('input[type=text]').css('border', 'solid 2px ' + lightColor);
		$('input[type=password]').css('border', 'solid 2px ' + lightColor);
		$('input[type=submit], button, input[type=button]').css('border', 'solid 2px ' + defaultColor);
		$('textarea').css('border', 'solid 2px ' + lightColor);
		$('td.list, th.list').css('border', 'solid 1px white');
		$('table.genericList td, table.genericList th').css('border', 'solid 1px white');
		
		// Update color picker control div
		$('#colorpicker').css('backgroundColor', defaultColor);
		$('#colorpicker').css('border-color', superLightColor); 
		
		// Set hover events to modify hover attributes
		$('.menuItem').hover(function(){
			$(this).css('backgroundColor', darkColor);
			$(this).css('color', lightColor);
		},function() {
			$(this).css('backgroundColor', lightColor);
			$(this).css('color', '#000000');
		});
		
		$('div.items').hover(function(){
			$(this).css('backgroundColor', lightColor);
			$(this).css('color', '#000000');
		},function() {
			$(this).css('backgroundColor', '');
			$(this).css('color', lightColor);
		});
		
		$('tr.itemRow').hover(function(){
			$(this).css('backgroundColor', '#000000');
			$(this).find('td.category').css('font-weight', 'bold');
		},function() {
			$(this).css('backgroundColor', darkColor);
			$(this).find('td.category').css('font-weight', 'normal');
		});
		
		$('a').hover(function(){
			$(this).css('color', lightColor);
		},function() {
			$(this).css('color', superLightColor);
		});
		
		$('div.similarFunctions a').hover(function(){
			$(this).css('backgroundColor', lightColor);
			$(this).css('color', superDarkColor);
		},function(){	
			$(this).css('backgroundColor', darkColor);
			$(this).css('color', superLightColor);
		});
		
		$('input[type=submit], button, input[type=button]').hover(function(){
			$(this).css('border', '2px solid ' + lightColor);
		},function(){	
			$(this).css('border', '2px solid ' + defaultColor);
		});
		
		$('input[type=text]').focus(function(){
			$(this).css('border', '2px solid ' + defaultColor);
			$(this).css('box-shadow', '0 0 10px ' + defaultColor);
		});
		
		$('input[type=text]').blur(function(){
			$(this).css('border', '2px solid ' + lightColor);
			$(this).css('box-shadow', '');
		});
		
		$('input[type=password]').focus(function(){
			$(this).css('border', '2px solid ' + defaultColor);
			$(this).css('box-shadow', '0 0 10px ' + defaultColor);
		});		
	
		$('input[type=password]').blur(function(){
			$(this).css('border', '2px solid ' + lightColor);
			$(this).css('box-shadow', '');
		});
		
		$('textarea').focus(function(){
			$(this).css('border', '2px solid ' + defaultColor);
			$(this).css('box-shadow', '0 0 10px ' + defaultColor);
		});		
	
		$('textarea').blur(function(){
			$(this).css('border', '2px solid ' + lightColor);
			$(this).css('box-shadow', '');
		});
		
		// Drop down items:
		$(document).off("mouseenter", ".ddMenuLink").on("mouseenter", ".ddMenuLink", function(){
			$(this).css('backgroundColor', darkColor);
			$(this).css('color', lightColor);
			$(this).find('.fakeLink').css('color', lightColor);
		});
		$(document).off("mouseleave", ".ddMenuLink").on("mouseleave", ".ddMenuLink", function(){
			$(this).css('backgroundColor', lightColor);
			$(this).css('color', '#000000');
			$(this).find('.fakeLink').css('color', '#000000');
		});
	}
	
	handleDropDownThemeing();
	
  }
  
  function changeComponentColors(){
	// Get our color variants
    darkColor = getColor('dark', 3);
    lightColor = getColor('light', 3);
    superDarkColor = getColor('dark', 5);
    superLightColor = getColor('light', 5);
    
    // Set the controls based on initial load
    setControls();
  }

  function updateUserThemeColorOption(){
	  $.ajax({
		url: "index.php?op=updatethemecolor",
		type: "POST",
		data: { theme_color:defaultColor },
	  }).done(function() {
		$('#infobox').html('<p class="success">Saved color settings in the database!</p>');
	  });
  }
  
  function updateUserContrastThemeOption(contrast_opt){
	  $.ajax({
		url: "index.php?op=updatethemecontrast",
		type: "POST",
		data: { theme_contrast:contrast_opt },
	  }).done(function() {
		$('#infobox').html('<p class="success">Saved theme contrast style in the database!</p>');
	  });
  }
  
  function modifyMagnificThemeColors(){
	if(contrastStyle == "light"){
		$(".white-popup").css('outline', 'solid 10px ' + lightColor);
		$(".magnificTitle").css('background-color', darkColor);
		  
		$('.mfp-close').hover(function(){
			$(this).css('backgroundColor', superDarkColor);
		},function() {
			$(this).css('backgroundColor', 'transparent');
		});
	}else if(contrastStyle == "dark"){
		$(".white-popup").css('border', 'solid 10px ' + darkColor);
		$(".magnificTitle").css('background-color', lightColor);
		$(".magnificTitle").css('color', superDarkColor);
		$('.mfp-close').css('color', '#000000');
		$('.magnificContentsDiv').css('background-color', darkColor);
		$('.magnificContentsDiv').css('color', '#FFFFFF');
		
		$('.mfp-close').hover(function(){
			$(this).css('backgroundColor', superLightColor);
			$(this).css('color', '#000000');
		},function() {
			$(this).css('backgroundColor', 'transparent');
			$(this).css('color', '#000000');
		});
	}
  }
 
  function collapse(image){
  
	// Find parent table element
	
	var tbl=image.parentNode;
	while (tbl && tbl.nodeName !== "TABLE") {
		tbl = tbl.parentNode;
	}

	// Now we can manipulate the entire table
	var tblRows = tbl.rows;
	for(var i=0; i<tblRows.length; i++){
		if(tblRows[i].className != "display"){
			tblRows[i].style.display = (rowVisible) ? "none" : "";
		}
	}
	rowVisible = !rowVisible;
	count++;
	if(count % 2 ==0){
		image.src="templates/ep-ic/general/images/collapse.gif";
	}else{
		image.src="templates/ep-ic/general/images/expand.gif";
	}
	
  }
  
  function checkDomainSelected(){
	  if(domainSelected){
		  // Remove text, add icon class, and shrink to 10%
		  var firstChild = $("div.menu .menuItem:nth-child(1)");
		  firstChild.width('10%');
		  firstChild.text("Home");
		  firstChild.addClass("homeBackgroundIcon");
		  
		  // Hide first select action
		  $("div.menu .menuItem:nth-child(2)").hide();
		  
		  // Resize all but the first
		  $("div.menu .menuItem:visible").each(function(e){
			if(!$(this).is(firstChild)){
				$(this).width('30%');
			}
		  });
		  
		  // Create domain select dropdown on 3rd item
		  buildDomainDropDownSelect($("div.menu .menuItem:nth-child(3)"));
		  
		  // Add floating top menu with select list to change domain quickly :)
		  buildFloatBar();
		  
	  }else{
		  $("div.menu .menuItem:nth-child(3)").hide();
		  $("div.menu .menuItem:visible").each(function(e){
			$(this).width('25%');
		  });
		  
		  // Create domain select dropdown on 2nd item
		  buildDomainDropDownSelect($("div.menu .menuItem:nth-child(2)"));
	  }
  }
  
  function buildDomainDropDownSelect(container){
	  // Make AJAX call to get user's domains
	  $.ajax({
		type: "POST",
		url: "index.php?op=getmydomainsobject",
		success: function(data) {
			myDomainData = data;
			if(myDomainData.length && typeof myDomainData != "undefined" && myDomainData != null && myDomainData != "" && myDomainData != "undefined"){
			  $(container).addClass("dropDownIcon");
			  var containerInnerHTML='<ul class="ddMenu">';
			  for(var i = 0; i < myDomainData.length; i++){ 
				var opStr = "index.php?op=choosedomaingonextop&nextop=&domainname=" + myDomainData[i].domainname;
				var domainLi = '<li class="ddMenuLink sameWindow"><a class="fakeLink" href="' + opStr + '">' + myDomainData[i].domainname + '</a></li>';
				containerInnerHTML += domainLi;
			  }
			  containerInnerHTML += '</ul>';
			  
			  $(container).append(containerInnerHTML);
			  
			  $(".ddMenu").width('30%');
			  
			  handleDropDownThemeing();
			}
		}
	  });
  }
  
  function handleDropDownThemeing(){
	if(contrastStyle == "light"){
		$('.ddMenuLink').css('backgroundColor', darkColor);
		$('.fakeLink').css('color', '#FFFFFF');
		$('.ddMenuLink').css('outline', '2px solid ' + superDarkColor);
	}else if(contrastStyle == "dark"){
		$('.ddMenuLink').css('backgroundColor', lightColor);
		$('.fakeLink').css('color', darkColor);
		$('.ddMenuLink').css('outline', '2px solid ' + superLightColor);
	}
  }
  
  function handleFloatBarThemeing(){
	if(contrastStyle == "dark"){
		$('.topRightDiv').css('backgroundColor', lightColor);
		$('.chooseDomain').css('color', darkColor);
	}else if(contrastStyle == "light"){
		$('.topRightDiv').css('backgroundColor', darkColor);
		$('.chooseDomain').css('color', lightColor);
	}
  }
  
  function deselectDomain(){
	  $.ajax({
		url: "index.php?op=deselectdomain",
		type: "POST",
	  }).done(function() {
		  
	  });
  }
  
  function buildFloatBar(){
	  
	  var htmlFloatBar = '<div class="topRightDiv">';
	  htmlFloatBar += '<div class="inlineBlockItem fixedNavBarDomainPickerSide"><p class="chooseDomain">Domain:&nbsp; <select class="selectDomainDD"></select> &nbsp; <button class="switchDomainButton">Go</button></p></div>';
	  htmlFloatBar += '<div class="inlineBlockItem fixedNavBarLinkIconsSide"><a href="index.php?op=deselectdomain" title="Home"><img src="templates/picante/general/images/picante_home.png" class="fixedNavBarLinkIcon"></a><a href="index.php?op=logout" title="Logout"><img src="templates/picante/general/images/signout.png" class="fixedNavBarLinkIcon"></a></div>';
	  htmlFloatBar += '</div>';
	  $("body").append(htmlFloatBar);
	  
	  // Don't theme this since our icons are a specific color
	  // handleFloatBarThemeing();
	  
	  // Floaty bar
	  $(document).scroll(function() {
		if($(document).scrollTop() >= 200){
			$(".topRightDiv").css('position', 'fixed');
			$(".topRightDiv").css('display', 'block');
		}else{
			$(".topRightDiv").css('position', 'absolute');
			$(".topRightDiv").css('display', 'none');
		}
	  });
	  
	  // Build select list in floatbar with user's domains
	   
	  // Make AJAX call to get user's domains
	  $.ajax({
		type: "POST",
		url: "index.php?op=getmydomainsobject",
		success: function(data) {
			myDomainData = data;
			for(var i = 0; i < myDomainData.length; i++){ 
				var optionStr = '<option value="' + myDomainData[i].domainname + '" title="' + myDomainData[i].domainname + '">' + myDomainData[i].domainname + '</option>';
				$(".selectDomainDD").append(optionStr);
			}
			// Select the currently active domain
			$(".selectDomainDD").val(selectedDomainName);
		}
	  });

	  $(".switchDomainButton").click(function() {
		 var opStr = "op=choosedomaingonextop&nextop=&domainname=" + $(".selectDomainDD").val();
	     location.href="index.php?" + opStr;
	  });
  }
  
  function addSelectAllAndDeselectAll(){
	  if($('table.genericList').length){
		  
		  //bind check all and uncheck all
		  $(document).on("click", ".checkAll", function(){
			  var parentTable = $(this).prevAll('table.genericList');
			  if(parentTable.length){
				  parentTable.find('input:checkbox').each(function(e){
					  $(this).prop('checked', true);
				  });
			  }
		  });
		  
		  $(document).on("click", ".uncheckAll", function(){
			  var parentTable = $(this).prevAll('table.genericList');
			  if(parentTable.length){
				  parentTable.find('input:checkbox').each(function(e){
					  $(this).prop('checked', false);
				  });
			  }
		  });
		  
		  // Add check all and uncheck all functionality after the table.
		  $('table.genericList').each(function(e){
			  $(this).after("<span class='checkAll'>Check All</span>&nbsp; | &nbsp;<span class='uncheckAll'>Uncheck All</span><br>");
		  });
	  }
  }
