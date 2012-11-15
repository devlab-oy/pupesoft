<?php

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Käteismyynnit")." $myy:</font><hr>";

	// Tarkistetaan että jos ei ole täsmäys päällä niin lukitaan täsmäyksen päivämäärät. Jos täsmäys on päällä, lukitaan normaalin raportin päivämäärät
	echo "<script type='text/javascript' language='JavaScript'>

			<!--
                                $(document).ready(function(){
                                    update_summa(\"tasmaytysform\");
                                  });

				function disableDates() {
					if (document.getElementById('tasmays').checked != true) {
						document.getElementById('pp').disabled = true;
						document.getElementById('kk').disabled = true;
						document.getElementById('vv').disabled = true;

						document.getElementById('ppa').disabled = false;
						document.getElementById('kka').disabled = false;
						document.getElementById('vva').disabled = false;

						document.getElementById('ppl').disabled = false;
						document.getElementById('kkl').disabled = false;
						document.getElementById('vvl').disabled = false;
					}
					else {
						document.getElementById('pp').disabled = false;
						document.getElementById('kk').disabled = false;
						document.getElementById('vv').disabled = false;

						document.getElementById('ppa').disabled = true;
						document.getElementById('kka').disabled = true;
						document.getElementById('vva').disabled = true;

						document.getElementById('ppl').disabled = true;
						document.getElementById('kkl').disabled = true;
						document.getElementById('vvl').disabled = true;
					}
				}

				function update_summa(ID) {
					obj = document.getElementById(ID);
					var summa = 0;
					var temp = 0;
					var solusumma = 0;
					var solut = 0;
					var erotus = 0;
					var edpointer = 1;
					var edpointer2 = 1;
					var pointer = 1;
					var pointer2 = 1;
					var kala = '';
					var kassa = 0;
					var loppukas = 0;
					var yht_alku = 0;
					var yht_kat = 0;
					var yht_katot = 0;
					var yht_kattil = 0;
					var yht_kasero = 0;
					var temp_kasero = 0;
					var yht_loppu = 0;

			 		for (i=0; i<obj.length; i++) {
						if (obj.elements[i].value == '') {
							obj.elements[i].value = 0;
						}

						//kala = kala+'\\n '+i+'. NIMI: '+obj.elements[i].id+' VALUE: '+obj.elements[i].value;

						if (obj.elements[i].id.substring(0,11) == ('rivipointer')) {
							var len = obj.elements[i].id.length;

							edpointer = pointer;
							edpointer2 = pointer2;

							pointer = obj.elements[i].id.substring(11,len);
							pointer2 = obj.elements[i].id.substring(11,len);
						}

						if (obj.elements[i].id.substring(0,10) == ('pohjakassa')) {
							if (obj.elements[i].value != '' && obj.elements[i].value != null) {
								if (pointer2 != edpointer2) {
									edpointer2 = pointer2;
									loppukas = 0;
								}

								loppukas += Number(obj.elements[i].value.replace(\",\",\".\"));

								if (document.getElementById('kassalippaan_loppukassa'+pointer2)) {
									document.getElementById('kassalippaan_loppukassa'+pointer2).value = loppukas.toFixed(2);
								}
								else {
									yht_loppu += Number(obj.elements[i].value.replace(\",\",\".\"));
								}

								summa += Number(obj.elements[i].value.replace(\",\",\".\"));
								temp += Number(obj.elements[i].value.replace(\",\",\".\"));
								yht_alku += Number(obj.elements[i].value.replace(\",\",\".\"));
							}
						}
						else if (obj.elements[i].id.substring(0,23) == ('kassalippaan_loppukassa')) {
							if (obj.elements[i].value != '' && obj.elements[i].value != null) {
								document.getElementById('yht_lopkas'+pointer).value = Number(obj.elements[i].value.replace(\",\",\".\"));
								yht_loppu += Number(obj.elements[i].value.replace(\",\",\".\"));
							}
						}
						else if (obj.elements[i].id.substring(0,13) == ('kateistilitys') && !isNaN(obj.elements[i].id.substring(13,14))) {
							if (obj.elements[i].value != '') {
								summa -= Number(obj.elements[i].value.replace(\",\",\".\"));
								yht_kattil += Number(obj.elements[i].value.replace(\",\",\".\"));

								loppukas -= Number(obj.elements[i].value.replace(\",\",\".\"));
								if (document.getElementById('kassalippaan_loppukassa'+pointer2)) {
									document.getElementById('kassalippaan_loppukassa'+pointer).value = loppukas.toFixed(2);
								}
							}
						}
						else if (obj.elements[i].value != '' && obj.elements[i].id == 'kaikkiyhteensa') {
							temp_value = Number(obj.elements[i].value.replace(\",\",\".\"));
							obj.elements[i].value = temp_value.toFixed(2);
						}
						else if (obj.elements[i].value != '' && obj.elements[i].id.substring(0,10) == ('kateisotto')) {
							summa -= Number(obj.elements[i].value.replace(\",\",\".\"));
							yht_katot += Number(obj.elements[i].value.replace(\",\",\".\"));

							loppukas -= Number(obj.elements[i].value.replace(\",\",\".\"));
							if (document.getElementById('kassalippaan_loppukassa'+pointer)) {
								document.getElementById('kassalippaan_loppukassa'+pointer).value = loppukas.toFixed(2);
							}
						}
						else if (obj.elements[i].value != '' && obj.elements[i].id.substring(0,19) == ('kateinen soluerotus')) {
							temp_kasero = Number(obj.elements[i].value.replace(\",\",\".\"));
							yht_kasero = yht_kasero + temp_kasero;
						}
						else if (obj.elements[i].value != '' && obj.elements[i].id.substring(0,23) == ('pankkikortti soluerotus')) {
							temp_kasero = Number(obj.elements[i].value.replace(\",\",\".\"));
							yht_kasero = yht_kasero + temp_kasero;
						}
						else if (obj.elements[i].value != '' && obj.elements[i].id.substring(0,23) == ('luottokortti soluerotus')) {
							temp_kasero = Number(obj.elements[i].value.replace(\",\",\".\"));
							yht_kasero = yht_kasero + temp_kasero;
						}
						else if (obj.elements[i].id.substring(0,8) == ('kateinen') && !isNaN(obj.elements[i].id.substring(13,14))) {
							if (pointer != edpointer) {
								edpointer = pointer;
								solut = 0;
							}

							if (obj.elements[i].value != '') {
								if (document.getElementById('kateinen erotus'+pointer).innerHTML !== null && document.getElementById('kateinen erotus'+pointer).innerHTML != '') {
									erotus = Number(document.getElementById('kateinen erotus'+pointer).innerHTML.replace(\",\",\".\"));
									document.getElementById('erotus'+pointer).value = erotus;
								}
								else {
									erotus = 0;
								}

								solut += Number(obj.elements[i].value.replace(\",\",\".\"));
								kassa = Number(obj.elements[i].value.replace(\",\",\".\"));

								solusumma = solut.toFixed(2) - erotus.toFixed(2);

								kassa = Number(kassa.toFixed(2));
								yht_kat += kassa;
								summa += kassa;
								temp += kassa;

								loppukas += Number(obj.elements[i].value.replace(\",\",\".\"));
								if (document.getElementById('kassalippaan_loppukassa'+pointer)) {
									document.getElementById('kassalippaan_loppukassa'+pointer).value = loppukas.toFixed(2);
								}

								document.getElementById('kateinen soluerotus'+pointer).value = solusumma.toFixed(2);

								if (solusumma.toFixed(2) == 0.00) {
									document.getElementById('kateinen soluerotus'+pointer).style.color = 'darkgreen';
								}
								else {
									document.getElementById('kateinen soluerotus'+pointer).style.color = '#FF5555';
								}

								document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
							}
						}
						else if (obj.elements[i].id.substring(0,12) == ('pankkikortti') && !isNaN(obj.elements[i].id.substring(17,18))) {
							if (pointer != edpointer) {
								edpointer = pointer;
								solut = 0;
							}

							if (obj.elements[i].value != '') {
								if (document.getElementById('pankkikortti erotus'+pointer).innerHTML != '') {
									erotus = Number(document.getElementById('pankkikortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
									document.getElementById('erotus'+pointer).value = Number(document.getElementById('pankkikortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
								}
								else {
									erotus = 0;
								}

								solut += Number(obj.elements[i].value.replace(\",\",\".\"));
								solusumma = solut - erotus;
								document.getElementById('pankkikortti soluerotus'+pointer).value = solusumma.toFixed(2);

								if (solusumma.toFixed(2) == 0.00) {
									document.getElementById('pankkikortti soluerotus'+pointer).style.color = 'darkgreen';
								}
								else {
									document.getElementById('pankkikortti soluerotus'+pointer).style.color = '#FF5555';
								}

								document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
							}
						}
						else if (obj.elements[i].id.substring(0,12) == ('luottokortti') && !isNaN(obj.elements[i].id.substring(17,18))) {
							if (pointer != edpointer) {
								edpointer = pointer;
								solut = 0;
							}

							if (obj.elements[i].value != '') {
								if (document.getElementById('luottokortti erotus'+pointer).innerHTML != '') {
									erotus = Number(document.getElementById('luottokortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
									document.getElementById('erotus'+pointer).value = Number(document.getElementById('luottokortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
								}
								else {
									erotus = 0;
								}

								solut += Number(obj.elements[i].value.replace(\",\",\".\"));

								solusumma = solut - erotus;
								document.getElementById('luottokortti soluerotus'+pointer).value = solusumma.toFixed(2);

								if (solusumma.toFixed(2) == 0.00) {
									document.getElementById('luottokortti soluerotus'+pointer).style.color = 'darkgreen';
								}
								else {
									document.getElementById('luottokortti soluerotus'+pointer).style.color = '#FF5555';
								}
								document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
							}
						}

						summa = Math.round(summa*100)/100;
						temp = Math.round(temp*100)/100;
						document.getElementById('kaikkiyhteensa').value = temp.toFixed(2);
						document.getElementById('yht_alkukassa').value = yht_alku.toFixed(2);
						document.getElementById('yht_kateinen').value = yht_kat.toFixed(2);
						document.getElementById('yht_kateisotto').value = yht_katot.toFixed(2);
						document.getElementById('yht_kateistilitys').value = yht_kattil.toFixed(2);
						document.getElementById('yht_kassaerotus').value = yht_kasero.toFixed(2);
						document.getElementById('yht_loppukassa').value = yht_loppu.toFixed(2);
						document.getElementById('loppukassa').value = summa.toFixed(2);
						document.getElementById('loppukassa2').value = summa.toFixed(2);

						document.getElementById('yht_alkukas').value = yht_alku.toFixed(2);
						document.getElementById('yht_kat').value = yht_kat.toFixed(2);
						document.getElementById('yht_katot').value = yht_katot.toFixed(2);
						document.getElementById('yht_kattil').value = yht_kattil.toFixed(2);
						document.getElementById('yht_kasero').value = yht_kasero.toFixed(2);

						if (obj.elements[i].value == 0 && obj.elements[i].id.substring(0,19) != 'kateinen soluerotus' && obj.elements[i].id.substring(0,23) != 'pankkikortti soluerotus' && obj.elements[i].id.substring(0,23) != 'luottokortti soluerotus') {
							obj.elements[i].value = '';
						}

					}
				}

				function toggleGroup(id) {
					if (document.getElementById(id).style.display != 'none') {
						document.getElementById(id).style.display = 'none';
					}
					else {
						document.getElementById(id).style.display = 'block';
					}
				}

				function verify() {

					var error = false;

					obj = document.getElementById('tasmaytysform');

			 		for (i=0; i < obj.length; i++) {
						if (obj.elements[i].id.substring(0,10) == ('pohjakassa') || obj.elements[i].id.substring(0,13) == ('kateistilitys') || obj.elements[i].id == 'kaikkiyhteensa' || obj.elements[i].id.substring(0,8) == ('kateinen') || obj.elements[i].id.substring(0,12) == ('pankkikortti') || obj.elements[i].id.substring(0,12) == ('luottokortti') || obj.elements[i].id.substring(0,10) == ('kateisotto')) {
							if (obj.elements[i].value != '' && obj.elements[i].value != null && isNaN(obj.elements[i].value.replace(\",\",\".\"))) {
								error = true;
							}
						}
					}

					if (error == true) {
						msg = '".t("Tietueiden täytyy sisältää vain numeroita").".';
						alert(msg);

						skippaa_tama_submitti = true;
						return false;
					}
					else {
						msg = '".t("Oletko varma?")."';

						if (confirm(msg)) {
							return true;
						}
						else {
							skippaa_tama_submitti = true;
							return false;
						}
					}
				}
			-->
	</script>";

	// Lockdown-funktio, joka tarkistaa onko kyseinen kassalipas jo täsmätty.
	function lockdown($vv, $kk, $pp, $tasmayskassa) {
		global $kukarow, $kassakone, $yhtiorow;

		if ($tasmayskassa == 'MUUT') {
			$row["nimi"] = 'MUUT';
		}
		else {
			$query = "SELECT nimi FROM kassalipas WHERE tunnus='$tasmayskassa' AND yhtio='$kukarow[yhtio]'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);
		}

		$tasmays_query = "	SELECT group_concat(distinct lasku.tunnus) ltunnukset
							FROM lasku
							JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio
							AND tiliointi.ltunnus = lasku.tunnus
							AND tiliointi.selite LIKE '%$row[nimi]%'
							AND tiliointi.korjattu = '')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							AND lasku.tila = 'X'
							AND lasku.tapvm = '$vv-$kk-$pp'";
		$tasmays_result = pupe_query($tasmays_query);
		$tasmaysrow = mysql_fetch_assoc($tasmays_result);

		if ($tasmaysrow["ltunnukset"] != "") {
			$tasmatty = array();
			$tasmatty["ltunnukset"] = $tasmaysrow["ltunnukset"];
			$tasmatty["kassalipas"] = $row["nimi"];

			return $tasmatty;
		}
		else {
			return false;
		}
	}

	function tosite_print ($vv, $kk, $pp, $ltunnukset, $tulosta = null) {
		global $kukarow, $kassakone, $yhtiorow, $printteri;

			$kassat_temp = "";

			if (is_array($ltunnukset)) {
				$kassat_temp = $ltunnukset["ltunnukset"];
			}

			$tasmays_query = "	SELECT tiliointi.*, lasku.comments kommentti
								FROM lasku
								JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio
								AND tiliointi.ltunnus = lasku.tunnus
								AND tiliointi.korjattu = '')
								JOIN tili ON (tili.yhtio = tiliointi.yhtio
								AND tili.tilino = tiliointi.tilino)
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								AND lasku.tunnus in ('$kassat_temp')
								ORDER BY tiliointi.tunnus, tiliointi.selite";
			$tasmays_result = pupe_query($tasmays_query);

			//kirjoitetaan  faili levylle..
			$filenimi = "/tmp/KATKIRJA.txt";
			$fh = fopen($filenimi, "w+");

			$linebreaker = "";
			$tilit = 0;
			$selite_count = 40;

			if (!is_array($kassakone) and strlen($kassakone) > 0) {
				$kassakone = unserialize(urldecode($kassakone));
			}

			if (is_array($kassakone)) {
				foreach($kassakone as $var) {
					$kassat .= "'".$var."',";
				}
				$kassat = substr($kassat,0,-1);
			}

			if ($kassat_temp != "" and !is_array($kassakone)) {
				$kassat = $kassat_temp;
			}

			$query = "	SELECT kateistilitys, kassaerotus, kateisotto
						FROM kassalipas
						WHERE tunnus in ($kassat)
						AND yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			if (is_array($kassakone) and count($kassakone) > 0) {
				$tilit = count($kassakone);
			}

			for ($ii = 0; $ii < $tilit; $ii++) {
				$linebreaker .= "-----------";
				$selite_count += 10;
			}

			$edltunnus = "X";
			$edselitelen = 0;

			while ($tasmaysrow = mysql_fetch_assoc($tasmays_result)) {

				if ($tasmaysrow["tilino"] != $row["kateistilitys"] and $tasmaysrow["tilino"] != $row["kassaerotus"] and $tasmaysrow["tilino"] != $row["kateisotto"] and !stristr($tasmaysrow["selite"], t("erotus"))) {

					if ($edltunnus != $tasmaysrow["ltunnus"]) {

						$ots  = t("Käteismyynnin tosite")." ($tasmaysrow[ltunnus]) $yhtiorow[nimi] $pp.$kk.$vv\n\n";
						$ots .= sprintf ('%-'.$selite_count.'.'.$selite_count.'s', t("Tapahtuma"));
						$ots .= sprintf ('%13s', t("Summa"));
						$ots .= "\n";
						$ots .= "$linebreaker----------------------------------------------------\n";
						fwrite($fh, $ots);
						$ots = chr(12).$ots;

						$edltunnus = $tasmaysrow["ltunnus"];
					}

					if ($edselitelen != strlen($tasmaysrow["selite"]) and $edselitelen != 0) {
						$prn = "\n";
						fwrite($fh, $prn);
						$rivit++;
					}

					$kommentti = $tasmaysrow["kommentti"];

					if ($rivit >= 60) {
						fwrite($fh, $ots);
						$rivit = 1;
					}

					$prn = sprintf ('%-'.$selite_count.'.'.$selite_count.'s', $tasmaysrow["selite"]);
					$prn .= str_replace(".",",", sprintf('%13s', $tasmaysrow["summa"]));
					$prn .= "\n";

					fwrite($fh, $prn);
					$rivit++;

					$edselitelen = strlen($tasmaysrow["selite"]);
				}
			}

			$prn  = "\n";
			$prn .= sprintf ('%-5000.5000s', 	$kommentti);
			$prn .= "\n\n";
			$rivit++;
			fwrite($fh, $prn);

			echo "<pre>",file_get_contents($filenimi),"</pre>";
			fclose($fh);

			if ($tulosta != null) {
				//haetaan tilausken tulostuskomento
				$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$printteri'";
				$kirres  = pupe_query($query);
				$kirrow  = mysql_fetch_assoc($kirres);
				$komento = $kirrow['komento'];

				$line = exec("a2ps -o $filenimi.ps -R --medium=A4 --chars-per-line=94 --columns=1 --margin=1 --borders=0 $filenimi");

				if ($komento == "email" and $kukarow["eposti"] != '') {
					// lähetetään meili
					$komento = "";

					$kutsu = t("Käteismyynnit", $kieli);

					$liite 				= "$filenimi.ps";
					$sahkoposti_cc 		= "";
					$content_subject 	= "";
					$content_body 		= "";

					include("inc/sahkoposti.inc");
				}
				elseif ($komento != "" and $komento != "email") {
					// itse print komento...
					$line = exec("$komento $filenimi.ps");
				}

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $filenimi");
				system("rm -f $filenimi.ps");
			}
	}

	// Jos ollaan täsmäyttämässä käteismyyntiä
	if (isset($tasmays) and $tasmays != "") {

		// Tarkistetaan eriävätkö kassalippaiden pankki- ja luottokorttitilit
		if (count($kassakone) > 1) {
			$kassat_temp = "";

			foreach($kassakone as $var) {
				$kassat_temp .= "'".$var."',";
			}

			$kassat_temp = substr($kassat_temp,0,-1);

			$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' and tunnus in ($kassat_temp)";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 1) {

				$account_check = array();

				while ($row = mysql_fetch_assoc($result)) {

					if ($row['kassa'] == '' or $row['pankkikortti'] == '' or $row['luottokortti'] == '' or $row['kateistilitys'] == '' or $row['kassaerotus'] == '' or $row['kateisotto'] == '') {
						echo "<font class='error'>".t("Kassalippaan")." $row[nimi] ".t("tiedot ovat puutteelliset").".</font><br>";
						$tee = '';
					}

					$account_check["luottokortti"][] = $row["luottokortti"];
					$account_check["pankkikortti"][] = $row["pankkikortti"];
				}

				$foo = "";
				$foo = array_count_values($account_check["luottokortti"]);

				if (count($foo) > 1) {
					echo "<font class='error'>".t("Kassalippaiden pankki- ja luottokorttitilit eriävät").".</font><br>";
					echo "<font class='error'>".t("Tarkista tiedot ja kokeile uudelleen").".</font><br><br>";
					$tee = '';
				}
				else {
					$foo = "";
				}

				if ($foo == "") {
					$foo = "";
					$foo = array_count_values($account_check["pankkikortti"]);

					if (count($foo) > 1) {
						echo "<font class='error'>".t("Kassalippaiden pankki- ja luottokorttitilit eriävät").".</font><br>";
						echo "<font class='error'>".t("Tarkista tiedot ja kokeile uudelleen").".</font><br><br>";
						$tee = '';
					}
					else {
						$foo = "";
					}
				}
			}
		}

		// Jos täsmäys on päällä ja ei olla valittu mitään kassalipasta -> error
		if (count($kassakone) == 0 and $muutkassat == '') {
			echo "<font class='error'>".t("Valitse kassalipas")."!</font><br>";
			$tee = '';
		}

		// Jos täsmäys on päällä ja tilitettävien sarakkeiden määrä on jotain muuta kuin väliltä 1-9 -> error
		if ((int) $tilityskpl < 1 or (int) $tilityskpl > 20) {
			echo "<font class='error'>".t("Sarakkeiden määrä pitää olla väliltä 1 - 20")."!</font><br>";
			$tee = '';
		}

		// Jos täsmäys on päällä ja ei olla annettu päivämäärää -> error
		if (($vv == '' or $kk == '' or $pp == '') and !checkdate($kk, $pp, $vv)) {
			echo "<font class='error'>".t("Syötä päivämäärä (pp-kk-vvvv)")."</font><br>";
			$tee = '';
		}

		// Ei osata vielä täsmätä käteissuorituksia
		if ($katsuori != '') {
			echo "<font class='error'>".t("Sinä et osaa vielä täsmäyttää käteissuorituksia.")."</font><br>";
			$tee = '';
		}

		// Tarkistetaan ettei kassalippaiden tilejä puutu
		if (count($kassakone) > 0) {
			$kassat_temp = "";

			foreach($kassakone as $var) {
				$kassat_temp .= "'".$var."',";
			}
			$kassat_temp = substr($kassat_temp,0,-1);

			$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' and tunnus in ($kassat_temp) and kassa != '' and pankkikortti != '' and luottokortti != '' and kateistilitys != '' and kassaerotus != '' and kateisotto != ''";
			$result = pupe_query($query);

			if (mysql_num_rows($result) != count($kassakone)) {
				echo "<font class='error'>".t("Ei voida täsmäyttää. Kassalippaan pakollisia tietoja puuttuu").".</font><br>";
				$tee = '';
			}
		}

	}

	// Aloitetaan tiliöinti
	if ($tee == "tiliointi") {

		$ktunnukset = "";

		$kassalipas_tunnus = unserialize(urldecode($kassalipas_tunnus));

		if (count($kassalipas_tunnus) > 0) {
			foreach ($kassalipas_tunnus as $key => $ktunnus) {
				$ktunnukset .= "'$ktunnus',";
			}
			$ktunnukset = substr($ktunnukset, 0, -1);
		}

		$query = "	INSERT INTO lasku SET
					yhtio      = '$kukarow[yhtio]',
					tapvm      = '$vv-$kk-$pp',
					tila       = 'X',
					laatija    = '$kukarow[kuka]',
					luontiaika = now()";
		$result = pupe_query($query);
		$laskuid = mysql_insert_id();

		$maksutapa	 	  = "";
		$kassalipas 	  = "";
		$tilino 		  = "";
		$pohjakassa 	  = "";
		$loppukassa 	  = "";
		$comments 		  = "";
		$comments_yht     = "";
		$tyyppi 		  = "";
		$kustp 			  = "";
		$loppukassa_array = array();

		$kassalippaat_array = populoi_kassalipas_muuttujat_kassakohtaisesti($_POST);

		foreach ($_POST as $kentta => $arvo) {

			if (stristr($kentta, "pohjakassa")) {
				if (stristr($kentta, "tyyppi")) {
					$tyyppi = $arvo;
					$comments .= "$arvo alkukassa: ";
				}
				else {
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments .= "$arvo<br>";
					$pohjakassa += $arvo;
				}
			}
			else if (stristr($kentta,"yht_lopkas")) {
				$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
				$comments .= "$tyyppi loppukassa: $arvo<br><br>";
				$loppukassa_array[$kassalipas] = $arvo;
			}

			if (stristr($kentta, "yht_")) {
				if ($kentta == "yht_kat") {
					$comments_yht .= "Käteinen yhteensä: ";
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments_yht .= "$arvo<br>";
				}
				else if ($kentta == "yht_katot") {
					$comments_yht .= "Käteisotto yhteensä: ";
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments_yht .= "$arvo<br>";
				}
				else if ($kentta == "yht_kattil") {
					$comments_yht .= "Käteistilitys yhteensä: ";
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments_yht .= "$arvo<br>";
				}
				else if ($kentta == "yht_kasero") {
					$comments_yht .= "Kassaerotus yhteensä: ";
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments_yht .= "$arvo<br>";
				}
			}

			if (stristr($kentta, "loppukassa")) {
				$loppukassa = $arvo;
			}

			if (stristr($arvo, "pankkikortti")) {
				$maksutapa = t("Pankkikortti");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);


				// Haetaan kassalipastiedot tietokannasta
				$query = "SELECT * FROM kassalipas WHERE yhtio = '$kukarow[yhtio]' AND tunnus IN ($ktunnukset) AND nimi = '$kassalipas'";
				$result = pupe_query($query);

				$kustp = "";

				if (mysql_num_rows($result) == 1) {
					$kassalipasrow = mysql_fetch_assoc($result);
					$tilino = $kassalipasrow["pankkikortti"];
					$kustp  = $kassalipasrow["kustp"];
				}

				if ($tilino == "") $tilino = $yhtiorow["pankkikortti"];
			}
			elseif (stristr($arvo, "luottokortti")) {
				$maksutapa = t("Luottokortti");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);

				// Haetaan kassalipastiedot tietokannasta
				$query = "SELECT * FROM kassalipas WHERE yhtio = '$kukarow[yhtio]' AND tunnus IN ($ktunnukset) AND nimi = '$kassalipas'";
				$result = pupe_query($query);

				$kustp = "";

				if (mysql_num_rows($result) == 1) {
					$kassalipasrow = mysql_fetch_assoc($result);
					$tilino = $kassalipasrow["luottokortti"];
					$kustp  = $kassalipasrow["kustp"];
				}

				if ($tilino == "") $tilino = $yhtiorow["luottokortti"];
			}
			elseif (stristr($arvo, "kateinen")) {
				$maksutapa = t("Käteinen");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);

				// Haetaan kassalipastiedot tietokannasta
				$query = "SELECT * FROM kassalipas WHERE yhtio = '$kukarow[yhtio]' AND tunnus IN ($ktunnukset) AND nimi = '$kassalipas'";
				$result = pupe_query($query);

				$kustp = "";

				if (mysql_num_rows($result) == 1) {
					$kassalipasrow = mysql_fetch_assoc($result);
					$tilino = $kassalipasrow["kassa"];
					$kustp  = $kassalipasrow["kustp"];
				}

				if ($tilino == "") $tilino = $yhtiorow["kassa"];
			}

			// Tarkistetaan ettei arvo ole nolla ja jos kentän nimi on joko solu tai erotus
			// Ei haluta tositteeseen nollarivejä
			if (abs(str_replace(",",".",$arvo)) > 0 and (stristr($kentta, "solu") or stristr($kentta, "erotus"))) {

				// Pilkut pisteiksi
				$arvo = str_replace(",", ".", $arvo);

				// Jos kentän nimi on soluerotus niin se tiliöidään kassaerotustilille (eli täsmäyserot), muuten normaalisti ylempänä parsetettu tilinumero
				if (stristr($kentta, "soluerotus")) {
					$tilino = $kassalipasrow["kassaerotus"];
					if ($tilino == "") $tilino = $yhtiorow["kassaerotus"];
				}

				// Jos kenttä on soluerotus tai erotus niin kerrotaan arvo -1:llä
				if (stristr($kentta, "soluerotus") or stristr($kentta, "erotus")) {
					$arvo = $arvo * -1;
				}

				$selitelisa = "";

				// Jos kenttä on soluerotus niin lisätään selitteeseen "kassaero"
				if (stristr($kentta, "soluerotus")) {
					$selitelisa .= " ".t("kassaero");
				}
				// Jos kenttä on erotus niin lisätään selitteeseen "erotus"
				elseif (stristr($kentta, "erotus")) {
					$selitelisa .= " ".t("erotus");
				}

				list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($tilino, $kustp);

				// Aletaan rakentaa inserttiä
				$query = "	INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino	 = '$tilino',
							kustp    = '{$kustp_ins}',
							kohde	 = '{$kohde_ins}',
							projekti = '{$projekti_ins}',
							tapvm    = '$vv-$kk-$pp',
							summa 	 = $arvo,
							vero     = 0,
							lukko    = '',
							selite   = '$kassalipas $maksutapa$selitelisa',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = pupe_query($query);
			}

			// Jos kenttä on käteistilitys, niin toinen tiliöidään käteistilitys-tilille ja se summa myös miinustetaan kassasta
			if (abs(str_replace(",",".",$arvo)) > 0 and stristr($kentta, "kateistilitys")) {

				$arvo = str_replace(",",".",$arvo);

				if ($kassalipasrow["kateistilitys"] == "") {
					$kassalipasrow["kateistilitys"] = $yhtiorow["kateistilitys"];
				}

				list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($kassalipasrow["kateistilitys"], $kustp);

				$query = "	INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kateistilitys]',
							kustp    = '{$kustp_ins}',
							kohde	 = '{$kohde_ins}',
							projekti = '{$projekti_ins}',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo,
							vero     = 0,
							lukko    = '',
							selite   = '$kassalipas ".t("Käteistilitys pankkiin kassasta")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = pupe_query($query);

				list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($kassalipasrow["kassa"], $kustp);

				if ($kassalipasrow["kassa"] == "") {
					$kassalipasrow["kassa"] = $yhtiorow["kassa"];
				}

				$query = "	INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kassa]',
							kustp    = '{$kustp_ins}',
							kohde	 = '{$kohde_ins}',
							projekti = '{$projekti_ins}',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo * -1,
							vero     = 0,
							lukko    = '',
							selite   = '$kassalipas ".t("Käteistilitys pankkiin kassasta")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = pupe_query($query);
			}

			// Jos kenttä on käteisotto, niin toinen tiliöidään käteisotto-tilille ja se summa myös miinustetaan kassasta
			if (abs(str_replace(",",".",$arvo)) > 0 and stristr($kentta, "kateisotto")) {
				$arvo = str_replace(",",".",$arvo);

				if ($kassalipasrow["kateisotto"] == "") {
					$kassalipasrow["kateisotto"] = $yhtiorow["kassaerotus"];
				}

				list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($kassalipasrow["kateisotto"], $kustp);

				$query = "	INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kateisotto]',
							kustp    = '$kustp',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo,
							vero     = 0,
							lukko    = '',
							selite   = '$kassalipas ".t("Käteisotto kassasta")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = pupe_query($query);

				if ($kassalipasrow["kassa"] == "") {
					$kassalipasrow["kassa"] = $yhtiorow["kassa"];
				}

				list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($kassalipasrow["kassa"], $kustp);

				$query = "	INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kassa]',
							kustp    = '$kustp',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo * -1,
							vero     = 0,
							lukko    = '',
							selite   = '$kassalipas ".t("Käteisotto kassasta")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = pupe_query($query);
			}
		}

		$pohjakassa = str_replace(".",",",sprintf('%.2f',$pohjakassa));

		$comments_yht .= "Loppukassa yhteensä: ";
		$loppukassa = str_replace(".",",",sprintf('%.2f',$loppukassa));
		$comments_yht .= "$loppukassa<br>";

		$kassa_json = json_encode($kassalippaat_array);
		$query = "	UPDATE lasku
					SET
						comments   = '$comments<br>".t("Alkukassa yhteensä").": $pohjakassa<br>$comments_yht',
						sisviesti2 = concat_ws('##', '$kassa_json', sisviesti2)
					WHERE yhtio  = '$kukarow[yhtio]'
					AND tunnus = $laskuid";
		$result = pupe_query($query);

		$tulosta = "kyllä";
		$lasku_id = array();
		$lasku_id["ltunnukset"] = $laskuid;
		tosite_print($vv, $kk, $pp, $lasku_id, $tulosta);
	}
	elseif ($tee != '') {

		//Jos halutaa failiin
		if ($printteri != '') {
			$vaiht = 1;
		}
		else {
			$vaiht = 0;
		}

		$kassat = "";
		$lisa   = "";

		if (is_array($kassakone)) {
			foreach($kassakone as $var) {
				$kassat .= "'".$var."',";
			}
			$kassat = substr($kassat,0,-1);
		}

		if ($muutkassat != '') {
			if ($kassat != '') {
				$kassat .= ",''";
			}
			else {
				$kassat = "''";
			}
		}

		if ($kassat != "") {
			$kassat = " and lasku.kassalipas in ($kassat) ";
		}
		else {
			$kassat = " and lasku.kassalipas = 'ei nayteta eihakat, akja'";
		}

		if ((int) $myyjanro > 0) {
			$query = "	SELECT tunnus
						FROM kuka
						WHERE yhtio	= '$kukarow[yhtio]'
						and myyja 	= '$myyjanro'
						AND myyja > 0";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			$lisa = " and lasku.myyja='$row[tunnus]' ";
		}
		elseif ($myyja != '') {
			$lisa = " and lasku.laatija='$myyja' ";
		}

		$lisa .= " and lasku.vienti in (";

		if ($koti == 'KOTI' or ($koti=='' and $ulko=='')) {
			$lisa .= "''";
		}

		if ($ulko == 'ULKO') {
			if ($koti == 'KOTI') {
				$lisa .= ",";
				}
			$lisa .= "'K','E'";
		}
		$lisa .= ") ";

		if (isset($tasmays) and $tasmays != '') {
			//ylikirjotetaan koko lisä, koska ei saa olla muita rajauksia
			$lisa = " and lasku.tapvm = '$vv-$kk-$pp'";
		}
		else {
			if ($vva == $vvl and $kka == $kkl and $ppa == $ppl) {
				$lisa .= " and lasku.tapvm = '$vva-$kka-$ppa'";
			}
			else {
				$lisa .= " and lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl'";
			}
		}

		$myyntisaamiset_tilit = "'$yhtiorow[kassa]','$yhtiorow[pankkikortti]','$yhtiorow[luottokortti]',";

		if (count($kassakone) > 0) {
			$kassat_temp = "";

			foreach($kassakone as $var) {
				$kassat_temp .= "'".$var."',";
			}

			$kassat_temp = substr($kassat_temp,0,-1);

			$query = "	SELECT *
						FROM kassalipas
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus in ($kassat_temp)";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == count($kassakone)) {
				while ($row = mysql_fetch_assoc($result)) {
					if ($row["kassa"] != $yhtiorow["kassa"]) {
						$myyntisaamiset_tilit .= "'$row[kassa]',";
					}
					if ($row["pankkikortti"] != $yhtiorow["pankkikortti"]) {
						$myyntisaamiset_tilit .= "'$row[pankkikortti]',";
					}
					if ($row["luottokortti"] != $yhtiorow["luottokortti"]) {
						$myyntisaamiset_tilit .= "'$row[luottokortti]',";
					}
				}
			}
			else {
				die("virhe");
			}
		}

		$myyntisaamiset_tilit = substr($myyntisaamiset_tilit, 0, -1);

		//jos monta kassalipasta niin tungetaan tämä queryyn.
		if (count($kassakone) > 1 and isset($tasmays) and $tasmays != '') {
			$selecti = "if(tiliointi.tilino = kassalipas.kassa OR tiliointi.tilino = '$yhtiorow[kassa]', concat(kassalipas.nimi, ' kateinen'),
				if(tiliointi.tilino = kassalipas.pankkikortti OR tiliointi.tilino = '$yhtiorow[pankkikortti]', 'Pankkikortti',
				if(tiliointi.tilino = kassalipas.luottokortti OR tiliointi.tilino = '$yhtiorow[luottokortti]', 'Luottokortti', 'Muut'))) tyyppi, ";
		}
		else {
			$selecti = "if(tiliointi.tilino = kassalipas.kassa OR tiliointi.tilino = '$yhtiorow[kassa]', 'Kateinen',
				if(tiliointi.tilino = kassalipas.pankkikortti OR tiliointi.tilino = '$yhtiorow[pankkikortti]', 'Pankkikortti',
				if(tiliointi.tilino = kassalipas.luottokortti OR tiliointi.tilino = '$yhtiorow[luottokortti]', 'Luottokortti', 'Muut'))) tyyppi, ";
		}

		//Haetaan käteislaskut
		$query = "	SELECT
					$selecti
					if(lasku.kassalipas = '', 'Muut', lasku.kassalipas) kassa,
					if(ifnull(kassalipas.nimi, '') = '', 'Muut', kassalipas.nimi) kassanimi,
					tiliointi.tilino,
					tiliointi.summa tilsumma,
					lasku.nimi,
					lasku.ytunnus,
					lasku.laskunro,
					lasku.tunnus,
					lasku.summa-lasku.pyoristys summa,
					lasku.laskutettu,
					lasku.tapvm,
					lasku.kassalipas,
					tiliointi.ltunnus,
					kassalipas.tunnus ktunnus
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN maksuehto ON (maksuehto.yhtio=lasku.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen != '')
					LEFT JOIN tiliointi ON (tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and tiliointi.korjattu = '' and tiliointi.tilino in ($myyntisaamiset_tilit))
					LEFT JOIN kassalipas ON (kassalipas.yhtio=lasku.yhtio and kassalipas.tunnus=lasku.kassalipas)
					WHERE
					lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'U'
					and lasku.alatila = 'X'
					$lisa
					$kassat
					ORDER BY kassa, kassanimi, tyyppi, lasku.tapvm, lasku.laskunro";
		$result = pupe_query($query);

		//haetaan viimeisin käteistäsmäytys joka on poistettu.
		//sisviesti2 käytetään formin esitäytössä
		$tasmaytys_query = "	SELECT comments,
								sisviesti2
								FROM lasku
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tapvm = '$vv-$kk-$pp'
								AND tila = 'U'
								AND comments != ''
								AND sisviesti2 != ''
								ORDER BY luontiaika DESC
								LIMIT 1";
		$tasmaytys_result = pupe_query($tasmaytys_query);
		$tasmaytys_row = mysql_fetch_assoc($tasmaytys_result);

		$tasmaytys_json_array = explode('##' , $tasmaytys_row['sisviesti2']);

		$tasmaytys_array = json_decode($tasmaytys_json_array[0], true);

		$i = 1;

		if (mysql_num_rows($result) == 0) {
			$i = 2;
			echo "<font class='error'>".t("Käteismyyntejä ei löydy tälle päivälle")."</font>";
		}

		$ltunnukset = array();

		// Tarkistetaan ensiksi onko kassalippaat jo tiliöity lockdown-funktion avulla
		if (isset($tasmays) and $tasmays != '') {
			$ltunnusx = array();
			if ($kassakone != '') {
				foreach ($kassakone as $kassax) {
					if ($ltunnusx = lockdown($vv, $kk, $pp, $kassax)) {
						$ltunnukset = array_merge($ltunnukset, $ltunnusx);
						$i++;
					}
				}
				if ($muutkassat != '') {
					if ($ltunnusx = lockdown($vv, $kk, $pp, $muutkassat)) {
						$ltunnukset = array_merge($ltunnukset, $ltunnusx);
						$i++;
					}
				}
			}
			elseif ($kassakone == '' and $muutkassat != '') {
				if ($ltunnusx = lockdown($vv, $kk, $pp, $muutkassat)) {
					$ltunnukset = array_merge($ltunnukset, $ltunnusx);
					$i++;
				}
			}

			if (count($ltunnukset) > 0) {
				tosite_print($vv, $kk, $pp, $ltunnukset);
				echo "$ltunnukset[kassalipas] ".t("on jo täsmätty. Tosite löytyy myös")." <a href='".$palvelin2."muutosite.php?tee=E&tunnus=$ltunnukset[ltunnukset]'>".t("täältä")."</a><br>";
			}
		}

		if ($i > 1) {
			// Jos tositteita löytyy niin ei tehdä mitään
		}
		else {
			if (isset($tasmays) and $tasmays != '') {
				echo "<table><tr><td>";
				echo "<font class='head'>".t("Täsmäys").":</font><br>";
				echo "<form method='post' id='tasmaytysform' onSubmit='return verify();'>";
				echo "<input type='hidden' name='tee' value='tiliointi'>";
				echo "<table width='100%'>";
				echo "<tr>";

				if ($tilityskpl > 1) {
					for ($yyy = 1; $yyy < $tilityskpl; $yyy++) {
						$yyyy = $yyy + 1;
						echo "<td>&nbsp;</td>";
					}
				}

				echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td align='center' style='width:100px' nowrap>".strtoupper(t("Tilitys"))." 1</td>";

				if ($tilityskpl > 1) {
					for ($yyy = 1; $yyy < $tilityskpl; $yyy++) {
						$yyyy = $yyy + 1;
						echo "<td align='center' style='width:100px' nowrap>".strtoupper(t("Tilitys"))." $yyyy</td>";
					}
				}

				echo "<td align='center' style='width:100px' nowrap>".strtoupper(t("Myynti"))."</td><td align='center' style='width:100px' nowrap>".strtoupper(t("Erotus"))."</td></tr>";
				echo "</tr>";

				$row = mysql_fetch_assoc($result);
				$row = get_pohjakassa($row);
				echo "<input type='hidden' id='rivipointer$i' name='rivipointer$i' value=''>";
				echo "<input type='hidden' name='tyyppi_pohjakassa$i' id='tyyppi_pohjakassa$i' value='$row[kassanimi]'>";
				echo "<tr><td colspan='";

				if ($tilityskpl > 1) {
					echo $tilityskpl+2;
				}
				else {
					echo "3";
				}

				echo "' align='left' class='tumma' width='300px' nowrap>$row[kassanimi] ".t("alkukassa").":</td>";
				$pohja = $row["pohjakassa"];
				echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='pohjakassa$i' name='pohjakassa$i' size='10' autocomplete='off' value='{$tasmaytys_array[$row['kassanimi']]['pohjakassa']}' onkeyup='update_summa(\"tasmaytysform\");'></td>";

				if ($tilityskpl > 1) {
					for ($yy = 1; $yy < $tilityskpl; $yy++) {
						echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td>";
					}
				}

				echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td><td class='tumma' style='width:100px' nowrap>&nbsp;</td></tr></table>";
			}
			else {
				echo "<table><td>";
			}

			echo "<table width='100%' id='nayta$i' style='display:none;'><tr>
					<th nowrap>".t("Kassa")."</th>
					<th nowrap>".t("Asiakas")."</th>
					<th nowrap>".t("Ytunnus")."</th>
					<th nowrap>".t("Laskunumero")."</th>
					<th nowrap>".t("Pvm")."</th>
					<th nowrap>$yhtiorow[valkoodi]</th></tr>";

			if ((!isset($tasmays) or $tasmays == '') and $vaiht == 1) {
				//kirjoitetaan  faili levylle..
				$filenimi = "/tmp/KATKIRJA.txt";
				$fh = fopen($filenimi, "w+");

				$ots  = t("Käteismyynnin päiväkirja")." $yhtiorow[nimi] $ppa.$kka.$vva-$ppl.$kkl.$vvl\n\n";
				$ots .= sprintf ('%-20.20s', t("Kassa"));
				$ots .= sprintf ('%-25.25s', t("Asiakas"));
				$ots .= sprintf ('%-10.10s', t("Y-tunnus"));
				$ots .= sprintf ('%-12.12s', t("Laskunumero"));
				$ots .= sprintf ('%-20.20s', t("Pvm"));
				$ots .= sprintf ('%-13.13s', "$yhtiorow[valkoodi]");
				$ots .= "\n";
				$ots .= "----------------------------------------------------------------------------------------------\n";
				fwrite($fh, $ots);
				$ots = chr(12).$ots;
			}

			$rivit 				= 1;
			$yhteensa 			= 0;
			$kassayhteensa 		= 0;

			$kateismaksu 		= "";
			$kateismaksuekotus	= "";
			$myynti_yhteensa 	= 0;
			$pankkikortti 		= "";
			$luottokortti 		= "";
			$edkassa 			= "";
			$edktunnus 			= "";
			$solu 				= "";
			$tilinumero 		= array();
			$kassalippaat 		= array();
			$kassalipas_tunnus 	= array();

			if (isset($tasmays) and $tasmays != '') {
				if (mysql_num_rows($result) > 0) {
					mysql_data_seek($result, 0);
				}

				while ($row = mysql_fetch_assoc($result)) {
					$row = get_pohjakassa($row);
					if ($row["tyyppi"] == 'Pankkikortti') {
						$pankkikortti = true;
					}
					if ($row["tyyppi"] == 'Luottokortti') {
						$luottokortti = true;
					}

					if (stristr($row["tyyppi"], 'kateinen')) {

						$solu = "kateinen";

						if ($edkassa != $row["kassa"] or ($kateinen != $row["tilino"] and $kateinen != '')) {

							if (stristr($kateismaksu, 'kateinen')) {

								$kassalippaat[$edkassanimi] = $edkassanimi;
								$kassalipas_tunnus[$edkassanimi] = $edktunnus;

								if ($row["tilino"] != '') {
									$tilinumero["kateinen"] = $row["tilino"];
								}
								elseif ($kateinen != '') {
									$tilinumero["kateinen"] = $kateinen;
								}
								else {
									$tilinumero["kateinen"] = $yhtiorow["kassa"];
									$kateinen				= $yhtiorow["kassa"];
								}

								echo "</table><table width='100%'>";
								echo "<tr>";
								echo "<td colspan='";

								if ($tilityskpl > 1) {
									echo $tilityskpl+6;
								}
								else {
									echo "9";
								}

								echo "'";
								echo "' class='tumma' width='300px' nowrap>$kateismaksuekotus ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
								echo "<input type='hidden' name='maksutapa$i' id='maksutapa$i' value='$solu#$tilinumero[kateinen]#$edkassanimi'>";
								echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu solu$i' name='solu$i' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['solu'.$i]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";

								if ($tilityskpl > 1) {
									$y = $i;
									$temp_indeksi = $i + 1;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu solu$y' name='solu$y' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['solu'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
										$temp_indeksi++;
									}
								}

								echo "<td align='right' class='tumma' style='width:100px' nowrap><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
								echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu soluerotus$i' size='10' disabled></td></tr>";
								echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
								echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";

								echo "<tr><td class='tumma' colspan='";
									if ($tilityskpl > 1) {
										echo $tilityskpl+6;
									}
									else {
										echo "9";
									}
								echo "' width='300px' nowrap>$edkassanimi ".t("käteisotto kassasta").":</td><td class='tumma' align='center'>";
								echo "<input type='text' name='kateisotto$i' id='kateisotto$i' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['kateisotto'.$i]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
								if ($tilityskpl > 1) {
									$y = $i;
									$temp_indeksi = $i + 1;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='kateisotto$y' name='kateisotto$y' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['kateisotto'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
										$temp_indeksi++;
									}
								}
								echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td><td class='tumma' style='width:100px' nowrap>&nbsp;</td></tr>";

								echo "<tr><td colspan='";
									if ($tilityskpl > 1) {
										echo $tilityskpl+6;
									}
									else {
										echo "9";
									}
								echo "' align='left' class='tumma' width='300px' nowrap>$edkassanimi ".t("käteistilitys pankkiin kassasta").":</td>";
								echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='kateistilitys$i' name='kateistilitys$i' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['kateistilitys'.$i]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
								if ($tilityskpl > 1) {
									$y = $i;
									$temp_indeksi = $i + 1;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='kateistilitys$y' name='kateistilitys$y' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['kateistilitys'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
										$temp_indeksi++;
									}
								}
								echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td><td class='tumma' style='width:100px' nowrap>&nbsp;</td></tr>";

								echo "<tr><td colspan='";
									if ($tilityskpl > 1) {
										echo $tilityskpl+6;
									}
									else {
										echo "9";
									}
								echo "' align='left' class='tumma' width='300px' nowrap>$edkassanimi ".t("loppukassa").":</td>";
								echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='kassalippaan_loppukassa$i' name='kassalippaan_loppukassa$i' size='10' disabled></td>";
								echo "<input type='hidden' name='yht_lopkas$i' id='yht_lopkas$i' value=''>";
								if ($tilityskpl > 1) {
									$y = $i;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td>";
									}
								}
								echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td><td class='tumma' style='width:100px' nowrap>&nbsp;</td></tr>";

								$i++;
							}
						}

						if ($edkassa != $row["kassa"] and $edkassa != '') {
							echo "<tr><td>&nbsp;</td></tr>";
							echo "<input type='hidden' id='rivipointer$i' name='rivipointer$i' value=''>";
							echo "<input type='hidden' name='tyyppi_pohjakassa$i' id='tyyppi_pohjakassa$i' value='$row[kassanimi]'>";
							echo "<tr><td colspan='";
								if ($tilityskpl > 1) {
									echo $tilityskpl+6;
								}
								else {
									echo "9";
								}
							echo "' align='left' class='tumma' width='300px' nowrap>$row[kassanimi] ".t("alkukassa").":</td>";
							$pohja = $row["pohjakassa"];
							$pohja = $tasmaytys_array[$edkassanimi]['pohjakassa'];
							echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='pohjakassa$i' name='pohjakassa$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");' value='{$pohja}'></td>";
							if ($tilityskpl > 1) {
								for ($yy = 1; $yy < $tilityskpl; $yy++) {
									echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td>";
								}
							}
							echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td><td class='tumma' style='width:100px' nowrap>&nbsp;</td></tr>";

							echo "</table>";
							echo "<table id='nayta$i' style='display:none;' width='100%'>";
							echo "<tr>
									<th>".t("Kassa")."</th>
									<th>".t("Asiakas")."</th>
									<th>".t("Ytunnus")."</th>
									<th>".t("Laskunumero")."</th>
									<th>".t("Pvm")."</th>
									<th>$yhtiorow[valkoodi]</th></tr>";

							$kassayhteensa = 0;
							$kateismaksuyhteensa = 0;
						}


						echo "<tr class='aktiivi'>";
						echo "<td>$row[kassanimi]</td>";
						echo "<td>".substr($row["nimi"],0,23)."</td>";
						echo "<td>$row[ytunnus]</td>";
						echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
						echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
						echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

						$kateismaksu 		= $row['tyyppi'];
						$kateismaksuekotus 	= t(str_replace("kateinen", "Käteinen", $kateismaksu));
						$kateismaksuyhteensa+= $row["tilsumma"];
						$yhteensa 			+= $row["tilsumma"];
						$kassayhteensa 		+= $row["tilsumma"];

						$kateinen    	= $row["tilino"];
						$edktunnus 		= $row["ktunnus"];
						$edkassa 	 	= $row["kassa"];
						$edkassanimi 	= $row["kassanimi"];
						$edkateismaksu 	= $kateismaksu;
					}
				}

				if ($solu != 'kateinen' and ($pankkikortti === true or $luottokortti === true)) {
					// jos meillä on vain pankkikortti ja/tai luottokorttitapahtumia eikä yhtään käteistä niin halutaan silti nähdä kassalippaan alku- ja loppukassat yms
					$edkassa = "halutaan kassalippaan tiedot";
					// tällä päästään alempaan iffiin käsiksi
					$kateismaksu 		= "kateinen";
					$kateismaksuekotus 	= t("Käteinen");

					mysql_data_seek($result,0);
					$row = mysql_fetch_assoc($result);
					$edkassanimi = $row["kassanimi"];

					$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' AND nimi = '$row[kassanimi]'";
					$kassalipasresult = pupe_query($query);
					$kassalipasrow = mysql_fetch_assoc($kassalipasresult);
					$kateinen = $kassalipasrow["kassa"];
					$row["tilino"] = $kassalipasrow["kassa"];
				}

				// MUUT KASSA
				if ($row["tilino"] != '') {
					$tilinumero["kateinen"] = $row["tilino"];
				}
				elseif ($kateinen != '') {
					$tilinumero["kateinen"] = $kateinen;
				}
				else {
					$tilinumero["kateinen"] = $yhtiorow["kassa"];
					$kateinen				= $yhtiorow["kassa"];
				}

				if ($edkassa == "halutaan kassalippaan tiedot") {
					$tilinumero["kateinen"] = $kassalipasrow["kassa"];
				}

				$solu = "kateinen";

				$kassalippaat[$edkassanimi] = $edkassanimi;
				$kassalipas_tunnus[$edkassanimi] = $edktunnus;

				echo "</table>";
				echo "<table width='100%'>";

				echo "<tr><td colspan='";
				if ($tilityskpl > 1) {
					echo $tilityskpl+6;
				}
				else {
					echo "9";
				}
				echo "' class='tumma' width='300px' nowrap>$kateismaksuekotus ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";

				echo "<input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[kateinen]#$edkassanimi'>";

				$temp_indeksi = 1;
				echo "<td class='tumma' align='center' width='100px' nowrap><input type='text' id='$solu solu$i' name='solu$i' size='10' autocomplete='off'  value='{$tasmaytys_array[$edkassanimi]['solu'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
				if ($tilityskpl > 1) {
					$y = $i;
					$temp_indeksi = $temp_indeksi + 1;
					for ($yy = 1; $yy < $tilityskpl; $yy++) {
						$y .= $i;
						echo "<td class='tumma' align='center' width='100px' nowrap><input type='text' id='$solu solu$y' name='solu$y' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['solu'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						$temp_indeksi++;
					}
				}
				echo "<td align='right' class='tumma' style='width:100px' nowrap><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
				echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";

				echo "</tr>";

				echo "<tr><td class='tumma' colspan='";
				if ($tilityskpl > 1) {
					echo $tilityskpl+6;
				}
				else {
					echo "9";
				}
				echo "' width='300px' nowrap>$edkassanimi ".t("käteisotto kassasta").": </td><td class='tumma' align='center' nowrap>";
				$temp_indeksi = 1;
				echo "<input type='text' name='kateisotto$i' id='kateisotto$i' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['kateisotto'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
				if ($tilityskpl > 1) {
					$y = $i;
					$temp_indeksi = $temp_indeksi + 1;
					for ($yy = 1; $yy < $tilityskpl; $yy++) {
						$y .= $i;
						echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='kateisotto$y' name='kateisotto$y' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['kateisotto'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						$temp_indeksi++;
					}
				}
				echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td><td class='tumma' style='width:100px' nowrap>&nbsp;</td></tr>";

				echo "<tr><td colspan='";
				if ($tilityskpl > 1) {
					echo $tilityskpl+6;
				}
				else {
					echo "9";
				}
				echo "' align='left' class='tumma' width='300px' nowrap>$edkassanimi ".t("käteistilitys pankkiin kassasta").":</td>";
				$temp_indeksi = 1;
				echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='kateistilitys$i' name='kateistilitys$i' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['kateistilitys'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
				if ($tilityskpl > 1) {
					$y = $i;
					$temp_indeksi = $temp_indeksi + 1;
					for ($yy = 1; $yy < $tilityskpl; $yy++) {
						$y .= $i;
						echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='kateistilitys$y' name='kateistilitys$y' size='10' autocomplete='off' value='{$tasmaytys_array[$edkassanimi]['kateistilitys'.$temp_indeksi]}' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						$temp_indeksi++;
					}
				}
				echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td><td class='tumma' style='width:100px' nowrap>&nbsp;</td></tr>";

				echo "<tr><td colspan='";
				if ($tilityskpl > 1) {
					echo $tilityskpl+6;
				}
				else {
					echo "9";
				}
				echo "' align='left' class='tumma' width='300px' nowrap>$edkassanimi ".t("loppukassa").":</td>";
				echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='kassalippaan_loppukassa$i' name='kassalippaan_loppukassa$i' size='10' disabled></td>";
				echo "<input type='hidden' name='yht_lopkas$i' id='yht_lopkas$i' value=''>";
				if ($tilityskpl > 1) {
					$y = $i;
					for ($yy = 1; $yy < $tilityskpl; $yy++) {
						$y .= $i;
						echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td>";
					}
				}
				echo "<td class='tumma' style='width:100px' nowrap>&nbsp;</td><td class='tumma' style='width:100px' nowrap>&nbsp;</td></tr>";

				echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
				echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
				$i++;

				if (count($kassakone) > 1) {
					echo "<tr><td>&nbsp;</td></tr>";
				}

				mysql_data_seek($result,0);
				$kateismaksuyhteensa = 0;
				$i++;
				$solu = "pankkikortti";

				echo "</table>";

				if ($pankkikortti) {
					echo "<table id='nayta$i' style='display:none' width='100%'>";
					echo "<tr>
							<th>".t("Kassa")."</th>
							<th>".t("Asiakas")."</th>
							<th>".t("Ytunnus")."</th>
							<th>".t("Laskunumero")."</th>
							<th>".t("Pvm")."</th>
							<th>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_assoc($result)) {

						if ($row["tyyppi"] == 'Pankkikortti') {

							if ($row["tilino"] != '') {
								$tilinumero["pankkikortti"] = $row["tilino"];
							}
							elseif ($kateinen != '') {
								$tilinumero["pankkikortti"] = $kateinen;
							}
							elseif (!$pankkikortti) {
								$tilinumero["pankkikortti"] = $yhtiorow["pankkikortti"];
							}

							echo "<tr class='aktiivi'>";
							echo "<td>$row[kassanimi]</td>";
							echo "<td>".substr($row["nimi"],0,23)."</td>";
							echo "<td>$row[ytunnus]</td>";
							echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
							echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
							echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

							$kateinen    		= $row["tilino"];
							$edkassa 	 		= $row["kassa"];
							$edkassanimi 		= $row["kassanimi"];
							$edkateismaksu 		= $kateismaksu;
							$edktunnus 			= $row["ktunnus"];
							$kateismaksuyhteensa+= $row["tilsumma"];
							$yhteensa 			+= $row["tilsumma"];
							$kassayhteensa 		+= $row["tilsumma"];
							$kateismaksu 		= "";
						}
					}
					echo "</table>";
				}

				$kassalippaat[$edkassanimi] = $edkassanimi;
				$kassalipas_tunnus[$edkassanimi] = $edktunnus;

				echo "<table width='100%'>";
				echo "<input type='hidden' id='rivipointer$i' name='rivipointer$i' value=''>";
				echo "<tr><input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[pankkikortti]#";
				if (count($kassakone) > 1) {
					foreach ($kassalippaat as $key => $lipas) {
						if (reset($kassalippaat) == $lipas) {
							echo "$lipas";
						}
						else {
							echo " / $lipas";
						}
					}
				}
				else {
					echo "$edkassanimi";
				}
				echo "'>";
				echo "<td colspan='6' class='tumma' width='300px' nowrap>".t("Pankkikortti yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
				echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu solu$i' name='solu$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
				if ($tilityskpl > 1) {
					$y = $i;
					for ($yy = 1; $yy < $tilityskpl; $yy++) {
						$y .= $i;
						echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu solu$y' name='solu$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
					}
				}
				echo "<td align='right' class='tumma' style='width:100px' nowrap><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
				echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";
				echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
				echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
				echo "</tr>";

				mysql_data_seek($result,0);
				$kateismaksuyhteensa = 0;
				$i++;
				$solu = "luottokortti";

				echo "</table>";

				if ($luottokortti) {
					echo "<table id='nayta$i' style='display:none' width='100%'>";
					echo "<tr>
							<th>".t("Kassa")."</th>
							<th>".t("Asiakas")."</th>
							<th>".t("Ytunnus")."</th>
							<th>".t("Laskunumero")."</th>
							<th>".t("Pvm")."</th>
							<th>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_assoc($result)) {

						if ($row["tyyppi"] == 'Luottokortti') {

							if ($row["tilino"] != '') {
								$tilinumero["luottokortti"] = $row["tilino"];
							}
							elseif ($kateinen != '') {
								$tilinumero["luottokortti"] = $kateinen;
							}
							elseif (!$luottokortti) {
								$tilinumero["luottokortti"] = $yhtiorow["luottokortti"];
							}

							echo "<tr class='aktiivi'>";
							echo "<td>$row[kassanimi]</td>";
							echo "<td>".substr($row["nimi"],0,23)."</td>";
							echo "<td>$row[ytunnus]</td>";
							echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
							echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
							echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

							$kateinen    		  = $row["tilino"];
							$edkassa 	 		  = $row["kassa"];
							$edkassanimi 		  = $row["kassanimi"];
							$edkateismaksu 		  = $kateismaksu;
							$edktunnus 			  = $row["ktunnus"];
							$kateismaksuyhteensa += $row["tilsumma"];
							$yhteensa 			 += $row["tilsumma"];
							$kassayhteensa 		 += $row["tilsumma"];
							$kateismaksu 		  = "";
						}
					}
					echo "</table>";
				}

				$kassalippaat[$edkassanimi] = $edkassanimi;
				$kassalipas_tunnus[$edkassanimi] = $edktunnus;

				echo "<table width='100%'>";
				echo "<input type='hidden' id='rivipointer$i' name='rivipointer$i' value=''>";
				echo "<tr>";
				echo "<input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[luottokortti]#";
				if (count($kassakone) > 1) {
					foreach ($kassalippaat as $key => $lipas) {
						if (reset($kassalippaat) == $lipas) {
							echo "$lipas";
						}
						else {
							echo " / $lipas";
						}
					}
				}
				else {
					echo "$edkassanimi";
				}
				echo "'>";
				echo "<td colspan='6' class='tumma' width='300px' nowrap>".t("Luottokortti yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
				echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu solu$i' name='solu$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
				if ($tilityskpl > 1) {
					$y = $i;
					for ($yy = 1; $yy < $tilityskpl; $yy++) {
						$y .= $i;
						echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu solu$y' name='solu$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
					}
				}
				echo "<td align='right' class='tumma' style='width:100px' nowrap><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
				echo "<td class='tumma' align='center' style='width:100px' nowrap><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";
				echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
				echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
				echo "</tr>";
			}
			else {
				while ($row = mysql_fetch_assoc($result)) {

					if ((($edkassa != $row["kassa"] and $edkassa != '') or ($kateinen != $row["tilino"] and $kateinen != ''))) {
						echo "</table><table width='100%'>";
						echo "<tr><td colspan='7' class='tumma'>$edtyyppi ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></td>";
						echo "<td align='right' class='tumma' style='width:100px'><b><div id='erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td></tr>";
						$i++;

						if ($edkassa == $row["kassa"]) {
							echo "</table><table id='nayta$i' style='display:none;' width='100%'>";
							echo "<tr>
									<th nowrap>".t("Kassa")."</th>
									<th nowrap>".t("Asiakas")."</th>
									<th nowrap>".t("Ytunnus")."</th>
									<th nowrap>".t("Laskunumero")."</th>
									<th nowrap>".t("Pvm")."</th>
									<th nowrap>$yhtiorow[valkoodi]</th></tr>";
						}

						if ($vaiht == 1) {
							$prn = "\n";
							$prn .= sprintf ("%-'.84s", $kateismaksuekotus." ".t("yhteensä")." ");
							$prn .= sprintf ("%'.10s", " ".str_replace(".",",", sprintf('%.2f', $kateismaksuyhteensa)));
							$prn .= "\n\n";

							fwrite($fh, $prn);
							$rivit++;
						}
						$kateismaksuyhteensa = 0;
					}

					if ($edkassa != $row["kassa"] and $edkassa != '') {

						echo "<tr><th colspan='7'>$edkassanimi yhteensä: </th>";
						echo "<td align='right' class='tumma'><b>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</b></td></tr>";
						echo "<tr><td>&nbsp;</td></tr>";
						echo "</table><table id='nayta$i' style='display:none;' width='100%'>";
						echo "<tr>
								<th>".t("Kassa")."</th>
								<th>".t("Asiakas")."</th>
								<th>".t("Ytunnus")."</th>
								<th>".t("Laskunumero")."</th>
								<th>".t("Pvm")."</th>
								<th>$yhtiorow[valkoodi]</th></tr>";

						if ($vaiht == 1) {
							$prn = sprintf ("%-'.84s", $edkassanimi." ".t("yhteensä")." ");
							$prn .= sprintf ("%'.10s", " ".str_replace(".", ",", sprintf('%.2f', $kassayhteensa)));
							$prn .= "\n\n\n";

							fwrite($fh, $prn);
							$rivit++;
						}

						$kassayhteensa = 0;
						$kateismaksuyhteensa = 0;
					}

					echo "<tr class='aktiivi'>";
					echo "<td>$row[kassanimi]</td>";
					echo "<td>".substr($row["nimi"],0,23)."</td>";
					echo "<td>$row[ytunnus]</td>";
					echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
					echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
					echo "<td align='right'>".str_replace(".", ",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

					$kateinen    		= $row["tilino"];
					$edkassa 	 		= $row["kassa"];
					$edkassanimi 		= $row["kassanimi"];
					$edkateismaksu 		= $kateismaksu;
					$edtyyppi 			= $row["tyyppi"];
					$kateismaksu 		= $row['tyyppi'];
					$kateismaksuekotus 	= t(str_replace("kateinen", "Käteinen", $kateismaksu));

					if ($vaiht == 1) {
						if ($rivit >= 60) {
							fwrite($fh, $ots);
							$rivit = 1;
						}
						$prn  = sprintf ('%-20.20s', 	$row["kassanimi"]);
						$prn .= sprintf ('%-25.25s', 	substr($row["nimi"],0,23));
						$prn .= sprintf ('%-10.10s', 	$row["ytunnus"]);
						$prn .= sprintf ('%-12.12s', 	$row["laskunro"]);
						$prn .= sprintf ('%-19.19s', 	tv1dateconv($row["laskutettu"], "pitka"));
						$prn .= str_replace(".",","		,sprintf ('%8s', $row["tilsumma"]));
						$prn .= "\n";

						fwrite($fh, $prn);
						$rivit++;
					}

					$kateismaksuyhteensa += $row["tilsumma"];
					$yhteensa += $row["tilsumma"];
					$kassayhteensa += $row["tilsumma"];
				}

				if ($edkassa != '') {
					echo "</table><table width='100%'>";
					echo "<tr><td colspan='6' class='tumma'>$edtyyppi ".t("yhteensä").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("Näytä / Piilota")."</a></th>";
					echo "<td align='right' class='tumma' style='width:100px'><b><div id='erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td></tr>";

					echo "<tr><th colspan='6'>$edkassanimi yhteensä:</th>";
					echo "<td align='right' class='tumma'><b>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</b></td></tr>";

					if ($vaiht == 1) {
						$prn = "\n";
						$prn .= sprintf ("%-'.84s", $kateismaksuekotus." ".t("yhteensä")." ");
						$prn .= sprintf ("%'.10s", " ".str_replace(".",",", sprintf('%.2f', $kateismaksuyhteensa)));
						$prn .= "\n\n";

						fwrite($fh, $prn);
						$rivit++;
						$prn = sprintf ("%-'.84s", $edkassanimi." ".t("yhteensä")." ");
						$prn .= sprintf ("%'.10s", " ".str_replace(".",",", sprintf('%.2f', $kassayhteensa)));
						$prn .= "\n\n";
						fwrite($fh, $prn);
					}

					$kassayhteensa = 0;
				}
			}

			if ($katsuori != '') {
				//Haetaan kassatilille laitetut suoritukset
				$query = "	SELECT suoritus.nimi_maksaja nimi, tiliointi.summa, lasku.tapvm
							FROM lasku use index (yhtio_tila_tapvm)
							JOIN tiliointi use index (tositerivit_index) ON (tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and tiliointi.tilino='$yhtiorow[kassa]')
							JOIN suoritus use index (tositerivit_index) ON (suoritus.yhtio=tiliointi.yhtio and suoritus.ltunnus=tiliointi.aputunnus)
							LEFT JOIN kuka ON (lasku.laatija=kuka.kuka and lasku.yhtio=kuka.yhtio)
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila	= 'X'
							and lasku.tapvm >= '$vva-$kka-$ppa'
							and lasku.tapvm <= '$vvl-$kkl-$ppl'
							ORDER BY lasku.laskunro";
				$result = pupe_query($query);

				$kassayhteensa = 0;

				if (mysql_num_rows($result) > 0) {
					echo "<br><table id='nayta$i' style='display:none'>";
					echo "<tr>
							<th nowrap>".t("Kassa")."</th>
							<th nowrap>".t("Asiakas")."</th>
							<th nowrap>".t("Ytunnus")."</th>
							<th nowrap>".t("Laskunumero")."</th>
							<th nowrap>".t("Pvm")."</th>
							<th nowrap>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_assoc($result)) {

						echo "<tr>";
						echo "<td>".t("Käteissuoritus")."</td>";
						echo "<td>".substr($row["nimi"],0,23)."</td>";
						echo "<td>$row[ytunnus]</td>";
						echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
						echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
						echo "<td align='right'>".str_replace(".",",",$row['summa'])."</td></tr>";

						if ($vaiht == 1) {
							if ($rivit >= 60) {
								fwrite($fh, $ots);
								$rivit = 1;
							}
							$prn  = sprintf ('%-20.20s', 	t("Käteissuoritus"));
							$prn .= sprintf ('%-25.25s', 	substr($row["nimi"],0,23));
							$prn .= sprintf ('%-10.10s', 	$row["ytunnus"]);
							$prn .= sprintf ('%-12.12s', 	$row["laskunro"]);
							$prn .= sprintf ('%-19.19s', 	tv1dateconv($row["laskutettu"], "pitka"));
							$prn .= str_replace(".",",",sprintf ('%-13.13s', 	$row["summa"]));
							$prn .= "\n";

							fwrite($fh, $prn);
							$rivit++;
						}
						$yhteensa += $row["summa"];
						$kassayhteensa += $row["summa"];
					}
				}
			}

			if (isset($tasmays) and $tasmays != '') {
				echo "<tr><td colspan='8'>&nbsp;</td></tr>";
			}

			echo "</table>";
			echo "<table width='100%'>";
			echo "<input type='hidden' id='myynti_yhteensa_hidden' name='myynti_yhteensa' value='$yhteensa'>";
			echo "<tr><td align='left' colspan='3'><font class='head'>";

			if (isset($tasmays) and $tasmays != '') {
				echo t("Myynti yhteensä");
			}
			else {
				echo t("Kaikki kassat yhteensä");
			}

			echo ":</font></td><td align='right'><input type='text' size='10'";

			echo "id='myynti_yhteensa' value='".sprintf('%.2f',$yhteensa);

			echo "' disabled></td></tr>";

			if (isset($tasmays) and $tasmays != '') {
				echo "<tr><td align='left' colspan='3'><font class='head'>".t("Kassalippaassa käteistä").":</td><td align='right'>";
				echo "<input type='text' id='kaikkiyhteensa' size='10' value='' disabled></td></tr>";
				echo "<tr><td align='left' colspan='3'><font class='head'>".t("Loppukassa yhteensä").":</td><td align='right'>";
				echo "<input type='text' name='loppukassa' id='loppukassa' size='10' disabled></td></tr>";
				echo "<tr><td>&nbsp;</td></tr>";
				echo "<tr><td align='left' colspan='3'><font class='head'>".t("Yhteenveto").":</td></tr>";
				echo "<tr><th colspan='3'>".t("Alkukassa").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_alkukassa' id='yht_alkukassa' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("Käteinen").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_kateinen' id='yht_kateinen' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("Käteisotto").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_kateisotto' id='yht_kateisotto' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("Käteistilitys").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_kateistilitys' id='yht_kateistilitys' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("Kassaerotus").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_kassaerotus' id='yht_kassaerotus' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("Loppukassa").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_loppukassa' id='yht_loppukassa' size='10' disabled></td></tr>";

				echo "<tr><td align='right' colspan='4'><input type='submit' value='".t("Hyväksy")."'></td></tr>";

				echo "<input type='hidden' name='loppukassa2' id='loppukassa2' value=''>";
				echo "<input type='hidden' name='yht_alkukas' id='yht_alkukas' value=''>";
				echo "<input type='hidden' name='yht_kat' id='yht_kat' value=''>";
				echo "<input type='hidden' name='yht_katot' id='yht_katot' value=''>";
				echo "<input type='hidden' name='yht_kattil' id='yht_kattil' value=''>";
				echo "<input type='hidden' name='yht_kasero' id='yht_kasero' value=''>";
				echo "<input type='hidden' name='kassalipas_tunnus' value='".urlencode(serialize($kassalipas_tunnus))."'>";
				echo "<input type='hidden' name='kassakone' value='".urlencode(serialize($kassakone))."'>";
				echo "<input type='hidden' name='pp' id='pp' value='$pp'>";
				echo "<input type='hidden' name='kk' id='kk' value='$kk'>";
				echo "<input type='hidden' name='vv' id='vv' value='$vv'>";
				echo "<input type='hidden' name='printteri' id='printteri' value='$printteri'>";
				echo "<input type='hidden' name='tilityskpl' id='tilityskpl' value='$tilityskpl'>";
				echo "</form>";
			}
			echo "</table>";
		}

		if ((!isset($tasmays) or $tasmays == '') and $vaiht == 1) {
			$prn = "\n";
			$prn .= sprintf ("%-'.84s", t("Yhteensä")." ");
			$prn .= sprintf ("%'.10s", " ".str_replace(".",",", sprintf('%.2f', $yhteensa)));
			$prn .= "\n";
			fwrite($fh, $prn);

			echo "<pre>",file_get_contents($filenimi),"</pre>";
			fclose($fh);

			//haetaan tilausken tulostuskomento
			$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$printteri'";
			$kirres  = pupe_query($query);
			$kirrow  = mysql_fetch_assoc($kirres);
			$komento = $kirrow['komento'];

			$line = exec("a2ps -o $filenimi.ps -R --medium=A4 --chars-per-line=94 --no-header --columns=1 --margin=0 --borders=0 $filenimi");

			if ($komento == "email" and $kukarow["eposti"] != '') {
				// lähetetään meili
				$komento = "";

				$kutsu = t("Käteismyynnit", $kieli);

				$liite 				= "$filenimi.ps";
				$sahkoposti_cc 		= "";
				$content_subject 	= "";
				$content_body 		= "";

				include("inc/sahkoposti.inc");
			}
			elseif ($komento != "" and $komento != "email") {
				// itse print komento...
				$line = exec("$komento $filenimi.ps");
			}

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $filenimi");
			system("rm -f $filenimi.ps");
		}

		echo "</table>";
	}

	// Käyttöliittymä
	echo "<br>";
	echo "<form method='post'>";
	echo "<table>";

	if (!isset($kka)) $kka = date("m");
	if (!isset($vva)) $vva = date("Y");
	if (!isset($ppa)) $ppa = date("d");

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	echo "<tr>";
	echo "<th>".t("Syötä myyjänumero")."</th>";
	echo "<td colspan='3'><input type='text' size='10' name='myyjanro' value='$myyjanro'>";

	$query = "	SELECT tunnus, kuka, nimi, myyja
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY nimi";
	$yresult = pupe_query($query);

	echo "<tr>";
	echo "<th>".t("TAI valitse käyttäjä")."</th>";
	echo "<td colspan='3'><select name='myyja'>";
	echo "<option value='' >".t("Kaikki")."</option>";

	while ($row = mysql_fetch_assoc($yresult)) {
		if ($row['kuka'] == $myyja) {
			$sel = 'selected';
		}
		else {
			$sel = "";
		}
		echo "<option value='$row[kuka]' $sel>($row[kuka]) $row[nimi]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	echo "<tr><td class='back'><br></td></tr>";

	if (!isset($tasmays) or !$tasmays) {
		$dis = "disabled";
		$dis2 = "";
	}
	else {
		$dis = "";
		$dis2 = "disabled";
	}

	if ($oikeurow['paivitys'] == 1) {

		if (isset($tasmays) and $tasmays != '') {
			$sel = 'CHECKED';
		}
		if ($tilityskpl == '') {
			$tilityskpl = 3;
		}

		echo "<tr>";
		echo "<th>".t("Täsmää käteismyynnit")."</th>";
		echo "<td colspan='3'><input type='checkbox' id='tasmays' name='tasmays' $sel onClick='disableDates();'><br></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Tilitettävien sarakkeiden määrä")."</th>";
		echo "<td colspan='3'><input type='text' id='tilityskpl' name='tilityskpl' size='3' maxlength='2' value='$tilityskpl' autocomplete='off'><br></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>";
		echo "<td><input type='text' name='pp' id='pp' value='$pp' size='3' $dis autocomplete='off'></td>";
		echo "<td><input type='text' name='kk' id='kk' value='$kk' size='3' $dis autocomplete='off'></td>";
		echo "<td><input type='text' name='vv' id='vv' value='$vv' size='5' $dis autocomplete='off'></td>";
		echo "</tr>";

		echo "<tr><td class='back'><br></td></tr>";
	}

	$query  = "	SELECT *
				FROM kassalipas
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tunnus";
	$vares = pupe_query($query);

	while ($varow = mysql_fetch_assoc($vares)) {
		$sel = '';
		if ($kassakone[$varow["tunnus"]] != '') $sel = 'CHECKED';
		echo "<tr>";
		echo "<th>".t("Näytä")."</th>";
		echo "<td colspan='3'><input type='checkbox' name='kassakone[$varow[tunnus]]' value='$varow[tunnus]' $sel> $varow[nimi]</td>";
		echo "</tr>";
	}

	$sel = '';
	if ($muutkassat != '') $sel = 'CHECKED';

	echo "<tr>";
	echo "<th>".t("Näytä")."</th>";
	echo "<td colspan='3'><input type='checkbox' name='muutkassat' value='MUUT' $sel>".t("Muut kassat")."</td>";
	echo "</tr>";

	$sel = '';
	if ($katsuori != '') $sel = 'CHECKED';

	echo "<tr>";
	echo "<th>".t("Näytä")."</th>";
	echo "<td colspan='3'><input type='checkbox' name='katsuori' value='MUUT' $sel>".t("Käteissuoritukset")."</td>";
	echo "</tr>";

	echo "<tr><td class='back'><br></td></tr>";

	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr>";
	echo "<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>";
	echo "<td><input type='text' name='ppa' id='ppa' value='$ppa' size='3' $dis2></td>";
	echo "<td><input type='text' name='kka' id='kka' value='$kka' size='3' $dis2></td>";
	echo "<td><input type='text' name='vva' id='vva' value='$vva' size='5' $dis2></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>";
	echo "<td><input type='text' name='ppl' id='ppl' value='$ppl' size='3' $dis2></td>";
	echo "<td><input type='text' name='kkl' id='kkl' value='$kkl' size='3' $dis2></td>";
	echo "<td><input type='text' name='vvl' id='vvl' value='$vvl' size='5' $dis2></td>";
	echo "</tr>";

	$chk1 = '';
	$chk2 = '';

	if ($koti == 'KOTI') {
		$chk1 = "CHECKED";
	}

	if ($ulko == 'ULKO') {
		$chk2 = "CHECKED";
	}

	if ($chk1 == '' and $chk2 == '') {
		$chk1 = 'CHECKED';
	}

	echo "<tr>";
	echo "<th>".t("Kotimaan myynti")."</th>";
	echo "<td colspan='3'><input type='checkbox' name='koti' value='KOTI' $chk1></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Vienti")."</th>";
	echo "<td colspan='3'><input type='checkbox' name='ulko' value='ULKO' $chk2></td>";
	echo "</tr>";

	$query = "SELECT * FROM kirjoittimet WHERE yhtio = '$kukarow[yhtio]'";
	$kires = pupe_query($query);

	echo "<tr>";
	echo "<th>".t("Valitse tulostuspaikka").":</th>";
	echo "<td colspan='3'><select name='printteri'>";
	echo "<option value=''>".t("Ei kirjoitinta")."</option>";

	while ($kirow = mysql_fetch_assoc($kires)) {
		if ($kirow["tunnus"] == $printteri) {
			$select = "SELECTED";
		}
		else {
			$select = "";
		}
		echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</table>";

	echo "</form>";

	require ("inc/footer.inc");

function get_pohjakassa($row) {
	global $kukarow;

	if (isset($row["pohjakassa"])) {
		return ($row);
	}

	$like = "%{\"loppukassa\":{%\"" . $row["kassanimi"] . "\"%}%}%";

	$pk_query = "SELECT tunnus, tapvm, sisviesti2
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					 	AND tila = 'X'
                        AND tapvm >= date_sub(current_date, interval 7 day)
						AND sisviesti2 LIKE '$like'
					ORDER BY tapvm DESC
					LIMIT 1;";
	$pk_result = pupe_query($pk_query);

	if (mysql_num_rows($pk_result) == 1) {
		$pk_row = mysql_fetch_assoc($pk_result);
		$pk_t = explode("##", $pk_row["sisviesti2"]);
		$pk = json_decode($pk_t[0], TRUE);
		$row["pohjakassa"] = $pk["loppukassa"][$row["kassanimi"]];
	}
	return $row;
}

function populoi_kassalipas_muuttujat_kassakohtaisesti($_post) {
	//Nämä kommentit pyrkii selkeyttämään code reviewsille mitä tässä funktiossa on yritettty hakea. saa poistaa

	/*
	 * $_post
	 *
	 * array(61) {
  ["rivipointer1"]=>
  string(0) ""
  ["tyyppi_pohjakassa1"]=>
  string(11) "Kassalipas2"
  ["pohjakassa1"]=>
  string(1) "1"
  ["maksutapa1"]=>
  string(25) "kateinen#1905#Kassalipas2"
  ["solu1"]=>
  string(1) "2"
  ["solu11"]=>
  string(1) "3"
  ["solu111"]=>
  string(1) "4"
  ["erotus1"]=>
  string(2) "38"
  ["soluerotus1"]=>
  string(6) "-29.00"
  ["kateisotto1"]=>
  string(1) "5"
  ["kateisotto11"]=>
  string(1) "6"
  ["kateisotto111"]=>
  string(1) "7"
  ["kateistilitys1"]=>
  string(1) "8"
  ["kateistilitys11"]=>
  string(1) "9"
  ["kateistilitys111"]=>
  string(2) "10"
  ["yht_lopkas1"]=>
  string(3) "-35"
  ["rivipointer2"]=>
  string(0) ""
  ["tyyppi_pohjakassa2"]=>
  string(11) "Kassalipas7"
  ["pohjakassa2"]=>
  string(2) "11"
  ["maksutapa2"]=>
  string(25) "kateinen#1905#Kassalipas7"
  ["solu2"]=>
  string(2) "12"
  ["solu22"]=>
  string(2) "13"
  ["solu222"]=>
  string(2) "14"
  ["kateisotto2"]=>
  string(2) "15"
  ["kateisotto22"]=>
  string(2) "16"
  ["kateisotto222"]=>
  string(2) "17"
  ["kateistilitys2"]=>
  string(2) "18"
  ["kateistilitys22"]=>
  string(2) "19"
  ["kateistilitys222"]=>
  string(2) "20"
  ["yht_lopkas2"]=>
  string(3) "-55"
  ["erotus2"]=>
  string(2) "84"
  ["soluerotus2"]=>
  string(6) "-45.00"
  ["loppukassa2"]=>
  string(6) "-90.00"
}
	 *
	 */
	
	$kassalippaat = array();
	$kassalippaan_indeksi = null;
	$monisoluisen_indeksi_array = null;
	foreach ($_post as $kentan_nimi => $kentan_arvo) {
		if(stristr($kentan_nimi, 'tyyppi_pohjakassa')) {
			preg_match_all('!\d+!', $kentan_nimi, $kassalippaan_indeksi);
			$kassalippaan_nimi = $_post['tyyppi_pohjakassa' . $kassalippaan_indeksi[0][0]];
			foreach($_post as $etsi_kassalipas_nimi => $etsi_kassalipas_arvo) {
				//etsitään kassalippaalle kuuluvat tilitys arvot
				if(strstr($etsi_kassalipas_nimi , $kassalippaan_indeksi[0][0])) {
					if(!stristr($etsi_kassalipas_nimi, 'solu') and !stristr($etsi_kassalipas_nimi, 'kateisotto') and !stristr($etsi_kassalipas_nimi, 'kateistilitys')) {
						//yksisoluiset halutaan tallentaa ilman perästä löytyvää indeksiä
						$kassalippaat[$kassalippaan_nimi][preg_replace("/[0-9]/", "", $etsi_kassalipas_nimi)] = $etsi_kassalipas_arvo;
					}
					else {
						//monisoluisiin halutaan 1, 11 ,111 indeksin sijaan 1, 2, 3 jne.
						preg_match_all('!\d+!', $etsi_kassalipas_nimi, $monisoluisen_indeksi_array);
						$monisoluisen_indeksi = strlen($monisoluisen_indeksi_array[0][0]);

						$solun_nimi = preg_replace("/[0-9]/", "", $etsi_kassalipas_nimi) . $monisoluisen_indeksi;
						$kassalippaat[$kassalippaan_nimi][$solun_nimi] = $etsi_kassalipas_arvo;
					}
				}
			}
		}
	}

	
	
	return $kassalippaat;

	/*
	 * $kassalippaat
	 *
	 * 
	 * array(2) {
  ["Kassalipas2"]=>
  array(16) {
    ["rivipointer"]=>
    string(0) ""
    ["tyyppi_pohjakassa"]=>
    string(11) "Kassalipas2"
    ["pohjakassa"]=>
    string(1) "1"
    ["maksutapa"]=>
    string(25) "kateinen#1905#Kassalipas2"
    ["solu1"]=>
    string(1) "2"
    ["solu2"]=>
    string(1) "3"
    ["solu3"]=>
    string(1) "4"
    ["erotus"]=>
    string(2) "38"
    ["soluerotus"]=>
    string(6) "-29.00"
    ["kateisotto1"]=>
    string(1) "5"
    ["kateisotto2"]=>
    string(1) "6"
    ["kateisotto3"]=>
    string(1) "7"
    ["kateistilitys1"]=>
    string(1) "8"
    ["kateistilitys2"]=>
    string(1) "9"
    ["kateistilitys3"]=>
    string(2) "10"
    ["yht_lopkas"]=>
    string(3) "-35"
  }
  ["Kassalipas7"]=>
  array(17) {
    ["rivipointer"]=>
    string(0) ""
    ["tyyppi_pohjakassa"]=>
    string(11) "Kassalipas7"
    ["pohjakassa"]=>
    string(2) "11"
    ["maksutapa"]=>
    string(25) "kateinen#1905#Kassalipas7"
    ["solu1"]=>
    string(2) "12"
    ["solu2"]=>
    string(2) "13"
    ["solu3"]=>
    string(2) "14"
    ["kateisotto1"]=>
    string(2) "15"
    ["kateisotto2"]=>
    string(2) "16"
    ["kateisotto3"]=>
    string(2) "17"
    ["kateistilitys1"]=>
    string(2) "18"
    ["kateistilitys2"]=>
    string(2) "19"
    ["kateistilitys3"]=>
    string(2) "20"
    ["yht_lopkas"]=>
    string(3) "-55"
    ["erotus"]=>
    string(2) "84"
    ["soluerotus"]=>
    string(6) "-45.00"
    ["loppukassa"]=>
    string(6) "-90.00"
  }
}

	 */
}
?>
