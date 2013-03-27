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
		case "M":
			$laskutyyppi = "Valmis maksatukseen";
			break;
		case "P":
			$laskutyyppi = "Lähdössä pankkiin";
			switch($alatila) {
				case "K":
					$alatila = "maksettu käteisalennuksella";
			}
			break;
		case "Q":
			$laskutyyppi = "Odottaa suoritusta";
			switch($alatila) {
				case "K":
					$alatila = "maksettu käteisalennuksella";
			}
			break;
		case "Y":
			$laskutyyppi = "Maksettu";
			switch($alatila) {
				case "K":
					$alatila = "käteisalennuksella";
			}
			break;
		case "K":
			$laskutyyppi = "Saapuminen";
			switch($alatila) {
				case "X":
					$alatila = "Valmis";
					break;
				case "I":
					$alatila = "Inventointi";
					break;
			}
			break;
		case "X":
			$laskutyyppi = "Muu tosite";
			break;
		case "L":
			$laskutyyppi = "Myyntitilaus";
			switch($alatila) {
				case "A":
					$alatila = "Keräyslista tulostettu";
					break;
				case "B":
					$alatila = "Rahtikirjatiedot syötetty";
					break;
				case "C":
					$alatila = "Kerätty";
					break;
				case "D":
					$alatila = "Toimitettu";
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
				case "A":
					$alatila = "Tulostusjonossa";
					break;
				case "B":
					$alatila = "Odottaa ennakkolaskun maksua";
					break;
				case "E":
					$alatila = "Ennakko poiminnassa";
					break;
				case "F":
					$alatila = "Odottaa hyväksyntää";
					break;
				case "G":
					$alatila = "Odottaa asiakkaan suoritusta";
					break;
				case "J":
					$alatila = "JT poiminnassa";
					break;
				case "T":
					$laskutyyppi = "Myyntitilaus";
					$alatila = "odottaa JT tuotteita";
					break;
				case "U":
					$laskutyyppi = "Kokonaistoimitus";
					$alatila = "odottaa JT tuotteita";
					break;
			}
			break;
		case "V":
			$laskutyyppi = "Valmistus";
			switch($alatila) {
				case "":
					$alatila = "kesken";
					break;
				case "K":
					$alatila = "korjattu";
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
				case "X":
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
				case "X":
					$alatila = "Valmis";
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
			$laskutyyppi = "Ostotilaus";
			switch($alatila) {
				case "":
					$alatila = "kesken";
					break;
				case "A":
					$alatila = "lähetetty/tulostettu";
					break;
				case "B":
					$alatila = "";
					break;
				case "G":
					$alatila = "Odottaa asiakkaan suoritusta";
					break;
				case "X":
					$alatila = "Valmis";
					break;
			}
			break;
		case "D":
			$laskutyyppi = "Mitätöity";
			switch($alatila) {
				case "L":
					$alatila = "Myyntitilaus";
					break;
				case "N":
					$alatila = "Myyntitilaus kesken";
					break;
				case "V":
					$alatila = "Valmistus";
					break;
				case "A":
					$alatila = "Työmääräys";
					break;
				case "E":
					$alatila = "Ennakkotilaus";
					break;
				case "R":
					$alatila = "Projekti";
					break;
				case "T":
					$alatila = "Tarjous";
					break;
				case "G":
					$alatila = "Siirtolista";
					break;
				case "S":
					$alatila = "Sisäinen työmääräys";
					break;
				case "O":
					$alatila = "Ostotilaus";
					break;
				case "Z":
					$alatila = "Terminaalitoimitus";
					break;
				case "B":
					$alatila = "Ostoskori";
					break;
				case "C":
					$alatila = "Reklamaatio";
					break;
				case "0":
					$alatila = "Ylläpitosopimus";
					break;
				case "F":
					$alatila = "Tallennettu web-tilaus/tarjous";
					break;
				case "H":
					$alatila = "Ostolasku";
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
		case "C":
			$laskutyyppi = "Reklamaatio";
			switch($alatila) {
				case "A":
					$alatila = "Odottaa tuotteita";
					break;
				case "B":
					$alatila = "Reklamaatio vastaanotettu";
					break;
				case "C":
					$alatila = "Reklamaatio hyllytetään";
					break;
			}
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
		case "F":
			$laskutyyppi = "Tallennettu web-tilaus/tarjous";
			switch($alatila) {
				case "D":
					$alatila = "hylätty";
					break;
				case "A":
					$alatila = "hyväksytty työmääräys";
					break;
				case "X":
					$alatila = "valmis työmääräys";
					break;
			}
			break;
	}

?>
