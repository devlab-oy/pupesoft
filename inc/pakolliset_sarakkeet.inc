<?php

/*
	Ylläpidetään taulujen pakollisia saraketietoja

	list($pakolliset, $kielletyt, $wherelliset)=pakolliset_sarakkeet($table);
*/

// määritellään pakolliset sarakkeet
if (!function_exists("pakolliset_sarakkeet")) {
	function pakolliset_sarakkeet($table) {
		global $yhtiorow;

		switch ($table) {
			case "tuote" :
				$pakolliset = array("TUOTENO");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM","KEHAHIN","VIHAHIN","VIHAPVM");
				$wherelliset = array("TUOTENO");
				break;
			case "tuotepaikat" :
				$pakolliset = array("TUOTENO","HYLLYALUE","HYLLYNRO","HYLLYVALI","HYLLYTASO","OLETUS");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "budjetti" :
				$pakolliset = array("KAUSI","TASO","SUMMA");
				$wherelliset = array("KAUSI","TASO","SUMMA", "KUSTANNUSPAIKKA", "KOHDE", "PROJEKTI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "asiakas" :
				//	Tämä estää syncronoinnin muilla kuin suomalaisilla toimittajilla..
				if (strtoupper($yhtiorow['maa']) == 'FI') {

					$pakolliset = array("YTUNNUS");

					if ($yhtiorow['asiakkaan_tarkenne'] == '') {
						$wherelliset = array("YTUNNUS", "OVTTUNNUS", "TOIM_OVTTUNNUS");
					}
					else {
						$wherelliset = array("YTUNNUS", "ASIAKASNRO");
					}

				}
				else {
					$pakolliset = array("YTUNNUS");
				}
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "toimi" :
				$pakolliset = array("YTUNNUS");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("YTUNNUS", "OVTTUNNUS");
				break;
			case "tullinimike" :
				$pakolliset = array("CN","KIELI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "tili" :
				$pakolliset = array("TILINO");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "maksuehto" :
				$pakolliset = array("TEKSTI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "asiakashinta" :
				$pakolliset = array("HINTA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("YTUNNUS", "ASIAKAS_RYHMA", "TUOTENO", "RYHMA");
				break;
			case "asiakaskommentti" :
				$pakolliset = array("YTUNNUS","TUOTENO");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "asiakasalennus" :
				$pakolliset = array("ALENNUS");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("YTUNNUS", "ASIAKAS_RYHMA", "TUOTENO", "RYHMA");
				break;
			case "perusalennus" :
				$pakolliset = array("RYHMA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "yhteyshenkilo" :
				$pakolliset = array("LIITOSTUNNUS","TYYPPI","NIMI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "avainsana" :
				$pakolliset = array("LAJI","SELITE");
				$wherelliset = array("LAJI","SELITE","SELITETARK","SELITETARK_2");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "tuoteperhe" :
				$pakolliset  = array("ISATUOTENO","TUOTENO","TYYPPI");
				$wherelliset = array("ISATUOTENO","TUOTENO","TYYPPI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "rahtimaksut" :
				$pakolliset = array("TOIMITUSTAPA","KILOTALKU","KILOTLOPPU");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "tuotteen_avainsanat" :
				$pakolliset = array("TUOTENO", "LAJI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "kalenteri" :
				$pakolliset = array("KUKA","ASIAKAS","PVMALKU");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "yhteensopivuus_auto" :
				$pakolliset = array("MERKKI","MALLI","MALLITARKENNE","KORITYYPPI","CC","MOOTTORITYYPPI","POLTTOAINE","SYLINTERIMAARA","SYLINTERINHALKAISIJA","TEHO_KW","TEHO_HV","ALKUKK","ALKUVUOSI","LOPPUKK","LOPPUVUOSI","LISATIEDOT","AUTODATA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("MERKKI","MALLI","MALLITARKENNE","KORITYYPPI","CC","MOOTTORITYYPPI","POLTTOAINE","SYLINTERIMAARA","SYLINTERINHALKAISIJA","TEHO_KW","TEHO_HV","ALKUKK","ALKUVUOSI","LOPPUKK","LOPPUVUOSI","LISATIEDOT","AUTODATA");
				break;
			case "yhteensopivuus_mp" :
				$pakolliset  = array("TYYPPI","MERKKI","CC","MALLI","VM");
				$kielletyt   = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("TYYPPI","MERKKI","CC","MALLI","VM");
				break;
			case "yhteensopivuus_tuote" :
				$pakolliset  = array("TYYPPI","ATUNNUS","TUOTENO");
				$kielletyt   = array("LAATIJA","LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("TYYPPI","ATUNNUS","TUOTENO");
				break;
			case "tuotteen_orginaalit" :
				$pakolliset = array("TUOTENO","ORIG_TUOTENO");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("TUOTENO", "ORIG_TUOTENO");
				break;
			case "autodata" :
				$pakolliset = array("AUTODATAID");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "autodata_tuote" :
				$pakolliset = array("TUOTERYHMA", "OP_REF");
				$kielletyt = array();
				break;
			case "rekisteritiedot" :
				$pakolliset = array("REKNO","MAA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "sanakirja" :
				$pakolliset = array("FI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$eiyhtiota = "TRIP";
				break;
			case "tuotteen_toimittajat" :
				$pakolliset = array("TUOTENO","TOIMITTAJA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("TUOTENO","TOIMITTAJA");
				break;
			case "kuka" :
				$pakolliset = array("KUKA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "todo" :
				$pakolliset = array("KUVAUS","PRIORITEETTI","PYYTAJA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "hinnasto" :
				$pakolliset = array("TUOTENO","HINTA","ALV");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "rahtisopimukset" :
				$pakolliset = array("YTUNNUS","RAHTISOPIMUS","TOIMITUSTAPA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
					break;
			case "toimitustapa" :
				$pakolliset = array("SELITE","tulostustapa");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "tuotteen_alv" :
				$pakolliset = array("TUOTENO","MAA", "ALV");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "sarjanumeron_lisatiedot" :
				$pakolliset = array("LIITOSTUNNUS");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "kustannuspaikka" :
				$pakolliset = array("TYYPPI", "NIMI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "taso" :
				$pakolliset = array("TYYPPI", "LAJI", "TASO", "NIMI");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				break;
			case "etaisyydet" :
				$pakolliset = array("VARASTO_POSTINO", "POSTINO", "KM", "POSTITP");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("VARASTO_POSTINO","POSTINO");
				break;
			case "abc_parametrit" :
				$pakolliset = array("TYYPPI", "LUOKKA");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM");
				$wherelliset = array("TYYPPI", "LUOKKA");
				break;
			case "liitetiedostot" :
				$pakolliset = array("LIITOS", "LIITOSTUNNUS", "KAYTTOTARKOITUS", "FILENAME");
				$kielletyt = array("LAATIJA", "LUONTIAIKA","MUUTTAJA","MUUTOSPVM", "DATA", "IMAGE_BITS", "IMAGE_CHANNELS", "IMAGE_HEIGHT", "IMAGE_WIDTH", "FILETYPE", "FILESIZE");
				break;
			default :
				$pakolliset = array("TUNTEMATON_TAULU");
				$kielletyt = array();
		}

		return array($pakolliset, $kielletyt, $wherelliset, $eiyhtiota);
	}
}

?>
