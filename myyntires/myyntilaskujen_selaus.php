<?php
	$useslave=1;
	include "../inc/parametrit.inc";

	echo "<font class='head'>".t("Avointen myyntilaskujen selaus")."</font><hr>";

	$tila = '';
	$kentat = 'laskunro, nimi, nimitark, erpcm, summa, viite, tunnus';
        $kentankoko = array(10,30,10,10,10,10,15);
	$array = split(",", $kentat);
	$count = count($array);
	for ($i=0; $i<=$count; $i++) {
			// tarkastetaan onko hakukentässä jotakin
			if (strlen($haku[$i]) > 0) {
				$lisa .= " and l." . $array[$i] . " like '%" . $haku[$i] . "%'";
				$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
			}
	}
	if (strlen($ojarj) > 0) {
		$jarjestys = $array[$ojarj];
	}
	else{
		$jarjestys = 'erpcm';
	}


// Myyntilaskuissa tila=U, kun lasku on laskutettu, X, jos lasku on lähetetty verkkolaskuna
// Alatila on A kun lasku on laskuttamatta.
	$maxrows = 200;
	$query = "SELECT COALESCE(l.laskunro,'-') laskunro, l.nimi nimi, l.nimitark nimitark, l.erpcm erpcm, l.summa summa, l.viite viite, l.tunnus tunnus, ytunnus
			FROM lasku l WHERE l.yhtio ='$kukarow[yhtio]' and l.tila = 'U' and l.mapvm='0000-00-00' $lisa
			 ORDER BY $jarjestys LIMIT $maxrows";
// Mikä on maksaja?


$result = mysql_query($query) or pupe_error($query);

 	echo "<form action='$PHP_SELF?tila=$tila' method='post'>";

        echo "<table><tr>";

        for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
                	echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=".$i.$ulisa."'>" . t(mysql_field_name($result,$i))."</a></th>";
        }

	echo "<th></th></tr>";
	echo "<tr>";

        for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<td><input type='text' size='$kentankoko[$i]' name='haku[$i]' value = '$haku[$i]'></td>";
        }
	echo "<td><input type='submit' value='".t("Etsi")."'></td></tr>";

        $row = 0;
        while ($maksurow=mysql_fetch_array ($result)) {

	  for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
	    if (mysql_field_name($result,$i) == 'laskunro') {
	      $tunnus = $maksurow[mysql_num_fields($result)-2];
	      echo "<td><a href=\"../muutosite.php?tee=E&tunnus=$tunnus\">$maksurow[$i]</a></td>";
	    } elseif (mysql_field_name($result,$i) == 'nimi') {
	      /* linkki CRM:aan */
	      echo "<td><a href=\"../crm/asiakasmemo.php?ytunnus=$maksurow[ytunnus]\">$maksurow[$i]</a></td>";
	    } else {
	      echo "<td>$maksurow[$i]</td>";
	    }
	  }
	  $asiakas_tunnus=$maksurow[mysql_num_fields($result)-2];
	  /*
	  if(isset($asiakas_tunnus)) {
	  echo "<td><a href=\"manuaalinen_suoritusten_kohdistus.php?tila=suorituksenvalinta&tunnus=$asiakas_tunnus\">Kohdista</a></td></tr>";
	  } else
	  */
	  echo "<td class='back'></td></tr>";
	  $row++;
	}

	echo "</tr></table></form>";
	if($row >= $maxrows) {
		echo "".t("Kysely on liian iso esitettäväksi, ainoastaan ensimmäiset")." $row ".t("riviä on näkyvillä. Ole hyvä, ja rajaa hakuehtoja").".";
	}

	//echo "Query: ". $query;
        include "../inc/footer.inc";

echo "<script LANGUAGE='JavaScript'>document.forms[0][0].focus()</script>";


?>
