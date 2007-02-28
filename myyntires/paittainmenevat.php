<?php

require "../inc/parametrit.inc";
echo "<font class='head'>".t("Etsi ja poista päittäin menevät suoritukset")."</font><hr>";

if ($tee=='T') {
	//$debug=1;
	$query="LOCK TABLES suoritus as a READ, suoritus as b READ, suoritus WRITE, tiliointi WRITE, sanakirja WRITE";
	$result = mysql_query($query) or pupe_error($query);
	$query = "SELECT a.tunnus atunnus, b.tunnus btunnus, a.ltunnus altunnus, b.ltunnus bltunnus, a.kirjpvm akirjpvm, a.summa asumma, b.kirjpvm bkirjpvm, b.summa bsumma
				  		FROM suoritus a, suoritus b
				  		WHERE a.yhtio='$kukarow[yhtio]' and b.yhtio='$kukarow[yhtio]' and
				  			a.kohdpvm = '0000-00-00' and b.kohdpvm = '0000-00-00' and
				  			a.asiakas_tunnus = b.asiakas_tunnus and
				  			a.summa < 0 and a.summa = -1 * b.summa";
	$paaresult = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($paaresult) > 0) {
		while ($suoritusrow=mysql_fetch_array ($paaresult)) {
			//Onko tilioinnit veilä olemassa ja suoritus oikeassa tilassa
			$query="SELECT tunnus, kirjpvm from suoritus where tunnus in ('$suoritusrow[atunnus]', '$suoritusrow[btunnus]') and kohdpvm = '0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 2) {		
				$suoritus1row=mysql_fetch_array ($result);
				$suoritus2row=mysql_fetch_array ($result);
				$query="SELECT ltunnus, summa, tilino from tiliointi where tunnus='$suoritusrow[altunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) == 1) {
					$tiliointi1row=mysql_fetch_array ($result);
					$query="SELECT ltunnus, summa, tilino from tiliointi where tunnus='$suoritusrow[bltunnus]'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) == 1) {
						$tiliointi2row=mysql_fetch_array ($result);
						$tapvm = $suoritus1row['kirjpvm'];
						if ($suoritus1row['kirjpvm'] < $suoritus2row['kirjpvm']) $tapvm = $suoritus2row['kirjpvm'];
						// Nyt kaikki on hyvin ja voimme tehdä päivitykset
						// Kirjataan päittäinmeno selvittelytilin kautta
						// Tiliöinniltä otetaan selvittelytilin vastatili
						$query="INSERT tiliointi (yhtio, ltunnus, tapvm, summa, tilino, selite, lukko, laatija, laadittu) values ('$kukarow[yhtio]', '$tiliointi1row[ltunnus]', '$tapvm', $tiliointi1row[summa], '$yhtiorow[selvittelytili]', '".t('Suoritettu päittäin')."',1,'$kukarow[kuka]',now())";
						if ($debug==1) echo "$query<br>"; else $result = mysql_query($query) or pupe_error($query);
						$query="INSERT tiliointi (yhtio, ltunnus, tapvm, summa, tilino, selite, lukko, laatija, laadittu) values ('$kukarow[yhtio]', '$tiliointi1row[ltunnus]', '$tapvm', $tiliointi1row[summa] * -1, '$tiliointi1row[tilino]', '".t('Suoritettu päittäin')."',1,'$kukarow[kuka]',now())";
						if ($debug==1) echo "$query<br>"; else $result = mysql_query($query) or pupe_error($query);
						
						$query="INSERT tiliointi (yhtio, ltunnus, tapvm, summa, tilino, selite, lukko, laatija, laadittu) values ('$kukarow[yhtio]', '$tiliointi2row[ltunnus]', '$tapvm', $tiliointi2row[summa], '$yhtiorow[selvittelytili]', '".t('Suoritettu päittäin')."',1,'$kukarow[kuka]',now())";
						if ($debug==1) echo "$query<br>"; else $result = mysql_query($query) or pupe_error($query);
						$query="INSERT tiliointi (yhtio, ltunnus, tapvm, summa, tilino, selite, lukko, laatija, laadittu) values ('$kukarow[yhtio]', '$tiliointi2row[ltunnus]', '$tapvm', $tiliointi2row[summa] * -1, '$tiliointi1row[tilino]', '".t('Suoritettu päittäin')."', 1,'$kukarow[kuka]',now())";
						if ($debug==1) echo "$query<br>"; else $result = mysql_query($query) or pupe_error($query);
						
						//Kirjataan suoritukset käytetyksi
						$query="UPDATE suoritus set kohdpvm = '$tapvm', summa=0 where tunnus='$suoritus1row[tunnus]'";
						if ($debug==1) echo "$query<br>"; else $result = mysql_query($query) or pupe_error($query);
						$query="UPDATE suoritus set kohdpvm = '$tapvm', summa=0 where tunnus='$suoritus2row[tunnus]'";
						if ($debug==1) echo "$query<br>"; else $result = mysql_query($query) or pupe_error($query);
					}
					else {
						echo "Järjestelmävirhe 1";
					}
				}
				else {
					echo "Järjestelmävirhe 2";
				}
			}
			else {
				echo "<font class='message'>" . t('Suoritus oli jo käytetty') . "<br>";
			}
		}
	}
	$tee = '';
	echo "<font class='message'>".t("Kohdistus on ok!")."</font><br>";
}

if ($tee=='') {
//Etsitään päittäin menevät suoritukset
	$query = "	SELECT a.tunnus, a.kirjpvm, a.summa,
					    b.tunnus, b.kirjpvm, b.summa
				  		FROM suoritus a, suoritus b
				  		WHERE a.yhtio='$kukarow[yhtio]' and b.yhtio='$kukarow[yhtio]' and
				  			a.kohdpvm = '0000-00-00' and b.kohdpvm = '0000-00-00' and
				  			a.asiakas_tunnus = b.asiakas_tunnus and
				  			a.summa < 0 and a.summa = -1 * b.summa";


	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) > 0) {
		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";
		while ($trow=mysql_fetch_array ($result)) {

			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($result); $i++) {
				echo "<td>$trow[$i]</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
		echo "<form action = '$php_self' method='post'>
					<input type='hidden' name = 'tee' value='T'>
					<input type='Submit' value='".t('Kohdista nämä tapahtumat päittäin')."'>
					</form>";
	}
	else {
		echo "<font class='message'>" . t("Sopivia suorituksia ei löytynyt. Kaikki hyvin!") . "</font><br>";
	}
}		
		
require "../inc/footer.inc";

?>
