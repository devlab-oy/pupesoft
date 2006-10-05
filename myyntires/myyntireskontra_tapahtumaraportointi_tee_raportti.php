<?php

// estet‰‰n sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

$nimi = "P‰‰kirja";
$alku = "CONCAT_WS(' ', tiliointi.tilino, tili.nimi) Tili, tiliointi.tapvm";
$jarj = "tiliointi.tilino, tiliointi.tapvm";

if ($tee == 'P') { // Haluttiinkin p‰iv‰kirja
  $nimi = "P‰iv‰kirja";
  $alku = "tiliointi.tapvm, CONCAT_WS(' ', tiliointi.tilino, tili.nimi) Tili";
  $jarj = "tiliointi.tapvm, tiliointi.tilino";
}

if ($alvk == 0) {
  echo "$nimi vuodelta $alvv<hr>";
  $lisa = "YEAR(tiliointi.tapvm) = '$alvv'";
}
else {
  if ($alvp == 0) {
    echo "$nimi kaudelta $alvv-$alvk<hr>";
    $lisa = "CONCAT_WS(' ', YEAR(tiliointi.tapvm),MONTH(tiliointi.tapvm)) = '$alvv $alvk'";
  }
  else {
    echo "<b>$nimi p‰iv‰lt‰ $alvv-$alvk-$alvp</b><hr>";
    $lisa = "tiliointi.tapvm = '$alvv-$alvk-$alvp'";
  }
}
if (strlen(trim($kohde)) > 0) {
  $lisa .= " and kohde = '" . $kohde . "'";
}
if (strlen(trim($proj)) > 0) {
  $lisa .= " and projekti = '" . $proj . "'";
}
if (strlen(trim($kustp)) > 0) {
  $lisa .= " and kustp = '" . $kustp . "'";
}
if (strlen(trim($proj)) > 0) {
  $lisa .= " and projekti = '" . $proj . "'";
}
if (strlen(trim($tili)) > 0) {
  $lisa .= " and tiliointi.tilino = '" . $tili . "'";
}

$haku_sql= "";
if($asiakas!='') {
  $haku_sql = " AND (lasku.ytunnus LIKE '%$asiakas%' OR lasku.nimi LIKE '%$asiakas%')";
}
$query = "SELECT DISTINCT $alku , kustp, kohde, projekti,
					CONCAT_WS(' ', selite, lasku.nimi) selite,  tiliointi.summa, vero, tiliointi.ltunnus, tiliointi.tunnus
				FROM tiliointi, tili, yriti,lasku, yhtio
				WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
                                        yhtio.yhtio='$kukarow[yhtio]' and
					tili.yhtio = '$kukarow[yhtio]' and
					tiliointi.tilino = tili.tilino and
					tiliointi.korjattu='' and
                                        yriti.yhtio=tili.yhtio AND
                                        lasku.tunnus=tiliointi.ltunnus AND
                                        lasku.yhtio='$kukarow[yhtio]' AND
                                        (yriti.oletus_rahatili=tili.tilino OR tili.tilino=yhtio.myyntisaamiset OR
                                         tili.tilino=yhtio.kassaale OR tili.tilino=yhtio.pyoristys) AND
					$lisa $haku_sql
				ORDER BY $jarj";


//echo "<font class='head'>query: $query</font>";
$result = mysql_query($query) or pupe_error($query);

echo "<table><tr>";
for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
  echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
}
while ($trow=mysql_fetch_array ($result)) {
  echo "<tr>";
  for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
    if ($i == 0) {
      if ($excel=='') { // Jos halutaan excel muotoa niin ei tehd‰ t‰t‰..

	if ($edtrow[$i] == $trow[$i]) { // Vaihtuiko joku??
	  $trow[$i] = "&nbsp";
	}
	else {
	  if ($eka != 0) {
	    echo "<td></td><td></td><td></td><td></td><td></td><td></td><td align = 'right'>";
	    printf("%.2f", $summa);
	    echo "</td><td>*</td></tr><tr>";
	    $summa = 0;
	  }
	  else {
	    $eka = 1;
	  }
	  $edtrow[0] = $trow[0];
	}
      }

    }

    if (($trow[$i]==$trow['tapvm']) and ($excel!=''))
      {
	$trow[$i]=substr($trow[$i],8,2).".".substr($trow[$i],5,2).".".substr($trow[$i],0,4);
      }

    if ($i > 5) {
      if ($i == 6) {
	echo "<td align = 'right'>";

	if($excel=='') { // tehd‰‰n linkki jos ei haluta excel muodossa
	  if($trow[8]!=0) {
	    echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[8]'>";
	    echo str_replace(".",",",sprintf("%.2f", $trow[$i]));
	    //printf("%.2f", $trow[$i]);
	    echo "</a>";
	  } else {
	    echo str_replace(".",",",sprintf("%.2f", $trow[$i]));
	  }
	}
	else {
	  echo str_replace(".",",",sprintf("%.2f", $trow[$i]));
	}
	echo "</td>";
	$summa += $trow[$i];
      }
      else {
	echo "<td align = 'right'>";
	echo str_replace(".",",",sprintf("%.2f", $trow[$i]));
	//printf("%.2f", $trow[$i]);
	echo "</td>";
      }

    }
    else {
      // Selv‰kieliset tarkenteet
      if (($i > 1) and ($i < 5) and ($trow[$i] > 0)) {
	$query = "SELECT nimi
								FROM kustannuspaikka WHERE tunnus = '$trow[$i]'";
	$vresult = mysql_query($query) or pupe_error($query);
	$vrow=mysql_fetch_array($vresult);
	$trow[$i] = $vrow[0];
      }
      echo "<td>$trow[$i]</td>";
    }
  }
  echo "</tr>";
}

if ($excel=='') { // Jos halutaan excel muotoa niin ei tehd‰ t‰t‰..
  echo "<tr><td></td><td></td><td></td><td></td><td></td><td align = 'right'>Yhteens‰¬§</td>";
  echo "<td align = 'right'>";
  printf("%.2f", $summa);
  echo "</td><td>*</td></tr>";
}

echo "</table><br>";

?>