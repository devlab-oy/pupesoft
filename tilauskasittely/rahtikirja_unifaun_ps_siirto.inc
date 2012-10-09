<?php

# Pupesoft -> Unifaun Printserver datasiirto

// jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($yhtiorow['kerayserat'] != 'K' and $kollityht > 0) {

	if ($unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
		
		$unifaun_uo_host = $unifaun_ps_host;
		$unifaun_uo_user = $unifaun_ps_user;
		$unifaun_uo_pass = $unifaun_ps_pass;
		$unifaun_uo_path = $unifaun_ps_path;
		$unifaun_uo_fail = $unifaun_ps_fail;
		$unifaun_uo_succ = $unifaun_ps_succ;
		$unifaun_uo_port = $unifaun_ps_port;
		
		require ("rahtikirja_unifaun_uo_siirto.inc");		
	}
	else {
		echo "<br><font class='error'>".t("UNIFAUN-aineiston luontiin tarvittavia parametreja puuttuu!")."</font><br>";
	}
}

?>