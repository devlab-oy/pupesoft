<?php

if (file_exists("inc/parametrit.inc")) {
	require ("inc/parametrit.inc");
	require ("inc/functions.inc");
}
else {
	require ("parametrit.inc");
	require ("functions.inc");
}


if ($yhtiorow["logo"] != '' and file_exists($yhtiorow["logo"])) {

	$image = getimagesize($yhtiorow["logo"]);
	$ix    = $image[0];			// kuvan x
	$iy    = $image[1];			// kuvan y

	if ($ix > $iy) {
		$koko = "width='150'";
	}
	else {
		$koko = "height='70'";
	}

	$logo = $yhtiorow["logo"];
}
else {
	$logo = "http://www.pupesoft.com/pupesoft.gif";
	$koko = "height='70'";
}

echo "<a class='puhdas' href='".$palvelin2."logout.php?toim=change'><img border='0' src='$logo' alt='logo' $koko style='padding:1px 3px 7px 3px;'></a><br>";

echo "$yhtiorow[nimi]<br>";
echo "$kukarow[nimi]<br><br>";

// estet‰‰n errorit tyhj‰st‰ arrayst‰
if (!isset($menu)) $menu = array();

if ($kukarow["extranet"] != "") {
	$extralisa = " and sovellus='extranet' ";
}
else {
	$extralisa = " ";
}

// mit‰ sovelluksia k‰ytt‰j‰ saa k‰ytt‰‰
$query = "	SELECT distinct sovellus
			FROM oikeu
			WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' $extralisa
			order by sovellus";
$result = mysql_query($query) or pupe_error($query);

// lˆytyi usea sovellus
if (mysql_num_rows($result) > 1) {

	// jos ollaan tulossa loginista, valitaan oletussovellus...
	if (isset($go) and $go != "") {
		$query = "select sovellus from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='$go' order by sovellus, jarjestys limit 1";
		$gores = mysql_query($query) or pupe_error($query);
		$gorow = mysql_fetch_array($gores);
		$sovellus = $gorow["sovellus"];
	}

	echo "	<form action='$PHP_SELF' name='vaihdaSovellus' method='POST'>
			<select name='sovellus' onchange='submit()'>";

	$sovellukset = array();

	while ($orow = mysql_fetch_array($result)) {
		$sovellukset[$orow['sovellus']] = t($orow['sovellus']);
	}

	//sortataan array phpss‰ jotta se menee kielest‰ riippumatta oikeeseen j‰rjestykseen
	//k‰yet‰‰n asort funktiota koska se ei riko mun itse antamia array-indexej‰
	asort($sovellukset, SORT_STRING);

	foreach ($sovellukset as $key => $val) {
		$sel = '';
		if ($sovellus == $key) $sel = "SELECTED";

		echo "<option value='$key' $sel>$val</option>";

		// sovellus on tyhj‰ kun kirjaudutaan sis‰‰n, ni otetaan eka..
		if ($sovellus == '') $sovellus = $key;
	}

	echo "</select></form><br>";
}
else {
	// lˆytyi vaan yksi sovellus, otetaan se
	$orow = mysql_fetch_array($result);
	$sovellus = $orow['sovellus'];
}

echo "<table width='100%'>";

//N‰ytet‰‰n aina exit-nappi
echo "<tr><td class='back'><a class='menu' href='logout.php' target='main'>".t("Exit")."</a></td></tr>";

// Mit‰ k‰ytt‰j‰ saa tehd‰?
// Valitaan ensin vain yl‰taso jarjestys2='0'
$query = "	SELECT nimi, jarjestys
			FROM oikeu
			WHERE yhtio		= '$kukarow[yhtio]' 
			and kuka		= '$kukarow[kuka]' 
			and sovellus	= '$sovellus' 
			and jarjestys2	= '0' 
			and hidden		= ''
			ORDER BY jarjestys";
$result = mysql_query($query) or pupe_error($query);

while ($orow = mysql_fetch_array($result)) {

	// tutkitaan onko meill‰ alamenuja
	$query = "SELECT nimi, nimitys, alanimi
			FROM oikeu
			WHERE yhtio		= '$kukarow[yhtio]' 
			and kuka		= '$kukarow[kuka]' 
			and sovellus	= '$sovellus' 
			and jarjestys	= '$orow[jarjestys]'
			and hidden		= ''
			ORDER BY jarjestys, jarjestys2";
	$xresult = mysql_query($query) or pupe_error($query);
	$mrow = mysql_fetch_array($xresult);

	// alamenuja lˆytyy, eli t‰m‰ on menu
	if (mysql_num_rows($xresult) > 1) {

		// jos ykkˆnen niin n‰ytet‰‰n avattu menu itemi
		if($menu[$mrow['nimitys']] == 1) {
			echo "<tr><td class='back'><a class='menu' href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=0'>- ".t("$mrow[nimitys]")."</a></td></tr>";

			// tehd‰‰n submenu itemit
			while ($mrow = mysql_fetch_array($xresult)) {
				echo "<tr><td class='back'><a class='menu' href='$mrow[nimi]";
				if ($mrow['alanimi'] != '') echo "?toim=$mrow[alanimi]";
				echo "' target='main'>  &bull; ".t("$mrow[nimitys]")."</a></td></tr>";
			}
		}
		else {
			// muuten n‰ytet‰‰n suljettu menuotsikko
			echo "<tr><td class='back'><a class='menu' href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=1'>+ ".t("$mrow[nimitys]")."</a></td></tr>";
		}
	}
	else {
		// normaali menuitem
		echo "<tr><td class='back'><a class='menu' href='$mrow[nimi]";
		if ($mrow['alanimi'] != '') echo "?toim=$mrow[alanimi]";
		echo "' target='main'>".t("$mrow[nimitys]")."</a></td></tr>";
	}

}

echo "</table>";
echo "</body></html>";

?>