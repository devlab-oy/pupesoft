<?php

	$query = "SELECT tapvm FROM lasku WHERE tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<b>".t("Lasku katosi")."</b><br>";
		exit;
	}
	$laskurow=mysql_fetch_array($result);

	$summa = round($summa,2);

	if ($vero != 0) { // Netotetaan alvi
		$alv = round($summa - $summa / (1 + ($vero / 100)),2);
		$summa -= $alv;
	}
	if (($kpexport!=1) and (strtoupper($yhtiorow['maa']) == 'FI')) $tositenro=0; // Jos tätä ei tarvita

	// jos on valittu tiliointipvm popupista
	if ($tiliointipvm != ''){
		$laskurow['tapvm']=$tiliointipvm;
	}

	$query = "INSERT into tiliointi set
					yhtio ='$kukarow[yhtio]',
					ltunnus = '$tunnus',
					tilino = '$tili',
					kustp = '$kustp',
					kohde = '$kohde',
					projekti = '$projekti',
					tapvm = '$laskurow[tapvm]',
					summa = '$summa',
					vero = '$vero',
					selite = '$selite',
					lukko = '',
					tosite = '$tositenro',
					laatija = '$kukarow[kuka]',
					laadittu = now()";
	$result = mysql_query($query) or pupe_error($query);
	$isa = mysql_insert_id(); // Näin löydämme tähän liittyvät alvit....

	if ($vero != 0) { // Tiliöidään alv
	        $query = "INSERT into tiliointi set
				yhtio ='$kukarow[yhtio]',
				ltunnus = '$tunnus',
				tilino = '$yhtiorow[alv]',
				kustp = '',
				kohde = '',
				projekti = '',
				tapvm = '$laskurow[tapvm]',
				summa = '$alv',
				vero = '',
				selite = '$selite',
				lukko = '1',
				laatija = '$kukarow[kuka]',
				laadittu = now(),
				aputunnus = $isa";
		$result = mysql_query($query) or pupe_error($query);
	}
?>
