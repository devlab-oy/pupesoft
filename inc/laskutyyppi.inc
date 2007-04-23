<?php
// Katsotaan tyyppi $laskutyyppi kentästä ja annetaan se selväkielisenä takaisin
	switch ($laskutyyppi) {
		case "H":
			$laskutyyppi = "Hyväksyttävänä";
			switch($alatila) {
				case "H":
					$alatila = "Pysäytetty";
			}
			break;
		case "K":
			$laskutyyppi = "Saapuva ostotilaus";
			break;
		case "Y":
			$laskutyyppi = "Maksettu";
			break;
		case "M":
			$laskutyyppi = "Valmis maksatukseen";
			break;
		case "P":
			$laskutyyppi = "Lähdössä pankkiin";
			switch($alatila) {
				case "K":
					$alatila = "Ostolasku maksettu käteisalennuksella";
			}
			break;
		case "Q":
			$laskutyyppi = "Odottaa suoritusta";
			break;
		case "X":
			$laskutyyppi = "Muu tosite";
			break;
		case "L":
			$laskutyyppi = "Myyntitilaus";
			switch($alatila) {
				case "A":
					$alatila = "Lähete tulostettu";
					break;
				case "B":
					$alatila = "Rahtikirjatiedot syötetty";
					break;
				case "C":
					$alatila = "Tilaus kerätty";
					break;
				case "D":
					$alatila = "Tilaus toimitettu";
					break;
				case "J":
					$alatila = "Odottaa loppulaskutusta";
					break;
				case "E":
					$alatila = "Vientitiedot syötetty";
					break;
				case "V":
					$alatila = "Laskutusvalmis";
					break;
				case "X":
					$alatila = "Laskutettu";
					break;
			}
			break;
		case "N":
			$laskutyyppi = "Myyntitilaus kesken";
			switch($alatila) {
				case "J":
					$alatila = "JT poiminnassa";
					break;
				case "E":
					$alatila = "Ennakko poiminnassa";
					break;
				case "A":
					$alatila = "Tulostusjonossa";
					break;
				case "F":
					$alatila = "Odottaa hyväksyntää";
					break;
				case "T":
					$alatila = "odottaa JT tuotteita";
					break;
				case "U":
					$alatila = "odottaa JT tuotteita";
					break;
			}
			break;
		case "A":
			$laskutyyppi = "Työmääräys";
			break;
		case "E":
			$laskutyyppi = "Ennakkotilaus";
			switch($alatila) {
				case "J":
					$alatila = "";
					break;
				case "A":
					$alatila = "lepäämässä";
					break;
				case "":
					$alatila = "kesken";
					break;
			}
			break;
		case "R":
			$laskutyyppi = "Projekti";
			switch($alatila) {
				case "":
					$alatila = "Kesken";
					break;
				case "A":
					$alatila = "Aktiivi";
					break;
				case "B":
					$alatila = "Valmis";
					break;
				case "X":
					$alatila = "Suljettu";
					break;
			}
			break;
		case "T":
			$laskutyyppi = "Tarjous";
			switch($alatila) {
				case "":
					$alatila = "Kesken";
					break;
				case "A":
					$alatila = "Tulostettu";
					break;
				case "B":
					$alatila = "Hyväksytty";
					break;
				case "T":
					$alatila = "Tilaus tehty";
					break;
				case "X":
					$alatila = "Hylätty";
					break;
			}
			break;
		case "G":
			$laskutyyppi = "Siirtolista";
			switch($alatila) {
				case "":
					$alatila = "kesken";
					break;
				case "J":
					$alatila = "tulostusjonossa";
					break;
				case "A":
					$alatila = "tulostettu";
					break;
				case "B":
					$alatila = "Rahtikirjatiedot syötetty";
					break;
				case "C":
					$alatila = "kerätty";
					break;
				case "D":
					$alatila = "toimitettu";
					break;
				case "V":
					$alatila = "vastaanotettu";
					break;
				case "E":
					$alatila = "ei kerättävää";
					break;
				case "T":
					$alatila = "odottaa JT tuotteita";
					break;
				case "P":
					$alatila = "JT poiminnassa";
					break;
			}
			break;
		case "S":
				$laskutyyppi = "Sisäinen työmääräys";
				switch($alatila) {
					case "":
						$alatila = "kesken";
						break;
					case "J":
						$alatila = "tulostusjonossa";
						break;
					case "A":
						$alatila = "tulostettu";
						break;
					case "C":
						$alatila = "kerätty";
						break;
					case "V":
						$alatila = "vastaanotettu";
						break;
					case "E":
						$alatila = "ei kerättävää";
						break;
					case "T":
						$alatila = "odottaa JT tuotteita";
						break;
					case "P":
						$alatila = "JT poiminnassa";
						break;
				}
				break;
		case "V":
			$laskutyyppi = "Valmistus";
			switch($alatila) {
				case "":
					$alatila = "kesken";
					break;
				case "J":
					$alatila = "tulostusjonossa";
					break;
				case "A":
					$alatila = "tulostettu";
					break;
				case "C":
					$alatila = "kerätty";
					break;
				case "V":
					$alatila = "valmistettu";
					break;
				case "E":
					$alatila = "ei kerättävää";
				case "Y":
					$alatila = "rivit valmistuksessa toisella otsikolla";
					break;
			}
			break;
		case "U":
			$laskutyyppi = "Myyntilasku";
			switch($alatila) {
				case "X":
					$alatila = "Lähetetty";
					break;
				case "V":
					$alatila = "Odottaa lähetystä";
					break;
			}
			break;
		case "O":
			$laskutyyppi = "Varaston ostotilaus";
			switch($alatila) {
				case "A":
					$alatila = "Tilaus lähetetty/tulostettu";
					break;
				case "B":
					$alatila = "";
					break;
			}
			break;
		case "D":
			$laskutyyppi = "Mitätöity";
			switch($alatila) {
				case "L":
					$alatila = "myyntitilaus";
					break;
				case "N":
					$alatila = "lähete";
					break;
				case "O":
					$alatila = "ostotilaus";
					break;
				case "H":
					$alatila = "ostolasku";
					break;
			}
			break;
		case "Z":
			$laskutyyppi = "Terminaalitoimitus";
			switch($alatila) {
				case "A":
					$alatila = "vastaanotettu";
					break;
				case "X":
					$alatila = "tulostettu";
					break;
			}
			break;
		case "B":
			$laskutyyppi = "Ostoskori";
			break;
		case "0":
			$laskutyyppi = "Ylläpitosopimus";
			switch($alatila) {
				case "D":
					$alatila = "poistettu";
					break;
				case "V":
					$alatila = "voimassa ei laskutettu";
					break;
				case "X":
					$alatila = "voimassa laskutettu";
					break;
			}
			break;
	}

?>