<?

global $output,$ara;
degiskenal(array("ara"));


$tablo="html";
$query="select distinct grup from $tablo order by grup";

//tablolistele3_4("html",array(),array("id","aciklama"),"","id",array("../../edit.gif"),array("htmlduzenle.php"),"id");
//$filtre=filterstring($filtre,$grup,"grup='$grup'");

if(isset($grup) or isset($ara)) {
	$filtre="grup='$grup'";//echo "filtre:$filtre";
	if($grup==""){
      $filtre.=" or grup is null";
    };

    if($ara<>""){
      $filtre="id like '%$ara%'";
    }


    $output.="<br>Filtre1: $filtre<br>";


	$output.=tablolistele3_4_2("html",array(),array("id","grup","aciklama"),$filtre,"id",array("../../incele.jpg","../../edit.gif","../../edit.gif","../../delete2.jpg"),array("htmlgoster.php","htmlduzenle.php","htmlduzenle.php?eskieditor=1&","htmlduzenle.php?sil=1&"),"id");
} else {

    if($ara<>""){
      $filtre=andle($filtre,"id like '%$ara%'");
    }
    $output.="<br>Filtre1: $filtre<br>";

	$output.=tablolistele3_5_3($tablo,$query,$baslik,array("grup"),$filtre,"",array("../../incele.jpg"),array(),"grup",$baslangic,30);
}

$output.="<br><br>
<a target=_blank href=htmlkodekle.php>Kod ekle </a><br>
<a target=_blank href=index.php>Anasayfa</a><br>
<br>
<form>Ara:
<input type=text name=ara>
<input type=submit>
</form>
";

?>
