<?php

//echo "data:<pre>".print_r($_REQUEST, true)."</pre>";	
//parametrit
require('../inc/parametrit.inc');

js_popup();
js_showhide();
enable_ajax();

require('inc/kalenteri.inc');

if($tee == "ASIAKASHAKU") {
	tee_asiakashaku($haku, $formi);
}
else {
	//	T‰m‰ on siis kalenteri jota meid‰n pit‰isi k‰sitell‰
	$kaleDIV = "omakalenteri2";

	//	Jos kalenteria ei ole viel‰ m‰‰ritetty niin se pit‰‰ tehd‰ uudestaan
	if($kaleID != $kaleDIV) {
		$kaleID 							= $kaleDIV;
		$kalenteri["div"] 					= $kaleDIV;
		$kalenteri["URL"] 					= "kalenteri.php";
		$kalenteri["url_params"]			= array("toim");
		$kalenteri["nakyma"]				= "KUUKAUSINAKYMA";	
		$kalenteri["sallittu_nakyma"]		= array("KUUKAUSINAKYMA", "VIIKKONAKYMA", "PAIVANAKYMA");	

		$kalenteri["kalenteri_tyypit"]				= array("kalenteri", "Memo", "Muistutus");
		$kalenteri["kalenteri_nayta_tyyppi"]		= array("kalenteri", "Muistutus");
		
		$kalenteri["kalenteri_ketka"]		= array("kaikki");
		$kalenteri["kalenteri_nayta_kuka"]	= array($kukarow["kuka"]);
		
		$kalenteri["kalenteri_jako"]		= "";
		
		alusta_kalenteri($kalenteri);
		$tee_div = "JOO";
	}
	
		
	//	Liitet‰‰n t‰m‰n k‰ytt‰j‰n tekem‰t memot yms aina mukaan.
	$data = kalequery();
	//echo "data:<pre>".print_r($data, true)."</pre>";

	if($tee_div == "JOO") {
		echo "<div id='$kaleDIV'>".kalenteri($data)."</div>";
	}
	else {
		echo kalenteri($data);
	}	
}

$ei_kelloa = 1;
require ("inc/footer.inc");

?>
