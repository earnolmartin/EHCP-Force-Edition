<?php
@ini_set("date.timezone","UTC");

function imgextension($f){
	return isextension($f,'img');
}

function isoextension($f){
	return isextension($f,'iso');
}

function isextension($f,$e){
	$f=basename($f);
	$ext = explode(".", strtolower($f));
	$ext=array_pop($ext);
	#echo "isleniyor: $f : ext:($ext) \n";
	return ($ext==$e);
}

function mymkdir($dirname){
	$dirname=trim($dirname);
	if($dirname<>'.' and $dirname<>'..') {
		if(!is_dir($dirname)) {
			if(mkdir($dirname,777,true)) echo "\ndirectory is made: ($dirname)\n";
			else "\nerror occured while making directory: ($dirname)\n";
		}
	}
}

if(!function_exists("print_r2")){
function print_r2($array)
{
	if (is_array($array)) return "<pre>Array:\n".str_replace(array("\n" , " "), array('<br>', '&nbsp;'), print_r($array, true)).'</pre>';
	elseif ($array===null) return "(null) ";
	elseif ($array==="") return "(bosluk= \"\")";
	elseif ($array===false) return "(bool-false)";
	elseif ($array===true) return "(bool-true)";
	else {
		return "Array degil:<br>(normal gosterim:$array)<br>print_r:(".print_r($array,true).") <br>var_dump:".var_dump($array);
	}
}
}

if(!function_exists('print_r3')){
function print_r3($ar,$header='') {
if(!$ar) return "(BOS-EMPTY)";
if(!is_array) return "Not Array:".$ar;

$sayi=count($ar);
$tr="<tr class='list'>";
$td="<td class='list'>";

$res.="<table border=1 class='list'> $header";

foreach($ar as $key=>$val) {
	$res.="$tr$td".$key."</td>$td".$val."</td></tr>";
}

$res.="</table>";
return $res;

/*
ic ice (recursive) yapmak icin, 
en basa, if(!is_array($ar)) return $ar;
$res.="<tr><td>".print_r3(key($ar))."</td><td>".print_r3($val)."</td></tr>";
*/
}
}

if(!function_exists("andle")){
function andle($s1,$s2) { //iki string'in andlenmi halini bulur. bir bosa "and" kullanlmaz. delphiden aldim..:)
  if($s1=='')$s1=$s2;
  elseif ($s2<>'')$s1=$s1.' and '.$s2;
  return $s1;
}
}

function to_array($ar){ # convert a variable to array if it is not already,
    if(is_array($ar)) return $ar;  # if array, dont do anything
    if(!$ar) return array(); # bos ise, bos array dondur.
    if(!is_array($ar)) return array($ar); # array olmayan bir degisken ise, arraya dondur ve return et.
    return "(arraya cevirme yapilamadi.)"; # hicbiri degilse hata var zaten.
}

function array_merge2($ar1,$ar2){
    return array_merge(to_array($ar1),to_array($ar2));
}


if(!function_exists("writeoutput")){
function writeoutput($file, $string, $mode="w",$log=true) {

	mymkdir(dirname($file)); # auto make the dir of filename

	if (!($fp = fopen($file, $mode))) {
			echo "hata: dosya acilamadi: $file (writeoutput) !";
			return false;
	}
	if (!fputs($fp, $string . "\n")) {
			fclose($fp);
			echo "hata: dosyaya yazilamadi: $file (writeoutput) !";
			return false;
	}



	fclose($fp);
	if($log) echo "\n".basename(__FILE__).": file written successfully: $file, mode:$mode \n";
	return true;
}
}

if(!function_exists("writeoutput2")){
function writeoutput2($file, $string, $mode="w",$debug=true) {
	$file=removeDoubleSlash($file);

	if ($debug){
		echo "\n".__FUNCTION__.":*** Writing to file ($file) the contents:\n\n$string\n\n";
	}

	mymkdir(dirname($file)); # auto make the dir of filename

	if (!($fp = fopen($file, $mode))) {
			echo "hata: dosya acilamadi: $file (writeoutput) !";
			return false;
	}
	if (!fputs($fp, $string . "\n")) {
			fclose($fp);
			echo "hata: dosyaya yazilamadi: $file (writeoutput) !";
			return false;
	}
	fclose($fp);
	return true;
}
}


if(!function_exists("alanlarial")){
function alanlarial($db2,$tablo) { // adodb de calsyor.
        foreach($db2->MetaColumnNames($tablo) as $alan) $alanlar[]=$alan;
    return $alanlar;
}
}

if(!function_exists("strop")){
function strop($str,$bas,$son) {
        return $bas.$str.$son;
}
}

if(!function_exists("arrayop")){
function arrayop($arr,$op) {
        foreach($arr as $ar) $ret[]=$op($ar,"{","}");
    return $ret;
}
}

if(!function_exists("executeprog2")){
function executeprog2($prog){ // echoes output.
        passthru($prog, $val);
        return ($val==0);
}
}

if(!function_exists('executeProg3')){
function executeProg3($prog,$echooutput=False){
	# executes program and return output
	if($echooutput) echo "\n".__FUNCTION__.": executing: ($prog)\n";
	exec($prog,$topcmd);
	if(!is_array($topcmd)) return "";
	foreach($topcmd as $t) $topoutput.=$t."\n";
	$out=trim($topoutput);
	if($echooutput and ($out<>'')) echo "\n$out\n";
	return $out;
}
}

if(!function_exists("executeprog")){
function executeprog($prog){ // does not echo output. only return it.
        $fp = popen("$prog", 'r');
        if(!$fp){
        	return "<br>Cannot Execute: $prog ".__FUNCTION__;
        }
        $read = fread($fp, 8192);
        pclose($fp);
        return $read;
}
}

if(!function_exists('degiskenal')){
function degiskenal($degiskenler) {
	$alansayisi=count($degiskenler);
	for ($i=0;$i<$alansayisi;$i++) {
		global ${$degiskenler[$i]};
		if($_POST[$degiskenler[$i]]<>"") ${$degiskenler[$i]}=$_POST[$degiskenler[$i]];
		else ${$degiskenler[$i]}=$_GET[$degiskenler[$i]];
		$degerler[]=${$degiskenler[$i]};
	};
	return $degerler;
}
}

if(!function_exists('replacelineinfile')){
function replacelineinfile($find,$replace,$where,$addifnotexists=false) {
	// edit a line starting with $find, to edit especially conf files..

	debugecho("\nreplaceline: ($find -> $replace) in ($where) \n ");
	$bulundu=false;

	$filearr=@file($where);
	//if($find=='$dbrootpass=') print_r($filearr);

	if(!$filearr) {
		echo "cannot open file... returning...\n";
		return false;
	} //else print_r($file);

	$len=strlen($find);
	$newfile=array();

	foreach($filearr as $line){
		$line=trim($line)."\n";
		$sub=substr($line,0,$len);
		if($sub==$find) {
			$line=$replace."\n";
			$bulundu=true;
		}
		$newfile[]=$line;

	}

	if($addifnotexists and !$bulundu){
		echo "Line not found, adding at end: ($replace)\n";
		$newfile[]=$replace;
	}

	return arraytofile($where,$newfile);
}

function replaceOrAddLineInFile($find,$replace,$where){
	return replacelineinfile($find,$replace,$where,true);
}

}


if(!function_exists("addifnotexists")){
	function addifnotexists($what,$where) {
		debugecho("\naddifnotexists: ($what) -> ($where) \n ",4);
		#bekle(__FUNCTION__." basliyor..");
		$what.="\n";
		$filearr=@file($where);
		if(!$filearr) {
			echo "cannot open file, trying to setup: ($where)\n";
			$fp = fopen($where,'w');
			fclose($fp);
			$filearr=file($where);

		} //else print_r($file);

		if(array_search($what,$filearr)===false) {
			echo "dosyada bulamadı ekliyor: $where -> $what \n";
			$filearr[]=$what;
			arraytofile($where,$filearr);

		} else {
			//echo "buldu... sorun yok. \n";
			// already found, so, do not add
		}
		
		#bekle(__FUNCTION__." bitti...");

	}
}

if(!function_exists("removeifexists")){
	function removeifexists($what,$where) {
		debugecho("\nremoveifexists: ($what) -> ($where) \n ",4);
		$filearr=@file($where);
		if(!$filearr) {
			echo "cannot open file, trying to setup: ($where)\n";
			$fp = fopen($where,'w');
			fclose($fp);
			$filearr=file($where);

		}
		
		if(is_array($filearr) && count($filearr) > 0){
			$newFileArr = array();
			foreach($filearr as $line){
				if(!startsWith($line, $what)){
					$newFileArr[] = $line;
				}
			}
			
			if(is_array($newFileArr) && count($newFileArr) > 0){
				arraytofile($where,$newFileArr);
			}
		}
	}
}


if(!function_exists('getlocalip')){
function getlocalip($interface='eth0') {
	global $localip;
	
	$ipline=exec("ifconfig | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | head -1");
	
	if(!isset($ipline) || empty($ipline)){
		$ipline = "127.0.0.1";
	}
	
	$localip=$ipline;
	
	return $ipline;	
	
}
}

if(!function_exists("debugecho")){
function debugecho($str,$level=0) {
	$currentlevel=4;
	if($level>=$currentlevel) echo $str;

}
}


if(!function_exists("arraytofile")){
function arraytofile($file,$lines) {
	$new_content = join('',$lines);
	$fp = fopen($file,'w');
	$write = fwrite($fp, $new_content);
	fclose($fp);
}
}

function inputform5ForTableConfig($tableConfig,$addArray,$isAdmin=false){
	// $isAdmin can be used in combo with another parameter in an input array to determine if a field should be enabled for administrators or disabled for non-administators including resellers.
	// It's an optional parameter that can be used to limit things further during form construction depending on which user submitted the request to build a form, so to speak
	// You have to tell this function how to use it, as it really doesn't have any purpose other than being a value you can use should you need it.
	
	# written for compatibility with inputform5 general function.
	# convert a table config (like in start of classapp.php, 'subdomainstable'=>array....) to an array that is acceptable by function inputform5 and call inputform5
	$fields=$tableConfig['insertfields'];
	$fields2=array();
	$say=count($fields);

	for($i=0;$i<$say;$i++) {
		if(is_array($fields[$i])) $newitem=$fields[$i]; # accept fields both arrays and non-arrays
		else $newitem=array($fields[$i]);
		if($tableConfig['insertfieldlabels'][$i]<>'') $newitem['lefttext']=$tableConfig['insertfieldlabels'][$i];
		$fields2[]=$newitem;
	}

	#$out.="Say:$say, <br>insertFields".print_r2($fields).print_r2($fields2);
	$fields2=array_merge($fields2,$addArray);
	#$out.=print_r2($fields2);
	return $out.inputform5($fields2,'',$isAdmin);

}

function inputform5($alanlar,$action='',$isAdmin = false) {
	// $isAdmin can be used in combo with another parameter in an input array to determine if a field should be enabled for administrators or disabled for non-administators including resellers.
	// It's an optional parameter that can be used to limit things further during form construction depending on which user submitted the request to build a form, so to speak
	// You have to tell this function how to use it, as it really doesn't have any purpose other than being a value you can use should you need it.
	
	global $debuglevel,$output;
/*
 * general purpose input form generator. examples below.
 *
sadece echo yapmaz.
degistirildi. artik textarea gosterebiliyor.
$res.="alanlar:".print_r2($alan);
$res.="degerler:".print_r2($deger);
 */
	if(!is_array($alanlar)) $alanlar=array($alanlar);# convert to array if not , i.e, you dont need to use an array if you only has one input element,
	$alanlar[]=array('_insert','tip'=>'hidden','varsayilan'=>'1');
	$alansayisi=count($alanlar);

	$res.="
	<script> // script for pass generate
var keylist='abcdefghijklmnopqrstuvwxyz123456789'
var temp=''
function generatepass(){
	temp=''
	for (i=0;i<6;i++)
	temp+=keylist.charAt(Math.floor(Math.random()*keylist.length))
	return temp
}
</script>

	<form method=post enctype='multipart/form-data' ";
	
	if($action<>""){$res.=" action='$action'";};
	$res.="><table class='inputform'>";

	if($debuglevel>2) $output.=print_r2($alanlar);

	foreach($alanlar as $alan)
		$res.=inputelement2($alan, $isAdmin);


	$res.="</table>";
	if(strstr($res,"input type='submit' ")===false) $res.="<input type=submit>";
	$res.="</form>";
	
	return $res;
        /* this function is very flexible, cok esnek yani... ingilizce yazdik diye yanlis anlasilmasin, anadoluda yazildi bu...;)
         * example usages:
         * echo inputform5('name')   # displays only an input form with field name
         * echo inputform5(array('name','surname'))  # input form with name, surname
         * echo inputform5(array(array('name','varsayilan'=>'defaultname'),'surname'))  # using default value
         * etc...
         */

}

function inputelement2($alan, $isAdmin = false){
	// $isAdmin can be used in combo with another parameter in an input array to determine if a field should be enabled for administrators or disabled for non-administators including resellers.
	// It's an optional parameter that can be used to limit things further during form construction depending on which user submitted the request to build a form, so to speak
	// You have to tell this function how to use it, as it really doesn't have any purpose other than being a value you can use should you need it.

	if(!is_array($alan)) $alan=array($alan); # convert to array if not


	$solyazi=$alan['solyazi'].$alan['lefttext'];
	$alanadi=$alan['alanadi'].$alan['name'];
	$alantipi=$alan['tip'].$alan['type'];
	$sagyazi=$alan['sagyazi'].$alan['righttext'];
	
	// CSS Class for row
	$cssclass = $alan['cssclass'];
	if(!isset($cssclass) || empty($cssclass)){
		$cssclass = "";
	}
	
	$cols=$alan['cols'];
	$rows=$alan['rows'];
	$cols=($cols==""?40:$cols);
	$rows=($rows==""?10:$rows);

	if(!$alantipi or $alantipi=='') $alantipi=$alan[1]; # second array element is field type
	if(!$alantipi or $alantipi=='') $alantipi='text';


	if($alanadi=='') $alanadi=$alan[0]; # fieldname is the first element, if not defined as 'alanadi'=>'fieldname_example'
	
	// Left text handling
	if(!$solyazi and !in_array($alantipi,array('hidden','comment','submit'))){
		 $solyazi=ucwords($alanadi);
		 $lastCharacter = substr($solyazi, -1);
		 if($lastCharacter != ":"){
			// Append a Colon
			$solyazi = $solyazi . ":";
		 }
	}else if($solyazi && !in_array($alantipi,array('hidden','comment','submit'))){
		if(strpos($solyazi, ' ') != false){
			 $wordsInLeftText = explode(' ', $solyazi);
			 if(count($wordsInLeftText) <= 5){
				$solyazi=ucwords($solyazi);
			 }
		 }
		 $lastCharacter = substr($solyazi, -1);
		 if($lastCharacter != ":" && (!isset($alan['skip-ending-colon']) || $alan['skip-ending-colon'] != true)){
			// Append a Colon
			$solyazi = $solyazi . ":";
		 }
	}else if(in_array($alantipi,array('hidden','comment','submit'))){
		$solyazi = "";
	}
	if($alantipi=='comment') $span=" colspan=3 "; # no 3 columns for comment type


	$varsayilan=$alan['varsayilan'];
	if(!$varsayilan) $varsayilan=$alan['default'];

	if(!$varsayilan and $alan['value']<>'') $varsayilan=$alan['value'];
	if(!$varsayilan and $alan['deger']<>'') $varsayilan=$alan['deger']; # ister varsayilan, ister value, ister deger de, gine de calisir..
	if($deger=='') $deger=$value=$varsayilan;
	
	if($alan['readonly']<>'') $readonly='readonly="yes"';


	$res.="<tr class='inputform";
	if(!empty($cssclass)){
		$res .= " " . $cssclass;
	}
	$res .= "'><td class='inputform' $span>";
	if($span=='') $res.=$solyazi."</td>\n<td class='inputform'>"; # no need to a new td if there is a col span
	
	switch($alantipi) {
		case 'password_with_generate':
			#$alantipi='password';
			#$alantipi='text';
    /* Password generator by cs4fun.lv */
$res.="<input id='$alanadi' type='text' name='$alanadi' value='$varsayilan'></td><td>
<input type=\"button\" value=\"Generate:\" onClick=\"$('#$alanadi').val(generatepass());\">
$sagyazi</td>\n";
            break;
    /* END Password generator by cs4fun.lv */
		case 'comment':
			$res.="$varsayilan</td>\n";
			break;
		case 'hidden&text':
			$res.="<input id='$alanadi' type='hidden' name='$alanadi' value='$varsayilan'>$varsayilan</td>\n";
			break;
		case 'password':
		case 'text':
		case 'hidden':
			$res.="<input id='$alanadi' type='$alantipi' name='$alanadi' value='$varsayilan'></td>\n";
			break;
		case 'textarea':
			$res.="<textarea id='$alanadi' cols=$cols name='$alanadi' rows=$rows $readonly>$varsayilan</textarea> <br></td>\n";
			break;
		case 'checkbox':
				if($alan['checked']) $checked="checked=".$alan['checked'];
				else $checked='';
				if($alan['disabled'] == 'disabled' || (!empty($alan['requires_admin']) && ($alan['requires_admin'] == true) && !$isAdmin)) $disabledInput="disabled"; 
				else $disabledInput = '';
				if($deger=='') $deger=$alanadi;
				$res.="<input type='checkbox' name='$alanadi'  value='$varsayilan' $checked $disabledInput>".$alan['secenekyazisi']."</td>\n";
		break;

		case 'radio':
			foreach($alan['secenekler'] as $deger2=>$yazi2)
				$res.="<input type=radio name='$alanadi' value='$deger2' ".($varsayilan==$deger2?'checked':'').">$yazi2<br>";
			$res.="</td>";
/*
			echo print_r2($alan);
			echo "<br>(varsayilan:$varsayilan)<br>";
*/
		break;

		case 'select':			
            $res.="<select id='$alanadi' name='$alanadi'>\n\r";
            if(!is_array($alan['secenekler'])) $alan['secenekler']=$varsayilan;
            foreach($alan['secenekler'] as $deger2=>$yazi2) {
				if($varsayilan==$deger2){
					$sel=" selected='yes'";
				}
				$res.="<option value='$deger2'$sel>$yazi2</option>\n\r";
				$sel = "";
			}
            #for ($j=0;$j<$sayi;$j++) $res.="<option value='".$varsayilan[$j]."'>".$varsayilan[$j]."</option>\n\r";
            $res.="</select></td>\n";
		break;

		case 'fileupload':
			$res.="\n<td><input type='file' id='$alanadi' name='$alanadi'></td>\n";
		break;
		
		case 'submit':
			if($deger == "No/Yes"){ // Special no yes confirm case
				$res.="\n<input type='submit' id='$alanadi' name='$alanadi' value='No'>&nbsp; <input type='submit' id='$alanadi' name='$alanadi' value='Yes'>\n";
			}else{
				$res.="\n<input type='submit' id='$alanadi' name='$alanadi' value='$deger'>\n";
			}
		break;


		default:
			$res.="<input type='text' id='$alanadi' name='$alanadi' value='$deger'></td>\n";
	}

	if($span=='' and $alantipi<>'password_with_generate') $res.="<td>$sagyazi</td>";
	
	#$res.="<td>($alantipi)</td></tr>\n";
	$res.="</tr>\n";
	return $res;
}

if(!function_exists("tablobaslikyaz")){
function tablobaslikyaz($alan,$baslik,$extra) {// tablolistelede kullanilmak icin yazildi.
$tr="<tr class='list'>";
$td="<td class='list'>";
$th="<th class='list'>";

$alansayisi=count($alan);

		$result2=" \n $tr";
        if (count($baslik)>0)
        {
			for ($i=0;$i<$alansayisi;$i++){
				if($baslik[$i]<>"") {
					$yaz=$baslik[$i];
				} else {
					$yaz=$alan[$i];
				}
				$result2.="$th$yaz</th>";
			}
		}
        else
        {
			for ($i=0;$i<$alansayisi;$i++){
				$yaz=$alan[$i]; $result2.="$th$yaz</th>";
			};
        }
        
        // Handle extra
		for ($i=0;$i<count($extra);$i++){
			$indexToStart = count($baslik) - count($extra) + $i;
			if($alansayisi + count($extra) == count($baslik)){
				$result2.="$th" . (isset($baslik) && is_array($baslik) && array_key_exists($indexToStart, $baslik) && !empty($baslik[$indexToStart]) ? $baslik[$indexToStart] : "") . "</th>";
			}else{
				$result2.=$th . "</th>";
			}
		}
		
        $result2.="</tr>\n ";
        return $result2;
}
}
function timediffhrs($timein, $timeout){
	$timeinsec = strtotime ($timein);
	$timeoutsec = strtotime ($timeout);
	$timetot = $timeoutsec - $timeinsec;
	$timehrs  = intval($timetot/3600);
	$timehrsi =(($timetot/3600)-$timehrs)*60;
	$timemins = intval(($timetot/60) -$timehrs*60);
	return $timehrs;
}


function getFirstPart($str,$splitter){
	$position = strpos($str,$splitter);
	if($position===false) return $str;
	else return substr($str, 0,$position);
}


function getLastPart($str,$splitter){
	$position = strrpos($str,$splitter);
	return substr($str, $position + 1);
}

function get_filename_from_url($url){
  $lastslashposition = strrpos($url,"/");
  $filename=substr($url, $lastslashposition + 1);
  return $filename;
}

function removeDoubleSlash($str){
	# why this function?: some directory names contain trailing slash like /example/this/, and some portions of existing codes uses that. Until fixed, new codes are written using this, to let both style work..
	# this function may be removed after all trailing slashes removed..
	return str_replace("//","/",$str);
}

function get_filename_extension($filename) {

		$lastdotposition = strrpos($filename,".");

		if ($lastdotposition === 0)	  { $extension = substr($filename, 1); }
		elseif ($lastdotposition == "")  { $extension = $filename; }
		else							 { $extension = substr($filename, $lastdotposition + 1); }

		return strtolower($extension);

}

if(!function_exists('securefilename')){
function securefilename($fn){	
	$ret=str_replace(array("\\",'..','%','&'),array('','',''),$fn);
	#$ret=escapeshellarg($ret);
	return $ret;
}
}

function passthru2($cmd,$no_remove=false,$no_escape=false){
	$cmd1=$cmd;
	if(!$no_remove) $cmd=removeDoubleSlash($cmd);
	if(!$no_escape) $cmd=escapeshellcmd($cmd);
	echo "\nexecuting command: $cmd1 \n(escapedcmd: $cmd)\n";
	passthru($cmd);
	return true;
}

function escapeDollarSignsBash($cmd){
	return str_replace('$', '\$', $cmd);
}

function passthru2_silent($cmd,$no_remove=false,$no_escape=false){
	$cmd1=$cmd;
	if(!$no_remove) $cmd=removeDoubleSlash($cmd);
	if(!$no_escape) $cmd=escapeshellcmd($cmd);
	passthru($cmd);
	return true;
}

function passthru3($cmd,$source=''){
	$cmd=removeDoubleSlash($cmd);
	# Echoes command and execute, does not escapeshellcmd
	echo "\n$source:Executing command: ($cmd) \n";
	passthru($cmd);
}

function date_tarih(){
	return date('Y-m-d h:i:s');
}

function my_shell_exec($cmd,$source=''){
    echo "\n$source: ".date_tarih()." Executing command: ($cmd)";
    echo shell_exec($cmd);
}

function trimstrip($str){
	return trim(stripslashes($str));
}

function isNumericField($f){
	return (substr_count($f,'int')>0 or substr_count($f,'float')>0) ;
}

function stripslashes_deep($value)
{
	$value = is_array($value) ?
				array_map('stripslashes_deep', $value) :
				stripslashes($value);

	return $value;
}


function validateIpAddress($ip_addr)
{
	return filter_var($ip_addr, FILTER_VALIDATE_IP);
}

if(!function_exists('buildoption2')) {
function buildoption2($adi,$arr,$selected) {
	$res="<select name='$adi'><option value=''>Select/Sec</option>";
    foreach($arr as $ar) $res.="<option value='$ar' ".(($ar==$selected)?"selected":"").">$ar</option>";
    $res.="</select>";
    return $res;
}
}


if(!function_exists("debug_print_backtrace2")){
function debug_print_backtrace2(){
	echo "<pre>";
	debug_print_backtrace();
	echo "</pre>";
}
}

if(!function_exists("debug_backtrace2")){
function debug_backtrace2(){
	$ar=debug_backtrace();
	$out="<br>";
	array_shift($ar); # enson cagrilan zaten bu. ona gerek yok. 
	$ar=array_reverse($ar);
	foreach($ar as $a) {
		$f=$a['file'];
		$f=explode("/",$f);
		$f=array_pop($f);
		#$nf=array();
		#$nf[]=array_pop($f);
		#$nf[]=array_pop($f);
		#$nf[]=array_pop($f); # son uc elemani al. cok uzun dosya adi/yolu olmasin diye
		#$nf=array_reverse($nf);
		#$f=implode("/",$nf);
		$out.="(".$f.':'.$a['line'].':'.$a['function'].")->";
		#$out.="(".$f.'->'.$a['function'].")->";
	
	}
	return $out."<br>";	
}
}

function textarea_to_array($area,$start=array(),$end=array()){
	$templ=array();
	$templates=explode("\n",$area);
	#echo print_r2($templates);
	$templates=array_merge($start,$templates,$end);	
	
	foreach($templates as $t) {
		$t=trim($t);
		$templ[$t]=$t;
		#echo "$t -> $t ekleniyor <br>";
	}
	#echo print_r2($templ);
	# bu çalışmadı, bug var veya anlamadım: $templ=array_merge($start,$templ,$end);	
	#array_push($templ,$end);  # bunlar da çalışmadı.
	#array_unshift($templ,$start);	
	#echo print_r2($templ);
	return $templ;
/*
çok ilginç, yukardaki array_merge fonksiyonları, array'ın indexlerini değiştiriyor:
çıktısı:
* Array gosteriliyor:
Array
(
    [4096] => 4096
    [2048] => 2048
    [256] => 256
    [512] => 512
    [1024] => 1024
    [1536] => 1536
)
Array gosteriliyor:
Array
(
    [0] => Array
        (
            [0] => seÃ§
        )

    [1] => 4096
    [2] => 2048
    [3] => 256
    [4] => 512
    [5] => 1024
    [6] => 1536
    [7] => Array
        (
        )

)
* 
 * 
 */	
	
}

/* Ubuntu Specific Functions */
function getUbuntuVersion(){
	exec("lsb_release -r | awk '{ print $2 }'", $version);
	if(!empty($version) && is_array($version)){
		return $version[0];
	}
	return false;
}

function getUbuntuReleaseYear(){
	$version = getUbuntuVersion();
	return substr($version, 0, stripos($version, "."));
}

function getUbuntuReleaseMonth(){
	$version = getUbuntuVersion();
	return substr($version, stripos($version, ".") + 1);
}

function getIsUbuntu(){
	exec("cat /etc/issue | awk '{ print $1 }'", $distro);
	if(is_array($distro) && !empty($distro)){
		if(strtolower($distro[0]) == "ubuntu"){
			return true;
		}
	}
	return false;
}

function getIsDebian(){
	exec("cat /etc/issue | awk '{ print $1 }'", $distro);
	if(is_array($distro) && !empty($distro)){
		if(strtolower($distro[0]) == "debian"){
			return true;
		}
	}
	return false;
}
/* End Ubuntu Specific Functions */

/* Start OS Specific Functions */
function sysIsUsingSystemD(){
	exec("ps -p 1 | awk '{print $4}' | tail -n 1", $sysd);
	if(!empty($sysd) && is_array($sysd)){
		if(!empty($sysd[0]) && $sysd[0] == "systemd"){
			return true;
		}
	}
	return false;
}

function serviceExists($service){
	// Neat:  http://stackoverflow.com/questions/2427913/how-can-i-grep-for-a-string-that-begins-with-a-dash-hyphen
	if(isset($service) && !empty($service)){
		// Below command is too slow
		// $serviceExists = shell_exec('service --status-all 2>&1 | grep -F -- "' . $service . '" | awk \'{print $4}\' | tr -d \'\n\'');
		
		$serviceExists = shell_exec('ls /etc/init.d 2>/dev/null | grep -F -- "' . $service . '"');
		if(isset($serviceExists) && !empty($serviceExists)){
			return true;
		}
		
		$serviceExists = shell_exec('find /lib/systemd/system -name "*' . $service . '*" -exec basename {} .service \; 2>/dev/null');
	
		if(isset($serviceExists) && !empty($serviceExists)){
			return true;
		}
	
		$serviceExists = shell_exec('find /etc/systemd/system -name "*' . $service . '*" -exec basename {} .service \; 2>/dev/null');
	
		if(isset($serviceExists) && !empty($serviceExists)){
			return true;
		}
	}
	return false;
}

function determinePHPFPMName(){
	// Below command takes too long
	// $serviceExists = shell_exec('service --status-all 2>&1 | grep -F -- "-fpm" | awk \'{print $4}\' | grep "php" | tr -d \'\n\'');
	
	$serviceExists = shell_exec('ls /etc/init.d 2>/dev/null | grep -F -- "-fpm"');
	
	if(isset($serviceExists) && !empty($serviceExists) && stripos($serviceExists, 'php') !== false){
		return $serviceExists;
	}
	
	$serviceExists = shell_exec('find /lib/systemd/system -name "*-fpm*" -exec basename {} .service \; 2>/dev/null');

	if(isset($serviceExists) && !empty($serviceExists) && stripos($serviceExists, 'php') !== false){
		return $serviceExists;
	}
	
	$serviceExists = shell_exec('find /etc/systemd/system -name "*-fpm*" -exec basename {} .service \; 2>/dev/null');
	
	if(isset($serviceExists) && !empty($serviceExists) && stripos($serviceExists, 'php') !== false){
		return $serviceExists;
	}
	
	return false;
}

function determineFTPUserFromCMD(){
	exec("cat /etc/passwd | grep vsftpd", $vsftpd_user_exists);
	if(!empty($vsftpd_user_exists) && is_array($vsftpd_user_exists)){
		if(!empty($vsftpd_user_exists[0])){
			return "vsftpd";
		}
	}
	return "ftp";
}

function determineBindUserFromCMD(){
	$bindUser = "bind";
	// Try bind - which is standard for Ubuntu
	exec('cat /etc/passwd | grep -o "^bind.*"', $bind_user_exists);
	if(!empty($bind_user_exists) && is_array($bind_user_exists)){
		if(!empty($bind_user_exists[0])){
			return "bind";
		}
	}
	
	// Unset bind user array
	unset($bind_user_exists);
	
	// Try named which may be used for other distros
	exec('cat /etc/passwd | grep -o "^named.*"', $bind_user_exists);
	if(!empty($bind_user_exists) && is_array($bind_user_exists)){
		if(!empty($bind_user_exists[0])){
			return "named";
		}
	}
	
	// Return bind9 user.
	return $bindUser;
}

/* End OS Specific Functions */

function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function removeInvalidCharsFromDomainName($string, $regexPattern){
	$string = strtolower($string);
			
	if(stripos($string, "http://") !== false){
		$string = str_replace("http://", "", $string);
	}
	
	if(stripos($string, "https://") !== false){
		$string = str_replace("https://", "", $string);
	}
	
	if(stripos($string, "www.") !== false){
		$string = str_replace("www.", "", $string);
	}
			
	// Need to replace invalid characters now!!!!
	if(isset($regexPattern) && !empty($regexPattern)){
		$string = preg_replace($regexPattern, "", $string);
	}
			
	// Break the domain name into parts (name and TLD)
	$positionOfFirstDot = stripos($string, ".");
	if($positionOfFirstDot !== false){
		$domainNameWithoutTLD = substr($string, 0, $positionOfFirstDot);
		$domainTLD = substr($string, $positionOfFirstDot);
	}
			
	// Remove hyphens from front
	while(isset($domainNameWithoutTLD) && startsWith($domainNameWithoutTLD, "-")){
		$domainNameWithoutTLD = substr($domainNameWithoutTLD, 1);
	}
	
	// Remove hyphens from front of tld
	while(isset($domainTLD) && startsWith($domainTLD, "-")){
		$domainTLD = substr($domainTLD, 1);
	}
			
	// Remove hyphens from back
	while(isset($domainNameWithoutTLD) && endsWith($domainNameWithoutTLD, "-")){
		$domainNameWithoutTLD = substr($domainNameWithoutTLD, 0, strlen($domainNameWithoutTLD) - 1);
	}
	
	// Remove hyphens from back of tld
	while(isset($domainTLD) && endsWith($domainTLD, "-")){
		$domainTLD = substr($domainTLD, 0, strlen($domainTLD) - 1);
	}
			
	if(isset($domainNameWithoutTLD) && isset($domainTLD)){
		$string = $domainNameWithoutTLD . $domainTLD;
	}
	
	return $string;
}

function removeAllTrailingSlashes($str){
	while(isset($str) && endsWith($str, "/")){
		$str = substr($str, 0, strlen($str) - 1);
	}
	return $str;
}

function removeInvalidChars($string, $mode){
	switch($mode){
		case "directory":
			$pattern = "/[^A-Za-z0-9\/_\-]/i";
			$string = preg_replace($pattern, "", $string);
			break;
		case "database":
			$pattern = "/[^A-Za-z0-9_]/i";
			$string = preg_replace($pattern, "", $string);
			break;
		case "title":
			$pattern = "/[^A-Za-z0-9_\-\s']/i";
			break;
		case "strictTitle":
			$pattern = "/[^A-Za-z0-9_\-\s]/i";
			break;
		case "name":
			$pattern = "/[^A-Za-z0-9_\-]/i";
			break;
		case "properName":
			$pattern = "/[^A-Za-z0-9_\-\s]/i";
			break;
		case "email":
			$pattern = "/[^A-Za-z0-9_\-@\.]/i";
			break;
		case "lettersandnumbers":
			$pattern = "/[^A-Za-z0-9]/i";
			break;
		case "domainname":
			// Lowercase for domain names only!!!
			$pattern = "/[^a-z0-9\-\.]/i";
			$string = removeInvalidCharsFromDomainName($string, $pattern);
			break;
		case "domainnamewithseparatorchar":
			// Lowercase for domain names only!!!
			$pattern = "/[^a-z0-9\-\.,=;]/i";
			$string = removeInvalidCharsFromDomainName($string, $pattern);
			break;
		case "domainnameport":
			// Lowercase for domain names only!!!
			// Allow port in the domain name for custom ports (example: ehcpforce.tk:8777)
			$pattern = "/[^a-z0-9:\-\.]/i";
			$string = removeInvalidCharsFromDomainName($string, $pattern);
			
			// Make sure we only have one : port colon 
			$positionOfFirstColon = stripos($string, ":");
			if($positionOfFirstColon !== false){
				// count the colon characters
				$colonSplit = explode(":", $string);
				if(is_array($colonSplit)){
					array_filter($colonSplit); // Remove empty records
					if(count($colonSplit) != 2){
						// Remove all colons since there is more than one
						$string = str_replace(":", "", $string);
					}
				}
			}
			
			break;
		case "subdomainname":			
			// Lowercase for subdomains too
			$pattern = "/[^a-z0-9\-]/i";
			$string = strtolower($string);
			
			if(stripos($string, "http://") !== false){
				$string = str_replace("http://", "", $string);
			}
			if(stripos($string, "https://") !== false){
				$string = str_replace("https://", "", $string);
			}
			if(stripos($string, "www.") !== false){
				$string = str_replace("www.", "", $string);
			}
			
			// Need to replace invalid characters now!!!!
			if(isset($pattern) && !empty($pattern)){
				$string = preg_replace($pattern, "", $string);
			}
			
			// Remove hyphens from front
			while(startsWith($string, "-")){
				$string = substr($string, 1);
			}
			
			// Remove hyphens from back
			while(endsWith($string, "-")){
				$string = substr($string, 0, strlen($string) - 1);
			}
			
			break;
		default:
			return $string;
	}
	if(isset($pattern) && !empty($pattern)){
		return preg_replace($pattern, "", $string);
	}
	
	return $string;
}

function setOwner($file, $owner){
	if(file_exists($file)){
		if(chown($file, $owner)){
			return true;
		}
	}
	return false;
}

function setPermissions($file, $mode){
	if(file_exists($file)){
		if(chmod($file, $mode)){
			return true;
		}
	}
	
	return false;
}

function domainNameValid($string, $skipRegex = false){
	$valid = true;
	
	if(empty($string)){
		$valid = false;
	}
			
	if(stripos($string, "http://") !== false){	
		$valid = false;
	}
			
	if(stripos($string, "https://") !== false){
		$valid = false;
	}
			
	if(stripos($string, "www.") !== false){
		$valid = false;
	}
			
	// If it's still valid, run regex to see if it's a valid domain
	if($valid && !$skipRegex){
		$valid = preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $string) //valid chars check
		&& preg_match("/^.{1,253}$/", $string) //overall length check
		&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $string);
	}
			
	// If it's still valid, make sure there is a domain ending and TLD
	if($valid){
		$positionOfLastDot = strrpos($string, ".");
		// Must have period in the domainname
		if($positionOfLastDot === false){
			$valid = false;
		}else{
			// If we have a period, make sure the length following it is greater than 2
			$remainingChars = substr($string, $positionOfLastDot);
			if(strlen($remainingChars) < 3){
				$valid = false;
			}
		}
	}
	
	return $valid;
}

function getIPAddress(){
	$ip = "";
	if(isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])){
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
	}else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}else if(isset($_SERVER['HTTP_X_REAL_IP']) && !empty($_SERVER['HTTP_X_REAL_IP'])){
		$ip = $_SERVER['HTTP_X_REAL_IP'];
	}else{
		$ip = $_SERVER['REMOTE_ADDR'];
	}
		
	if(!isValidIPAddress($ip)){
		return "";
	}
		
	return $ip;
}

function isValidIPAddress($ip, $allowLocalIPs = false){
	$valid = false;		
	if(!$allowLocalIPs){
		$valid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}else{
		$valid = filter_var($ip, FILTER_VALIDATE_IP);
	}
	return $valid;
}

function inputValid($string, $mode){
	$valid = true;
	switch($mode){
		case "domainname":
			$valid = domainNameValid($string);
			break;
		case "domainnameport":
			// Do normal domain name validation
			$valid = domainNameValid($string, true);
			
			// Check colon count
			$colonParts = explode(":", $string);
			if(count($colonParts) > 2){
				$valid = false;
			}
			break;
		case "certificate_key":
			if(strpos($string, "PRIVATE KEY") === FALSE){
				$valid = false;
			}
			break;
		case "certificate":
			if(strpos($string, "-----BEGIN CERTIFICATE-----") === FALSE){
				$valid = false;
			}
			if(strpos($string, "-----END CERTIFICATE-----") === FALSE){
				$valid = false;
			}
			break;
		case "directory_at_least_two_levels":
			if(substr_count($string, '/') < 2){
				$valid = false;
            }    		
           	$protectedPaths = array("/var/www/vhosts", "/var/www/new", "/var/www/php_sessions", "/var/www/webalizer", "/var/www/passivedomains");
           	if(in_array($string, $protectedPaths)){
				$valid = false;
			}
			break;
		case "email_address":
			$valid = filter_var($string, FILTER_VALIDATE_EMAIL);
			break;
		case "url":
			$valid = filter_var($string, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
			break;
	}
	return $valid;
}

function testCertificateAndPrivateKeyHashMatch($cert, $key){
	$testCertPath = "/var/www/new/ehcp/test/test.crt";
	$testCertKeyPath = "/var/www/new/ehcp/test/test.key";
	writeoutput2($testCertPath, $cert, "w+", false);
	writeoutput2($testCertKeyPath, $key, "w+", false);
	
	// Unset our arrays if they were set at some point
	if(isset($errorsWithCert)){
		unset($errorsWithCert);
	}
	if(isset($errorsWithCertKey)){
		unset($errorsWithCertKey);
	}
	if(isset($openSSLCertHash)){
		unset($openSSLCertHash);
	}
	if(isset($openSSLCertKeyHash)){
		unset($openSSLCertKeyHash);
	}
	
	// Check to make sure we have a valid cert and cert key file
	exec("openssl x509 -noout -modulus -in " . $testCertPath . " 2>&1 | grep -o -e error -e unable", $errorsWithCert);
	exec("openssl rsa -noout -modulus -in " . $testCertKeyPath . " 2>&1 | grep -o -e error -e unable", $errorsWithCertKey);
	if(is_array($errorsWithCert) && count($errorsWithCert) > 0){
		return false;
	}
	if(is_array($errorsWithCertKey) && count($errorsWithCertKey) > 0){
		return false;
	}
	
	// Now that we know the keys are OK, check the hashes and make sure they match...
	exec("openssl x509 -noout -modulus -in " . $testCertPath . " | openssl md5", $openSSLCertHash);
	exec("openssl rsa -noout -modulus -in " . $testCertKeyPath . " | openssl md5", $openSSLCertKeyHash);
	if(is_array($openSSLCertHash) && is_array($openSSLCertKeyHash)){
		if(trim($openSSLCertHash[0]) == trim($openSSLCertKeyHash[0])){
			return true;
		}
	}
	return false;
}

function makeSureSSLTestFileMatches($cert, $key){
	$testCertPath = "/var/www/new/ehcp/test/test.crt";
	$testCertKeyPath = "/var/www/new/ehcp/test/test.key";
	
	// Make sure the files contain what has been sent in...
	$inTestCrt = trim(file_get_contents($testCertPath));
	$inTestKey = trim(file_get_contents($testCertKeyPath));
	
	if($cert != $inTestCrt || $key != $inTestKey){
		return false;
	}
	return true;
}

function testCertificateChainValid($chain){
	// Unset variable if set before...
	if(isset($openSSLResultsChainValid)){
		unset($openSSLResultsChainValid);
	}
	
	// Check to see if the chain certificate entered is valid
	$testCertChainPath = "/var/www/new/ehcp/test/chain.crt";
	writeoutput2($testCertChainPath, $chain, "w+", false);
	
	exec("openssl verify $testCertChainPath 2>&1 | grep OK", $openSSLResultsChainValid);
	if(is_array($openSSLResultsChainValid) && count($openSSLResultsChainValid) > 0){
		if(stripos($openSSLResultsChainValid[0], "OK") !== false){
			return true;
		}
	}
	return false;
}

function makeSureSSLTestChainFileMatches($chain){
	$testCertChainPath = "/var/www/new/ehcp/test/chain.crt";
	$inTestChain = trim(file_get_contents($testCertChainPath));
	if($chain != $inTestChain){
		return false;
	}
	return true;
}

function manageService($service, $action){
	passthru2("service $service $action", true, true);
}

function getServiceActionStr($service, $action){
	return "service $service $action";
}

function getCustomApache2ListenPorts(){
	// Get the ports Apache2 is listening on
	$originalBindPorts = shell_exec('cat "/etc/apache2/ports.conf" | grep "Listen"');
	
	if(isset($originalBindPorts) && !empty($originalBindPorts)){
		// Split each Listen match into an array
		if(stripos($originalBindPorts, "\n") != False){
			$originalBindPorts = explode("\n", $originalBindPorts);
		}else{
			// Must be only one port, so add it to our array.
			$originalBindPorts[] = $originalBindPorts;
		}
		
		// Remove any empty values in our array
		$originalBindPorts = array_filter($originalBindPorts);
		
		// We want to ignore these ports, as the replacement file will handle the correct base ports based on web server configuration	
		$ignorePorts = array("80", "443");
		
		// Loop through each listen entry, get only the port, and add it to the list of custom ports if it's not in our ignore ports array.
		foreach($originalBindPorts as $port){
			$port = preg_replace("/[^0-9]/","", $port); 
			if(!in_array($port, $ignorePorts)){
				$realPorts[] = $port;
			}
		}
		
		if(isset($realPorts) && is_array($realPorts)){
			return $realPorts;
		}
	}
	
	// Default return
	return false;
}

function addCustomPortsToApache($ports){
	if(is_array($ports)){
		foreach($ports as $port){
			writeoutput2("/etc/apache2/ports.conf", "Listen " . $port, "a+");
		}
	}
}

if(!function_exists("stripContentsAfterLine")){
	function stripContentsAfterLine($firstMatch, $content){
		$finalContent = "";
		
		foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line){
			if(trim($line) == trim($firstMatch) || startsWith(trim($line), $firstMatch)){
				return $finalContent . $firstMatch . "\n";
			}
			$finalContent .= $line . "\n";
		}
		
		return $finalContent; 
	}
}

if(!function_exists("getContentsAfterLine")){
	function getContentsAfterLine($firstMatch, $content){
		$finalContent = "";
		$foundMatch = false;
		
		foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line){
			if(trim($line) == trim($firstMatch) || startsWith(trim($line), $firstMatch) || $foundMatch){
				if($foundMatch === true){
					$finalContent .= $line . "\n";
				}
				$foundMatch = true;
			}
		}
		
		return $finalContent; 
	}
}

/* LEFT OVERS FROM DBUTIL */

function selectstring($alanlar) {
	//if(count($alanlar)==0) return false;
    $res=$alanlar[0];
    $alansayisi=count($alanlar);

    for($i=1;$i<$alansayisi;$i++) {
		if(trim($alanlar[$i])<>"")$res.=",".$alanlar[$i];
	}
    return $res;
}

function buildquery2($select,$filtre,$orderby){ // v1.0
    $res=$select;
	if($filtre<>"") {
	    $res.=" where $filtre";
	};

	if($sirala<>"") {
	    $res.=" order by $sirala";
	};
    return $res;
}
?>
