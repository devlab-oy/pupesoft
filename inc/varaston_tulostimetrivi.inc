<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys


	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (substr(mysql_field_name($result,$i),0,9)=='varasto') {

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";

		$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' AND varasto_status != 'P'";
		$kires = mysql_query($query) or pupe_error($query);

		while ($kirow=mysql_fetch_array($kires))
		{
			if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[tunnus]' ".$sel[$kirow["tunnus"]].">$kirow[nimitys]</option>";
		}

		$ulos .= "</select></td>";

		$jatko=0;
	}

	if (mysql_field_name($result,$i)=='pakkaamo') {

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";

		$query = "select distinct nimi from pakkaamo where yhtio='$kukarow[yhtio]'";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos .= "<option value=''>".t("Ei pakkaamoa")."</option>";

		while ($kirow=mysql_fetch_array($kires))
		{
			if ($kirow["nimi"]==$row[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[nimi]' ".$sel[$kirow["nimi"]].">$kirow[nimi]</option>";
		}

		$ulos .= "</select></td>";

		$jatko=0;
	}


	if (substr(mysql_field_name($result,$i),0,9) == 'printteri') {

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";

		$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[tunnus]' ".$sel[$kirow["tunnus"]].">$kirow[kirjoitin]</option>";
		}

		$ulos .= "</select></td>";

		$jatko=0;
	}

?>