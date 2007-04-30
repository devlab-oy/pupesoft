<?php

require ("inc/parametrit.inc");
echo "<font class='head'>Myyntitilaus ostolaskusta (proof of concept)</font><hr>";

if (isset($ebid)) {
	// määritellään polut
	$laskut     = "/home/jarmo/einv/ok/";

	if (!file_exists($laskut.$ebid)) {
		echo "Tällä $ebid ei löydy laskua (" . $laskut.$ebid . ")<br>";
	}
	else {
		$xml = simplexml_load_file($laskut.$ebid);
		require('inc/verkkolasku-in-pupevoice.inc');
		
		echo "Tuotetiedot löydetty! Niitä on " . sizeof($tuotetiedot) . "<br>";
		$ttuoteno = $tuoteno;
		unset($tuoteno);
		
		for ($i=0;$i < sizeof($tuotetiedot);$i++) {
			echo "<font class='message'>Tuotenumero $ttuoteno[$i] Määrä $rkpl[$i] $info[$i]</font><br>";
			$tila			= '';
			$valinta		= '';
			$varaosavirhe 	= '';
			$tuoteerror 	= 0;

			$tuoteno	= "?" . $ttuoteno[$i]; //Tämä on toimittajan tuotenumero
			$kpl  		= $rkpl[$i];

			//$hinta 	  = (float) str_replace(",", ".", $rivi[3]);
			//$ale	  = (float) str_replace(",", ".", $rivi[4]);
			//$netto 	  = $rivi[5];

			if ($tuoteno != '' and $kpl != 0) {

				///* Toimittajan tuotenumerospecial*///
				if (substr($tuoteno,0,1) == '?') {
					$tuoteno = substr($tuoteno,1);
					$query = "	SELECT *
								FROM tuotteen_toimittajat
								JOIN tuote USING (yhtio, tuoteno)
								WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
								and tuotteen_toimittajat.toim_tuoteno='$tuoteno'";
					$result = mysql_query($query) or pupe_error($query);
				
					if (mysql_num_rows($result) == 1) {
						$trow = mysql_fetch_array($result);
					}
				}
				
				if (!is_array($trow)) {
					$query = "	SELECT *, tuote.tuoteno as tuoteno
									FROM tuote
									LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.oletus!=''
									WHERE tuote.yhtio='$kukarow[yhtio]'
									and tuote.tuoteno='$tuoteno'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 1) {
						$trow = mysql_fetch_array($result);
						$tuoteno = $trow["tuoteno"];
					}
				}
				
				if (is_array($trow)) {
					if ($kukarow["kesken"] == 0) {
						//yhtiön oletusalvi!
						$xwquery = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
						$xwtres  = mysql_query($xwquery) or pupe_error($xwquery);
						$xwtrow  = mysql_fetch_array($xwtres);

						$alv = (float) $xwtrow["selite"];	

						$ytunnus = "WEKAROTO";
						$varasto = (int) $kukarow["varasto"];
						$valkoodi = $yhtiorow["valkoodi"]."##";

						$jatka	= "JATKA";
						$tee	= "OTSIK";
						$override_ytunnus_check = "YES";
						$toim = 'RIVISYOTTO';

						require ("tilauskasittely/otsik.inc");
						echo "<font class='message'>Perustin tilauksen $kukarow[kesken]</font><br>";
					}
					if ($kukarow["kesken"] == 0) {
						echo "Ei tilausta! Paha! Lopetetaan!";
						exit;
					}
					echo "<font class='message'>$tuoteno $trow[nimitys] löytyi!</font><br>";
					require('tilauskasittely/lisaarivi.inc');
				}
				else {
					echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
				}
			}

			$tuoteno	= '';
			$kpl		= '';
			$hinta		= '';
			$ale		= '';
			$alv		= '';
			$var		= '';
			$toimaika	= '';
			$kommentti	= '';
			$rivitunnus = '';

		} // end while eof
		echo "<br><font class='message'>Rivit on kopioitu ostolaskulta tilaukselle nro $kukarow[kesken]!</font><br><br><br>";
		unset($ebid);
	}
}
if (!isset($ebid)) {
		if ($kukarow["kesken"] != 0) {
		echo "<font class='message'>Sinulla on kesken tilausnro " . $kukarow["kesken"]."</font><br><br>";
		}
		else {
			echo "<font class='message'>Uusi myyntitilaus perustetaan</font><br><br>";
		}
		echo "<form action = '$PHP_SELF' method='post'>
			<table>
			<tr><th>Käsiteltävä verkkolasku</th>
			<td><input type='text' name='ebid' value = '$ebid'></td>
			<td><input type='Submit' value='".t("Luo rivit")."'></td></tr></table></form>";
}


?>
