<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require "inc/parametrit.inc";

	if (!isset($tee))		$tee = "";
	if (!isset($tyyppi))	$tyyppi = "";
	if (!isset($tiliote))	$tiliote = "";
	if (!isset($tilino))	$tilino = "";

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	enable_ajax();

	if ($tee == 'T' and (int) $kuitattava_tiliotedata_tunnus > 0) {

		$query = "	SELECT kuitattu
					FROM tiliotedata
					WHERE yhtio = '$kukarow[yhtio]'
					AND perheid = '$kuitattava_tiliotedata_tunnus'";
		$kuitetaan_result = pupe_query($query);
		$kuitetaan_row = mysql_fetch_assoc($kuitetaan_result);

		$kuitataan_lisa = $kuitetaan_row['kuitattu'] == '' ? " kuitattu = '$kukarow[kuka]', kuitattuaika = now() " : " kuitattu = '', kuitattuaika = '0000-00-00 00:00:00' ";

		$query = "	UPDATE tiliotedata SET
					$kuitataan_lisa
					WHERE yhtio = '$kukarow[yhtio]'
					AND perheid = '$kuitattava_tiliotedata_tunnus'";
		$kuitetaan_result = pupe_query($query);

		die("TRUE");
	}

	echo "<font class='head'>".t("Pankkiaineistojen selailu")."</font><hr>";

	//Olemme tulossa takasin suorituksista
	if ($tee == 'Z' or $tiliote == 'Z') {
		$query = "	SELECT tilino
					FROM yriti
					WHERE tunnus = $mtili
					and yhtio = '$kukarow[yhtio]'
					and kaytossa = ''";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Tili katosi")."</font><br>";

			require ("inc/footer.inc");
			exit;
		}
		else {
			$yritirow = mysql_fetch_array ($result);
			$tee = 'T';
			$tilino = $yritirow['tilino'];
			$tyyppi = 1;
		}
	}

	if ($tee == 'X' or $tee == 'XX' or $tee == "XS" or $tee == "XXS") {

		if ($tee == 'X') {
			// Pyyntö seuraavasta tiliotteesta
			$query = "	SELECT *
						FROM tiliotedata use index (yhtio_tilino_alku)
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND alku    > '$pvm'
						AND tilino  = '$tilino'
						AND tyyppi  = '1'
						ORDER BY tunnus
						LIMIT 1";
			$tyyppi = 1;
		}
		elseif ($tee == 'XX') {
			// Pyyntö edellisestä tiliotteesta
			$query = "	SELECT *
						FROM tiliotedata use index (yhtio_tilino_alku)
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND alku    < '$pvm'
						AND tilino  = '$tilino'
						AND tyyppi  = '1'
						ORDER BY tunnus desc
						LIMIT 1";
			$tyyppi = 1;
		}
		elseif ($tee == 'XS') {
			// Pyyntö seuraavasta viiteaineistosta
			$query = "	SELECT *
						FROM tiliotedata use index (yhtio_tilino_alku)
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND alku    > '$pvm'
						AND tilino  = '$tilino'
						AND tyyppi  = '3'
						ORDER BY tunnus
						LIMIT 1";
			$tyyppi = 3;
		}
		elseif ($tee == 'XXS') {
			// Pyyntö seuraavasta viiteaineistosta
			$query = "	SELECT *
						FROM tiliotedata use index (yhtio_tilino_alku)
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND alku    < '$pvm'
						AND tilino  = '$tilino'
						AND tyyppi  = '3'
						ORDER BY tunnus desc
						LIMIT 1";
			$tyyppi = 3;
		}

		$tiliotedataresult = pupe_query($query);

		if (mysql_num_rows($tiliotedataresult) == 0) {
			$tee = '';
		}
		else {
			$tee = 'T';
			$tiliotedatarow = mysql_fetch_array ($tiliotedataresult);
			$pvm = $tiliotedatarow['alku'];
		}

	}

	if ($tee == 'S') {
		// Tarkistetaan oliko pvm ok
		$val = checkdate($kk, $pp, $vv);
		if (!$val) {
			echo "<b>".t("Virheellinen pvm")."</b><br>";
		}
		else {
			$pvm = $vv . "-" . $kk . "-" . $pp;
		}
		$tee = '';
	}

	if ($tee == 'T') {
		$tee = 'S'; // Pvm on jo kunnossa
	}

	if ($tee == 'S') {

		echo "<script language='javascript'>
			function vaihdacss(solut) {
				$('td[name=td_'+solut+']').each(
					function() {
						if ($(this).hasClass('spec')) {
							$(this).attr('class', '');
						}
						else {
							$(this).attr('class', 'spec');
						}
					}
				);

				if ($('#kuitattu_'+solut).hasClass('spec')) {
					$('#kuitattu_'+solut).html('<font class=\"ok\">".t("Kuitattu").": {$kukarow['nimi']} @ ".tv1dateconv(date("Y-m-d H:i:s"),"pitkä")."</font>');
				}
				else {
					$('#kuitattu_'+solut).html('');
				}
			}

			function suljedivi(tunnus) {
				$('#ifd_'+tunnus).hide();
			}

			function lataaiframe(tunnus, url) {

				var ifd = $('#ifd_'+tunnus);
				var ifr = $('#ifr_'+tunnus);

				if (ifr.length) {

					if (ifr.attr('src') == url) {
						ifd.toggle();
					}
					else {
						ifd.show();
						ifr.attr('src', url);
					}
				}
				else {
					ifd.show();
					ifd.html(\"<div style='float:right;'><a href=\\\"javascript:suljedivi('\"+tunnus+\"');\\\">".t("Piilota")." <img src='{$palvelin2}pics/lullacons/stop.png'></a></div><iframe id='ifr_\"+tunnus+\"' src='\"+url+\"' style='width:100%; height: 800px; border: 1px; display: block;'></iFrame>\");
				}
			}

		</script>";


		if ($tyyppi == '3') {
			$query = "	SELECT tiliotedata.*,
						ifnull(kuka.nimi, tiliotedata.kuitattu) kukanimi
						FROM tiliotedata
						LEFT JOIN kuka ON (kuka.yhtio = tiliotedata.yhtio AND kuka.kuka = tiliotedata.kuitattu)
						WHERE tiliotedata.yhtio = '{$kukarow['yhtio']}'
						and tiliotedata.alku    = '$pvm'
						and tiliotedata.tilino  = '$tilino'
						and tiliotedata.tyyppi  = '$tyyppi'
						ORDER BY tunnus";
		}
		else {

			$tjarjlista = "";

			if ($tiliotejarjestys != "") {
				$tjarjlista = "sorttauskentta,";
			}

			$query = "	SELECT tiliotedata.*,
						ifnull(kuka.nimi, tiliotedata.kuitattu) kukanimi,
						if(left(tieto, 3) in ('T40','T50','T60','T70') or kuitattu != '', 2, 1) sorttauskentta
						FROM tiliotedata
						LEFT JOIN kuka ON (kuka.yhtio = tiliotedata.yhtio AND kuka.kuka = tiliotedata.kuitattu)
						WHERE tiliotedata.yhtio = '{$kukarow['yhtio']}'
						and tiliotedata.alku    = '$pvm'
						and tiliotedata.tilino  = '$tilino'
						and tiliotedata.tyyppi  = '$tyyppi'
						ORDER BY $tjarjlista perheid, tunnus";
		}
		$tiliotedataresult = pupe_query($query);

		// Lopetusmuuttujaa varten, muuten ylikirjoittuu
		$lopp_pvm    = $pvm;
		$lopp_tilino = $tilino;
		$lopp_tyyppi = $tyyppi;

		$txttieto = "";
		$txtfile  = "$tilino-$pvm.txt";

		$tilioterivilaskuri = 1;
		$tilioterivimaara	= mysql_num_rows($tiliotedataresult);

		if ($tilioterivimaara == 0) {
			echo "<font class='message'>".t("Tuollaista aineistoa ei löytynyt")."! $query</font><br>";
			$tee = '';
		}
		else {
			while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {
				$tietue = $tiliotedatarow['tieto'];

				if ($tiliotedatarow['tyyppi'] == 1) {
					require "inc/tiliote.inc";
				}
				if ($tiliotedatarow['tyyppi'] == 2) {
					require "inc/LMP.inc";
				}
				if ($tiliotedatarow['tyyppi'] == 3) {
					require "inc/naytaviitteet.inc";
				}

				$txttieto .= $tiliotedatarow["tieto"];
				$tilioterivilaskuri++;
			}

			echo "</table>";

			$filename = md5(uniqid()).".txt";
			file_put_contents("/tmp/".$filename, $txttieto);

			echo "<br>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='$txtfile'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$filename'>";
			echo "<input type='submit' value='".t("Tallenna tiedosto")."'></form>";
		}
	}

	if ($tee == '') {

		$query = "	SELECT *
					FROM yriti
					WHERE yhtio  = '$kukarow[yhtio]'
					and kaytossa = '' ";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sinulla ei ole yhtään pankkitiliä")."</font><hr>";
			require ("inc/footer.inc");
			exit;
		}

		$querylisa = "";
		if (!isset($kk)) $kk = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($vv)) $vv = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($pp)) $pp = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

		if ($tilino != "") $querylisa .= " and tiliotedata.tilino = '$tilino' ";
		if ($tyyppi != "") $querylisa .= " and tyyppi = '$tyyppi' ";

		echo "<form name = 'valikko' method='post'><table>
			  <tr>
			  <th>".t("Tapahtumapvm")."</th>
			  <td>
			  	<input type='hidden' name='tee' value='S'>
				<input type='text' name='pp' maxlength='2' size=2 value='$pp'>
				<input type='text' name='kk' maxlength='2' size=2 value='$kk'>
				<input type='text' name='vv' maxlength='4' size=4 value='$vv'></td>
			  </tr>
			  <tr>
			  <th>".t("Pankkitili")."</th>
			  <td><select name='tilino'>";

		echo "<option value=''>".t("Näytä kaikki")."</option>";

		while ($yritirow = mysql_fetch_array ($result)) {
			$chk = "";
			if ($yritirow["tilino"] == $tilino) $chk = "selected";
			echo "<option value='$yritirow[tilino]' $chk>$yritirow[nimi] ($yritirow[tilino])";
		}

		$chk = array_fill_keys(array($tyyppi), " selected") + array_fill_keys(array('1', '2', '3'), '');

		echo "</select></td></tr>
				<tr>
				<th>".t("Laji")."</th>
				<td><select name='tyyppi'>
					<option value=''>".t("Näytä kaikki")."
					<option value='1' $chk[1]>".t("Tiliote")."
					<option value='2' $chk[2]>".t("Lmp")."
					<option value='3' $chk[3]>".t("Viitesiirrot")."
				</select>
				</td>
				<td class='back'><input type='submit' value='".t("Hae")."'></td>
				</tr>
				</table><br>
				</form>";

		$query = "	SELECT alku, loppu, concat_ws(' ', yriti.nimi, yriti.tilino) tili, if(tyyppi='1', 'tiliote', if(tyyppi='2','lmp','viitesiirrot')) laji, tyyppi, yriti.tilino
					FROM tiliotedata
					JOIN yriti ON (yriti.yhtio = tiliotedata.yhtio and yriti.tilino = tiliotedata.tilino)
	                WHERE tiliotedata.yhtio = '$kukarow[yhtio]'
					and tiliotedata.alku   >= '$vv-$kk-$pp'
					$querylisa
					GROUP BY alku, tili, laji
					ORDER BY alku DESC, tiliotedata.tilino, laji";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sopivia pankkiainestoja ei löytynyt")."</font><hr>";
			require ("inc/footer.inc");
			exit;
		}

		echo "<table>";
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) ."</th>";
		}
		echo "</tr>";

		while ($row = mysql_fetch_array ($result)) {

			echo "<tr class='aktiivi'>";

			for ($i=0; $i<mysql_num_fields($result)-2 ; $i++) {
				if ($i < 2) {
					echo "<td>".tv1dateconv($row[$i])."</td>";
				}
				else {
					echo "<td>$row[$i]</td>";
				}
			}

			$edalku = $row["alku"];

			echo "	<form name = 'valikko' method='post'>
					<input type='hidden' name='tee' value='T'>
					<input type='hidden' name='lopetus' value='${palvelin2}tilioteselailu.php////tee=//pp=$pp//kk=$kk//vv=$vv//tilino=$tilino//tyyppi=$tyyppi'>
					<input type='hidden' name='pvm' value='$row[alku]'>
					<input type='hidden' name='tyyppi' value='$row[tyyppi]'>
					<input type='hidden' name='tilino' value='$row[tilino]'>
					<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
			  		</form>
			  		</tr>";
		}
		echo "</table></form>";

		$tee = "";
		$formi = 'valikko';
		$kentta = 'pp';
	}

	require ("inc/footer.inc");

?>