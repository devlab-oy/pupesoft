<?php

# Pupesoft -> Unifaun datasiirto

//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($kollityht > 0) {

	if (($unifaun_host != "" and $unifaun_user != "" and $unifaun_pass != "" and $unifaun_path != "") or $unifaun_path != "") {

		require("tilauskasittely/unifaun.php");

		$unifaun = new Unifaun($unifaun_host, $unifaun_user, $unifaun_pass, $unifaun_path);
		$unifaun->setYhtioRow($yhtiorow);
		$unifaun->setKukaRow($kukarow);
		$unifaun->setToimitustapaRow($toitarow);
		$unifaun->setRahtikirjaRow($rakir_row);
		$unifaun->setYhteensa($yhteensa);
		$unifaun->setViite($viite);
		$unifaun->setKolliYht($kollityht);
		$unifaun->setKilotYht($kilotyht);
		$unifaun->setPakkauskuvaus($pakkauskuvaus);
		$unifaun->setMehto($mehto);

		if ($phpnimi == "rahtikirja_custom.php") {
			$unifaun->setPostiRow($osoitelappurow);
			$unifaun->setRahtikirjaRow($osoitelappurow);
		}
		else {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus in ($otunnukset)
						ORDER BY tunnus LIMIT 1";
			$tempr = mysql_query($query) or pupe_error($query);
			$unifaun->setPostiRow(mysql_fetch_assoc($tempr));
		}

		$unifaun->_getXML();
		$unifaun->ftpSend();

	}
	else {
		echo "<br><font class='error'>".t("UNIFAUN-aineiston luontiin tarvittavia parametreja puuttuu!")."</font><br>";
	}
}

?>