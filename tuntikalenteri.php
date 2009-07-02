<?php

require('inc/parametrit.inc');

if($nayta_pdf != 1) {
	js_popup();
	js_showhide();
	enable_ajax();	
}


require('inc/kalenteri.inc');

//	Tämä on siis kalenteri jota meidän pitäisi käsitellä
$kaleDIV = "tuntikalenteri";

//	Jos kalenteria ei ole vielä määritetty niin se pitää tehdä uudestaan
if($kaleID != $kaleDIV) {
	$otunnus = $projekti;
	
	$kaleID 							= $kaleDIV;
	$kalenteri["div"] 					= $kaleDIV;
	$kalenteri["URL"] 					= "tuntikalenteri.php";
	$kalenteri["nakyma"]				= "RIVINAKYMA_PAIVA";
	$kalenteri["sallittu_nakyma"]		= array("RIVINAKYMA_PAIVA", "RIVINAKYMA_VIIKKO");
	

	$kalenteri["kalenteri_tuntidata"]			= array("tyotunnit");
	$kalenteri["kalenteri_nayta_tuntidata"]		= array("tyotunnit");
	$kalenteri["kalenteri_ketka"]		= array("kaikki");
	$kalenteri["kalenteri_nayta_kuka"]	= array("");
	
	alusta_kalenteri($kalenteri);
	$tee_div = "JOO";
}

	
//	Liitetään tämän käyttäjän tekemät memot yms aina mukaan.
$data = kalequery();
//echo "data:<pre>".print_r($data, true)."</pre>";

if($tee_div == "JOO") {
	echo "<div id='$kaleDIV'>".kalenteri($data)."</div>";
}
else {
	echo kalenteri($data);
}	

require ("inc/footer.inc");

?>