<?php
// Kirjoitetaan lasku
		$selite = str_replace ("'", " ", $vientiselite); // Poistaa SQL-virheen mahdollisuuden
		$tpv = substr($pvm,0,2);
		$tpk = substr($pvm,2,2);
		$tpp = substr($pvm,4,2);
		if ($tpv < 1000) $tpv += 2000;
		
		$query = "INSERT into lasku set
                                yhtio = '$yritirow[yhtio]',
								nimi = 'Kulu $yritirow[nimi] $maksaa',
                                hyvak1 = '$yritirow[hyvak]',
                                hyvaksyja_nyt = '$yritirow[hyvak]',
                                tapvm = '$tpv-$tpk-$tpp',
                                tila = 'X',
                                laatija = 'tiliote',
                                luontiaika = now()";

		$result = mysql_query($query) or pupe_error($query);
		$kulutunnus = mysql_insert_id ($link);

		$query = "INSERT into tiliointi set
                                yhtio = '$yritirow[yhtio]',
                                ltunnus = '$kulutunnus',
                                tilino = '$yritirow[oletus_rahatili]',
                                kustp = '',
                                tapvm = '$tpv-$tpk-$tpp',
                                summa = '$maara',
                                vero = '0',
                                lukko = '',
								selite = '$selite',
                                laatija = 'tiliote',
                                laadittu = now()";
		$result = mysql_query($query) or pupe_error($query);
		$vasumma = $maara * -1;

		$query = "INSERT into tiliointi set
                                yhtio = '$yritirow[yhtio]',
                                ltunnus = '$kulutunnus',
                                tilino = '$yritirow[oletus_kulutili]',
                                kustp = '$yritirow[oletus_kustp]',
                                kohde = '$yritirow[oletus_kohde]',
                                projekti = '$yritirow[oletus_projekti]',
                                tapvm = '$tpv-$tpk-$tpp',
                                summa = '$vasumma',
                                vero = '0',
                                lukko = '',
								selite = '$selite',
                                laatija = 'tiliote',
                                laadittu = now()";
		$result = mysql_query($query) or pupe_error($query);
        $query= "UPDATE tiliotedata SET kasitelty = now(), tiliointitunnus=" . mysql_insert_id ($link) . " WHERE tunnus='$tiliotedataid'";
		$dummyresult = mysql_query($query) or pupe_error($query);
		// Merkataan käsitellyiksi & lisätään linkki
		echo "<font class='message'>*** ".t("tosite luotu")." ***</font><br>"
?>
