<?php

if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
	$otsikko_apu = $_POST["otsikko"];
	$uutinen_apu = $_POST["uutinen"];

	require ("inc/parametrit.inc");

	$otsikko = $otsikko_apu;
	$uutinen = $uutinen_apu;
}

if ($toim == "") {
	echo "<font class='head'>".t("Intra Uutiset")."</font><hr>";
	$tyyppi = "uutinen";
}
elseif ($toim == "EXTRANET") {
	echo "<font class='head'>".t("Extranet Uutiset")."</font><hr>";
	$tyyppi = "extranet_uutinen";
}
elseif ($toim == "VIIKKOPALAVERI") {
	echo "<font class='head'>".t("Viikkopalaveri")."</font><hr>";
	$tyyppi = "viikkopalaveri";
}
elseif ($toim == "ASIAKASPALVELU") {
	echo "<font class='head'>".t("Asiakaspalvelu")."</font><hr>";
	$tyyppi = "asiakaspalvelu";
}
elseif ($toim == "RYJO") {
	echo "<font class='head'>".t("Ryjo")."</font><hr>";
	$tyyppi = "ryjo";
}

if ($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") {
	$kulisa = "";
}
else {
	$kulisa = " and kuka='$kukarow[kuka]' ";
}


if ($tee == 'LISAA') {

	if (strlen($otsikko) > 0 and strlen($uutinen) > 0) {

		$liitostunnus = 0;

		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {

			$filetype = $_FILES['userfile']['type'];
			$filesize = $_FILES['userfile']['size'];
			$filename = $_FILES['userfile']['name'];

			$file = fopen($_FILES['userfile']['tmp_name'], 'r');
			$data = addslashes(fread($file, $filesize));

			$selite = trim($otsikko);

			$query = "SHOW variables like 'max_allowed_packet'";
			$result = mysql_query($query) or pupe_error($query);
			$varirow = mysql_fetch_array($result);

			if ($filesize > $varirow[1]) {
				echo "<font class='error'>".t("Liitetiedosto on liian suuri")."! ($varirow[1]) </font>";
			}
			else {
				// lisätään kuva
				$query = "	insert into liitetiedostot set
							yhtio    = '$kukarow[yhtio]',
							liitos   = 'kalenteri',
							data     = '$data',
							selite   = '$selite',
							filename = '$filename',
							filesize = '$filesize',
							filetype = '$filetype'";
				$result = mysql_query($query) or pupe_error($query);
				$liitostunnus = mysql_insert_id();
				$kuva = $liitostunnus;
			}
		}

		$uutinen = nl2br(strip_tags($uutinen, '<a>'));
		$otsikko = nl2br(strip_tags($otsikko, '<a>'));

		// ollaanko valittu konsernitasoinen uutinen
		if ($konserni != '') $konserni = $yhtiorow['konserni'];

		if ($tunnus != 0) {
			$query = "	UPDATE kalenteri SET ";
			$postquery = " WHERE tunnus = '$tunnus' ";
		}
		else {
			$query = "	INSERT INTO kalenteri
						SET
						kuka 		= '$kukarow[kuka]',
						tapa 		= '$tyyppi',
						tyyppi 		= '$tyyppi',
						yhtio 		= '$kukarow[yhtio]',
						pvmalku 	= now(), ";
			$postquery = "";
		}

		$query .= "	kentta01 	= '$otsikko',
					kentta02 	= '$uutinen',
					kentta03 	= '$kuva',
					konserni 	= '$konserni',
					kieli 		= '$lang',
					kokopaiva	= '$kokopaiva',
					kuittaus	= '$lukittu'";
		$query .= $postquery;
		$result = mysql_query($query) or pupe_error($query);
		$katunnus = mysql_insert_id();

		if ($liitostunnus != 0) {
			// päivitetään kuvalle vielä linkki toiseensuuntaa
			$query = "update liitetiedostot set liitostunnus='$katunnus' where tunnus='$liitostunnus'";
			$result = mysql_query($query) or pupe_error($query);
		}

		$tee = "";
	}
	else {

		echo "<font class='error'>".t("Sekä otsikko että uutinen on syötettävä!")."</font><br><br>";
		$rivi["kentta01"]  = $otsikko;
		$rivi["kentta02"]  = $uutinen;
		$rivi["konserni"]  = $konserni;
		$rivi["kokopaiva"] = $kokopaiva;
		$tee = "SYOTA";
	}
}

if ($tee == "SYOTA") {

	$rivi["pvmalku"] = date('Y-m-d');

	if ($tunnus > 0) {
		$query  = "	select *
					from kalenteri
					where tyyppi='$tyyppi' and tunnus='$tunnus' and yhtio='$kukarow[yhtio]' $kulisa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$rivi = mysql_fetch_array($result);
		}
		else {
			echo "<br><br>".t("VIRHE: Et voi muokata uutista!")."<br>";
			exit;
		}
	}

	$rivi['kentta02'] = strip_tags($rivi['kentta02'], '<a>');

	echo "<form enctype='multipart/form-data' name='sendfile' action='$PHP_SELF' method='post'>
		<input type='hidden' name='tee' value='LISAA'>
		<input type='hidden' name='toim' value='$toim'>
		<input type='hidden' name='tunnus' value='$rivi[tunnus]'>
		<table width='400'>
		<tr>
			<th>".t("Otsikko")."</th>
			<td><input type='text' size='40' name='otsikko' value='$rivi[kentta01]'></td>
		</tr>
		<tr>
			<th>".t("Uutinen")."</th>
			<td><textarea wrap='none' name='uutinen' cols='40' rows='15'>$rivi[kentta02]</textarea></td>
		</tr>";

	if ($tunnus > 0) {

		if ($rivi["kentta03"] != '') {
			echo "
				<tr>
					<th>".t("Nykyinen kuva")."</th>
					<td><img src=view.php?id=$rivi[kentta03]' width='130'></td>
				</tr>";

			echo "<input type='hidden' name='kuva' value='$rivi[kentta03]'>";
		}

		echo "
			<tr>
				<th>".t("Syötä uusi kuva")."</th>
				<td><input type='file' name='userfile'></td>
			</tr>";
	}
	else {
		echo "
			<tr>
				<th>".t("Kuva")."</th>
				<td><input type='file' name='userfile'></td>
			</tr>";
	}

	echo "<tr>
			<th>".t("Toimittaja")."</th>
			<td>$kukarow[nimi]</td>
		 </tr>
		 <tr>
			<th>".t("Päivämäärä")."</th>
			<td>$rivi[pvmalku]</td>
		 </tr>";

	echo "<tr><th>".t("Kieli").":&nbsp;</th><td><select name='lang'>";

	$query  = "show columns from sanakirja";
	$fields =  mysql_query($query);

	while ($apurow = mysql_fetch_array($fields)) {
		$sel = "";
		if ($rivi["kieli"] == $apurow[0] or ($rivi["kieli"] == "" and $apurow[0] == $yhtiorow["kieli"])) {
			$sel = "selected";
		}
		if ($apurow[0] != "tunnus" and $apurow[0] != "aikaleima" and $apurow[0] != "kysytty") {
			$query = "select distinct nimi from maat where koodi='$apurow[0]'";
			$maare = mysql_query($query);
			$maaro = mysql_fetch_array($maare);
			$maa   = strtolower($maaro["nimi"]);
			if ($maa=="") $maa = $apurow[0];
			echo "<option value='$apurow[0]' $sel>".t($maa)."</option>";
		}
	}
	
	if ($rivi['kokopaiva'] != "") $check = "CHECKED";
	else $check = "";
	
	echo "<tr>
		<th>".t("Prioriteetti")."</th>
		<td><input type='checkbox' name='kokopaiva' $check> ".t("Näytetäänkö uutinen aina päällimmäisenä")."</td>
	</tr>";

	if ($yhtiorow['konserni'] != '') {
		
		if ($rivi['konserni'] != "") $check = "CHECKED";
		else $check = "";
		
		echo "<tr>
			<th>".t("Konserni")."</th>
			<td><input type='checkbox' name='konserni' $check> ".t("Näytetäänkö uutinen konsernin kaikilla yrityksillä")."</td>
		</tr>";
	}
	if (($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") and ($rivi["kuka"] == $kukarow["kuka"])) {
		if ($rivi['kuittaus'] != "") $check = "CHECKED";
		else $check = "";

		echo "<tr>
				<th>".t("Lukko")."</th>
				<td><input type='checkbox' name='lukittu' value='L' $check>".t("Lukitse palaveri. Lukittua palaveria ei voi muokata eikä poistaa.")."</td>
			</tr>";
	}

	echo "
		</table>

		<br><input type='submit' value='".t("Syötä")."'>

		</form>";
}

if ($tee == "POISTA") {
	$query  = "	UPDATE kalenteri
				SET tyyppi = concat('DELETED ',tyyppi)
				WHERE tyyppi='$tyyppi' and tunnus='$tunnus' and kuka='$kukarow[kuka]' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$tee = "";
}

if ($tee == "PRINTTAA") {

	echo "
		<script language=\"JavaScript\">
		<!--
    	    function printtaa() {
				window.print();
				window.location = \"$PHP_SELF?toim=$toim\";
    	    }
		//-->
		</script>";

	$query = "	select *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='$tyyppi'
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
				<B><SMALL>&nbsp;".t("Toimittaja").": $toimittaja</SMALL></B><BR>
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


if ($tee == '') {

	if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
		echo "<form method='post' action='$PHP_SELF'>
			<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='SYOTA'>";
		echo "<input type='submit' value='".t("Lisää uusi uutinen")."'>";
		echo "</form><br><br>";
	}

	if ($limit=="all") $limit = "";
	elseif ($limit=="50") $limit = "limit 50";
	elseif ($limit=="10") $limit = "limit 10";
	else $limit = "limit 5";

	if ($yhtiorow['konserni'] != "") {
		$ehto = "(kalenteri.yhtio='$kukarow[yhtio]' or kalenteri.konserni='$yhtiorow[konserni]')";
	}
	else {
		$ehto = "kalenteri.yhtio='$kukarow[yhtio]'";
	}

	$query = "	select *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='$tyyppi' and $ehto
				order by kokopaiva desc, pvmalku desc, kalenteri.tunnus desc
				$limit";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<table width='600'>";

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

			echo "	<tr><td colspan='2' class='back'><font class='head'>$uutinen[kentta01]</font><hr></td></tr>
					<tr>
					<td valign='top' align='center' width='140'><br>$kuva<br><br></td>
					<td valign='top'>$uutinen[kentta02]</font><br><a href='$PHP_SELF?tee=PRINTTAA&toim=$toim&tun=$uutinen[tun]'>".t("Tulosta")."</a></td>
					</tr>";
			
			echo"<tr><th colspan='2'>";
			echo "Toimittaja: $uutinen[nimi]<br>Päivämäärä: $uutinen[pvmalku]";

			if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
				if (($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") and ($uutinen["kuittaus"] == "")) {
					echo "<br><br><form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tee' value='SYOTA'>";
					echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
					echo "<input type='submit' value='".t("Muokkaa")."'>";
					echo "</form> ";

					if ($uutinen["kuka"] == $kukarow["kuka"] and $uutinen["yhtio"] == $kukarow["yhtio"]) {
						echo " <form method='post' action='$PHP_SELF'><input type='hidden' name='toim' value='$toim'>";
						echo "<input type='hidden' name='tee' value='POISTA'>";
						echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
						echo "<input type='submit' value='".t("Poista")."'>";
						echo "</form>";
					}
				}
				elseif ($toim != "VIIKKOPALAVERI" and $toim != "ASIAKASPALVELU" and $toim != "RYJO" and $uutinen["kuka"] == $kukarow["kuka"] and $uutinen["yhtio"] == $kukarow["yhtio"]) {
					echo "<br><br><form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tee' value='SYOTA'>";
					echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
					echo "<input type='submit' value='".t("Muokkaa")."'>";
					echo "</form> ";
					echo " <form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tee' value='POISTA'>";
					echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
					echo "<input type='submit' value='".t("Poista")."'>";
					echo "</form>";
				}
			}
			echo "</th></tr>";
			echo"<tr><td colspan='2' class='back'><br></td></tr>";	
			
		}
		echo "</table>";

		echo "<a href='$PHP_SELF?limit=10&toim=$toim'>".t("Näytä viimeiset 10 uutista")."</a><br>";
		echo "<a href='$PHP_SELF?limit=50&toim=$toim'>".t("Näytä viimeiset 50 uutista")."</a><br>";
		echo "<a href='$PHP_SELF?limit=all&toim=$toim'>".t("Näytä kaikki uutiset")."</a><br>";
	}

}

if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
	require("inc/footer.inc");
}

?>