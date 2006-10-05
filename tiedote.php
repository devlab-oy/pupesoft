<?php
	require("inc/parametrit.inc");

	print "<font class='head'>Tiedotteet: $id</font><hr>";
	print "<table width='700' align='left' border='0' cellpadding='2' cellspacing='1'>";

	//Lisätään rivi
	if ($tee == 'LISAA') {
		$today = date('Y-m-d H:i:s');
		if (strlen($teksti) > 0) {
			if (is_uploaded_file($userfile) == TRUE) {
				$filenimi=$userfile_name;
				if($userfile_size > 0) {
					move_uploaded_file($userfile, 'liitteet/'.$filenimi);
				}
				else {
					echo "Tiedosto on tyhja!<br>";
				}
			}
			else {
				echo "Tiedostoa ei liitetty!<br>";
			}
			$query = "insert into memo (kuka,tapa,date,teksti,file,yhtio)
					  values ('$kukarow[kuka]','tiedote','$today','$teksti','$filenimi', '$kukarow[yhtio]')";
			mysql_query($query) or pupe_error($query);

			$tee = '';
		}
	}

	if ($tee == "PAIVITA") { //Nyt editoidaan
		$query = "	update memo
					set teksti='$teksti'
					where id='$id' and yhtio='$kukarow[yhtio]'";
		mysql_query($query) or pupe_error($query);
		$tee = '';
	}

	if ($tee == "POISTA") { //Poistetaan
		$query = "	delete
					from memo
					where id='$id' and yhtio='$kukarow[yhtio]'";
		mysql_query($query) or pupe_error($query);
		echo "<font class='message'>Tiedote poistettu</font>";
		$tee = '';
	}

	if ($tee == "EDITOI") { //etsitaan sisalto editoitavaksi
		$queryt = "	select teksti
					from memo
					where id='$id' and yhtio='$kukarow[yhtio]'";
		$resultt = mysql_query($queryt) or pupe_error($queryt);
		$text = mysql_fetch_array($resultt);
        $text = $text[0];
		$tee = 'PAIVITA';
	}


	if($tee == '') {
		echo "	<tr>
				<form action='$PHP_SELF' method='POST'>
				<td align='left'>
				<input type='hidden' name='tee' value='LISAA'>
				<input type='submit' value='Lisaa tiedote'></form></td></tr>";
	}
	else{
		echo "	<tr>
				<form action='$PHP_SELF' enctype='multipart/form-data' method='POST'>
				<td><textarea wrap='hard' name='teksti' cols='83' rows='15'>$text</textarea></td></tr>
				<tr><td>Liite:&nbsp;&nbsp;<input type='file' name='userfile'></td></tr>
				<tr><td align='left'>
				<input type='hidden' name='tee' value='$tee'>
				<input type='hidden' name='id' value='$id'>
				<input type='submit' value='$tee'></form></td></tr>";
	}

	$query = " 	SELECT date, kuka, teksti, id, file
				FROM memo where tapa='tiedote' and yhtio='$kukarow[yhtio]'
				ORDER BY id DESC";
	$result = mysql_query($query) or pupe_error($query);

	while($tiedot = mysql_fetch_array($result)) {
		$urllink ='';
		if($kukarow["superuser"] == 'S') {
			$urllink = "<a href='$PHP_SELF?tee=EDITOI&id=$tiedot[id]'>Editoi</font></a>";
			$urllink2 = "<a href='$PHP_SELF?tee=POISTA&id=$tiedot[id]'>Poista</font></a>";
		}
		print "<tr>
				<th colspan='2' align='left'>&nbsp; Tiedottaja: $tiedot[kuka] Aika: $tiedot[date] &nbsp;
				<a href='liitteet/$tiedot[file]' target='_blank'>$tiedot[file]</a> &nbsp; $urllink &nbsp; $urllink2</th>
				</tr>
				<td colspan='2'><font class='message'><pre>$tiedot[teksti]</pre></font></td>
				</tr>";
	}
	print "</table>
		   </body>
		   </html>";
?>