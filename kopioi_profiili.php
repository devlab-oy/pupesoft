<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Kopioi k�ytt�j�profiileja").":</font><hr>";

		if (isset($fromkuka)) {
			$fromkuka = explode('##',$fromkuka);

			$fromyhtio = $fromkuka[1];
			$fromkuka  = $fromkuka[0];
		}



		if ($copyready!='') // ollaan painettu kopio submittia
		{
			echo "<font class='message'>".t("Kopioitiin k�ytt�j�profiili")." $fromkuka ($fromyhtio) --> Yhti�lle $tokuka</font><br><br>";

			//haetaan profiili kaikki tiedot
			$query = "SELECT * FROM oikeu where kuka='$fromkuka' and profiili='$fromkuka' and yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			//poistetaan se uudelya yhti�lt� jos se on olemassa
			$query = "delete from oikeu where kuka='$fromkuka' and profiili='$fromkuka' and yhtio='$tokuka'";
			$delre = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar)) {
				$query = "insert into oikeu values ('$fromkuka','$row[sovellus]','$row[nimi]','$row[alanimi]','$row[paivitys]','','$row[nimitys]','$row[jarjestys]','$row[jarjestys2]','$fromkuka','$tokuka','$row[hidden]',0)";
				$upres = mysql_query($query) or pupe_error($query);
			}

			//p�ivitet��n my�s k�ytt�jien tiedot joilla on t�m� profiili
			$query = "	SELECT *
						FROM kuka
						WHERE yhtio='$tokuka' and profiilit!=''";
			$kres = mysql_query($query) or pupe_error($query);
			while ($krow = mysql_fetch_array($kres)) {
				$profiilit = explode(',', $krow["profiilit"]);
				if (count($profiilit) > 0) {
					//k�yd��n l�pi k�ytt�j�n kaikki profiilit
					$triggeri = "";
					foreach($profiilit as $prof) {
						//jos t�m� kyseinen profiili on ollut k�ytt�j�ll� aikaisemmin, niin joudumme p�ivitt�m��n oikeudet
						if (strtoupper($prof) == strtoupper($fromkuka)) {
							$triggeri = "HAPPY";
						}
					}

					if ($triggeri == "HAPPY") {
						//poistetaan k�ytt�j�n vanhat
						$query = "	DELETE FROM oikeu
									WHERE yhtio='$tokuka' and kuka='$krow[kuka]' and lukittu=''";
						$pres = mysql_query($query) or pupe_error($query);

						//k�yd��n uudestaan profiili l�pi
						foreach($profiilit as $prof) {
							$query = "	SELECT *
										FROM oikeu
										WHERE yhtio='$tokuka' and kuka='$prof' and profiili='$prof'";
							$pres = mysql_query($query) or pupe_error($query);

							while ($trow = mysql_fetch_array($pres)) {
								//joudumme tarkistamaan ettei t�t� oikeutta ole jo t�ll� k�ytt�j�ll�.
								//voi olla esim jos se on lukittuna annettu
								$query = "	SELECT yhtio
											FROM oikeu
											WHERE kuka		= '$krow[kuka]'
											and sovellus	= '$trow[sovellus]'
											and nimi		= '$trow[nimi]'
											and alanimi 	= '$trow[alanimi]'
											and paivitys	= '$trow[paivitys]'
											and nimitys		= '$trow[nimitys]'
											and jarjestys 	= '$trow[jarjestys]'
											and jarjestys2	= '$trow[jarjestys2]'
											and yhtio		= '$tokuka'";
								$tarkesult = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($tarkesult) == 0) {
									$query = "	INSERT into oikeu
												SET
												kuka		= '$krow[kuka]',
												sovellus	= '$trow[sovellus]',
												nimi		= '$trow[nimi]',
												alanimi 	= '$trow[alanimi]',
												paivitys	= '$trow[paivitys]',
												nimitys		= '$trow[nimitys]',
												jarjestys 	= '$trow[jarjestys]',
												jarjestys2	= '$trow[jarjestys2]',
												yhtio		= '$tokuka'";
									$rresult = mysql_query($query) or pupe_error($query);
								}
							}
						}
						echo "<font class='message'>P�ivitettiin k�ytt�j�n $krow[kuka] profiili $prof</font><br>";
					}
				}
			}

			$fromkuka='';
			$tokuka='';
			$fromyhtio='';
			$toyhtio='';
		}

		$query	= "	SELECT distinct yhtio, nimi
					from yhtio
					where konserni = '$yhtiorow[konserni]'
					and konserni != ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<br><font class='message'>".t("Palvelimella ei ole muita yhti�it�")."!</font>";
			exit;
		}

		$sovyhtiot = "'$kukarow[yhtio]'";

		while ($prow = mysql_fetch_array($result)) {
			$sovyhtiot .= ",'$prow[yhtio]'";
		}

		// tehd��n k�ytt�j�listaukset
		$query = "	SELECT distinct kuka, profiili, yhtio
					FROM oikeu
					WHERE kuka = profiili
					and profiili != ''
					and yhtio in ($sovyhtiot)";
		$kukar = mysql_query($query) or pupe_error($query);

		echo "<br><form method='post'>";
		echo "<input type='hidden' name='tila' value='copy'>";

		echo "<font class='message'>".t("Kopioitava profiili").":</font>";

		echo "<table><tr><th align='left'>".t("Profiili").":</th><td>
		<select name='fromkuka' onchange='submit()'>
		<option value=''>".t("Valitse profiili")."</option>";

		while ($kurow=mysql_fetch_array($kukar)) {
			if ($fromkuka==$kurow["profiili"] and $fromyhtio == $kurow["yhtio"]) $select='selected';
			else $select='';

			echo "<option $select value='$kurow[profiili]##$kurow[yhtio]'>$kurow[profiili] ($kurow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "</table>";

		echo "<br><br><font class='message'>".t("Mille yhti�lle kopioidaan").":</font>";

		// tehd��n k�ytt�j�listaukset

		$query = "SELECT distinct yhtio, nimi FROM yhtio WHERE yhtio in ($sovyhtiot) and yhtio!='$fromyhtio'";
		$kukar = mysql_query($query) or pupe_error($query);

		echo "<table><tr><th align='left'>".t("Yhti�").":</th><td>
		<select name='tokuka' onchange='submit()'>
		<option value=''>".t("Valitse yhti�")."</option>";

		while ($kurow=mysql_fetch_array($kukar))
		{
			if ($tokuka==$kurow["yhtio"]) {
				$select = 'selected';
				$tonimi = $kurow["nimi"];
			}
			else $select='';

			echo "<option $select value='$kurow[yhtio]'>$kurow[nimi] ($kurow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "</table>";

		if (($tokuka!='') and ($fromkuka!=''))
		{
			echo "<br><br>";
			echo "<input type='submit' name='copyready' value='".t("Kopioi k�ytt�profiili")." $fromkuka --> Yhti�lle $tonimi'>";
		}

		echo "</form>";

	require("inc/footer.inc");
?>
