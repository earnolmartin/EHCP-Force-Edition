$(document).ready(function() {
	$("select#template_file").change(function(e){
		getGlobalTemplateFile($(this).val(), $("select#webserver_mode"), $("select#webserver_type"));
	});
	
	if($("select#template_file").length){
		$("select#template_file").trigger('change');
	}
	
	isPolicyDInstalled();
	getPublicServerSettings();
	handleExpanders();
	handleShowAdvancedAdminOptions();
});

function getGlobalTemplateFile(template, webserverMode, webserverType){
	$.get("index.php?op=getGlobalWebTemplate&template=" + template + "&server=" + webserverType + "&mode=" + webserverMode, function( data ) {
		try{
			if(data.hasOwnProperty('template_contents')){
				if($("textarea#template_contents").length){
					$("textarea#template_contents").val(data.template_contents);
				}
			}
			if(data.hasOwnProperty('using_default')){
				if(data.using_default){
					$("span.usingDefaultTemplateNo").css('display', 'none');
					$("span.usingDefaultTemplateYes").css('display', 'inline');
				}else{
					$("span.usingDefaultTemplateNo").css('display', 'inline');
					$("span.usingDefaultTemplateYes").css('display', 'none');
				}
			}
			if(data.hasOwnProperty('error_reading_file')){
				if(data.error_reading_file){
					$("textarea#template_contents").val('');
					$("textarea#template_contents").attr("disabled","disabled"); 
				}
			}
		}catch(e){
			window.console && console.log(e);
		}
		
	});
}

function isPolicyDInstalled(){
	$.get("index.php?op=ispolicydinstalled", function( data ) {
		if(data.hasOwnProperty('policyDInstalled') && data.policyDInstalled === false){
			if($("div.itemPolicyd.items").length){
				$("div.itemPolicyd.items").hide();
			}
		}
	});
}

function getPublicServerSettings(){
	$.get("index.php?op=getpublicserversettings", function( data ) {
		// Remove custom http if it's not enabled
		if(data.hasOwnProperty('customhttp') && data.customhttp === false){
			var custHTTP = $("div.itemCustomhttp");
			if(custHTTP.length){
				custHTTP.remove();
			}
		}
		
		// Remove custom DNS if it's not enabled
		if(data.hasOwnProperty('customdns') && data.customdns === false){
			var custDNS = $("div.itemCustomdns");
			if(custDNS.length){
				custDNS.remove();
			}
		}
		
		// Remove SSL if not enabled
		if(data.hasOwnProperty('adddomainsslcert') && data.adddomainsslcert === false){
			var custSSL = $("div.itemAdddomainsslcert");
			if(custSSL.length){
				custSSL.remove();
			}
		}
		
		// Show correct web server type title
		if(data.hasOwnProperty('webservertype')){
			$(".itemDomainapache .itemText").text($(".itemDomainapache .itemText").text().replace('Apache', data.webservertype));
			$(".itemDomainapache img").attr('alt', $(".itemDomainapache img").attr('alt').replace('Apache', data.webservertype));
			$(".itemDomainapache").attr('title', $(".itemDomainapache").attr('title').replace('Apache', data.webservertype));
		}
		
		// Show and hide transfer option (only available to admins)
		if(!data.hasOwnProperty('isadmin') || (data.hasOwnProperty('isadmin') && !data.isadmin)){
			var transferDomContainer = $("div.itemMovedomaintoanotheraccount");
			if(transferDomContainer.length){
				transferDomContainer.remove();
			}
		}
	});
}

function handleExpanders(){
	$(".ehcp-all-expander").click(function(e){
		if(!$(this).hasClass('ehcp-all-active')){
			$(".ehcp-all-active").each(function(e){
				$(this).text("Click to " + $(this).text());
			});
			
			$(".ehcp-all-expander").removeClass('ehcp-all-active').removeClass('ehcp-all-clickme').addClass('ehcp-all-clickme');
			
			$(this).addClass('ehcp-all-active').removeClass('ehcp-all-clickme');
			
			// Remove Click to from text
			var currentText = $(this).text();
			currentText = currentText.replace("Click to ", "");
			$(this).text(currentText);
			
			var areaToExpand = $(this).attr('expand');
			if(areaToExpand){
				$(".ehcp-all-contentToExpand").removeClass('ehcp-all-hide').addClass('ehcp-all-hide');
				$("." + areaToExpand).removeClass('ehcp-all-hide');
			}
		}
	});
}

function handleShowAdvancedAdminOptions(){
	$("button.ehcp-all-showAdminAdvancedOptions").click(function(e){
		if(!$(this).hasClass('ehcp-all-active')){
			$(this).addClass('ehcp-all-active');
			$(this).text('Hide Advanced Admin Options');
			$(".ehcp-all-adminAdvancedOption", $(this).parent()).removeClass('ehcp-all-hide');
		}else{
			$(this).removeClass('ehcp-all-active');
			$(this).text('Show Advanced Admin Options');
			$(".ehcp-all-adminAdvancedOption", $(this).parent()).removeClass('ehcp-all-hide').addClass('ehcp-all-hide');
		}
	});
}
