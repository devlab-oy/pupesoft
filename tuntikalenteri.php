<?php

require('inc/parametrit.inc');

if($nayta_pdf != 1) {
	js_popup();
	js_showhide();
	enable_ajax();	
}


require('inc/kalenteri.inc');

//	T�m� on siis kalenteri jota meid�n pit�isi k�sitell�
$kaleDIV = "tuntikalenteri";

//	Jos kalenteria ei ole viel� m��ritetty niin se pit�� tehd� uudestaan
if($kaleID != $kaleDIV) {
	$otunnus = $projekti;
	
	$kaleID 							= $kaleDIV;
	$kalenteri["div"] 					= $kaleDIV;
	$kalenteri["URL"] 					= "tuntikalenteri.php";
	$kalenteri["nakyma"]				= "RIVINAKYMA_PAIVA";
	$kalenteri["sallittu_nakyma"]		= array("RIVINAKYMA_PAIVA", "RIVINAKYMA_VIIKKO");
	

	$kalenteri["kalenteri_tuntidata"]			= array("tyotunnit");
	$kalenteri["kalenteri_nayta_tuntidata"]		= array("tyotunnit");
	$kalenteri["kalenteri_ketka"]		= array("aivan_kaikki");
	$kalenteri["kalenteri_nayta_kuka"]	= array("");
	
	alusta_kalenteri($kalenteri);
	$tee_div = "JOO";
}

	
//	Liitet��n t�m�n k�ytt�j�n tekem�t memot yms aina mukaan.
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