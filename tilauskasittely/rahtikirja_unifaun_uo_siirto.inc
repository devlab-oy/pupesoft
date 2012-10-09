<?php

# Pupesoft -> Unifaun Online datasiirto

// jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if (1 == 2 and $kollityht > 0) {

	if ($unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {

		require("inc/unifaun_send.inc");

		$unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);

		$unifaun->setYhtioRow($yhtiorow);
		$unifaun->setKukaRow($kukarow);
		$unifaun->setToimitustapaRow($toitarow);
		$unifaun->setRahtikirjaRow($rakir_row);
		$unifaun->setYhteensa($yhteensa);
		$unifaun->setViite($viite);
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

		$kollitiedot = array(
			'maara' => $kollityht,
			'paino' => $kilotyht,
			'pakkauskuvaus' => $pakkauskuvaus[0]
		);

		$unifaun->setContainerRow($kollitiedot);

		$unifaun->ftpSend();

	}
	else {
		echo "<br><font class='error'>".t("UNIFAUN-aineiston luontiin tarvittavia parametreja puuttuu!")."</font><br>";
	}
}

?>