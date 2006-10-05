<?php

if (!function_exists("check_ostoskori")) {

	// otetetaan sisään ostoskorin numero ja asiakkaan tunnus
	// palautetaan ostoskorin otsikko arraynä tai virhetilanteissa FALSE

	function check_ostoskori($ostoskori, $liitostunnus) {

		global $kukarow, $yhtiorow;

		// errör zheckit
		if (!is_numeric($ostoskori)) {
			echo "<font class='error'>Ostoskori not numeric!</font><br>";
			return false;
		}

		$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Asiakasta ei löydy")."!</font><br>";
			return false;
		}
		else {
			$asiakasrow = mysql_fetch_array($result);
		}

		// kaikki ok, etsitään kori
		$query = "	SELECT *
					FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
					WHERE yhtio = '$kukarow[yhtio]' and
					tila = 'B' and
					liitostunnus = '$liitostunnus' and
					alatila = '$ostoskori'";
		$koriresult = mysql_query($query) or pupe_error($query);

		// koria ei löydy, tehdään sellanen
		if (mysql_num_rows($koriresult) == 0) {

	 		//yhtiön oletusalvi!
			$wquery = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
			$wtres  = mysql_query($wquery) or pupe_error($wquery);
			$wtrow  = mysql_fetch_array($wtres);

			$query = "	INSERT INTO lasku set
						yhtio 			= '$kukarow[yhtio]',
						alv				= '$wtrow[selite]',
						ytunnus			= '$asiakasrow[ytunnus]',
						liitostunnus 	= '$liitostunnus',
						tila			= 'B',
						alatila 		= '$ostoskori'";
			$koriin = mysql_query($query) or pupe_error($query);
			$tunnus = mysql_insert_id();

			// haetaan kori
			$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
			$koriin = mysql_query($query) or pupe_error($query);
			$palautus = mysql_fetch_array($koriin);
		}
		elseif (mysql_num_rows($koriresult) == 1) {
			$palautus = mysql_fetch_array($koriresult);
		}
		else {
			echo "<font class='error'>Too many ostoskories löyty!</font><br>";
			$palautus = false;
		}

		return $palautus;
	}

}

if (!function_exists("lisaa_ostoskoriin")) {

	function lisaa_ostoskoriin($ostoskori, $liitostunnus, $tuoteno, $kpl) {

		global $kukarow, $yhtiorow;

		$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Tuotetta ei löydy")."!</font><br>";
			return false;
		}
		else {
			$trow = mysql_fetch_array($result);
		}

		if ($laskurow = check_ostoskori($ostoskori, $liitostunnus)) {

			// lisätään rivi
			$kukarow["kesken"]	= $laskurow["tunnus"];
			$ytunnus			= $laskurow["ytunnus"];
			$kpl				= (float) $kpl;
			$tuoteno			= $tuoteno;
			$toimaika			= "";
			$kerayspvm			= "";
			$hinta				= "";
			$netto				= "";
			$ale				= "";
			$var				= "";
			$alv				= "";
			$paikka				= "";
			$varasto			= "";
			$rivitunnus			= "";
			$korvaavakielto		= "";
			$varataan_saldoa	= "";
			$var				= "Y";

			if (file_exists("../tilauskasittely/lisaarivi.inc")) {
				require ("../tilauskasittely/lisaarivi.inc");
			}
			else {
				require ("lisaarivi.inc");
			}

		}
		else {
			return false;
		}
	}

}
?>