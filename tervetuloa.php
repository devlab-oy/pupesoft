<?php

require ("inc/parametrit.inc");

if (!isset($tee) or $tee == '') {
	echo "<table>";
	echo "<tr>";
	
	if (isset($limit)) {
		if ($limit=="all") 		$limit = "";
		elseif ($limit=="50") 	$limit = "limit 50";
		elseif ($limit=="10") 	$limit = "limit 10";
	}
	else { 
		$limit = "limit 5";
	}
	
	if ($yhtiorow['konserni'] != "") {
		$ehto = "(kalenteri.yhtio='$kukarow[yhtio]' or kalenteri.konserni='$yhtiorow[konserni]')";
	}
	else {
		$ehto = "kalenteri.yhtio='$kukarow[yhtio]'";
	}

	if ($kukarow['kieli'] == $yhtiorow['kieli']) {
		$lisa = " and (kalenteri.kieli = '$kukarow[kieli]' or kalenteri.kieli = '') ";
	}
	else {
		$lisa = " and kalenteri.kieli = '$kukarow[kieli]' ";
	}

	$query = "	select *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='uutinen' $lisa and $ehto
				order by kokopaiva desc, pvmalku desc, kalenteri.tunnus desc
				$limit";

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result)>0) {

		///* uutiset *///
		echo "<td class='back' valign='top' width=350'>";

		while($uutinen = mysql_fetch_array($result)) {

			/*
			toimittaja = kuka
			paivays    = pvmalku
			otsikko    = kentta01
			uutinen    = kentta02
			kuvaurl    = kentta03
			*/

			$kuva = "";

			if ($uutinen["kentta03"] != "") {
				$kuva = "<img src='view.php?id=$uutinen[kentta03]' width='130'>";
			}

			if ($yhtiorow["logo"] != '' and $kuva == '') {
				$kuva = "<img src='$yhtiorow[logo]' width='130'>";
			}

			if ($kuva == '') {
				$kuva = "<img src='http://www.pupesoft.com/pupesoft.gif' width='130'>";
			}

			if ($uutinen['nimi'] == "") {
				$uutinen['nimi'] = $uutinen['toimittaja'];
			}

			echo "
			<table width='400'>
			<tr>
			<td colspan='2' class='back'><font class='head'>$uutinen[kentta01]</font><hr></td>
			</tr><tr>
			<td valign='top' align='center' width='140'><br>$kuva<br><br></td>
			<td valign='top'>$uutinen[kentta02]<br>
			<a href='$PHP_SELF?tee=PRINTTAA&tun=$uutinen[tun]'>".t("Tulosta")."</a>
			</font></td>
			</tr><tr>
			<th colspan='2'>".t("Laatija:")." $uutinen[nimi]<br>".t("P‰iv‰m‰‰r‰").": $uutinen[pvmalku]</th>
			</tr>
			</table><br>\n";
		}

		echo "<a href='tervetuloa.php?limit=10'>".t("N‰yt‰ viimeiset 10 uutista")."</a><br>";
		echo "<a href='tervetuloa.php?limit=50'>".t("N‰yt‰ viimeiset 50 uutista")."</a><br>";
		echo "<a href='tervetuloa.php?limit=all'>".t("N‰yt‰ kaikki uutiset")."</a><br>";

		echo "</td>";
	}

	///* Hyv‰ksytt‰v‰t laskut*///
	echo "<td class='back' width='10'></td>";

	echo "<td class='back' valign='top' width='350'>";

	// haetaan kaikki yritykset, jonne t‰m‰ k‰ytt‰j‰ p‰‰see
	$query  = "	select distinct yhtio.yhtio, yhtio.nimi from kuka
				join yhtio using (yhtio)
				where kuka='$kukarow[kuka]'";
	$kukres = mysql_query($query) or pupe_error($query);

	while ($kukrow = mysql_fetch_array($kukres)) {

		$query = "SELECT count(*) FROM lasku
			WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukrow[yhtio]' and alatila = 'H' and tila!='D'
			ORDER BY erpcm";
		$result = mysql_query($query) or pupe_error($query);
		$piilorow=mysql_fetch_array ($result);

		$query = "	SELECT tapvm, erpcm 'er‰pvm', ytunnus, nimi, round(summa * vienti_kurssi, 2) 'kotisumma'
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukrow[yhtio]' and alatila!='H' and tila!='D'
					ORDER BY erpcm";
		$result = mysql_query($query) or pupe_error($query);

		if ((mysql_num_rows($result) > 0) or ($piilorow[0] > 0)) {

			echo "<table width='100%'>";

			// ei n‰ytet‰ suotta firman nime‰, jos k‰ytt‰j‰ kuuluu vaan yhteen firmaan
			if (mysql_num_rows($kukres) == 1) $kukrow["nimi"] = "";

			echo "<tr><td colspan='".mysql_num_fields($result)."' class='back'><font class='head'>".t("Hyv‰ksytt‰v‰t laskusi")." $kukrow[nimi]</font><hr></td></tr>";

			if ($piilorow[0] > 0)
				echo "<tr><td colspan='".mysql_num_fields($result)."' class='back'>". sprintf(t('Sinulla on %d pys‰ytetty‰ laskua'), $piilorow[0]) . "</tr>";

			if (mysql_num_rows($result) > 0) {
				for ($i = 1; $i < mysql_num_fields($result); $i++) {
					echo "<th>" . t(mysql_field_name($result,$i))."</th>";
				}
				while ($trow=mysql_fetch_array ($result)) {
					echo "<tr>";
					for ($i=1; $i<mysql_num_fields($result); $i++) {
						if (mysql_field_name($result,$i) == "nimi" and $kukrow["yhtio"] == $kukarow["yhtio"]) {
							echo "<td><a href='hyvak.php'>$trow[$i]</a></td>";
						}
						else {
							echo "<td>$trow[$i]</td>";
						}
					}
					echo "</tr>";
				}
			}
			echo "</table><br><br>";
		}

	}

	///* RUOKALISTA *///
	$query = "	select *, kalenteri.tunnus tun, year(pvmalku) vva, month(pvmalku) kka, dayofmonth(pvmalku) ppa, year(pvmloppu) vvl, month(pvmloppu) kkl, dayofmonth(pvmloppu) ppl
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='ruokalista'
				and kalenteri.yhtio='$kukarow[yhtio]'
				and pvmalku<=now()
				and pvmloppu>=now()
				LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		while($uutinen = mysql_fetch_array($result)) {
			echo "
				<table width='100%'>
				<tr><td colspan='5' class='back'><font class='head'>".t("Ruokalista")." $uutinen[ppa].$uutinen[kka].-$uutinen[ppl].$uutinen[kkl].$uutinen[vvl]</font><hr></td></tr>
				<tr><th>".t("Maanantai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta01]</td></tr>
				<tr><th>".t("Tiistai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta02]</td></tr>
				<tr><th>".t("Keskiviikko")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta03]</td></tr>
				<tr><th>".t("Torstai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta04]</td></tr>
				<tr><th>".t("Perjantai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta05]</td></tr>
				</table>";
		}
	}

	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

if (isset($tee) and $tee == "PRINTTAA") {

	echo "
		<script language=\"JavaScript\">
		<!--
    	    function printtaa() {
				window.print();
				window.location = \"$PHP_SELF\";
    	    }
		//-->
		</script>";

	$query = "	select *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='uutinen'
				and kalenteri.yhtio='$kukarow[yhtio]'
				and kalenteri.tunnus='$tun'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);

	/*
	toimittaja = kuka
	paivays    = pvmalku
	otsikko    = kentta01
	uutinen    = kentta02
	kuvaurl    = kentta03
	*/

	$kuvaurl = "";

	if ($row["kentta03"] != "") {
		$kuvaurl = "<img src='view.php?id=$row[kentta03]' width='130'>";
	}

	if ($yhtiorow["logo"] != '' and $kuvaurl == '') {
		$kuvaurl = "<img src='$yhtiorow[logo]' width='130'>";
	}

	if ($kuvaurl == '') {
		$kuvaurl = "<img src='http://www.pupesoft.com/pupesoft.gif' width='130'>";
	}


	$otsikko        = $row["kentta01"];
	$uutinen        = $row["kentta02"];
	$paivays        = $row["pvmalku"];
	$toimittaja     = $row["kuka"];

	print "

		<TITLE>$otsikko - $paivays</TITLE>
		<BODY BGCOLOR='#FFFFFF' TEXT='#000000' LINK='#336699' VLINK='#336699' ALINK='#336699' onLoad='printtaa();'>

		<CENTER>
		<BR><BR>

		<TABLE CELLSPACING='0' CELLPADDING='5' WIDTH='400' BORDER='1' BORDERCOLOR='#000000' BGCOLOR='#FFFFFF'><TR><TD colspan=2>
				<FONT FACE='Lucida,Verdana,Helvetica,Arial' SIZE='+2'>
						<B>$otsikko</B><BR>
				</FONT>
				<HR COLOR='GRAY'>

				<TABLE BORDER='0' CELLSPACING='5' CELLPADDING='4'><TR>
						<TD VALIGN='TOP'>
								$kuvaurl
						</TD><TD VALIGN='TOP'>
								<FONT FACE='Lucida,Verdana,Helvetica,Arial' SIZE='-1'>
								$uutinen
								</FONT>
						</TD>
				</TR></TABLE>

		</TD></TR>

		<TR><TD VALIGN=middle bgcolor='#000000'>
				<FONT FACE='Lucida,Verdana,Helvetica,Arial' COLOR='#FFFFFF'>
				<B><SMALL>&nbsp;".t("Laatija:")." $toimittaja</SMALL></B><BR>
				<B><SMALL>&nbsp;".t("P&auml;iv&auml;m&auml;&auml;r&auml;").": $paivays</SMALL></B>
				</FONT>
		<!--
			</TD><TD ALIGN=right bgcolor='#000000'>
			<IMG SRC='print.gif' WIDTH='21' HEIGHT='21' BORDER='0' ALT='".t("tulosta uutinen")."'>&nbsp;
			<IMG SRC='koti.gif' WIDTH='21' HEIGHT='21' BORDER='0' ALT='".t("koti")."'>&nbsp;
		-->
		</TD></TR></TABLE>

		</CENTER>
		</BODY>

		";
}


require("inc/footer.inc");

?>
