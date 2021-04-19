<?php

	function atk_eratietue () {
		global $atk, $yhtiorow, $vtietue, $laskurow, $atkrivi, $vientiarvo;
		
		$atk .= sprintf ('%-13.13s', 	str_replace('-','',$laskurow["tullausnumero"]));
		$atk .= sprintf ('%04d',		$atkrivi);				//rivinumero
		$atk .= sprintf ('%03d', 		"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"01");					//Tietuetyyppi
		$atk .= sprintf ('%-1.1s', 		"");					//Tyhjä
		$atk .= sprintf ('%04d', 		$vtietue);				//viimeisen tietueen järjestysnumero
		$atk .= sprintf ('%-1.1s',		"E");					//Käsittelytyyppi
		$atk .= sprintf ('%-2.2s', 		"11");					//Tullipiirin johto hardcoded
		$atk .= sprintf ('%-6.6s', 		"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		$yhtiorow["tullikamari"]);	//Valvova tullipiiri
		$atk .= sprintf ('%-2.2s', 		"");					//Tapcossa tyhjä
		$atk .= sprintf ('%-3.3s', 		substr(str_replace('-','',$laskurow["tullausnumero"]),7,3)); //VXX
		$atk .= sprintf ('%05d',	 	substr(str_replace('-','',$laskurow["tullausnumero"]),0,2).substr(str_replace('-','',$laskurow["tullausnumero"]),4,3)); //VVPPP
		$atk .= sprintf ('%-1.1s', 		"S");					//Kielikoodi
		$atk .= sprintf ('%-1.1s', 		"1");					//Vienti kyseessä
		$atk .= sprintf ('%-1.1s', 		"R");					//Rekisteröity asiakas
		$atk .= sprintf ('%-1.1s', 		"");					//Ei ilmoiteta
		$atk .= sprintf ('%-1.1s',		"Z");					//Kotitullaus
		$atk .= sprintf ('%06d',	 	substr($laskurow["tapvm"],8,2).substr($laskurow["tapvm"],5,2).substr($laskurow["tapvm"],2,2));	//PPKKVV
		$atk .= sprintf ('%-4.4s', 		"");					//Ei ilmoiteta
		$atk .= sprintf ('%05d',	 	str_replace('-','',$yhtiorow["tullin_asiaknro"]));		
		$atk .= sprintf ('%05d', 		"");					//Ei ilmoiteta
		$atk .= sprintf ('%-15.15s', 	$laskurow["laskunro"]);	//Huolinnan viite (lakunro)
		$atk .= sprintf ('%-1.1s', 		$laskurow["kuljetusmuoto"]);
		$atk .= sprintf ('%-1.1s', 		$laskurow["sisamaan_kuljetusmuoto"]);
		$atk .= sprintf ('%-1.1s', 		$laskurow["kontti"]);
		$atk .= sprintf ('%-2.2s', 		$laskurow["aktiivinen_kuljetus_kansallisuus"]);
		$atk .= sprintf ('%-3.3s', 		$laskurow["poistumistoimipaikka_koodi"]);
		$atk .= sprintf ('%06d',	 	substr($laskurow["tapvm"],8,2).substr($laskurow["tapvm"],5,2).substr($laskurow["tapvm"],2,2));	//PPKKVV
		$atk .= sprintf ('%-4.4s', 		"");					//Tyhjä
		$atk .= sprintf ('%05d', 		substr(str_replace('-','',$laskurow["tullausnumero"]),0,2).substr(str_replace('-','',$laskurow["tullausnumero"]),4,3));	//VVPPP
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s', 		"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s', 		"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s', 		"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s', 		"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s', 		"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s', 		"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s', 		"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s', 		"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%-11.11s', 	str_replace('-','',sprintf ('%08d',$yhtiorow["ytunnus"])));	//Ytunnus
		$atk .= sprintf ('%-1.1s', 		"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-1.1s',		"");					//Tyhjä
		$atk .= sprintf ('%014d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-7.7s', 		"");					//Tyhjä
		$atk .= sprintf ('%022d',	 	"");					//Tyhjä	
		$atk .= sprintf ('%011d',	 	"");					//Tyhjä
		$atk .= sprintf ('%011d',	 	"");					//Tyhjä
		$atk .= sprintf ('%037d',		"");					//Tyhjä
		$atk .= sprintf ('%-15.15s', 	"");					//Tyhjä
		$atk .= sprintf ('%012d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-185.185s',	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Tyhjä
		$atk .= sprintf ('%-293.293s',	"");					//Tyhjä
		$atk .= sprintf ('%-3.3s', 		"EUR");					//Valuutta
		$atk .= sprintf ('%-10.10s', 	"");					//Tyhjä

	}
		
	function atk_arvotietue () {
		global $atk, $yhtiorow, $laskurow, $atkrivi, $vientiarvo, $laskunarvo;
		
		$atk .= sprintf ('%-13.13s', 	str_replace('-','',$laskurow["tullausnumero"]));
		$atk .= sprintf ('%04d',		$atkrivi);
		$atk .= sprintf ('%03d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"03");					//Tietuetyyppi
		$atk .= sprintf ('%-1.1s', 		"");					//Tyhjä
		$atk .= sprintf ('%-1.1s', 		"2");					//Tapahtumalaji
		$atk .= sprintf ('%-11.11s', 	str_replace('-','',sprintf ('%08d',$yhtiorow["ytunnus"])));	//Ytunnus
		$atk .= sprintf ('%-9.9s',		$laskurow["laskunro"]);	//Lakunro viitteenä
		$atk .= sprintf ('%-17.17s',	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Tyhjä
		$atk .= sprintf ('%-1.1s', 		"");					//Tyhjä
		$atk .= sprintf ('%-1.1s', 		"");					//Ei ilmoiteta
		$atk .= sprintf ('%-3.3s', 		"EXW");					//Incoterms, nyt hardcoded!!!!!!!!!!!!!!
		$atk .= sprintf ('%-32.32s', 	"");					//Ei ilmoiteta
		
		$atk .= sprintf ('%-3.3s',	 	$laskurow["valkoodi"]);
		
		if ($laskurow["valkoodi"] != 'EUR') {
			$atk .= sprintf ('%014d', 	round($vientiarvo,0));	//Valuuttamäärä, muut kuin EUR
		}
		else {
			$atk .= sprintf ('%014d', 	"");					//Valuuttamäärä	
		}
		
		$atk .= sprintf ('%-3.3s',		"");					//Tyhjä
		$atk .= sprintf ('%014d', 		"");					//Tyhjä
		
		$atk .= sprintf ('%012d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%-2.2s', 		"");					//Ei pakollinen
		$atk .= sprintf ('%012d', 		"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Ei pakollinen
		$atk .= sprintf ('%012d', 		"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Ei pakollinen
		$atk .= sprintf ('%012d', 		"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Ei pakollinen
		$atk .= sprintf ('%012d', 		"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Ei pakollinen
		$atk .= sprintf ('%012d', 		"");					//Tyhjä
		
		$atk .= sprintf ('%-2.2s', 		"");					//Ei pakollinen
		$atk .= sprintf ('%012d', 		"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Ei pakollinen
		$atk .= sprintf ('%012d', 		"");					//Tyhjä
		
		$atk .= sprintf ('%024d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-147.147s', 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-13.13s', 	"");					//Tyhjä
		$atk .= sprintf ('%08d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-13.13s', 	"");					//Tyhjä
		$atk .= sprintf ('%08d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-13.13s', 	"");					//Tyhjä
		$atk .= sprintf ('%08d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-13.13s', 	"");					//Tyhjä
		$atk .= sprintf ('%08d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-13.13s', 	"");					//Tyhjä
		$atk .= sprintf ('%020d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		$laskurow["kauppatapahtuman_luonne"]);
		$atk .= sprintf ('%012d', 		"");					//Tyhjä			
		$atk .= sprintf ('%012d', 		str_replace('.','',$laskurow["lisattava_era"]));	//lisättävä kustannus
		$atk .= sprintf ('%012d',	 	str_replace('.','',$laskurow["vahennettava_era"]));	//vähennettävä kustannus
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%012d', 		str_replace('.','',$laskunarvo)); //Summa ilman desimaalierotinta
		$atk .= sprintf ('%-384.384s', 	"");					//Tyhjä

	}
	
	function atk_nimiketietue () {
		global $atk, $yhtiorow, $laskurow, $rivirow, $atkrivi, $tullarvo, $vientiarvo;
		
		$atk .= sprintf ('%-13.13s', 	str_replace('-','',$laskurow["tullausnumero"]));
		$atk .= sprintf ('%04d',		$atkrivi);
		$atk .= sprintf ('%03d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"04");					//Tietuetyyppi
		$atk .= sprintf ('%-1.1s', 		"");					//Tyhjä
		$atk .= sprintf ('%-8.8s', 		$rivirow["tullinimike1"]);
		$atk .= sprintf ('%-2.2s', 		"");
		$atk .= sprintf ('%-31.31s',	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',		$laskurow["maa_maara"]);
		$atk .= sprintf ('%-3.3s', 		"");					//Tyhjä
		$atk .= sprintf ('%04d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s', 		"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%010d',	 	$rivirow["nettop"]);	//Paljouden määrä
		$atk .= sprintf ('%-4.4s',	 	"KGM");					//Paljouden yksikkö
		
		if ($rivirow["su"] != '') {
			$atk .= sprintf ('%010d',	$rivirow["kpl"]);		//2-paljouden määrä
			$atk .= sprintf ('%-4.4s',	$rivirow["su_vientiilmo"]);		//2-paljous, XXX muodossa
		}
		else {
			$atk .= sprintf ('%010d',	 "");					//Tyhjä
			$atk .= sprintf ('%-4.4s',	 "");					//Tyhjä
		}
		
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%02d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-6.6s',	 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s', 	"");					//Tyhjä
		$atk .= sprintf ('%-8.8s',	 	"");					//Tyhjä
		$atk .= sprintf ('%06d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-2.2s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-4.4s',	 	"");					//Tyhjä
		$atk .= sprintf ('%010d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%033d',	 	"");					//Tyhjä
		$atk .= sprintf ('%-3.3s',	 	"");					//Tyhjä
		$atk .= sprintf ('%014d',	 	"");					//Tyhjä
		
		$atk .= sprintf ('%-4.4s', 		"");					//Taric lisäkoodi 1
		$atk .= sprintf ('%-4.4s', 		"");					//Taric lisäkoodi 2
		$atk .= sprintf ('%-17.17s',	"");					//Tyhjä
		$atk .= sprintf ('%-17.17s', 	"");					//Tyhjä
		$atk .= sprintf ('%-12.12s',	"");					//Tyhjä	
		$atk .= sprintf ('%-4.4s', 		$rivirow["tullikohtelu"]); //Vientimenettely
		$atk .= sprintf ('%-14.14s',	"");					//Tyhjä
		$atk .= sprintf ('%-141.141s',	"");					//Tyhjä
		$atk .= sprintf ('%010d',		str_replace('.','',$tullarvo)); //Tullausarvo
		$atk .= sprintf ('%010d',	 	"");					//Ei käytössä
		$atk .= sprintf ('%-30.30s',	"");					//Tyhjä

	}	

?>
