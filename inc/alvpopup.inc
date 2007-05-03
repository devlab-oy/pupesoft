<?php

// Tämä funktio tekee alv-popupin, jossa on yhtiön maakoodin mukaiset alv%tit

if (!function_exists("alv_popup")) {
	function alv_popup($nimi, $oletus) {

		// tarvitaan yhtiörowta ja kukarowta
		global $yhtiorow, $kukarow;

		$ulos = "";

		$query = "	SELECT selite
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'alv'
					ORDER BY jarjestys,selite";
		$vresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vresult) == 0) {
			echo "<font class = 'error'>".t("Yrityksen ALV%tit puuttuu!")."'$kukarow[yhtio]'</font>";
		}
		else {
			$ulos = "<select name='$nimi'>";
			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($oletus == $vrow['selite']) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] %";
			}
			$ulos .= "</select>";
		}
		return $ulos;
	}
}

if (!function_exists("alv_popup_oletus")) {
	function alv_popup_oletus($nimi, $oletus, $maa = '') {

		// tarvitaan yhtiörowta ja kukarowta
		global $yhtiorow, $kukarow;

		$ulos = "";

		$laji = "alv";
		$lisa = "";

		// syötettiin maa
		if ($maa != "" and $maa != $yhtiorow["maa"]) {
			// tutkitaan ollaanko siellä alv-rekisteröity
			$query = "select * from yhtion_toimipaikat where yhtio = '$kukarow[yhtio]' and maakoodi = '$maa' and vat_numero != ''";
			$alhire = mysql_query($query) or pupe_error($query);

			// ollaan alv-rekisteröity, haetaan oikea ALV-kanta
			if (mysql_num_rows($alhire) == 1) {
				$laji = "alvulk";
				$lisa = "and selitetark_2 = '$maa'";
			}
		}

		$query = "	SELECT selite
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = '$laji' $lisa
					ORDER BY jarjestys,selite";
		$vresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vresult) == 0) {
			echo "<font class = 'error'>".t("Yrityksen ALV%tit puuttuu!")."'$kukarow[yhtio]'</font>";
		}
		else {

			$ulos = "<select name='$nimi'>";
			$sel = '';
			if ($oletus == '') {
				$sel = "selected";
			}
			$ulos .= "<option value = '' $sel>".t("Oletus");

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel = "";
				if ($oletus == $vrow['selite']) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] %";
			}
			$ulos .= "</select>";
		}
		return $ulos;
	}
}

if (!function_exists("alv_oletus")) {
	function alv_oletus() {
		global $kukarow;
		$ulos=0;
		$query = "	SELECT selite
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'alv' and selitetark != ''
					ORDER BY jarjestys,selite";
		$vresult = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($vresult) != 1) {
			echo "<font class = 'error'>".t("Yrityksen oletusALV%tit puuttuu tai niitä on monta!")."</font>";
		}
		else {
			$vrow=mysql_fetch_array($vresult);
			$ulos = $vrow['selite'];
		}
		return $ulos;
	}
}

?>
