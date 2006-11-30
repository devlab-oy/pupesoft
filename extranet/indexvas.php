<?php

require ("parametrit.inc");
require ("functions.inc");

//katsotaan millon www roottia on viimeks modifioitu.. otetaan siit‰ versionumero.
$polku=dirname($_SERVER['SCRIPT_FILENAME'])."/.";

if ($yhtiorow["logo"] != '') {

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

echo "<a href='".$palvelin2."logout.php?toim=change'><img border='0' src='$logo' alt='logo' $koko style='padding:0px 3px 7px 3px;'></a><br>";
//echo "<font class='info'>pupesoft.com v.".date("d/m/y@H:i", filemtime($polku))."</font><br><br>";

echo "$yhtiorow[nimi]<br>";
echo "$kukarow[nimi]<br><br>";

// estet‰‰n errorit tyhj‰st‰ arrayst‰
if (!isset($menu)) $menu = array();

// mit‰ sovelluksia k‰ytt‰j‰ saa k‰ytt‰‰
$query = "	SELECT distinct sovellus
			FROM oikeu
			WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and sovellus='extranet'";
$result = mysql_query($query) or pupe_error($query);

// lˆytyi vaan yksi sovellus, otetaan se
$orow = mysql_fetch_array($result);
$sovellus = $orow['sovellus'];

//N‰ytet‰‰n aina exit-nappi
echo "<br><a class='menu' href='logout.php' target='main'>".t("Exit")."</a><br>";


// Mit‰ k‰ytt‰j‰ saa tehd‰?
// Valitaan ensin vain yl‰taso jarjestys2='0'

$query = "SELECT nimi, jarjestys
		FROM oikeu
		WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and sovellus='$sovellus' and jarjestys2='0'
		ORDER BY jarjestys";
$result = mysql_query($query) or pupe_error($query);

while ($orow = mysql_fetch_array($result)) {

	// tutkitaan onko meill‰ alamenuja
	$query = "SELECT nimi, nimitys, alanimi
			FROM oikeu
			WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and sovellus='$sovellus' and jarjestys='$orow[jarjestys]'
			ORDER BY jarjestys, jarjestys2";
	$xresult = mysql_query($query) or pupe_error($query);
	$mrow = mysql_fetch_array($xresult);

	// alamenuja lˆytyy, eli t‰m‰ on menu
	if (mysql_num_rows($xresult) > 1) {

		// jos ykkˆnen niin n‰ytet‰‰n avattu menu itemi
		if($menu[$mrow['nimitys']] == 1) {
			echo "- <a class='menu' href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=0'>".t("$mrow[nimitys]")."</a><br>";

			// tehd‰‰n submenu itemit
			while ($mrow = mysql_fetch_array($xresult)) {
				echo "&nbsp;&bull; <a class='menu' href='$mrow[nimi]";
				if ($mrow['alanimi'] != '') echo "?toim=$mrow[alanimi]";
				echo "' target='main'>".t("$mrow[nimitys]")."</a><br>";
			}
		}
		else {
			// muuten n‰ytet‰‰n suljettu menuotsikko
			echo "+ <a class='menu' href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=1'>".t("$mrow[nimitys]")."</a><br>";
		}
	}
	else {
		// normaali menuitem
		echo "<a class='menu' href='$mrow[nimi]";
		if ($mrow['alanimi'] != '') echo "?toim=$mrow[alanimi]";
		echo "' target='main'>".t("$mrow[nimitys]")."</a><br>";
	}

}

echo "</body></html>";

?>