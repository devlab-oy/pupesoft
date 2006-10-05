<?php

require('inc/parametrit.inc');

// poimitaan kuluva päivä, raportin timestampille

echo "<font class='head'>".t("Siirto ulkoiseen kirjanpitoon")."</font><hr>";

if ($kausi!='') {
	$riviloppu="\n";
	$erotin="\t";
	$nimi = "/tmp/SIE-$kukarow[yhtio]-".date("ymd.His-s").".txt";
	$toot = fopen($nimi,"w+");
	
	fputs($toot, '#FLAGGA'.$erotin.'0'.$riviloppu);

	fputs($toot, '#PROGRAM'.$erotin.'"Pupesoft the open alternative"'.$riviloppu);

	fputs($toot, '#FORMAT'.$erotin.'PC8'.$riviloppu);

	fputs($toot, '#GEN'.$erotin.date('Ymd').$erotin.date('H.i.s').$riviloppu);

	fputs($toot, '#SIETYP'.$erotin.'4'.$riviloppu);

	fputs($toot, '#ORGNR'.$erotin.$yhtiorow['ytunnus'].$riviloppu);

	fputs($toot, "#FNAMN".$erotin.'"'.$yhtiorow['nimi'].'"'.$riviloppu);
	fputs($toot, '#ADRESS'.$erotin.'"'.$yhtiorow['osoite'].'"'.$erotin.'"'.$yhtiorow['postino']." ".$yhtiorow['postitp'].'"'.$riviloppu);



	if ($perustiedot=='on') {

		//Tilikartta
		echo "<font class='message'>Tilikartta</font><br>";
		$query = "SELECT tilino, nimi FROM tili WHERE yhtio = '$kukarow[yhtio]'";

		$result = mysql_query($query)
					or die ("Kysely ei onnistu $query");
		$ulos='';
		while ($trow = mysql_fetch_array($result)) {
			$ulos.='#KONTO'.$erotin.$trow['tilino'].$erotin.'"'.$trow['nimi'].'"'.$riviloppu;
		}
		fputs($toot, $ulos);
		
		//Kustannuspaikan koodien haku
		echo "<font class='message'>Tarkenteet</font><br>";
		$query = "SELECT tunnus, nimi FROM kustannuspaikka WHERE yhtio = '$kukarow[yhtio]'";

		$result = mysql_query($query)
					or die ("Kysely ei onnistu $query");
		$ulos='';
		while ($trow = mysql_fetch_array($result)) {
			$ulos.='#DIM'.$erotin.$trow['tunnus'].$erotin.'"'.$trow['nimi'].'"'.$riviloppu;
		}
		fputs($toot, $ulos);

	}
	echo "<font class='message'>Tapahtumat</font><br>";
	//Itse tapahtumat
	$query  = "SELECT date_format(tiliointi.tapvm, '%Y%m%d') tapvm,
			tilino, kustp, projekti, tiliointi.summa
			summa, selite, ytunnus, ltunnus, mapvm, tiliointi.tunnus tunnus, lasku.laskunro laskunro, nimi
			FROM tiliointi, lasku
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and tosite=''
			and lasku.tapvm=tiliointi.tapvm and left(tiliointi.tapvm,7)='$kausi' and korjattu=''
			ORDER BY ltunnus, tiliointi.tapvm, tilino, kustp, projekti";

	$result = mysql_query($query) or pupe_error($query);

	while ($trow=mysql_fetch_array ($result)) {
	
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
	echo "<br><br><br>";
	$kausi='';
}

if ($kausi == '') {
//Näytetään käyttöliittymä
	echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
		<table>
		<tr><td>".t("Anna kausi")."</td><td><input type = 'text' name = 'kausi' size=8> Esim 2003-08</td></tr>
		<tr><td>Perustiedot</td><td><input type = 'checkbox' name = 'perustiedot'></td></tr>
		<tr><td></td><td><input type = 'submit' value = '".t("Valitse")."'></td></tr>
		</table></form>";
	$formi = 'valinta';
	$kentta = 'kausi';
}

require "inc/footer.inc";
?>
