<?php

require ("inc/parametrit.inc");


echo "<font class='head'>".t("Ruokalistat")."</font><hr>";

if ($tee == 'LISAA') {
	
	$kentta01 = nl2br(strip_tags($kentta01));
	$kentta02 = nl2br(strip_tags($kentta02));
	$kentta03 = nl2br(strip_tags($kentta03));
	$kentta04 = nl2br(strip_tags($kentta04));
	$kentta05 = nl2br(strip_tags($kentta05));
	
	$query = "	INSERT INTO kalenteri
				SET 
				kuka = '$kukarow[kuka]',
				tapa = 'ruokalista',
				tyyppi = 'ruokalista',
				pvmalku = '$vva-$kka-$ppa 00:00:00',
				pvmloppu = '$vvl-$kkl-$ppl 23:59:59',
				kentta01 = '$kentta01',
				kentta02 = '$kentta02',
				kentta03 = '$kentta03',
				kentta04 = '$kentta04',
				kentta05 = '$kentta05',					
				yhtio = '$kukarow[yhtio]'";
	mysql_query($query) or pupe_error($query);
	
	$tee = "";
}

if ($tee == "AMERIKA") {
	
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='AMERIKA'>";
	echo "<input type='hidden' name='viikko' value='viikko1'>";
	echo "<input type='submit' value='".t("Valitse viikko 1")."'></td>";
	echo "</form>";
	
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='AMERIKA'>";
	echo "<input type='hidden' name='viikko' value='viikko2'>";
	echo "<input type='submit' value='".t("Valitse viikko 2")."'></td>";
	echo "</form>";
	
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='AMERIKA'>";
	echo "<input type='hidden' name='viikko' value='viikko3'>";
	echo "<input type='submit' value='".t("Valitse viikko 3")."'></td>";
	echo "</form>";
	
	if ($viikko == "") {
		$viikko = "viikko1";
	}
	
	// get reviews
	ob_start();
	$retval = @readfile("http://www.lounasamerika.com/prod01.htm");
	if (false !== $retval) {
		$retval = ob_get_contents();
	}
	ob_end_clean();
	
	$page	= $retval;
	$start	= strpos($page, 'Maanantai');
	$done	= substr($page, $start);
	
	$strip  = strip_tags($done,"<td>");
	
	$strip  = str_replace("\r\n","",$strip);
	$strip  = str_replace("\n","",$strip);
	$strip  = str_replace("</td>","</td>\n",$strip);
	$strip  = str_replace("&nbsp;"," ",$strip);	
	
	
	$list	= explode("\n",$strip);
	
	echo "<table>";
	$a = 0;
	$b = 0;
	
	for ($i=0;$i<count($list);$i++) {
													
		$list[$i] = strip_tags($list[$i]);
							
		if (strtoupper(substr(trim($list[$i]),0,5)) == "MAANA" or
			strtoupper(substr(trim($list[$i]),0,5)) == "TIIST" or
			strtoupper(substr(trim($list[$i]),0,5)) == "KESKI" or
			strtoupper(substr(trim($list[$i]),0,5)) == "TORST" or
			strtoupper(substr(trim($list[$i]),0,5)) == "PERJA") {
																							
			if ($a == 0) {
				$b++;
			}
																							
			if ($a == 0 and $b == 1) {
				$viikko1alku = strip_tags(trim($list[$i]));
			}
			if ($a == 1 and $b == 1) {
				$viikko2alku = strip_tags(trim($list[$i]));
			}
			if ($a == 2 and $b == 1) {
				$viikko3alku = strip_tags(trim($list[$i]));
			}
			
			
			if ($a == 0 and $b == 5) {
					$viikko1loppu = strip_tags(trim($list[$i]));
			}
			if ($a == 1 and $b == 5) {
				$viikko2loppu = strip_tags(trim($list[$i]));
			}
			if ($a == 2 and $b == 5) {
				$viikko3loppu = strip_tags(trim($list[$i]));
			}
		}				
					
		if ($a == 0 and
			strtoupper(substr(trim($list[$i]),0,5)) != "MAANA" and
			strtoupper(substr(trim($list[$i]),0,5)) != "TIIST" and
			strtoupper(substr(trim($list[$i]),0,5)) != "KESKI" and
			strtoupper(substr(trim($list[$i]),0,5)) != "TORST" and
			strtoupper(substr(trim($list[$i]),0,5)) != "PERJA") {
			$viikko1[$b][] = strip_tags(trim($list[$i]));		
		}
		if ($a == 1 and
			strtoupper(substr(trim($list[$i]),0,5)) != "MAANA" and
			strtoupper(substr(trim($list[$i]),0,5)) != "TIIST" and
			strtoupper(substr(trim($list[$i]),0,5)) != "KESKI" and
			strtoupper(substr(trim($list[$i]),0,5)) != "TORST" and
			strtoupper(substr(trim($list[$i]),0,5)) != "PERJA") {
			$viikko2[$b][] = strip_tags(trim($list[$i]));
		}
		if ($a == 2 and
			strtoupper(substr(trim($list[$i]),0,5)) != "MAANA" and
			strtoupper(substr(trim($list[$i]),0,5)) != "TIIST" and
			strtoupper(substr(trim($list[$i]),0,5)) != "KESKI" and
			strtoupper(substr(trim($list[$i]),0,5)) != "TORST" and
			strtoupper(substr(trim($list[$i]),0,5)) != "PERJA") {
			$viikko3[$b][] = strip_tags(trim($list[$i]));			
		}
		
		$a++;
		
		if ($a == 3) {
			$a = 0;
		}
	}

	echo "<form action='$PHP_SELF' method='post'>
		<input type='hidden' name='tee' value='LISAA'>
		<table>";
	
	$alku = explode(" ", ${$viikko."alku"});	
	$alku = explode(".", $alku[1]);
	$ppa  = $alku[0];
	$kka  = $alku[1];
	$vva  = date("Y"); 

	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>";	
	
	$loppu = explode(" ", ${$viikko."loppu"});	
	$loppu = explode(".", $loppu[1]);
	$ppl  = $loppu[0];
	$kkl  = $loppu[1];
	$vvl  = date("Y");
				
	echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";

	echo "		
		<tr>
			<th>".t("Maanantai")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta01' cols='40' rows='7'>";
			
	for($b=0; $b<sizeof(${$viikko}[1]); $b++) {
		echo ${$viikko}[1][$b]."\n";
	}
			
	echo "</textarea></td>
		</tr>";
	echo "		
		<tr>
			<th>".t("Tiistai")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta02' cols='40' rows='7'>";
	
	for($b=0; $b<sizeof(${$viikko}[2]); $b++) {
		echo ${$viikko}[2][$b]."\n";
	}
	
	echo "	</textarea></td>
		</tr>";
	echo "		
		<tr>
			<th>".t("Keskiviikko")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta03' cols='40' rows='7'>";
	
	for($b=0; $b<sizeof(${$viikko}[3]); $b++) {
		echo ${$viikko}[3][$b]."\n";
	}
	
	echo "	</textarea></td>
		</tr>";
	echo "		
		<tr>
			<th>".t("Torstai")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta04' cols='40' rows='7'>";
	for($b=0; $b<sizeof(${$viikko}[4]); $b++) {
		echo ${$viikko}[4][$b]."\n";
	}
	echo "	</textarea></td>
		</tr>";
	echo "		
		<tr>
			<th>".t("Perjantai")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta05' cols='40' rows='7'>";
	
	for($b=0; $b<sizeof(${$viikko}[5]); $b++) {
		echo ${$viikko}[5][$b]."\n";
	}
	
	echo "	</textarea></td>
		</tr>
		
		</table>

		<br><input type='submit' value='".t("Syötä")."'>

		</form>";
}




if ($tee == "SYOTA") {
	if ($tunnus > 0) {
		$query  = "	select *, year(pvmalku) vva, month(pvmalku) kka, dayofmonth(pvmalku) ppa, year(pvmloppu) vvl, month(pvmloppu) kkl, dayofmonth(pvmloppu) ppl
					from kalenteri 
					where tyyppi='ruokalista' and tunnus='$tunnus' and kuka='$kukarow[kuka]' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 1) {
			$rivi   = mysql_fetch_array($result);
		
			$query  = "	delete 
						from kalenteri 
						where tyyppi='ruokalista' and tunnus='$tunnus' and kuka='$kukarow[kuka]' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			
		}
		else {
			echo "".t("VIRHE: Et voi muokata ruokalistaa")."!<br>";
		}
	}
		
	echo "<form action='$PHP_SELF' method='post'>
		<input type='hidden' name='tee' value='LISAA'>
		<table>";
		
	if (!isset($rivi["kka"])) $rivi["kka"] = date("m");
	if (!isset($rivi["vva"])) $rivi["vva"] = date("Y");
	if (!isset($rivi["ppa"])) $rivi["ppa"] = date("d");

	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$rivi[ppa]' size='3'></td>
			<td><input type='text' name='kka' value='$rivi[kka]' size='3'></td>
			<td><input type='text' name='vva' value='$rivi[vva]' size='5'></td>";	
	
	if (!isset($rivi["kkl"])) $rivi["kkl"] = date("m");
	if (!isset($rivi["vvl"])) $rivi["vvl"] = date("Y");
	if (!isset($rivi["ppl"])) $rivi["ppl"] = date("d");
				
	echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$rivi[ppl]' size='3'></td>
			<td><input type='text' name='kkl' value='$rivi[kkl]' size='3'></td>
			<td><input type='text' name='vvl' value='$rivi[vvl]' size='5'></td>";			

	$rivi['kentta01'] = strip_tags($rivi['kentta01']);
	$rivi['kentta02'] = strip_tags($rivi['kentta02']);
	$rivi['kentta03'] = strip_tags($rivi['kentta03']);
	$rivi['kentta04'] = strip_tags($rivi['kentta04']);
	$rivi['kentta05'] = strip_tags($rivi['kentta05']);

	echo "		
		<tr>
			<th>".t("Maanantai")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta01' cols='40' rows='7'>$rivi[kentta01]</textarea></td>
		</tr>";
	echo "		
		<tr>
			<th>".t("Tiistai")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta02' cols='40' rows='7'>$rivi[kentta02]</textarea></td>
		</tr>";
	echo "		
		<tr>
			<th>".t("Keskiviikko")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta03' cols='40' rows='7'>$rivi[kentta03]</textarea></td>
		</tr>";
	echo "		
		<tr>
			<th>".t("Torstai")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta04' cols='40' rows='7'>$rivi[kentta04]</textarea></td>
		</tr>";
	echo "		
		<tr>
			<th>".t("Perjantai")."</th>
			<td colspan='3'><textarea wrap='none' name='kentta05' cols='40' rows='7'>$rivi[kentta05]</textarea></td>
		</tr>
		
		</table>

		<br><input type='submit' value='".t("Syötä")."'>

		</form>";					
}

if ($tee == "POISTA") {
	$query  = "	delete 
				from kalenteri 
				where tyyppi='ruokalista' and tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	
	$tee = "";
}


if ($tee == '') {
	
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='SYOTA'>";
	echo "<input type='submit' value='".t("Lisää uusi ruokalista")."'></td>";
	echo "</form>";

	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='AMERIKA'>";
	echo "<input type='submit' value='".t("Lisää uusi Luonasamerika-ruokalista")."'></td>";
	echo "</form>";

	if ($limit=="all") $limit = "";
	elseif ($limit=="50") $limit = "limit 50";
	elseif ($limit=="10") $limit = "limit 10";
	else $limit = "limit 5";
	
	$query = "	select *, kalenteri.tunnus tun, year(pvmalku) vva, month(pvmalku) kka, dayofmonth(pvmalku) ppa, year(pvmloppu) vvl, month(pvmloppu) kkl, dayofmonth(pvmloppu) ppl
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='ruokalista' and kalenteri.yhtio='$kukarow[yhtio]' 
				order by pvmalku desc $limit";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) > 0) {
		
		while($uutinen = mysql_fetch_array($result)) {

			echo "<font class='head'>".t("Ruokalista")." $uutinen[ppa].$uutinen[kka].$uutinen[vva] - $uutinen[ppl].$uutinen[kkl].$uutinen[vvl]</font><hr>";

			echo "<table><tr><th>".t("Maanantai")."</th><th>".t("Tiistai")."</th><th>".t("Keskiviikko")."</th><th>".t("Torstai")."</th><th>".t("Perjantai")."</th></tr>
				<tr>
				<td valign='top'>$uutinen[kentta01]</td>
				<td valign='top'>$uutinen[kentta02]</td>
				<td valign='top'>$uutinen[kentta03]</td>
				<td valign='top'>$uutinen[kentta04]</td>
				<td valign='top'>$uutinen[kentta05]</td>
				</tr>";
			
			echo "<tr><td colspan='5' class='back'>";
			
			// napit begin
			echo "<table><tr><td class='back'>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='SYOTA'>";
			echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
			echo "<input type='submit' value='".t("Muokkaa")."'>";
			echo "</form>";
			echo "</td><td class='back'>";
			echo "</td></tr></table>";
			// napit end

			echo "</td></tr></table>";		
		}
		
		echo "<a href='$PHP_SELF?limit=10'>".t("Näytä viimeiset 10 ruokalistaa")."</a><br>";
		echo "<a href='$PHP_SELF?limit=50'>".t("Näytä viimeiset 50 ruokalistaa")."</a><br>";
		echo "<a href='$PHP_SELF?limit=all'>".t("Näytä kaikki ruokalistat")."</a><br>";
	}
	
}

require("inc/footer.inc");

?>