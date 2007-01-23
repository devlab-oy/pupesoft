<?php

// käytetään slavea jos sellanen on
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Laskutuksen tiliöinnit")."</font><hr>";

// tutkaillaan saadut muuttujat

$pp 	= sprintf("%02d", trim($pp));
$kk 	= sprintf("%02d", trim($kk));
$vv 	= sprintf("%04d", trim($vv));

$pp1 	= sprintf("%02d", trim($pp1));
$kk1 	= sprintf("%02d", trim($kk1));
$vv1 	= sprintf("%04d", trim($vv1));

if ($osasto == "") $osasto = trim($osasto2);
if ($try    == "")    $try = trim($try2);

// härski oikeellisuustzekki
if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";
if ($pp1 == "00" or $kk1 == "00" or $vv1 == "0000") $tee = $pp1 = $kk1 = $vv1 = "";
// piirrellään formi
echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
echo "<table>";
echo "<tr>";
echo "<th>".t('Syötä alku pp-kk-vvvv')."</th>";
echo "<td><input type='text' name='pp' size='5' value='$pp'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='vv' size='7' value='$vv'></td>";
echo "</tr>";
echo "<tr>";
echo "<th>".t('Syötä loppu pp-kk-vvvv')."</th>";
echo "<td><input type='text' name='pp1' size='5' value='$pp1'><input type='text' name='kk1' size='5' value='$kk1'><input type='text' name='vv1' size='7' value='$vv1'></td>";
echo "</tr>";
echo "</table>";

echo "<br>";
echo "<input type='hidden' name='tee' value='tee'>";
echo "<input type='submit' value='".t('Näytä tiliöinnit')."'>";
echo "</form>";

if ($tee == "tee") {
	
	// haetaan halutut tiliöinnit
	$query  = "	SELECT  concat_ws('/',t.tilino,ti.nimi) tili,
				concat_ws('/',t.kustp,k.nimi) kustp,
				concat_ws('/',t.kohde,ko.nimi) kohde,
				concat_ws('/',t.projekti,p.nimi) projekti,
				sum(t.summa) summa, count(*) kpl
				FROM lasku l
				JOIN tiliointi t on l.yhtio=t.yhtio and l.tunnus=t.ltunnus and l.tapvm=t.tapvm and korjattu=''
				LEFT JOIN tili ti on l.yhtio=ti.yhtio and t.tilino=ti.tilino
				LEFT JOIN kustannuspaikka k on l.yhtio=k.yhtio and t.kustp=k.tunnus
				LEFT JOIN kustannuspaikka ko on l.yhtio=ko.yhtio and t.kohde=ko.tunnus
				LEFT JOIN kustannuspaikka p on l.yhtio=p.yhtio and t.projekti=p.tunnus
				WHERE l.yhtio = '$kukarow[yhtio]' and l.tapvm >= '$vv-$kk-$pp' and
						l.tapvm <= '$vv1-$kk1-$pp1' and l.tila='U'
				GROUP BY 1,2,3,4";
	$result = mysql_query($query) or pupe_error($query);
	echo "<table>";
	echo "<tr>";
	for ($i = 0; $i < mysql_num_fields($result); $i++) {
		echo "<th>" . t(mysql_field_name($result,$i)) ."</th>";
	}	
	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "<td>$trow[$i]</td>";
		}

		echo "</tr>";
	}
	echo "</table>";
}
require ("../inc/footer.inc");

?>
