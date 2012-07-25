<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

require('inc/parametrit.inc');

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}

// poimitaan kuluva päivä, raportin timestampille
echo "<font class='head'>".t("SIE - Siirto ulkoiseen kirjanpitoon")."</font><hr>";

if ($kausi != '') {

	$riviloppu	= "\n";
	$erotin		= "\t";
	$nimi 		= "/tmp/SIE-$kukarow[yhtio]-".date("ymd.His-s").".txt";
	$toot 		= fopen($nimi,"w+");

	fputs($toot, '#FLAGGA'.$erotin.'0'.$riviloppu);
	fputs($toot, '#PROGRAM'.$erotin.'"Pupesoft"'.$riviloppu);
	fputs($toot, '#FORMAT'.$erotin.'PC8'.$riviloppu);
	fputs($toot, '#GEN'.$erotin.date('Ymd').$erotin.date('H.i.s').$riviloppu);
	fputs($toot, '#SIETYP'.$erotin.'4'.$riviloppu);
	fputs($toot, '#ORGNR'.$erotin.$yhtiorow['ytunnus'].$riviloppu);
	fputs($toot, "#FNAMN".$erotin.'"'.$yhtiorow['nimi'].'"'.$riviloppu);
	fputs($toot, '#ADRESS'.$erotin.'"'.$yhtiorow['osoite'].'"'.$erotin.'"'.$yhtiorow['postino']." ".$yhtiorow['postitp'].'"'.$riviloppu);

	if ($perustiedot == 'on') {

		//Tilikartta
		echo "<font class='message'>".t("Tilikartta")."</font><br>";

		$query = "	SELECT tilino, nimi
					FROM tili
					WHERE yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		$ulos = '';

		while ($trow = mysql_fetch_assoc($result)) {
			$ulos.='#KONTO'.$erotin.$trow['tilino'].$erotin.'"'.$trow['nimi'].'"'.$riviloppu;
		}

		fputs($toot, $ulos);

		//Kustannuspaikan koodien haku
		echo "<font class='message'>".t("Tarkenteet")."</font><br>";

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytossa != 'E'
					ORDER BY tyyppi, koodi+0, koodi, nimi";
		$result = pupe_query($query);

		$ulos = '';

		while ($trow = mysql_fetch_assoc($result)) {
			$ulos.='#DIM'.$erotin.$trow['tunnus'].$erotin.'"'.$trow['nimi'].'"'.$riviloppu;
		}

		fputs($toot, $ulos);
	}

	echo "<font class='message'>".t("Tapahtumat")."</font><br>";

	//Itse tapahtumat
	$query  = "	SELECT date_format(tiliointi.tapvm, '%Y%m%d') tapvm,
				tiliointi.tilino,
				tiliointi.kustp,
				tiliointi.projekti,
				tiliointi.summa summa,
				tiliointi.selite,
				lasku.ytunnus,
				tiliointi.ltunnus,
				lasku.mapvm,
				tiliointi.tunnus tunnus,
				lasku.laskunro laskunro,
				lasku.nimi
				FROM tiliointi
				JOIN lasku ON (tiliointi.yhtio=lasku.yhtio and lasku.tunnus=tiliointi.ltunnus)
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				and left(tiliointi.tapvm, 7) = '$kausi'
				and tiliointi.korjattu = ''
				and tiliointi.tosite = ''
				ORDER BY tiliointi.ltunnus, tiliointi.tapvm, tiliointi.tilino, tiliointi.kustp, tiliointi.projekti";
	$result = pupe_query($query);

	while ($trow = mysql_fetch_assoc($result)) {

		if (($vanhaltunnus!=$trow['ltunnus']) or ($vanhatapvm!=$trow['tapvm'])) { // Uusi tosite

			if ($kesken == 1) {
				$ulos.=$riviloppu."}";
				fputs($toot,$ulos.$riviloppu);
				$kesken = 0;
			}

			$ulos='#VER'.$erotin.'""'.$erotin.'""'.$erotin.$trow['tapvm'].$erotin.'"';
			if ($trow['nimi']=='') $ulos.=$trow['nimi']; else $ulos.=$trow['selite'];
			$ulos.='"'.$riviloppu."{".$riviloppu;
			$kesken = 1;
			$vanhaltunnus = $trow['ltunnus'];
			$vanhatapvm = $trow['tapvm'];
		}

		$ulos.=$erotin.'#TRANS'.$erotin.$trow['tilino'].$erotin.'{'.$trow['kustp'].' '.$trow['kohde'].' '.$trow['projekti'].'}';
		$ulos.=$erotin.$trow['summa'].$erotin.$trow['tapvm'].$erotin.'"'.$trow['selite'].'"'.$riviloppu;

	}

	if ($kesken == 1) {
		$ulos.=$riviloppu."}";
		fputs($toot,$ulos.$riviloppu);
		$kesken = 0;
	}

	fclose($toot);

	echo "<br><table>";
	echo "<tr><th>".t("Tallenna aineisto").":</th>";
	echo "<form method='post' class='multisubmit'>";
	echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
	echo "<input type='hidden' name='kaunisnimi' value='SIEout.txt'>";
	echo "<input type='hidden' name='tmpfilenimi' value='".basename($nimi)."'>";
	echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
	echo "</table><br>";

	echo "<br><br><br>";
}


//Näytetään käyttöliittymä
echo "<form name = 'valinta' method='post'>
	<table><tr><th>".t("Anna kausi")."</th><td>";


	//	Haetaan alkupiste
	$kausia 	= 24;
	$kuukausi 	= date("m");
	$vuosi 		= date("Y");

	echo "<select name='kausi'>
		<option value = ''>".t('Valitse kausi')."</option>";

	for ($i = 1; $i < $kausia; $i++) {

		$kuukausi = str_pad((int) $kuukausi, 2, 0, STR_PAD_LEFT);

		$sel = "";

		if ($kausi == $vuosi."-".$kuukausi) {
			$sel = "SELECTED";
		}

		echo "<option value='$vuosi-$kuukausi' $sel>$kuukausi/$vuosi</option>";

		if ($kuukausi == 01) {
			$kuukausi = 12;
			$vuosi--;
		}
		else {
			$kuukausi--;
		}

	}
	echo "</select>";
	echo "</td></tr>";
		
	$chk = "";
	if (isset($perustiedot) and $perustiedot != "") $chk = "CHECKED";
	
	echo "<tr><th>".t("Perustiedot")."</th><td><input type = 'checkbox' name = 'perustiedot' $chk></td></tr></table>";

	echo "<br><br><input type = 'submit' value = '".t("Aja raportti")."'></td></tr>
	</form>";

$formi = 'valinta';
$kentta = 'kausi';

require "inc/footer.inc";

?>