<?php

	if ($tee == '') {
			echo "<font class='head'>".t("Arvonlis‰verolaskelma")."</font><hr>";

			echo "<form action = 'raportit.php' method='post'>
					<input type = 'hidden' name = 'toim' value = 'alv'>
					<input type = 'hidden' name = 'tee' value = '1'>
					<table><tr>
					<td>".t("Arvonlis‰laskelma")."</td>
					<td><select name='alvv'>
					";

			for ($i = date("Y"); $i >= date("Y")-4; $i--) {
				if ($i == date("Y")) $sel = "selected";
				else $sel = "";
				echo "<option value='$i' $sel>$i</option>";
			}

			echo "
					</select>
					<select name='alvk'>
					<option value = '01'>01
					<option value = '02'>02
					<option value = '03'>03
					<option value = '04'>04
					<option value = '05'>05
					<option value = '06'>06
					<option value = '07'>07
					<option value = '08'>08
					<option value = '09'>09
					<option value = '10'>10
					<option value = '11'>11
					<option value = '12'>12</select>
					</td>
					<td>".t("Ruotsimoodi:")."</td>
					<td><input type = 'checkbox' name = 'ruotsi'></td>
					<td><input type = 'submit' value = '".t("N‰yt‰")."'></td>
					</tr></table></form>";
			echo "<form action = 'raportit.php' method='post'>
					<input type = 'hidden' name = 'toim' value = 'alv'>
					<input type = 'hidden' name = 'tee' value = '2'>
					<table><tr>
					<td>".t("Alv-tarkistus")."</td>
					<td><select name='alvv'>
					";

			for ($i = date("Y"); $i >= date("Y")-4; $i--) {
				if ($i == date("Y")) $sel = "selected";
				else $sel = "";
				echo "<option value='$i' $sel>$i</option>";
			}

			echo "
					</select>
					<select name='alvk'>
					<option value = '01'>01
					<option value = '02'>02
					<option value = '03'>03
					<option value = '04'>04
					<option value = '05'>05
					<option value = '06'>06
					<option value = '07'>07
					<option value = '08'>08
					<option value = '09'>09
					<option value = '10'>10
					<option value = '11'>11
					<option value = '12'>12</select>
					</td>
					<td><input type = 'submit' value = '".t("N‰yt‰")."'></td>
					</tr></table></form>";

			require ("inc/footer.inc");
			exit;
	}

	if ($tee == 1) {

		$lokk = $alvk + 1;
		$lovv = $alvv;

		if ($lokk > 12) {
			$lokk -= 12;
			$lovv++;
		}
		$alloppu = $lovv . "-" . $lokk . "-01";

		echo "<font class='head'>".t("Arvonlis‰verolaskelma kaudelta")." $alvv-$alvk</font><hr>";

		if ($ruotsi != 'on') {
			
			// Myynti_eu tilit
			$eu_tilit = "'".$yhtiorow["myynti_eu"]."'";
				
			$query = "SELECT group_concat(distinct concat(\"'\",tilino_eu,\"'\")) tilino_eu from asiakas where yhtio='$kukarow[yhtio]' and tilino_eu != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi1 = mysql_fetch_array($res);
			
			if ($rivi1["tilino_eu"] != "") {
				$eu_tilit .= ",".$rivi1["tilino_eu"];
			}
			
			$query = "SELECT group_concat(distinct concat(\"'\",tilino_eu,\"'\")) tilino_eu from tuote where yhtio='$kukarow[yhtio]' and tilino_eu != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi2 = mysql_fetch_array($res);
			
			if ($rivi2["tilino_eu"] != "") {
				$eu_tilit .= ",".$rivi2["tilino_eu"];
			}
			
			$query = "SELECT group_concat(distinct concat(\"'\",tilino_eu,\"'\")) tilino_eu from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and tilino_eu != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi2 = mysql_fetch_array($res);
			
			if ($rivi2["tilino_eu"] != "") {
				$eu_tilit .= ",".$rivi2["tilino_eu"];
			}

			$query = "SELECT group_concat(distinct concat(\"'\",tilino_eu,\"'\")) tilino_eu from tuotteen_alv where yhtio='$kukarow[yhtio]' and tilino_eu != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi2 = mysql_fetch_array($res);
			
			if ($rivi2["tilino_eu"] != "") {
				$eu_tilit .= ",".$rivi2["tilino_eu"];
			}


			// Osto_eu tilit		
			$eu_ostotilit = "'".$yhtiorow["osto_eu"]."'";

			$query = "SELECT group_concat(distinct concat(\"'\",tilino,\"'\")) tilino_eu from toimi where yhtio='$kukarow[yhtio]' and tilino != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi2 = mysql_fetch_array($res);
			
			if ($rivi2["tilino_eu"] != "") {
				$eu_ostotilit .= ",".$rivi2["tilino_eu"];
			}
			
			$query = "	(SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa, '".t("myyntireskontra")."' Tyyppi, Vero,
 						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tila = 'U')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
						tiliointi.korjattu = '' and
						tiliointi.tapvm >= '$alvv-$alvk-01' and
						tiliointi.tapvm < '$alloppu' and
						tiliointi.vero > 0
						group by 1, 2, 3)
						UNION
						(SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa, '".t("muu")."' Tyyppi, Vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila = 'X')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
						tiliointi.korjattu = '' and
						tiliointi.tapvm >= '$alvv-$alvk-01' and
						tiliointi.tapvm < '$alloppu' and
						tiliointi.vero > 0
						group by 1, 2, 3)
						UNION
						(SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa, '".t("ostoreskontra")."' Tyyppi, Vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila in ('H','Y','M','P','Q'))
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
						tiliointi.korjattu = '' and
						tiliointi.tapvm >= '$alvv-$alvk-01' and
						tiliointi.tapvm < '$alloppu' and
						tiliointi.vero > 0
						group by 1, 2, 3)
						UNION
						(SELECT '' Maa, concat('".t("yhteisˆmyynnit")."',' ','".t("tili")."',': ',tiliointi.tilino,' ',tili.nimi) Tyyppi, Vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila='U' and lasku.vienti = 'E')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tiliointi.korjattu = ''
						and tiliointi.tilino in ($eu_tilit)
						and tiliointi.tapvm >= '$alvv-$alvk-01'
						and tiliointi.tapvm < '$alloppu'
						and tiliointi.vero = 0
						group by 1, 2, 3)
						UNION
						(SELECT '' Maa, concat('".t("yhteisˆostot")."',' ','".t("tili")."',': ',tiliointi.tilino,' ',tili.nimi) Tyyppi, Vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila in ('H','Y','M','P','Q') and vienti = 'F')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tiliointi.korjattu = ''
						and tiliointi.tilino in ($eu_ostotilit)
						and tiliointi.tapvm >= '$alvv-$alvk-01'
						and tiliointi.tapvm < '$alloppu'
						and tiliointi.vero = 0
						group by 1, 2, 3)
						order by 2, 3, 1";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
			}
			echo "</tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>					
					<td>$trow[Maa]</td>
					<td>$trow[Tyyppi]</td>
					<td align = 'right'>".(float) $trow["Vero"]."%</td>					
					<td align = 'right'>".$trow["Verollinen summa"]."</td>
					<td align = 'right'>$trow[Verot]</td>
					<td align = 'right'>$trow[Kpl]</td></tr>";

			}
			echo "</table><br><br>";
		}

		echo t("K‰siviennit")."<br>";

		$query = "	SELECT ltunnus, tiliointi.tapvm, tiliointi.summa, concat_ws(' ',selite, lasku.nimi) selite
					FROM tiliointi
					JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = '' and
					tiliointi.aputunnus = '' and
					tiliointi.tapvm >= '$alvv-$alvk-01' and
					tiliointi.tapvm < '$alloppu' and
					tiliointi.tilino = '$yhtiorow[alv]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}

		while ($trow = mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				if ($i == 0) {
					if ($kukarow['taso'] < 2) {
						echo "<td>$trow[tapvm]</td>";
					}
					else {
						echo "<td>";
						echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'>";
						echo "$trow[$i]</td>";
					}
				} else {
					echo "<td>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table><br><br>";

		
		if ($ruotsi != 'on') {
			$verolisa = " tiliointi.vero ";
		}
		else {
			$verolisa = " concat_ws(' ',vero, if(lasku.tila='U','".t("ulos")."','".t("sis‰‰n")."')) vero ";
		}
		
		$query = "	SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa, if(lasku.valkoodi='', '$yhtiorow[valkoodi]', lasku.valkoodi) Valuutta, $verolisa, tiliointi.tilino, tili.nimi, 
					round(sum(tiliointi.summa * (1+tiliointi.vero/100)),2) Bruttosumma,
					round(sum(tiliointi.summa * tiliointi.vero/100),2) Verot, 
					round(sum(tiliointi.summa/if(lasku.vienti_kurssi=0,1,lasku.vienti_kurssi) * (1+vero/100)),2) Bruttosumma_valuutassa,
					round(sum(tiliointi.summa/if(lasku.vienti_kurssi=0,1,lasku.vienti_kurssi) * vero/100),2) Verot_valuutassa,
					count(*) Kpl
					FROM tiliointi
					JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
					LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					and tiliointi.korjattu = ''
					and tiliointi.tapvm >= '$alvv-$alvk-01'
					and tiliointi.tapvm < '$alloppu'
					and tiliointi.vero 	> 0
					GROUP BY 1,2,3,4,5
					ORDER BY 1,2,3,4,5";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		echo "<th valign='top'>" . t("Maa") . "</th>";
		echo "<th valign='top'>" . t("Val") . "</th>";
		echo "<th valign='top'>" . t("Vero") . "</th>";
		echo "<th valign='top'>" . t("Tili") . "</th>";
		echo "<th valign='top'>" . t("Nimi") . "</th>";
		echo "<th valign='top'>" . t("Verollinen summa") . "</th>";
		echo "<th valign='top'>" . t("Verot") . "</th>";		
		echo "<th valign='top'>" . t("Verollinen summa valuutassa") . "</th>";
		echo "<th valign='top'>" . t("Verot valuutassa") . "</th>";		
		echo "<th valign='top'>" . t("Kpl") . "</th>";
		echo "</tr>";
		
		while ($trow = mysql_fetch_array ($result)) {
						
			if (isset($edvero) and ($edvero != $trow["vero"] or $edmaa != $trow["Maa"])) { // Vaihtuiko verokanta?
				echo "<tr><td colspan='6' align='right'>".t("Yhteens‰").":</td><td align = 'right'>$verosum</td><td colspan='2'></td><td align = 'right'>$kplsum</td></tr>";
				
				$verosum 	= 0.0;
				$kplsum 	= 0.0;				
			}
						
			echo "<tr>";
			echo "<td valign='top'>$trow[Maa]</td>";
			echo "<td valign='top'>$trow[Valuutta]</td>";
			echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
			echo "<td valign='top'>$trow[tilino]</td>";
			echo "<td valign='top'>$trow[nimi]</td>";
			echo "<td valign='top' align='right' nowrap>$trow[Bruttosumma]</td>";
			echo "<td valign='top' align='right' nowrap>$trow[Verot]</td>";
			
			if (strtoupper($trow["Maa"]) != strtoupper($yhtiorow["maa"])) {
				echo "<td valign='top' align='right' nowrap>$trow[Bruttosumma_valuutassa]</td>";
				echo "<td valign='top' align='right' nowrap>$trow[Verot_valuutassa]</td>";
			}
			else {
				echo "<td valign='top' align='right'></td>";
				echo "<td valign='top' align='right'></td>";	
			}
			
			echo "<td valign='top' align='right' nowrap>$trow[Kpl]</td>";
			echo "</tr>";
									
			$verosum += $trow['Verot'];
			$kplsum  += $trow['Kpl'];
			$verotot += $trow['Verot'];
			$kpltot  += $trow['Kpl'];
															
			$edvero = $trow["vero"];
			$edmaa 	= $trow["Maa"];
		}
		
		echo "<tr><td colspan='6' align='right'>".t("Yhteens‰").":</td><td align = 'right'>$verosum</td><td colspan='2'></td><td align = 'right'>$kplsum</td></tr>";
		echo "<tr><td colspan='6' align='right'>".t("Kaikki yhteens‰").":</td><td align = 'right'>$verotot</td><td colspan='2'></td><td align = 'right'>$kpltot</td></tr>";
		echo "</table><br>";
	}

	if ($tee == '2') {
		echo "<font class='head'>".t("Alv-tarkistus kaudelta")." $alvv-$alvk</font><hr>";
		echo "".t("Tarkistetaan automaattiviennit")."<br>";

		$query = "	SELECT tunnus, ltunnus, tapvm, summa, selite, round(summa * vero / 100 ,2) Verot
					FROM tiliointi
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = '' and
					left(tapvm,7) = '" . $alvv  . "-" . $alvk . "' and
					vero > 0 and
					selite <> 'Myyntikirja'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}
		// Tilaa errorille
		echo "<th>".t("Virhe")."</th></tr>";

		while ($trow=mysql_fetch_array ($result)) {
			// Etsit‰‰n vastaava automaattitiliˆinti
			$query = "	SELECT summa, ltunnus
						FROM tiliointi
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
						tiliointi.korjattu = '' and
						aputunnus = '$trow[0]'";
			$xresult = mysql_query($query) or pupe_error($query);

			$error='';

			if (mysql_num_rows($xresult) != 1) {
				$error = t("Vastaavaa automaattivienti‰ ei ole tai niit‰ on liikaa");
			}
			else {
				$xrow=mysql_fetch_array ($xresult);

				$heitto = $xrow["summa"] - $trow["Verot"];
				$heitto = abs($heitto);

				if ($heitto > 0.02) {
					$error = " ".t("Vienti heitt‰‰")." $heitto";
				}
			}
			if ($error != '') {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					if ($i == 0) {
						if ($kukarow['taso'] < 2) {
							echo "<td>$trow[tapvm]</td>";
						}
						else {
							echo "<td>";
							echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'>";
							echo "$trow[$i]</td>";
						}
					} else {
						echo "<td>$trow[$i]</td>";
					}
				}
				echo "<td>$error</td></tr>";
			}
		}
		echo "</table><br>Done!<br><br>";

		echo "".t("Tarkistetaan ep‰ilytt‰v‰t k‰siviennit")."<br>";
		$query = "	SELECT ltunnus, tapvm, summa, selite
					FROM tiliointi
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = '' and
					aputunnus = '' and
					left(tapvm,7) = '" . $alvv  . "-" . $alvk . "' and
					selite != 'Myyntikirja' and
					tilino = '$yhtiorow[alv]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				if ($i == 0) {
					if ($kukarow['taso'] < 2) {
						echo "<td>$trow[tapvm]</td>";
					}
					else {
						echo "<td>";
						echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'>";
						echo "$trow[$i]</td>";
					}
				} else {
					echo "<td>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table><br>Done!<br><br>";
	}
?>
