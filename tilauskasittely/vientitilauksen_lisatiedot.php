<?php
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Lis‰tietojen syˆttˆ")."</font><hr>";

	if ($tapa == "tuonti" and $tee != "") {

		$query = "SELECT *
				  FROM lasku
				  WHERE tunnus in ($otunnus) and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "laskua ei lˆydy";
			exit;
		}
		else {
			$laskurow = mysql_fetch_array ($result);
		}

		if ($tee == "update") {

			$ultilno = tarvitaanko_intrastat($maa_lahetys, $maa_maara);

			$query = "	UPDATE lasku
						SET maa_maara = '$maa_maara',
						maa_lahetys = '$maa_lahetys',
						kauppatapahtuman_luonne = '$kauppatapahtuman_luonne',
						kuljetusmuoto = '$kuljetusmuoto',
						sisamaan_kuljetus = '$sisamaan_kuljetus',
						sisamaan_kuljetusmuoto  = '$sisamaan_kuljetusmuoto',
						sisamaan_kuljetus_kansallisuus = '$sisamaan_kuljetus_kansallisuus',
						kontti  = '$kontti',
						aktiivinen_kuljetus = '$aktiivinen_kuljetus',
						aktiivinen_kuljetus_kansallisuus = '$aktiivinen_kuljetus_kansallisuus',
						poistumistoimipaikka = '$poistumistoimipaikka',
						poistumistoimipaikka_koodi = '$poistumistoimipaikka_koodi',
						bruttopaino = '$bruttopaino',
						lisattava_era = '$lisattava_era',
						vahennettava_era = '$vahennettava_era',
						ultilno = '$ultilno'
						WHERE tunnus in ($otunnus) and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			
			$tee = "";
						
			if ($lopetus != "") {
				lopetus($lopetus, 'meta');
			}
		}

		if ($tee == "K") {

			// n‰ytet‰‰n viel‰ laskun tiedot, ettei kohdisteta p‰in berberi‰
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Tapvm")."</th>";
			echo "<th>".t("Summa")."</th>";
			echo "<th>".t("Toimitusehto")."</th>";
			echo "</tr>";
			echo "<tr><td>$laskurow[ytunnus]</td><td>$laskurow[nimi]</td><td>$laskurow[tapvm]</td><td>$laskurow[summa] $laskurow[valkoodi]</td><td>$laskurow[toimitusehto]</td></tr>";
			echo "</table><br>";

			$query  = "	SELECT sum(tuotemassa*(varattu+kpl)) massa, sum(varattu+kpl) kpl, sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.otunnus in ($otunnus)";
			$painoresult = mysql_query($query) or pupe_error($query);
			$painorow = mysql_fetch_array($painoresult);

			if ($painorow["kpl"] > 0) {
				$osumapros = round($painorow["kplok"] / $painorow["kpl"] * 100, 2);
			}
			else {
				$osumapros = "N/A";
			}

			echo "<font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on: %s KG, %s %%:lle kappaleista on annettu paino."),$painorow["massa"],$osumapros)."</font><br><br>";

			echo "<table>";
			echo "<form method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='asiakasid' value='$asiakasid'>
					<input type='hidden' name='toiminto' value='lisatiedot'>
					<input type='hidden' name='otunnus' value='$otunnus'>
					<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tee' value='update'>";

			$query = "SELECT sum(kollit) kollit, sum(kilot) kilot
					  FROM rahtikirjat
					  WHERE otsikkonro in ($otunnus) and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$rahtirow = mysql_fetch_array ($result);

			if ($laskurow["bruttopaino"] == 0) $laskurow["bruttopaino"] = $rahtirow["kilot"];

			echo "<tr>";
			echo "<th>".t("Bruttopaino").":</th>";
			echo "<td><input type='text' name='bruttopaino' value='$laskurow[bruttopaino]' style='width:300px;'></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("L‰hetysmaa").":</th>";
			echo "<td>";
			echo "<select name='maa_lahetys' style='width:300px;'>";

			$query = "	SELECT distinct koodi, nimi
						FROM maat
						where nimi != ''
						ORDER BY koodi";
			$result = mysql_query($query) or pupe_error($query);

			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row[0] == $laskurow["maa_lahetys"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("M‰‰r‰maan koodi").":</th>";
			echo "<td>";
			echo "<select name='maa_maara' style='width:300px;'>";

			$query = "	SELECT distinct koodi, nimi
						FROM maat
						where nimi != ''
						ORDER BY koodi";
			$result = mysql_query($query) or pupe_error($query);

			if ($laskurow["maa_maara"] == "") $laskurow["maa_maara"] = $yhtiorow["maa"];

			while ($row = mysql_fetch_array($result)) {
				$sel = '';
				if($row[0] == $laskurow["maa_maara"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";
			echo "</tr>";

			if ($laskurow["tuontipvm"] == '0000-00-00') {
				$pp = date('d');
				$kk = date('m');
				$vv = date('Y');
				$laskurow["tuontipvm"] = $vv."-".$kk."-".$pp;
			}

			echo "<tr>";
			echo "<th>".t("Kauppatapahtuman luonne").":</th>";
			echo "<td>";
			echo "<select name='kauppatapahtuman_luonne' style='width:300px;'>";

			$result = t_avainsana("KT");

			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row["selite"] == $laskurow["kauppatapahtuman_luonne"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
			}

			echo "</select></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("Kuljetusmuoto").":</th>";
			echo "<td>";
			echo "<select name='kuljetusmuoto' style='width:300px;'>";

			$result = t_avainsana("KM");

			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row["selite"] == $laskurow["kuljetusmuoto"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
			}

			echo "</select></td>";
			echo "</tr>";

			echo "</table>";

			echo "<input type='hidden' name='tapa' value='$tapa'";
			echo "<br><input type='submit' value='".t("P‰ivit‰ tiedot")."'>";
			echo "</form>";

			echo "<br><br>";
			$tunnus = $otunnus;
			require ("raportit/naytatilaus.inc");
		}

	}
	elseif ($tee != "") {
		if ($tee == 'L') {

			list($poistumistoimipaikka, $poistumistoimipaikka_koodi) = explode("##", $poistumistoimipaikka, 2);

			if ($aktiivinen_kuljetus_kansallisuus == '') {
				$aktiivinen_kuljetus_kansallisuus = $sisamaan_kuljetus_kansallisuus;
			}

			$aktiivinen_kuljetus_kansallisuus = strtoupper($aktiivinen_kuljetus_kansallisuus);
			$sisamaan_kuljetus_kansallisuus = strtoupper($sisamaan_kuljetus_kansallisuus);
			$maa_maara = strtoupper($maa_maara);

			$otunnukset = explode(',',$otunnus);

			foreach($otunnukset as $otun) {

				// lasketaan rahtikirjalta jos miell‰ on nippu tilauksia tai jos bruttopainoa ei ole annettu k‰yttˆliittym‰st‰
				if (count($otunnukset) > 1 or !isset($bruttopaino) or (int) $bruttopaino == 0) {
					$query = "	SELECT sum(kilot) kilot
								FROM rahtikirjat
								WHERE otsikkonro = '$otun' and yhtio='$kukarow[yhtio]'";
					$result   = mysql_query($query) or pupe_error($query);
					$rahtirow = mysql_fetch_array ($result);
					$bruttopaino = $rahtirow['kilot'];
				}

				$query = "SELECT varasto from lasku where yhtio = '$kukarow[yhtio]' and tunnus = '$otun'";
				$laskun_res = mysql_query($query) or pupe_error($query);
				$laskun_row = mysql_fetch_array($laskun_res);

				$query = "SELECT maa from varastopaikat where yhtio = '$kukarow[yhtio]' and tunnus = '$laskun_row[varasto]'";
				$varaston_res = mysql_query($query) or pupe_error($query);
				$varaston_row = mysql_fetch_array($varaston_res);

				$ultilno = tarvitaanko_intrastat($varaston_row["maa"], $maa_maara);

				$query = "	UPDATE lasku
							SET maa_maara 					= '$maa_maara',
							maa_lahetys						= '$varaston_row[maa]',
							kauppatapahtuman_luonne 		= '$kauppatapahtuman_luonne',
							kuljetusmuoto 					= '$kuljetusmuoto',
							sisamaan_kuljetus 				= '$sisamaan_kuljetus',
							sisamaan_kuljetusmuoto  		= '$sisamaan_kuljetusmuoto',
							sisamaan_kuljetus_kansallisuus 	= '$sisamaan_kuljetus_kansallisuus',
							kontti  						= '$kontti',
							aktiivinen_kuljetus 			= '$aktiivinen_kuljetus',
							aktiivinen_kuljetus_kansallisuus= '$aktiivinen_kuljetus_kansallisuus',
							poistumistoimipaikka 			= '$poistumistoimipaikka',
							poistumistoimipaikka_koodi 		= '$poistumistoimipaikka_koodi',
							bruttopaino 					= '$bruttopaino',
							lisattava_era 					= '$lisattava_era',
							vahennettava_era 				= '$vahennettava_era',
							comments						= '$lomake_lisatiedot',
							ultilno							= '$ultilno'
							WHERE tunnus = '$otun' and yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);

				//p‰ivitet‰‰n alatila vain jos tilaus ei viel‰ ole laskutettu
				$query = "	UPDATE lasku
							SET alatila = 'E'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$otun'
							and tila = 'L'
							and alatila NOT IN ('X', 'J')";
				$result = mysql_query($query) or pupe_error($query);
			}

			$tee = '';

			if ($lopetus != "") {
				lopetus($lopetus, 'meta');
			}
		}

		if ($tee == 'K') {

			echo "<table>";
			echo "<form method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='otunnus' value='$otunnus'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tee' value='L'>";

			$query = "SELECT *
					  FROM lasku
					  WHERE tunnus in ($otunnus) and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			$query = "SELECT sum(kollit) kollit, sum(kilot) kilot
					  FROM rahtikirjat
					  WHERE otsikkonro in ($otunnus) and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$rahtirow = mysql_fetch_array($result);

			if ($laskurow["bruttopaino"] == 0) $laskurow["bruttopaino"] = $rahtirow["kilot"];

			$query = "SELECT * from asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
			$result = mysql_query($query) or pupe_error($query);
			$asiakasrow = mysql_fetch_array($result);

			$query  = "	SELECT sum(tuotemassa*(varattu+kpl)) massa, sum(varattu+kpl) kpl, sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.otunnus in ($otunnus)";
			$painoresult = mysql_query($query) or pupe_error($query);
			$painorow = mysql_fetch_array($painoresult);

			if ($painorow["kpl"] > 0) {
				$osumapros = round($painorow["kplok"] / $painorow["kpl"] * 100, 2);
			}
			else {
				$osumapros = "N/A";
			}

			// otetaan defaultit asiakkaalta jos laskulla ei ole mit‰‰n
			if ($laskurow["poistumistoimipaikka_koodi"]       == "") $laskurow["poistumistoimipaikka_koodi"]       = $asiakasrow["poistumistoimipaikka_koodi"];
			if ($laskurow["kuljetusmuoto"]                    ==  0) $laskurow["kuljetusmuoto"]                    = $asiakasrow["kuljetusmuoto"];
			if ($laskurow["kauppatapahtuman_luonne"]          ==  0) $laskurow["kauppatapahtuman_luonne"]          = $asiakasrow["kauppatapahtuman_luonne"];
			if ($laskurow["aktiivinen_kuljetus_kansallisuus"] == "") $laskurow["aktiivinen_kuljetus_kansallisuus"] = $asiakasrow["aktiivinen_kuljetus_kansallisuus"];
			if ($laskurow["aktiivinen_kuljetus"]              == "") $laskurow["aktiivinen_kuljetus"]              = $asiakasrow["aktiivinen_kuljetus"];
			if ($laskurow["kontti"]                           ==  0) $laskurow["kontti"]                           = $asiakasrow["kontti"];
			if ($laskurow["sisamaan_kuljetusmuoto"]           ==  0) $laskurow["sisamaan_kuljetusmuoto"]           = $asiakasrow["sisamaan_kuljetusmuoto"];
			if ($laskurow["sisamaan_kuljetus_kansallisuus"]   == "") $laskurow["sisamaan_kuljetus_kansallisuus"]   = $asiakasrow["sisamaan_kuljetus_kansallisuus"];
			if ($laskurow["sisamaan_kuljetus"]                == "") $laskurow["sisamaan_kuljetus"]                = $asiakasrow["sisamaan_kuljetus"];
			if ($laskurow["maa_maara"]                        == "") $laskurow["maa_maara"]                        = $asiakasrow["maa_maara"];

			echo "<tr>";
			echo "<th>6.</th>";
			echo "<th>".t("Kollim‰‰r‰")."</th>";
			echo "<td>$rahtirow[kollit]</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>17.</th>";
			echo "<th>".t("M‰‰r‰maan koodi").":</th>";
			echo "<td>";

			$query = "	SELECT distinct koodi, nimi
						FROM maat
						where nimi != ''
						ORDER BY koodi";
			$maat_result = mysql_query($query) or pupe_error($query);

			echo "<select name='maa_maara' style='width:300px;'>";
			echo "<option value=''>".t("Valitse")."</option>";

			while ($row = mysql_fetch_array($maat_result)) {
				$sel = '';
				if ($row[0] == $laskurow["maa_maara"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select>";
			echo "</td>";

			echo "<td class='back'>".t("Pakollinen kentt‰")."</td></tr>";

			if ($laskurow["vienti"] == "K") {
				echo "<tr>";
				echo "<th>18.</th>";
				echo "<th>".t("Sis‰maan kuljetusv‰line").":</th>";
				echo "<td>";
				echo "<input type='text' name='sisamaan_kuljetus' style='width:200px;' value='$laskurow[sisamaan_kuljetus]'>";

				echo "<select name='sisamaan_kuljetus_kansallisuus' style='width:100px;'>";
				echo "<option value=''>".t("Valitse")."</option>";
				mysql_data_seek($maat_result, 0);
				while ($row = mysql_fetch_array($maat_result)) {
					$sel = '';
					if ($row[0] == $laskurow["sisamaan_kuljetus_kansallisuus"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[0]' $sel>$row[1]</option>";
				}
				echo "</select>";

				echo "</td>";
				echo "<td class='back'>".t("Pakollinen kentt‰")."</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<th>26.</th>";
				echo "<th>".t("Sis‰maan kuljetusmuoto").":</th>";
				echo "<td>";
				echo "<select name='sisamaan_kuljetusmuoto' style='width:300px;'>";

				$result = t_avainsana("KM");

				echo "<option value=''>".t("Valitse")."</option>";

				while($row = mysql_fetch_array($result)){
					$sel = '';
					if($row["selite"] == $laskurow["sisamaan_kuljetusmuoto"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
				}
				echo "</select></td>";
				echo "<td class='back'>".t("Pakollinen kentt‰")."</td>";
				echo "</tr>";

				$chk1 = '';
				$chk2 = '';
				if($laskurow["kontti"] == 1) {
					$chk1 = 'checked';
				}
				if($laskurow["kontti"] == 0) {
					$chk2 = 'checked';
				}

				echo "<tr>";
				echo "<th>19.</th>";
				echo "<th>".t("Kulkeeko tavara kontissa").":</th>";
				echo "<td>Kyll‰ <input type='radio' name='kontti' value='1' $chk1> Ei <input type='radio' name='kontti' value='0' $chk2></td>";
				echo "<td class='back'>".t("Pakollinen kentt‰")."</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<th>21.</th>";
				echo "<th>".t("Aktiivisen kuljetusv‰lineen tunnus ja kansalaisuus").":</th>";
				echo "<td>";
				echo "<input type='text' name='aktiivinen_kuljetus' style='width:200px;' value='$laskurow[aktiivinen_kuljetus]'>";
				echo "<select name='aktiivinen_kuljetus_kansallisuus' style='width:100px;'>";
				echo "<option value=''>".t("Valitse")."</option>";
			
				mysql_data_seek($maat_result, 0);
			
				while ($row = mysql_fetch_array($maat_result)) {
					$sel = '';
					if ($row[0] == $laskurow["aktiivinen_kuljetus_kansallisuus"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[0]' $sel>$row[1]</option>";
				}
				echo "</select>";

				echo "</td>";
				echo "<td class='back'>Voidaan j‰tt‰‰ tyhj‰ksi jos asiakas t‰ytt‰‰</td>";
				echo "</tr>";
			}

			echo "<tr>";
			echo "<th>24.</th>";
			echo "<th>".t("Kauppatapahtuman luonne").":</th>";
			echo "<td>";
			echo "<select NAME='kauppatapahtuman_luonne' style='width:300px;'>";

			$result = t_avainsana("KT");

			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row["selite"] == $laskurow["kauppatapahtuman_luonne"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
			}
		
			echo "</select></td>";
			echo "<td class='back'>".t("Pakollinen kentt‰")."</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>25.</th>";
			echo "<th>".t("Kuljetusmuoto rajalla").":</th>";
			echo "<td>";
			echo "<select NAME='kuljetusmuoto' style='width:300px;'>";

			$result = t_avainsana("KM");

			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row["selite"] == $laskurow["kuljetusmuoto"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
			}
			echo "</select></td>";
			echo "<td class='back'>".t("Pakollinen kentt‰")."</td>";
			echo "</tr>";

			if ($laskurow["vienti"] == "K") {
				echo "<tr>";
				echo "<th>29.</th>";
				echo "<th>".t("Poistumistoimipaikka").":</th>";
				echo "<td>";
				echo "<select name='poistumistoimipaikka' style='width:300px;'>";
				echo "<option value = '##'>".t("Valitse")."</option>";

				$vresult = t_avainsana("TULLI");

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($laskurow["poistumistoimipaikka_koodi"] == $vrow["selite"]) {
						$sel = "selected";
					}
					echo "<option value = '$vrow[selitetark]##$vrow[selite]' $sel>$vrow[selitetark] $vrow[selite]</option>";
				}

				echo "</select></td>";
				echo "<td class='back'>".t("Pakollinen kentt‰")."</td>";
				echo "</tr>";

				if ($laskurow["lisattava_era"] == 0) {
					$laskurow["lisattava_era"] = $yhtiorow["tulli_lisattava_era"];
				}
				if ($laskurow["vahennettava_era"] == 0) {
					$laskurow["vahennettava_era"] = $yhtiorow["tulli_vahennettava_era"];
				}

				echo "<tr>";
				echo "<th>28.</th>";
				echo "<th>".t("V‰hennett‰v‰ er‰, ulkomaiset kustannukset")."</th>";
				echo "<td><input type='text' name='vahennettava_era' style='width:300px;' value='$laskurow[vahennettava_era]'></td>";
				echo "</tr>";

				echo "<tr>";
				echo "<th>28.</th>";
				echo "<th>".t("Toimitusehdon mukainen lis‰tt‰v‰ er‰")."</th>";
				echo "<td><input type='text' name='lisattava_era' style='width:300px;' value='$laskurow[lisattava_era]'></td>";
				echo "</tr>";
			}

			echo "<tr>";
			echo "<th>35.</th>";
			echo "<th>".t("Bruttopaino").":</th>";
			echo "<td><input type='text' name='bruttopaino' value='$laskurow[bruttopaino]' style='width:300px;'></td>";
			echo "</tr>";

			if ($laskurow["vienti"] == "K") {
				echo "<tr>";
				echo "<th>44.</th>";
				echo "<th>".t("Lis‰tiedot")."</th>";
				echo "<td><input type='text' name='lomake_lisatiedot' style='width:300px;' value='$laskurow[comments]'></td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "<br><input type='submit' value='".t("P‰ivit‰ tiedot")."'>";
			echo "</form>";

			echo "<br><br><font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on: %s KG, %s %%:lle kappaleista on annettu paino."),$painorow["massa"],$osumapros)."</font><br><br>";

			$tunnus = $otunnus;
			require ("raportit/naytatilaus.inc");
		}
	}
	
	// meill‰ ei ole valittua tilausta
	if ($tee == '' and $toim == "MUOKKAA") {
		
		$formi  = "find";
		$kentta = "etsi";

		// tehd‰‰n etsi valinta
		echo "<form name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		
		echo "<table>";
		echo "<tr>";
		echo "<th>";
		echo t("Valitse tapa");
		echo "</th>";
		echo "<td>";
		
		$seltuoteni = "";
		
		if ($tapa == "tuonti") {
			$seltuoteni = "SELECTED";
		}
		
		echo "<select name='tapa'>";
		echo "<option value='vienti'>".t("Vienti")."</option>";
		echo "<option value='tuonti' $seltuoteni>".t("Tuonti")."</option>";
		echo "</select>";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th>";
		echo t("Syˆt‰ nimi")." / ".t("Laskunumero")." / ".t("Saapumisnumero");
		echo "</th>";
		echo "<td>";
		echo "<input type='text' name='etsi' value='$etsi'>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<br><input type='Submit' value='".t("Etsi")."'>";
		echo "</form><br><br>";
		
		if (trim($etsi) != "") {

			$haku = '';
			if (is_string($etsi))  $haku = "and lasku.nimi LIKE '%$etsi%'";
			if (is_numeric($etsi)) $haku = "and lasku.laskunro='$etsi' or lasku.tunnus='$etsi'";

			if ($tapa == "tuonti") $tila = " and lasku.tila='K' and lasku.vanhatunnus=0 ";
			else $tila = " and lasku.tila in ('L','U') and lasku.alatila IN ('X', 'J') ";

			$query = "	SELECT lasku.laskunro, lasku.nimi, lasku.luontiaika, kuka.nimi laatija, lasku.vienti, lasku.tapvm, group_concat(lasku.tunnus) tunnus
						FROM lasku 
						LEFT JOIN kuka on kuka.yhtio = lasku.yhtio and kuka.tunnus = lasku.myyja
						WHERE lasku.yhtio = '$kukarow[yhtio]' 
						and lasku.vienti != '' 
						$tila
						$haku
						GROUP BY lasku.laskunro
						ORDER BY lasku.tapvm desc
						LIMIT 50";
			$tilre = mysql_query($query) or pupe_error($query);

			echo "<table>";

			if (mysql_num_rows($tilre) > 0) {

				echo "<tr>";

				echo "<th>".t("Laskunro")."</th>";
				echo "<th>".t("Asiakas")."</th>";
				echo "<th>".t("Laadittu")."</th>";
				echo "<th>".t("Laatija")."</th>";
				echo "<th>".t("Vienti")."</th>";
				echo "<th>".t("Tapvm")."</th>";

				echo "</tr>";

				while ($tilrow = mysql_fetch_array($tilre)) {

					echo "<tr>";

					echo "<td>$tilrow[laskunro]</td>";
					echo "<td>$tilrow[nimi]</td>";
					echo "<td>".tv1dateconv($tilrow["luontiaika"], "P")."</td>";
					echo "<td>$tilrow[laatija]</td>";
					echo "<td>$tilrow[vienti]</td>";
					echo "<td>".tv1dateconv($tilrow["tapvm"])."</td>";

					echo "<td class='back'>
							<form method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='otunnus' value='$tilrow[tunnus]'>
							<input type='hidden' name='tee' value='K'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='tapa' value='$tapa'>
							<input type='submit' value='".t("Valitse")."'></form></td>";

				}
				echo "</tr>";
			}
			else {
				echo "<tr>";
				echo "<th colspan='5'>".t("Yht‰‰n laskua ei lˆytynyt")."!</th>";
				echo "</tr>";
			}
			
			echo "</table>";
		}
	}	
	elseif ($tee == '') {

		$formi="find";
		$kentta="etsi";

		// tehd‰‰n etsi valinta
		echo "<form name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo t("Etsi tilausta (asiakkaan nimell‰ / tilausnumerolla)").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';
		if (is_string($etsi))  $haku="and nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and tunnus='$etsi'";

		//listataan laskuttamattomat tilausket
		$query = "	SELECT tunnus tilaus, nimi asiakas, luontiaika laadittu, laatija, vienti, erpcm, ytunnus, nimi, nimitark, postino, postitp, maksuehto, lisattava_era, vahennettava_era, ketjutus,
					maa_maara, kuljetusmuoto, kauppatapahtuman_luonne, sisamaan_kuljetus, sisamaan_kuljetusmuoto, poistumistoimipaikka, poistumistoimipaikka_koodi, alatila
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					and tila = 'L'
					and alatila in ('B','D','E')
					AND vienti in ('K','E')
					$haku
					ORDER by 5,6,7,8,9,10,11,12,13,14";
		$tilre = mysql_query($query) or pupe_error($query);

		echo "<br><br>";

		echo "<table>";

	 	if (mysql_num_rows($tilre) > 0) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($tilre)-18; $i++)
				echo "<th align='left'>".t(mysql_field_name($tilre,$i))."</th>";

			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Lis‰tiedot")."</th>";
			echo "</tr>";

			$lask = -1;
			$tunnukset 			= '';
			$ketjutus			= '';
			$erpcm				= '';
			$ytunnus			= '';
			$nimi				= '';
			$nimitark			= '';
			$postino			= '';
			$postitp			= '';
			$maksuehto			= '';
			$lisattava_era		= '';
			$vahennettava_era	= '';

			while ($tilrow = mysql_fetch_array($tilre)) {
				$query = "	SELECT sum(if(varattu>0,1,0))	veloitus, sum(if(varattu<0,1,0)) hyvitys
							from tilausrivi
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$tilrow[tilaus]'";
				$hyvre = mysql_query($query) or pupe_error($query);
				$hyvrow = mysql_fetch_array($hyvre);

				if ($ketjutus == '' and $erpcm == $tilrow["erpcm"] and $ytunnus == $tilrow["ytunnus"] and $nimi == $tilrow["nimi"] and $nimitark == $tilrow["nimitark"] and $postino == $tilrow["postino"] and $postitp == $tilrow["postitp"] and $maksuehto == $tilrow["maksuehto"] and $lisattava_era == $tilrow["lisattava_era"] and $vahennettava_era == $tilrow["vahennettava_era"]) {
					$tunnukset .= $tilrow["tilaus"].",";
					$lask++;
					echo "</tr>\n";
				}
				else {
					if ($lask >= 1) {
						$tunnukset = substr($tunnukset, 0, -1); // Vika pilkku pois
						echo "<td class='back'>
								<form method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='otunnus' value='$tunnukset'>
								<input type='hidden' name='tee' value='K'>
								<input type='submit' name='tila' value='".t("Ketjuta lis‰tiedot")."'></form></td>";
					}

					$tunnukset = $tilrow["tilaus"].",";

					if ($lask != -1) {
						echo "</tr>\n";
					}
					$lask = 0;
				}

				echo "\n\n<tr>";

				for ($i=0; $i<mysql_num_fields($tilre)-18; $i++)
					echo "<td>$tilrow[$i]</td>";

				if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] == 0) {
					$teksti = "Veloitus";
				}
				if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] > 0) {
					$teksti = "Veloitusta ja hyvityst‰";
				}
				if ($hyvrow["hyvitys"] > 0  and $hyvrow["veloitus"] == 0) {
					$teksti = "Hyvitys";
				}
				echo "<td>$teksti</td>";

				if ($tilrow['alatila'] == 'E' and $tilrow['vienti'] == 'K' and $tilrow['maa_maara'] != '' and $tilrow['kuljetusmuoto'] != '' and $tilrow['kauppatapahtuman_luonne'] > 0 and $tilrow['sisamaan_kuljetus'] != '' and $tilrow['sisamaan_kuljetusmuoto'] != '' and $tilrow['poistumistoimipaikka'] != '' and $tilrow['poistumistoimipaikka_koodi'] != '') {
					echo "<td><font color='#00FF00'>".t("OK")."</font></td>";
				}
				elseif ($tilrow['alatila'] == 'E' and $tilrow['vienti'] == 'E' and $tilrow['maa_maara'] != '' and $tilrow['kuljetusmuoto'] != '' and $tilrow['kauppatapahtuman_luonne'] > 0) {
					echo "<td><font color='#00FF00'>".t("OK")."</font></td>";
				}
				else {
					echo "<td>".t("Kesken")."</td>";
				}

				echo "<td class='back'>
						<form method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='otunnus' value='$tilrow[tilaus]'>
						<input type='hidden' name='tee' value='K'>
						<input type='submit' name='tila' value='".t("Valitse")."'></form></td>";

				$ketjutus			= $tilrow["ketjutus"];
				$erpcm				= $tilrow["erpcm"];
				$ytunnus			= $tilrow["ytunnus"];
				$nimi				= $tilrow["nimi"];
				$nimitark			= $tilrow["nimitark"];
				$postino			= $tilrow["postino"];
				$postitp			= $tilrow["postitp"];
				$maksuehto			= $tilrow["maksuehto"];
				$lisattava_era		= $tilrow["lisattava_era"];
				$vahennettava_era	= $tilrow["vahennettava_era"];
			}

			if ($tunnukset != '' and $lask >= 1) {
				$tunnukset = substr($tunnukset, 0, -1); // Vika pilkku pois

				echo "<td class='back'>
						<form method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='otunnus' value='$tunnukset'>
						<input type='hidden' name='tee' value='K'>
						<input type='hidden' name='extra' value='K'>
						<input type='submit' name='tila' value='".t("Ketjuta lis‰tiedot")."'></form></td>";
				$tunnukset = '';
			}
			echo "</tr>";
		}
		else {
			echo "<tr>";
			echo "<th colspan='5'>".t("Ei tilauksia")."!</th>";
			echo "</tr>";
		}
		echo "</table><br>";
	}

	require "../inc/footer.inc";
?>