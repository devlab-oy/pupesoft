<?php

// estetään sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

if ($ytunnus != '') {
	require ("../inc/asiakashaku.inc");
}

if (($ytunnus != '') and ($monta==1)) {
    $ytunnus = $asiakasrow['ytunnus'];
	$tunnus = $asiakasrow['tunnus'];
    include("myyntilaskut_asiakasraportti_tee_raportti.php");
    return;

}
if ($ytunnus=='') {
	$formi = 'haku';
	$kentta = 'ytunnus';

	/* hakuformi */
	echo "<form name='$formi' action='$PHP_SELF' method='GET'>";
	echo "<p>".t("Etsi asiakasta nimellä tai y-tunnuksella").": ";
	echo "<input type='hidden' name='alatila' value='etsi'>";
	echo "<input type='text' name='ytunnus' value='$asiakas->ytunnus'>";
	echo " ";
	echo "<input type='submit' value='".t("Etsi")."'>";
	echo "</form>";
}
?>
