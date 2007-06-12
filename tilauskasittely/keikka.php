<?php

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
	require ("../inc/parametrit.inc");
}

echo "<font class='head'>".t("Saapuva ostotilaus")."</font><hr>";

// scripti balloonien tekemiseen
echo "
	<script>
	var DH = 0;var an = 0;var al = 0;var ai = 0;if (document.getElementById) {ai = 1; DH = 1;}else {if (document.all) {al = 1; DH = 1;} else { browserVersion = parseInt(navigator.appVersion); if ((navigator.appName.indexOf('Netscape') != -1) && (browserVersion == 4)) {an = 1; DH = 1;}}} function fd(oi, wS) {if (ai) return wS ? document.getElementById(oi).style:document.getElementById(oi); if (al) return wS ? document.all[oi].style: document.all[oi]; if (an) return document.layers[oi];}
	function pw() {return window.innerWidth != null? window.innerWidth: document.body.clientWidth != null? document.body.clientWidth:null;}
	function mouseX(evt) {if (evt.pageX) return evt.pageX; else if (evt.clientX)return evt.clientX + (document.documentElement.scrollLeft ?  document.documentElement.scrollLeft : document.body.scrollLeft); else return null;}
	function mouseY(evt) {if (evt.pageY) return evt.pageY; else if (evt.clientY)return evt.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop); else return null;}
	function popUp(evt,oi) {if (DH) {var wp = pw(); ds = fd(oi,1); dm = fd(oi,0); st = ds.visibility; if (dm.offsetWidth) ew = dm.offsetWidth; else if (dm.clip.width) ew = dm.clip.width; if (st == \"visible\" || st == \"show\") { ds.visibility = \"hidden\"; } else {tv = mouseY(evt) + 20; lv = mouseX(evt) - (ew/4); if (lv < 2) lv = 2; else if (lv + ew > wp) lv -= ew/2; if (!an) {lv += 'px';tv += 'px';} ds.left = lv; ds.top = tv; ds.visibility = \"visible\";}}}
	</script>
";

// kohdisteaan keikkaa laskun tunnuksella $otunnus
if ($toiminto == "kohdista") {
	require('ostotilausten_rivien_kohdistus.inc');
}

// yhdistetaan keikkaan $otunnus muita keikkoja
if ($toiminto == "yhdista") {
	require('ostotilausten_rivien_yhdistys.inc');
}

// poistetaan vanha keikka numerolla $keikkaid
if ($toiminto == "poista") {
	$eisaapoistaa = 0;

	$query  = "select tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and uusiotunnus='$tunnus' and tyyppi='O'";
	$delres = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($delres) != 0) {
		$eisaapoistaa++;
	}

	$query = "	select tunnus
				from lasku
				where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus<>0 and laskunro='$laskunro'";
	$delres2 = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($delres2) != 0) {
		$eisaapoistaa++;
	}

	if ($eisaapoistaa == 0) {
		$query  = "delete from lasku where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkaid'";
		$result = mysql_query($query) or pupe_error($query);

		// formissa on tullut myˆs $ytunnus, joten n‰in p‰‰st‰‰n takaisin selaukseen
		$toiminto = "";
	}
	else {
		echo "<font class='error'>Keikkaan on jo liitetty laskuja tai kohdistettu rivej‰, sit‰ ei voi poistaa!!!<br>";
		// formissa on tullut myˆs $ytunnus, joten n‰in p‰‰st‰‰n takaisin selaukseen
		$toiminto = "";
	}

}

// tulostetaan tarvittavia papruja $otunnuksen mukaan
if ($toiminto == "tulosta") {
	// Haetaan itse keikka
	$query    = "	select *
					from lasku
					where tunnus = '$otunnus'
					and yhtio = '$kukarow[yhtio]'";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);

	// katotaan liitetyt laskut
	$query = "	select
				GROUP_CONCAT(distinct vanhatunnus SEPARATOR ',') volaskutunn
				from lasku
				where yhtio = '$kukarow[yhtio]'
				and tila = 'K'
				and vienti in ('C','F','I','J','K','L')
				and vanhatunnus <> 0
				and laskunro = '$laskurow[laskunro]'
				HAVING volaskutunn is not null";
	$llres = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($llres) > 0) {
		$llrow = mysql_fetch_array($llres);

		$query = "	select
					GROUP_CONCAT(viesti SEPARATOR ' ') viesti,
					GROUP_CONCAT(tapvm SEPARATOR ' ') tapvm
					from lasku
					where yhtio = '$kukarow[yhtio]'
					and tunnus in ($llrow[volaskutunn])";
		$llres = mysql_query($query) or pupe_error($query);
		$llrow = mysql_fetch_array($llres);


		$laskurow["tapvm "] = $llrow["tapvm"];
		$laskurow["viesti"] = $llrow["viesti"];

	}

	if (count($komento) == 0) {
		$tulostimet = array('Purkulista','Tuotetarrat','Tariffilista');

		echo "<font class='message'>".t("Keikka")." $laskurow[laskunro] $laskurow[nimi]</font><br><br>";

		require('../inc/valitse_tulostin.inc');
	}

	if ($komento["Purkulista"] != '')
		require('tulosta_purkulista.inc');

	if ($komento["Tuotetarrat"] != '')
		require('tulosta_tuotetarrat.inc');

	if ($komento["Tariffilista"] != '')
		require('tulosta_tariffilista.inc');

	// takaisin selaukseen
	$toiminto = "";
	$ytunnus = $laskurow["ytunnus"];
}

// syˆtet‰‰n keikan lis‰tietoja
if ($toiminto == "lisatiedot") {
	require ("ostotilauksen_lisatiedot.inc");
}

// chekataan tilauksen varastopaikat
if ($toiminto == "varastopaikat") {
	require('ostorivienvarastopaikat.inc');
}

// lis‰ill‰‰n keikkaan kululaskuja
if ($toiminto == "kululaskut") {
	require('kululaskut.inc');
}

// tehd‰‰n errorichekkej‰ jos on varastoonvienti kyseess‰
if ($toiminto == "kaikkiok" or $toiminto == "kalkyyli") {
	$query = "	SELECT nimi
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]'
				and kesken  = '$otunnus'";
	$result = mysql_query($query) or pupe_error($query);

	$varastoerror = 0;

	if (file_exists("/tmp/$kukarow[yhtio]-keikka.lock")) {
		echo "<font class='error'>".t("VIRHE: Keikkaa ei voi vied‰ varastoon.")." ".t("Varastoonvienti on kesken!")."</font><br>";
		$varastoerror = 1;
	}
	elseif (mysql_num_rows($result) != 0){
		while ($rivi = mysql_fetch_array($result)) {
			echo "<font class='error'>".t("VIRHE: Keikkaa ei voi vied‰ varastoon.")." ".sprintf(t("K‰ytt‰j‰ll‰ %s on kohdistus kesken!"), $rivi["nimi"])."</font><br>";
		}
		$varastoerror = 1;
	}

	if ($varastoerror != 0) {
		echo "<br><form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='toiminto' value=''>";
		echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
		echo "<input type='submit' value='".t("Takaisin")."'>";
		echo "</form>";

		$ytunnus = "";
		$toiminto = "dummieimit‰‰n";
	}
}

// lasketaan lopullinen varastonarvo
if ($toiminto == "kaikkiok") {
	require ("varastonarvo_historia.inc");
}

// vied‰‰n keikka varastoon
if ($toiminto == "kalkyyli") {
	require ("kalkyyli.inc");
}

// jos ollaan annettu $ytunnus haetaan toimittajan tiedot arrayseen $toimittajarow
if ($ytunnus != "" or $toimittajaid != "") {
	$keikkamonta = 0;
	$hakutunnus = $ytunnus;

	require ("../inc/kevyt_toimittajahaku.inc");

	$keikkamonta += $monta;

	if ($ytunnus == "") {
		$ytunnus = $hakutunnus;
		require ("../inc/asiakashaku.inc");

		$toimittajaid  = $asiakasid;
		$toimittajarow = $asiakasrow;
		$keikkamonta  += $monta;
	}

	if ($keikkamonta > 1) {
		$toimittajaid  = "";
		$toimittajarow = "";
		$ytunnus 	   = "";
	}
}


if ($toiminto == "" and $ytunnus == "") {
	echo "<table>";
	echo "<form name='toimi' action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<tr>";
	echo "<th>".t("Etsi toimittaja")."</th>";
	echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
	echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr>";
	echo "</form>";
	echo "</table>";

	// kursorinohjausta
	$formi    = "toimi";
	$kentta   = "ytunnus";
	$toiminto = "";

	// n‰ytet‰‰n mill‰ toimittajilla on keskener‰isi‰ keikkoja
	$query = "	select ytunnus, nimi, osoite, postitp, swift, group_concat(if(comments!='',comments,NULL) SEPARATOR '<br><br>') comments, liitostunnus, count(*) kpl, group_concat(distinct laskunro SEPARATOR ', ') keikat
				from lasku
				where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus=0
				group by liitostunnus, ytunnus, nimi, osoite, postitp, swift
				order by nimi, ytunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 0) {

		echo "<br><font class='head'>".t("Keskener‰iset keikat")."</font><hr>";

		echo "<table>";
		echo "<tr><th>".t("ytunnus")."</th><th>".t("nimi")."</th><th>".t("osoite")."</th><th>".t("swift")."</th><th>".t("keikkanumerot")."</th><th>".t("kpl")."</th><th></th></tr>";

		while ($row = mysql_fetch_array($result)) {

			echo "<tr class='aktiivi'>";

			// tehd‰‰n pop-up divi jos keikalla on kommentti...
			if ($row["comments"] != "") {
				echo "<div id='$row[liitostunnus]' style='position:absolute; z-index:100; visibility:hidden; width:500px; background:#555555; color:#FFFFFF; border: 1px solid; padding:5px;'>";
				echo $row["comments"];
				echo "</div>";
				echo "<td valign='top'><a class='menu' onmouseout=\"popUp(event,'$row[liitostunnus]')\" onmouseover=\"popUp(event,'$row[liitostunnus]')\">$row[ytunnus]</a></td>";
			}
			else {
				echo "<td valign='top'>$row[ytunnus]</td>";
			}

			echo "<td>$row[nimi]</td><td>$row[osoite] $row[postitp]</td><td>$row[swift]</td><td>$row[keikat]</td><td>$row[kpl]</td>";
			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$row[liitostunnus]'>";
			echo "<td><input type='submit' value='".t("Valitse")."'></td>";
			echo "</form>";
			echo "</tr>";
		}

		echo "</table>";
	}
}

// perustetaan uusi keikka toimittajalle $ytunnus
if ($toiminto == "uusi" and $toimittajaid > 0) {
	// haetaan seuraava vapaa keikkaid
	$query  = "select max(laskunro) from lasku where yhtio='$kukarow[yhtio]' and tila='K'";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);
	$id		= $row[0]+1;

	$query  = "select kurssi from valuu where nimi='$toimittajarow[oletus_valkoodi]' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);
	$kurssi = $row["kurssi"];

	// meill‰ on $toimittajarow haettuna ylh‰‰ll‰
	$query = "	insert into lasku set
				yhtio        = '$kukarow[yhtio]',
				laskunro     = '$id',
				ytunnus	     = '$toimittajarow[ytunnus]',
				nimi         = '$toimittajarow[nimi]',
				valkoodi     = '$toimittajarow[oletus_valkoodi]',
				vienti       = '$toimittajarow[oletus_vienti]',
				vienti_kurssi= '$kurssi',
				toimitusehto = '$toimittajarow[toimitusehto]',
				osoite       = '$toimittajarow[osoite]',
				postitp      = '$toimittajarow[postitp]',
				maa			 = '$toimittajarow[maa]',
				maa_lahetys  = '$toimittajarow[maa]',
				swift        = '$toimittajarow[swift]',
				liitostunnus = '$toimittajarow[tunnus]',
				tila         = 'K',
				luontiaika	 = now(),
				laatija		 = '$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	// selaukseen
	$toiminto = "";
}

// selataan toimittajan keikkoja
if ($toiminto == "" and $ytunnus != "") {

	// n‰ytet‰‰n v‰h‰ toimittajan tietoja
	echo "<table>";
	echo "<tr>";
	echo "<th colspan='5'>".t("Toimittaja")."</th><th>".t("Uusi keikka")."</th>";
	echo "</tr><tr>";
	echo "<td>$toimittajarow[ytunnus]</td>";
	echo "<td>$toimittajarow[nimi]</td>";
	echo "<td>$toimittajarow[osoite]</td>";
	echo "<td>$toimittajarow[postino]</td>";
	echo "<td>$toimittajarow[postitp]</td>";

	echo "<td>";
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toiminto' value='uusi'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
	echo "<input type='submit' value='".t("Perusta uusi keikka")."'>";
	echo "</form>";
	echo "</td>";
	echo "</tr>";

	if (trim($toimittajarow["fakta"]) != "") {
		echo "<tr><td colspan='5'>$toimittajarow[fakta]</td></tr>";
	}

	echo "</table><br>";

	if ($naytakaikki == "YES") {
		$limitti = " ";
	}
	else {
		$limitti = " LIMIT 50";
	}

	// etsit‰‰n vanhoja keikkoja, vanhatunnus pit‰‰ olla tyhj‰‰ niin ei listata liitettyj‰ laskuja
	$query = "	select *
				from lasku use index (tila_index)
				where yhtio='$kukarow[yhtio]'
				and liitostunnus='$toimittajaid'
				and tila='K'
				and alatila=''
				and vanhatunnus=0
				order by laskunro desc
				$limitti";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='head'>".t("Keskener‰iset keikat")."</font><hr>";

		if (mysql_num_rows($result) == 50 and $naytakaikki == "") {
			echo "<table><tr><td class='back'><font class='error'>".t("HUOM: Toimittajalla on yli 50 avointa keikkaa! Vain 50 viimeisint‰ n‰ytet‰‰n.")."</font></td>";

			echo "<td class='back'>";
			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
			echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
			echo "<input type='hidden' name='naytakaikki' value='YES'>";
			echo "<input type='submit' value='".t("N‰yt‰ kaikki")."'>";
			echo "</form>";
			echo "</td>";
			echo "</tr></table><br>";
		}

		echo "<table>";
		echo "<tr>";
		echo "<th valign='top'>".t("keikka")."</th>";
		echo "<th valign='top'>".t("ytunnus")." /<br>".t("nimi")."</th>";
		echo "<th valign='top'>".t("kohdistus")." /<br>".t("lis‰tiedot")."</th>";
		echo "<th valign='top'>".t("paikat")." /<br>".t("sarjanrot")."</th>";
		echo "<th valign='top'>".t("kohdistettu")." /<br>".t("varastossa")."</th>";
		echo "<th valign='top'>".t("ostolaskuja")." /<br>".t("kululaskuja")."</th>";
		echo "<th valign='top'>".t("toiminto")."</th>";
		echo "</tr>";

		$keikkakesken = 0;
		if (file_exists("/tmp/$kukarow[yhtio]-keikka.lock")) {
			$keikkakesken = file_get_contents("/tmp/$kukarow[yhtio]-keikka.lock");
		}
		
		while ($row = mysql_fetch_array($result)) {

			// tutkitaan onko kaikilla tuotteilla on joku varastopaikka
			$query  = "select * from tilausrivi use index (uusiotunnus_index) where yhtio='$kukarow[yhtio]' and uusiotunnus='$row[tunnus]' and tyyppi='O'";
			$tilres = mysql_query($query) or pupe_error($query);

			$kplyhteensa = 0;  // apumuuttuja
			$kplvarasto  = 0;  // apumuuttuja
			$eipaikkoja  = 0;  // apumuuttuja
			$eituotteet  = ""; // apumuuttuja

			while ($rivirow = mysql_fetch_array($tilres)) {
				$query = "select * from tuote where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]'";
				$tuore = mysql_query($query) or pupe_error($query);
				$tuote = mysql_fetch_array($tuore);

				$kplyhteensa++; // lasketaan montako tilausrivi‰ on kohdistettu

				if (($rivirow["kpl"] != 0 and $rivirow["varattu"] == 0) or ($rivirow["kpl"] == 0 and $rivirow["varattu"] == 0)) {
					$kplvarasto++; // lasketaan montako tilausrivi‰ on viety varastoon
				}

				// jos kyseess‰ on saldollinen tuote
				if ($tuote['ei_saldoa'] == "") {
					//jos rivi on jo viety varastoon niin ei en‰‰ katota sen paikkaa
					if ($rivirow["kpl"] == 0 and $rivirow["varattu"] != 0) {
						// katotaan lˆytyykˆ tuotteelta varastopaikka joka on tilausriville tallennettu
						$query = "	select *
									from tuotepaikat use index (tuote_index)
									where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]' and hyllyalue='$rivirow[hyllyalue]' and hyllynro='$rivirow[hyllynro]' and hyllytaso='$rivirow[hyllytaso]' and hyllyvali='$rivirow[hyllyvali]'";
						$tpres = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($tpres)==0) {
							$eipaikkoja++;
						}
					}
				}
			}

			if ($eipaikkoja == 0 and $kplyhteensa > 0) {
				$varok=1;
				$varastopaikat = "<font color='#00FF00'>".t("ok")."</font>";
			}
			else {
				$varok=0;
				$varastopaikat = t("kesken");
			}

			// tutkitaan onko kaikki lis‰tiedot syˆtetty vai ei...
			$query = "	select *
						from lasku use index (PRIMARY)
						where lasku.yhtio='$kukarow[yhtio]'
						and tunnus='$row[tunnus]'
						and maa != '$yhtiorow[maa]'
						and (maa_lahetys = '' or bruttopaino = '' or kauppatapahtuman_luonne <= 0 or kuljetusmuoto = '' or toimaika = '0000-00-00')
						and kauppatapahtuman_luonne != '999'";
			$okres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($okres)==0) {
				$lisok=1;
				$lisatiedot = "<font color='#00FF00'>".t("ok")."</font>";
			}
			else {
				$lisok=0;
				$lisatiedot = t("kesken");
			}

			// katotaan onko kohdistus tehty pennilleen
			if ($row["kohdistettu"] == 'K') {
				$kohok=1;
				$kohdistus = "<font color='#00FF00'>".t("ok")."</font>";
			}
			else {
				$kohok=0;
				$kohdistus = t("kesken");
			}

			// katotaan onko kaikki sarjanumerot ok
			$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu+tilausrivi.kpl kpl
						FROM tilausrivi use index (uusiotunnus_index)
						JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
						WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
						tilausrivi.uusiotunnus='$row[tunnus]' and
						tilausrivi.tyyppi='O'";
			$toimresult = mysql_query($query) or pupe_error($query);

			$sarjanrook = 0;
			$sarjanrot = "<font color='#00FF00'>".t("ok")."</font>";

			while ($toimrow = mysql_fetch_array($toimresult)) {

				if ($toimrow["kpl"] < 0) {
					$tunken = "myyntirivitunnus";
				}
				else {
					$tunken = "ostorivitunnus";
				}

				// tilausrivin tunnus pit‰‰ lˆyty‰ sarjanumeroseurannasta
				$query = "select * from sarjanumeroseuranta use index (yhtio_ostorivi) where yhtio='$kukarow[yhtio]' and tuoteno='$toimrow[tuoteno]' and $tunken='$toimrow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);

				// pit‰‰ olla yht‰monta sarjanumeroa liitettyn‰ kun kamaa viety varastoon
				if (mysql_num_rows($sarjares) != abs($toimrow["kpl"])+abs($toimrow["varattu"])) {
					$sarjanrook  = 1; // ei ole kaikilla tuotteilla sarjanumeroa
					$sarjanrot = "kesken";
				}
			}


			// katotaan onko liitettyj‰ laskuja
			// ('C','F','I','J','K','L') // vaihto-omaisuus ja raaka-aine
			// ('B','C','J','E','F','K','H','I','L') // kaikki

			$query = "	select count(*) num,
						sum(if(vienti='C' or vienti='F' or vienti='I' or vienti='J' or vienti='K' or vienti='L',1,0)) volasku,
						sum(if(vienti!='C' and vienti!='F' and vienti!='I' and vienti!='J' and vienti!='K' and vienti!='L',1,0)) kulasku,
						sum(if(vienti='C' or vienti='F' or vienti='I' or vienti='J' or vienti='K' or vienti='L',summa,0)) vosumma,
						sum(if(vienti!='C' and vienti!='F' and vienti!='I' and vienti!='J' and vienti!='K' and vienti!='L',arvo,0)) kusumma
						from lasku use index (yhtio_tila_laskunro)
						where yhtio='$kukarow[yhtio]'
						and tila='K'
						and vanhatunnus<>0
						and laskunro='$row[laskunro]'";
			$llres = mysql_query($query) or pupe_error($query);
			$llrow = mysql_fetch_array($llres);

			if ($llrow["vosumma"] != 0) $llrow["vosumma"] = "($llrow[vosumma])"; else $llrow["vosumma"]=""; // kaunistellaan summa sulkuihin
			if ($llrow["kusumma"] != 0) $llrow["kusumma"] = "($llrow[kusumma])"; else $llrow["kusumma"]=""; // kaunistellaan summa sulkuihin
			if ($llrow["volasku"] == "") $llrow["volasku"] = 0; // kaunistellaan tyhj‰t nollaks
			if ($llrow["kulasku"] == "") $llrow["kulasku"] = 0; // kaunistellaan tyhj‰t nollaks

			echo "<tr class='aktiivi'>";

			// tehd‰‰n pop-up divi jos keikalla on kommentti...
			if ($row["comments"] != "") {
				echo "<div id='$row[laskunro]' style='position:absolute; z-index:100; visibility:hidden; width:500px; background:#555555; color:#FFFFFF; border: 1px solid; padding:5px;'>";
				echo t("Keikka").": $row[laskunro] / $row[nimi]<br><br>";
				echo $row["comments"];
				echo "</div>";
				echo "<td valign='top'><a class='menu' onmouseout=\"popUp(event,'$row[laskunro]')\" onmouseover=\"popUp(event,'$row[laskunro]')\">$row[laskunro]</a></td>";
			}
			else {
				echo "<td valign='top'>$row[laskunro]</td>";
			}

			echo "<td valign='top'>$row[ytunnus]<br>$row[nimi]</td>";
			echo "<td valign='top'>$kohdistus<br>$lisatiedot</td>";
			echo "<td valign='top'>$varastopaikat<br>$sarjanrot</td>";
			echo "<td valign='top'>$kplyhteensa<br>$kplvarasto</td>";
			echo "<td valign='top'>$llrow[volasku] $llrow[vosumma]<br>$llrow[kulasku] $llrow[kusumma]</td>";

			// jos t‰t‰ keikkaa ollaan just viem‰ss‰ varastoon ei tehd‰ dropdownia
			if ($keikkakesken == $row["tunnus"]) {
				echo "<td>".t("Varastoonvienti kesken")."</td>";
			}
			else {
				echo "<form action='$PHP_SELF' method='post'>";
				echo "<td align='right'>";
				echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
				echo "<input type='hidden' name='otunnus' value='$row[tunnus]'>";
				echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
				echo "<input type='hidden' name='keikkaid' value='$row[laskunro]'>";
				echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
				echo "<input type='hidden' name='laskunro' value='$row[laskunro]'>";
				echo "<select name='toiminto'>";

				// n‰it‰ saa tehd‰ aina keikalle
				echo "<option value='kohdista'>"         .t("Kohdista rivej‰")."</option>";
				echo "<option value='kululaskut'>"       .t("Keikan laskut")."</option>";
				echo "<option value='lisatiedot'>"       .t("Lis‰tiedot")."</option>";
				echo "<option value='yhdista'>"          .t("Yhdist‰ keikkoja")."</option>";

				// poista keikka vaan jos ei ole yht‰‰n rivi‰ kohdistettu ja ei ole yht‰‰n kululaskua liitetty
				if ($kplyhteensa == 0 and $llrow["num"] == 0) {
					echo "<option value='poista'>"       .t("Poista keikka")."</option>";
				}

				// jos on kohdistettuja rivej‰ niin saa tehd‰ n‰it‰
				if ($kplyhteensa > 0) {
					echo "<option value='varastopaikat'>".t("Varastopaikat")."</option>";
					echo "<option value='tulosta'>"      .t("Tulosta paperit")."</option>";
				}

				// jos on kohdistettuja rivej‰ ja lis‰tiedot on syˆtetty ja varastopaikat on ok ja on viel‰ jotain viet‰v‰‰ varastoon
				if ($kplyhteensa > 0 and $varok == 1 and $kplyhteensa != $kplvarasto and $sarjanrook == 0) {
					echo "<option value='kalkyyli'>"     .t("Vie varastoon")."</option>";
				}

				// jos lis‰tiedot, kohdistus ja paikat on ok sek‰ kaikki rivit on viety varastoon, niin saadaan laskea virallinen varastonarvo
				if ($lisok == 1 and $kohok == 1 and $varok == 1 and $kplyhteensa == $kplvarasto and $sarjanrook == 0) {
					echo "<option value='kaikkiok'>"     .t("Laske virallinen varastonarvo")."</option>";
				}

				echo "</select>";
				echo "<input type='submit' value='".t("Tee")."'>";
				echo "</td>";
				echo "</form>";
			}
			echo "</tr>";
		}

		echo "</table>";
	}
}

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
	require ("../inc/footer.inc");
}

?>