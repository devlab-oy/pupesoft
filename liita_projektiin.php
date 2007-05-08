<?php

require ("inc/parametrit.inc");


/*
	Ty‰kalu laskujen/tilausten liitt‰miseksi osaksi projekti
	
	N‰in esim erilliset rahtilaskut,hyvitykset voidaan liitt‰‰ osaksi projektia myˆs j‰lkik‰teen
	
	T‰st‰ on apua kun laskemme koko projektin arvoa.
	
*/

echo "<font class='head'>".t("Liit‰ tilaus projektiin")."</font><hr><br><br>";


if($tee=="KORJAA" or $tee=="LIITA") {
	//	tarkastetaan ett‰ tunnusnippu on edelleen ok
	$query = "	SELECT nimi, nimitark, tila, alatila, tunnusnippu, tunnus from lasku where yhtio='$kukarow[yhtio]' and tila IN ('R') and tunnusnippu>0 and tunnus='$tunnusnippu'";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result)>0) {
		$laskurow=mysql_fetch_array($result);
		
		$query = "	SELECT nimi, nimitark, tila, alatila, tunnusnippu, tunnus from lasku where yhtio='$kukarow[yhtio]' and tila IN ('L','G','E','V','W','N','T') and tunnus='$tunnus' and tunnusnippu<>tunnus";
		$res = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($res)>0) {
			$row=mysql_fetch_array($res);
			
			if($tee=="LIITA") {
				$query="update lasku set tunnusnippu='$tunnusnippu' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
				$updres=mysql_query($query) or pupe_error($query);
				echo "<font class='message'>".t("Liitettiin tilaus")." $tunnus ".t("tilaukseen")." $tunnusnippu</font><br><br>";
			
				$tee="";
				$tunnus="";
			}
			else {
				$laskutyyppi=$laskurow["tila"];
				$alatila=$laskurow["alatila"];

				//tehd‰‰n selv‰kielinen tila/alatila
				require "inc/laskutyyppi.inc";

				echo "<table>
						<tr>
							<th>".t("Tilaus johon liitet‰‰n")."</th>
						</tr>
						<tr>
							<td>$laskurow[tunnusnippu] $laskurow[nimi] - ".t("$laskutyyppi")." ".t("$alatila")."</td>
						</tr>
						<tr>
							<td class='back'><br></td>
						</tr>";
						
				$laskutyyppi=$row["tila"];
				$alatila=$row["alatila"];

				//tehd‰‰n selv‰kielinen tila/alatila
				require "inc/laskutyyppi.inc";

				if($row["tunnusnippu"]>0) {
					$lisa="<td class='back'><font class='message'>".t("HUOM! tilaus on jo liitettyn‰ projektiin")." $row[tunnusnippu]</font></td>";
				}
				else {
					$lisa = "";
				}
				
				echo "<table>
						<tr>
							<th>".t("Tilaus joka liitet‰‰n")."</th>
						</tr>
						<tr>
							<td>$row[tunnus] $row[nimi] - ".t("$laskutyyppi")." ".t("$alatila")."</td>$lisa
						</tr>
						<tr>
							<td class='back'><br></td>
						</tr>";

						
				echo "	<tr>
							<form action='$PHP_SELF' method='post' name='projekti' autocomplete='off'>
							<input type='hidden' name='tee' value='LIITA'>
							<input type='hidden' name='tunnusnippu' value='$tunnusnippu'>
							<input type='hidden' name='tunnus' value='$tunnus'>
							<td class='back' align='right'><input type='Submit' value='".t("liit‰")."'></td>
							</form>
						</tr>
					</table>";	
			}			
		}
		else {
			$tunnusvirhe = "<font class='error'>".("Tilausta ei voida liitt‰‰. Tilausnumero voi olla v‰‰r‰ tai tilaus on p‰‰tilaus")."</font><br>";
			$tee="HAE";
		}
	}
	else {
		$tunnusnippuvirhe = "Sopivaa tilausta ei lˆydy. Tilauksen pit‰‰ olla normaali tilaus tai projekti.";
		$tee="";
	}
}

if($tee == "HAE") {
	$query = "	SELECT nimi, nimitark, tila, alatila, tunnusnippu, tunnus from lasku where yhtio='$kukarow[yhtio]' and tila IN ('R') and tunnus='$tunnusnippu'";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result)>0) {
		$laskurow=mysql_fetch_array($result);
		if($laskurow["tunnusnippu"]>0) {			
			$laskutyyppi=$laskurow["tila"];
			$alatila=$laskurow["alatila"];

			//tehd‰‰n selv‰kielinen tila/alatila
			require "inc/laskutyyppi.inc";

			echo "<table>
					<tr>
						<th>".t("Tilaus johon liitet‰‰n")."</th>
					</tr>
					<tr>
						<td>$laskurow[tunnusnippu] $laskurow[nimi] - ".t("$laskutyyppi")." ".t("$alatila")."</td>
					</tr>
					<tr>
						<td class='back'><br></td>
					</tr>					
					<tr>
						<th>".t("Anna tilausnumero jonka haluat liitt‰‰")."</th>
					</tr>
					<tr>
						<form action='$PHP_SELF' method='post' name='projekti' autocomplete='off'>
						<input type='hidden' name='tee' value='KORJAA'>
						<input type='hidden' name='tunnusnippu' value='$tunnusnippu'>
						<td><input type='text' name='tunnus' size='15' maxlength='14' value='$tunnus'></td>
						<td class='back'><input type='Submit' value='".t("Jatka")."'></td>
						<td class='back'><font class='error'>".t($tunnusvirhe)."</font></td>
						</form>
					</tr>
				</table>";
		}
		else {
			//	pit‰isikˆ sallia sellainen tehd‰?
			$tunnusnippuvirhe =  "Tilauksella ei ole tunnusnippua";
			$tee="";			
		}
	}
	else {
		$tunnusnippuvirhe = "Sopivaa tilausta ei lˆydy. Tilauksen pit‰‰ olla projekti.";
		$tee="";
	}
}


if($tee == "") {
	echo "<table>
			<tr>
				<th>".t("Anna projekti/tilausnumero")."<br>".t("johon haluat liitt‰‰ tilauksen")."</th>
			</tr>
			<tr>
				<form action='$PHP_SELF' method='post' name='projekti' autocomplete='off'>
				<input type='hidden' name='tee' value='HAE'>
				<td><input type='text' name='tunnusnippu' size='15' maxlength='14' value='$tunnusnippu'></td>
				<td class='back'><input type='Submit' value='".t("Jatka")."'></td>
				<td class='back'><font class='error'>".t($tunnusnippuvirhe)."</font></td>
				</form>
			</tr>
		</table>";
}


require ("inc/footer.inc");

?>