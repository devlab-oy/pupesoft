<?php

	// $otunnukset on kaikki laskujen tunnukset, jotka kuuluu tähän samaan rahtikirjaan
	// $row arrayssa on kaikki rahtikirjan tiedot

	// katotaan, onko rahtikirjan kaikki tilaukset jo punnittu
	$query    = "select distinct vanhatunnus from lasku where yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset)";
	$vanhares = mysql_query($query) or pupe_error($query);

	// otetaan talteen kaikkien tilausten vanhatunnus:kset
	$vanhatunnus = "";

	while ($vanharow = mysql_fetch_array($vanhares)) {
		$vanhatunnus    .="'$vanharow[vanhatunnus]',";
	}

	// vikat pilkut pois
	$vanhatunnus = substr($vanhatunnus,0,-1);

	// sitte haetaan kaikki tunnukset jotka kuuluu näille vanhatunnuksille
	$query    = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and vanhatunnus in ($vanhatunnus)";
	$vanhares = mysql_query($query) or pupe_error($query);

	// nollataan muuttujat
	$vanhatunnuspuuttuu = array();
	$oikeatunnus = "";      // tänne tulee kaikki tunnukset, jotka pitää oikeasti laittaa samaan laskuun
	$oikeaotsiko = array(); // ja sama arrayna

	while ($vanharow = mysql_fetch_array($vanhares)) {
		// tunnuksia talteen
		$oikeatunnus .="'$vanharow[tunnus]',";
		$oikeaotsiko[]=  $vanharow["tunnus"];

		// katotaan löytyykö rahtikirjatiedot
		$query    = "select * from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$vanharow[tunnus]'";
		$vanhachk = mysql_query($query) or pupe_error($query);

		// jos tilaukselle ei ole syötetty rahtikirjatietoja, lisätään tunnus arrayseen
		if (mysql_num_rows($vanhachk) == 0) {
			$vanhatunnuspuuttuu[] = $vanharow["tunnus"];
		}
	}

	// vikat pilkut pois
	$oikeatunnus = substr($oikeatunnus,0,-1);

//echo "rahtikirjaan kuuluu tilaukset: $otunnukset<br>";
//echo "niiden tilausten vanhatunnukset: $vanhatunnus<br>";
//echo "niiden vanhatunnusten kaikki tilaukset: $oikeatunnus<br>";

	if (count($vanhatunnuspuuttuu) > 0) {
		echo "<font class='error'>";
		echo "Kyseessä on jälkivaatimus ja kaikkia paketteja ei ole punnittu! Rahtikirjaa ei voida tulostaa vielä!<br><br>";
		echo "Syöttäkää rahtikirjatiedot tilauksille nro: ";

		foreach ($vanhatunnuspuuttuu as $apuvanha) echo "$apuvanha ";

		echo "<br><br></font>";

		// sanotaan vaan exit.. ens versiossa sitten osataan nätimmin
		exit;
	}

	// katotaan onko tilaus jo laskuttettu
	$query = "select tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus in ($oikeatunnus) and uusiotunnus=0";
	$jvres = mysql_query($query) or pupe_error($query);

	// onko tilaus laskutettu
	if (mysql_num_rows($jvres) == 0) {
		$laskutettu = "kylla";
	}
	else {
		$laskutettu = "ei";
	}

	// jos on merkattu lähettäjä rahdinmaksajaksi, lisätään rahtikulut tilaukselle
	$rahtihinta  = "";
	$rahdinhinta = "<li>Ei rahtikuluja";

	if ($row['merahti'] == 'K') {

		//joudutaan tässä keisissa laskemaan uudestaan kilot
		//summataan kaikki painot yhteen kaikilta $vanhatunnus laskuilta vaikka ne ei kuuluisikaan tähän tulostukseen
		$query = "SELECT pakkaus, sum(kilot), sum(kollit), sum(kuutiot), sum(lavametri) FROM rahtikirjat WHERE otsikkonro in ($oikeatunnus) and yhtio='$kukarow[yhtio]' group by pakkaus order by pakkaus";
		$pakka = mysql_query($query) or pupe_error($query);

		// muutama muuttuja tarvitaan
		$pakkaus2       = array();
		$kilot2         = array();
		$kollit2        = array();
		$kuutiot2       = array();
		$lavametri2     = array();
		$kilotyht       = 0;
		$lavatyht       = 0;
		$kollityht      = 0;
		$kuutiotyht     = 0;

		while ($pak = mysql_fetch_array($pakka)) {
			$pakkaus2[]   = $pak[0];
			$kilot2[]     = $pak[1];
			$kollit2[]    = $pak[2];
			$kuutiot2[]   = $pak[3];
			$lavametri2[] = $pak[4];
			$kilotyht    += $pak[1];
			$kollityht   += $pak[2];
			$kuutiotyht  += $pak[3];
			$lavatyht    += $pak[4];
		}

		//haetaan tällä rahtikirjalle rahtimaksu
		$query = "select * from rahtimaksut where toimitustapa='$toimitustapa' and kilotalku<='$kilotyht' and kilotloppu>='$kilotyht' and yhtio='$kukarow[yhtio]'";
		$rares = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($rares)==1) {
			$rahti = mysql_fetch_array($rares);
			$rahdinhinta = "<li>Rahdin hinta: $rahti[rahtihinta] $yhtiorow[valkoodi] ($kilotyht kg)";

			// kirjoitetaan hintarivi ekalle otsikolle
			$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
			$rhire = mysql_query($query) or pupe_error($query);
			$trow  = mysql_fetch_array($rhire);

			$otunnus			= $oikeaotsiko[0];
			$laskurow["alv"]	= $row["alv"];
			$hinta				= $rahti["rahtihinta"]; // rahtihinta
			$laskurow["tila"]	= "L";
			$laskurow["vienti"]	= $row["vienti"];
			$alv 				= '';
			$nimitys			= "$toimitustapa";
			$kommentti			= "";

			require("tilauskasittely/alv.inc");

			$rahtihinta			= $hinta; // rahtihinta tarvitaan matikassa

			if ($laskutettu == "ei") {
				$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  ('$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '$kommentti')";
				$addtil = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			$rahdinhinta = "<li><font class='error'>".t("Rahtimaksua ei löytynyt! Toimitustapa").": '$toimitustapa' ".t("Kilot").": '$kilotyht'</font>";
		}
	}

	// kirjoitetaan jv kulurivi ekalle otsikolle
	$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
	$rhire = mysql_query($query) or pupe_error($query);
	$trow  = mysql_fetch_array($rhire);

	$otunnus			= $oikeaotsiko[0];
	$laskurow["alv"]	= $row["alv"];
	$hinta				= $toitarow['jvkulu']; // jv kulu
	$laskurow["tila"]	= "L";
	$laskurow["vienti"]	= $row["vienti"];
	$alv 				= 'X';
	$nimitys			= "Jälkivaatimuskulu";
	$kommentti			= "";

	require("tilauskasittely/alv.inc");

	$jvhinta			= $hinta; // jv kulu tarvitaan matikassa

	if ($laskutettu == "ei") {

		// lock tables, kopsattu vientilaskutuksesta
		$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, sanakirja WRITE, tapahtuma WRITE, tuotepaikat WRITE, tiliointi WRITE, maksuehto READ, tullinimike READ, kuka READ, varastopaikat READ, tuote READ, rahtikirjat READ, kirjoittimet READ, tuotteen_avainsanat READ, asiakas READ, rahtimaksut READ, avainsana READ, pankkiyhteystiedot READ, yhtion_toimipaikat READ";
		$locre = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("Laskutetaan tilaus")."...<br></font>";

		$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  ('$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '$kommentti')";
		$addtil = mysql_query($query) or pupe_error($query);

		// annetaan tässä vaiheessa jälkivaatimukselle lasku- ja viitenumero...
		$query = "SELECT max(laskunro) FROM lasku WHERE yhtio='$kukarow[yhtio]' and tila='U'";
		$viire = mysql_query($query) or pupe_error($query);
		$lrow  = mysql_fetch_array($viire);

		$lasno = $lrow[0] + 1;
		$viite = $lasno + 99900000000; //Tästä voimme tunnistaa, että viite kuuluu jälkivaatimukselle

		require("inc/generoiviite.inc");

		// päivitetään tilauksille laskunumero ja viite..
		$query  = "update lasku set laskunro='$lasno', viite='$viite', viesti=concat_ws(' ', viesti, 'Kuitataan jälkivaatimuksena saaduksi.') where yhtio='$kukarow[yhtio]' and tunnus in ($oikeatunnus)";
		$upvii  = mysql_query($query) or pupe_error($query);

//echo "päivitettiin tilauksille $oikeatunnus laskunumero $lasno ja viite $viite (alkuluku $lrow[0] + 1 + 99900000000)<br>";

		// käydään läpi otsikot
		foreach($oikeaotsiko as $doit) {
			//laskutetaan tämä tilaus.. laskutus lukee kukarow kesken
//echo "laskutetaan $doit<br>";
			$kukarow['kesken']=$doit;
			require("tilauskasittely/laskutus.inc");
		}

		// tehdään ulasku
		$aputun    = $tunnukset;
		$tunnukset = $oikeatunnus;

//echo "tehdään u-lasku tunnuksista: $tunnukset<br>";

		require("tilauskasittely/teeulasku.inc");
		$tunnukset = $aputun;
		// saadaan takasin laskurow jossa on U-laskun tiedot

		// vaikka laskutettiin kaikki, niin muutetaan alatila takasisin "rahtikirjatiedot syötetty", niin saadaan tulostettua
		// loputkin rahtikirjat toisista varastoista. alatila muutetaan vielä oikeasti tulostetuille vähän myöhemmin oikeaksi
		$query = "update lasku set alatila='B' where yhtio='$kukarow[yhtio]' and tunnus in ($oikeatunnus)";
		$jvres = mysql_query($query) or pupe_error($query);
		$jvrow  = mysql_fetch_array($viire);

//echo "siirretään tilausten $oikeatunnus alatila takasin B:ksi<br>";
//echo "u-lasku sai tunnuksen $laskurow[tunnus]<br>";

		// unlock tables
		$query = "UNLOCK TABLES";
		$locre = mysql_query($query) or pupe_error($query);

	}
	else {
		// tämä tilaus on jo laskutettu, haetaan vaan U-laskun tiedot
		$query  = "select distinct uusiotunnus from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus in ($oikeatunnus)";
		$uusres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($uusres) == 0) {
			echo "<font class='error'>".t("*SHIT!* Tilaus vaikuttaa laskutetulta, mutta yhtään laskua ei löydy! *SHIT!*")."</font><br>";
		}
		else {
			$uusrow = mysql_fetch_array($uusres);
			// haetaan laskun tiedot
			$query  = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus = '$uusrow[uusiotunnus]' and tila='U'";
			$us2res = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($us2res);
			$viite  = $laskurow['viite'];
//echo "$query<br>";
		}

		if (mysql_num_rows($uusres) == 1) {
			echo "<font class='message'>".t("Tilaus on jo laskutettu laskulla")." $laskurow[laskunro]. ".t("Tulostetaan vain rahtikirja").".</font><br>";
		}
		elseif (mysql_num_rows($uusres) > 1) {
			echo "<font class='error'>".t("Tilaus on laskutettu useammalla laskulla. UUUH! Käytetään ekaa laskua. Laskunnumero").": $laskurow[laskunro]</font><br>";
		}

//echo "u-laskun tunnus on $laskurow[tunnus]<br>";

	}

	$yhteensa = $laskurow["summa"];
	$summa    = $laskurow['summa'] - $jvhinta - $rahtihinta;

	$jvtext  = "<li>".t("Jälkivaatimuskulu").": $jvhinta $yhtiorow[valkoodi];";
	$jvtext .= "<li>".t("Loppusumma yhteensä").": $yhteensa $yhtiorow[valkoodi];";

	$aputeksti = "".t("JÄLKIVAATIMUS")."";

?>