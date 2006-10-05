<?php

	if ((mysql_field_name($result, $i)=="rahtikirja") and ($t[$i]!=''))
	{
		if (!file_exists("tilauskasittely/".$t[$i]))
		{
			$virhe[$i] = "".t("Tiedosto tilauskasittely")."/$t[$i] ".t("ei löydy")."!";
			$errori = 1;
		}
	}

	if ((mysql_field_name($result, $i)=="hetiera") and ($t[$i]!=''))
	{
		// otetaan arvo talteen
		$apuhetiera = $t[$i];
		if ($t[$i]!='H' and $t[$i]!='E' and $t[$i]!='K')
		{
			$virhe[$i] = "".t("Syötä H = heti , E = erätulostus tai K = koontirahtikirja!")."";
			$errori = 1;
		}
	}

	if ((mysql_field_name($result, $i)=="multi_jv") and ($t[$i]!='')) {
		if ($apuhetiera == 'H' and $t[$i] != '') {
			$virhe[$i] = "".t("Multi-jälkivaatimus toimii vain erärahtikirjoissa")."!";
			$errori = 1;
		}
	}
		
	// jos toimitustavan nimeä ollaan muuttamassa...
	if ((mysql_field_name($result, $i)=="selite") and ($t[$i]!=$trow[$i]))
	{
		$toita = trim($t[$i]);
		$toita = str_replace("'","",$toita);
		$toita = str_replace("\"","",$toita);
		
		$query = "select tunnus from toimitustapa where selite='$toita' and yhtio='$kukarow[yhtio]'";
		$updre = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($updre)!=0) {
			$virhe[$i] = "".t("Et voi muuttaa toimitustaavan nimeä, koska")." '$toita' ".t("on jo olemassa")."!";
			$errori = 1;
		}
		else {
			$query = "update toimitustapa set selite='$toita' where selite='$trow[$i]' and yhtio='$kukarow[yhtio]'";
			$updre = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("Päivitettiin")." ".mysql_affected_rows()." ".t("toimitustapa")."";

			$query = "update asiakas set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
			$updre = mysql_query($query) or pupe_error($query);
			
			echo ", ".mysql_affected_rows()." ".t("asiakasta")."";
			
			$query = "update lasku set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]' and ((tila='L' and alatila in('A','C')) or tila='N')";
			$updre = mysql_query($query) or pupe_error($query);

			echo ", ".mysql_affected_rows()." ".t("otsikkoa")."";
			
			$query = "update rahtimaksut set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
			$updre = mysql_query($query) or pupe_error($query);
			
			echo ", ".mysql_affected_rows()." ".t("rahtimaksua")."";

			$query = "update rahtisopimukset set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]'";
			$updre = mysql_query($query) or pupe_error($query);
			
			echo ", ".mysql_affected_rows()." rahtisopimusta";

			$query = "update rahtikirjat set toimitustapa='$toita' where toimitustapa='$trow[$i]' and yhtio='$kukarow[yhtio]' and tulostettu='0000-00-00 00:00:00'";
			$updre = mysql_query($query) or pupe_error($query);

			echo "ja ".mysql_affected_rows()." ".t("tulostamatonta rahtikirjaa").".</font><br><br>";
		}
	}

?>
