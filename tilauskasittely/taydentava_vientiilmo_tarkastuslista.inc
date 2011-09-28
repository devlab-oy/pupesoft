<?php	
		
		function tark_dateconv ($date) {
			//kההntהה mysqln vvvv-kk-mm muodon muotoon pp.kk.vvvv
			return substr($date,8,2).".".substr($date,5,2).".".substr($date,0,4);
		} 
		
		function tark_otsikko () {
			global $tark, $yhtiorow, $ajopvm, $alku, $loppu, $tarksivu, $laskurow, $tarksivu;
			
			$tark .= sprintf ('%-38.38s',	$yhtiorow["nimi"]);
			$tark .= sprintf ('%-44.44s',	"Tulliselvitystietojen Tarkastuslista");
			$tark .= sprintf ('%-40.40s', 	"Ajopהivהmההrה: $ajopvm");
			$tark .= "\r\n";
			
			$tark .= sprintf ('%-38.38s',	" ");
			$tark .= sprintf ('%-20.20s', 	"Tullikamari: ".$yhtiorow["tullikamari"]);
			$tark .= sprintf ('%-20.20s', 	"Pההte: ".$yhtiorow["tullipaate"]);
			$tark .= "\r\n";
			
			$tark .= sprintf ('%-38.38s',   "Pvm: ".tark_dateconv($laskurow["tapvm"])); 
			$tark .= sprintf ('%-44.44s', 	"Jakso: $alku - $loppu");
			$tark .= sprintf ('%-40.40s', 	"Sivu: ".$tarksivu);
			$tark .= "\r\n";
				
			$tark .= sprintf ('%-18.18s',	"Tullausnumero");
			$tark .= sprintf ('%-12.12s', 	"Loppusumma");
			$tark .= sprintf ('%-8.8s', 	"As.Nro");
			$tark .= sprintf ('%-36.36s', 	"Huomautuksia");
			$tark .= sprintf ('%-8.8s', 	"TSM");
			$tark .= sprintf ('%-10.10s', 	"PASSINRO");
			$tark .= "\r\n";
			
			$tark .= "-----------------------------------------------------------------------------------------------------------------------";
			$tark .= "\r\n";
			
		}
		
		function tark_rivi () { 
			global $tark, $yhtiorow, $edtapvm, $laskurow, $vientiarvo;	
			
			$tark .= sprintf ('%-18.18s',	$laskurow["tullausnumero"]);						
			$tark .= sprintf ('%12s',sprintf ('%-.2f', $vientiarvo)."  ");
			$tark .= sprintf ('%-8.8s', 	" ");
			$tark .= sprintf ('%-15.15s', 	$laskurow["laskunro"]);			
			$tark .= sprintf ('%-8.8s',		" ");
			$tark .= sprintf ('%-10.10s', 	" ");
			$tark .= "\r\n";
			
			$edtapvm = $laskurow["tapvm"];	

		}
		function tark_yht () { 
			global $tark, $pvmyht;	
			
			$tark .= "\r\n";
			$tark .= sprintf ('%-18.18s',	"PVM YHT.");
			$tark .= sprintf ('%12s',sprintf ('%-.2f', $pvmyht)."  ");
			$tark .= "\r\n";
		}
?>