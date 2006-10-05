<?php
	
	function paperi_otsikko () {
		global $paperi, $yhtiorow, $ajopvm, $alku, $loppu, $paperisivu;
		
		$paperi .= sprintf ('%-25.25s', 	"VIENNIN ATK-TULLAUS");
		$paperi .= "\r\n";
		
		$paperi .= sprintf ('%-30.30s',		"Viejä: ".$yhtiorow["nimi"]);
		$paperi .= sprintf ('%-40.40s', 	"Jaksolta $alku - $loppu");
		$paperi .= sprintf ('%-40.40s', 	"TÄYDENTÄVÄ TULLI-ILMOITUS");
		$paperi .= sprintf ('%-22.22s', 	"Ajopvm. $ajopvm");
		$paperi .= sprintf ('%-15.15s', 	"Sivu nro ".$paperisivu);
		$paperi .= "\r\n";
		
		$paperi .= sprintf ('%-30.30s', 	"Asiakasnro ".$yhtiorow["tullin_asiaknro"]);
		$paperi .= sprintf ('%-40.40s', 	"Y-tunnus ".$yhtiorow["ytunnus"]);
		$paperi .= "\r\n";
		
		$paperi .= "-------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
		$paperi .= "\r\n";
		
		$paperi .= sprintf ('%-11.11s',		"Viite");
		$paperi .= sprintf ('%-25.25s', 	"Vastaanottaja");
		$paperi .= sprintf ('%-25.25s', 	"Kuljetusväl. tunn.");
		$paperi .= sprintf ('%-10.10s',		"KM");
		$paperi .= sprintf ('%-13.13s', 	"Kontti");
		$paperi .= sprintf ('%-16.16s', 	"Akt.kulj. kans.");
		$paperi .= sprintf ('%-14.14s',		"Poist.toimip.");
		$paperi .= sprintf ('%-12.12s',		"KT");
		$paperi .= sprintf ('%-11.11s',		"Laskun nro");
		$paperi .= sprintf ('%-13.13s', 	"Toimitusehto");
		$paperi .= sprintf ('%-19.19s', 	"Tullausnumero");
		$paperi .= "\r\n";
		
		$paperi .= sprintf ('%-11.11s',		"Vientipvm");
		$paperi .= sprintf ('%-25.25s', 	"");
		$paperi .= sprintf ('%-25.25s', 	"Valuutta");
		$paperi .= sprintf ('%-10.10s', 	"Val.määrä");
		$paperi .= sprintf ('%-13.13s',	 	"Muuntokurssi");
		$paperi .= sprintf ('%-16.16s', 	"Lis./Väh. kust.");
		$paperi .= sprintf ('%-14.14s', 	"Vientiarvo");
		$paperi .= sprintf ('%-12.12s',		"EUR-ilmoitus");
		$paperi .= "\r\n";
		
		$paperi .= "-------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
		$paperi .= "\r\n";
		
		$paperi .= sprintf ('%-11.11s',		" ");
		$paperi .= sprintf ('%-25.25s', 	"Tavaran kuvaus");
		$paperi .= sprintf ('%-25.25s', 	"Tullinimike");
		$paperi .= sprintf ('%-10.10s',		"");
		$paperi .= sprintf ('%-13.13s', 	"Nettopaino");
		$paperi .= sprintf ('%-16.16s', 	"2-palj.");
		$paperi .= sprintf ('%-14.14s',		"2-palj. laji");
		$paperi .= sprintf ('%-12.12s',		"Menettely");
		$paperi .= sprintf ('%-11.11s',		"Määrämaa");
		$paperi .= sprintf ('%-13.13s', 	"Vientiarvo");
		$paperi .= sprintf ('%-15.15s', 	"Lisenssit/luvat");
		$paperi .= "\r\n";
		
	}
	
	function paperi_otsikkorivi () {
		global $paperi, $yhtiorow, $laskurow, $vientiarvo, $laskunarvo;
		
		$paperi .= "\r\n";
		$paperi .= "\r\n";
		$paperi .= "-------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
		$paperi .= "\r\n";
		
		$paperi .= sprintf ('%-11.11s',		$laskurow["laskunro"]);
		$paperi .= sprintf ('%-25.25s', 	substr($laskurow["nimi"],0,23));
		$paperi .= sprintf ('%-25.25s', 	$laskurow["sisamaan_kuljetus"]." / ".$laskurow["aktiivinen_kuljetus"]);
		$paperi .= sprintf ('%-10.10s', 	$laskurow["sisamaan_kuljetusmuoto"]." / ".$laskurow["kuljetusmuoto"]);
		$paperi .= sprintf ('%-13.13s', 	$laskurow["kontti"]);
		$paperi .= sprintf ('%-16.16s',		$laskurow["aktiivinen_kuljetus_kansallisuus"]);
		$paperi .= sprintf ('%-14.14s', 	$laskurow["poistumistoimipaikka_koodi"]);
		$paperi .= sprintf ('%-12.12s', 	$laskurow["kauppatapahtuman_luonne"]);
		$paperi .= sprintf ('%-11.11s',		$laskurow["laskunro"]);
		$paperi .= sprintf ('%-13.13s', 	$laskurow["toimitusehto"]);
		$paperi .= sprintf ('%-19.19s', 	$laskurow["tullausnumero"]);
		
		$paperi .= "\r\n";
		
		$paperi .= sprintf ('%-11.11s',		$laskurow["tapvm"]);
		$paperi .= sprintf ('%-25.25s',	 	"");
		$paperi .= sprintf ('%-25.25s',		$laskurow["valkoodi"]);
		$paperi .= sprintf ('%-10.10s', 	$laskunarvo);
		$paperi .= sprintf ('%-13.13s', 	"1");
		
		if (abs($laskurow["lisattava_era"]) > 0)
			$lis = "+".abs($laskurow["lisattava_era"]);
			
		if (abs($laskurow["vahennettava_era"]) > 0)
			$vah = "/-".abs($laskurow["vahennettava_era"]);
		
		$paperi .= sprintf ('%-16.16s',	 	$lis.$vah);		
		$paperi .= sprintf ('%-14.14s',		$vientiarvo); //tilastoarvo
		$paperi .= sprintf ('%-12.12s', 	"EUR");

		$paperi .= "\r\n";
		$paperi .= "-------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
	}
	
	function paperi_nimikerivi () {
		global $paperi, $rivirow, $laskurow, $tullarvo;
		
		$paperi .= "\r\n";
		$paperi .= sprintf ('%-11.11s', 	" ");
		$paperi .= sprintf ('%-25.25s',		substr($rivirow["nimitys"],0,23));
		$paperi .= sprintf ('%-25.25s', 	$rivirow["tullinimike1"]);
		$paperi .= sprintf ('%-10.10s', 	" ");
		$paperi .= sprintf ('%-13.13s', 	$rivirow["nettop"]);
		
		if ($rivirow["su"] != '') {
			$paperi .= sprintf ('%-16.16s', $rivirow["kpl"]);
			$paperi .= sprintf ('%-14.14s', $rivirow["su"]);
		}
		else {
			$paperi .= sprintf ('%-16.16s', " ");
			$paperi .= sprintf ('%-14.14s', " ");
		}
		
		$paperi .= sprintf ('%-12.12s', 	$rivirow["tullikohtelu"]);
		$paperi .= sprintf ('%-11.11s',		$laskurow["maa_maara"]);
		$paperi .= sprintf ('%-13.13s', 	$tullarvo);
		$paperi .= sprintf ('%-19.19s', 	" ");
		
		$paperi .= "\r\n";
	}
	
	function paperi_loppu () {
		global $paperi, $kukarow, $yhtiorow, $ajopvm, $tietuemaara, $laskutusarvo;
		
		
		$paperi .= chr(12);
		
		paperi_otsikko();
		
		$paperi .= "\r\n";
		$paperi .= "\r\n";
		$paperi .= "\r\n";
		
		$paperi .= sprintf ('%-60.60s',"Koko aineiston tietuemäärä").$tietuemaara." kpl\r\n";
		$paperi .= sprintf ('%-60.60s',"Koko aineiston vientiarvo").$laskutusarvo." EUR\r\n";
		
		$paperi .= "\r\n";
		$paperi .= "\r\n";
		$paperi .= "-------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
		$paperi .= "\r\n";
		$paperi .= sprintf ('%-60.60s',"Päiväys ja asiamiehen allekirjoitus")."| Tulliviranomaisen merkinnöille\r\n";
		$paperi .= sprintf ('%-60.60s',$yhtiorow["kotipaikka"]."  ".$ajopvm)."|\r\n";
		$paperi .= sprintf ('%-60.60s',"")."|\r\n";
		$paperi .= sprintf ('%-60.60s',$yhtiorow["nimi"])."|\r\n";
		$paperi .= sprintf ('%-60.60s',$kukarow["nimi"])."|\r\n";
		$paperi .= sprintf ('%-60.60s',$kukarow["puhno"])."|\r\n";
		$paperi .= "\r\n";
	}
			
?>