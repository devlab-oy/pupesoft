<?php

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once("inc/parametrit.inc"));
elseif (@include_once("parametrit.inc"));
else exit;

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once("inc/functions.inc"));
elseif (@include_once("functions.inc"));
else exit;

echo "<style type='text/css'>
body {
  margin: 0px;
  padding: 0px 0px 0px 2px;
}
a.puhdas:hover {
  text-decoration: none;
  background-color: transparent;
}
form {
  margin:0px 0px 10px 8px;
}
</style>";

unset($isizelogo);

if ((int) $yhtiorow["logo"] > 0) {
  $liite = hae_liite($yhtiorow["logo"], "Yllapito", "array");
  if ($liite !== false) {
    $isizelogo[0] = $liite["image_width"];
    $isizelogo[1] = $liite["image_height"];
  }
  unset($liite);
}
elseif (@file($yhtiorow["logo"])) {
  $isizelogo = getimagesize($yhtiorow["logo"]);
}

if (isset($isizelogo) and is_array($isizelogo)) {
  if ((int) $yhtiorow["logo"] > 0) {
    $logo   = "view.php?id=".$yhtiorow["logo"];
  }
  else {
    $image = getimagesize($yhtiorow["logo"]);
    $logo = $yhtiorow["logo"];
  }

  $ix    = $isizelogo[0];      // kuvan x
  $iy    = $isizelogo[1];      // kuvan y

  if ($ix > $iy) {
    $koko = "width='150'";
  }
  else {
    $koko = "height='70'";
  }
  $yhtio_nimi = "";
}
else {
  $logo = "{$pupesoft_scheme}api.devlab.fi/pupesoft.png";
  $koko = "width='150'";
  $yhtio_nimi = "<font class='info'>$yhtiorow[nimi]</font><br>";
}

echo "<div style='margin: 5px 0px 10px 8px;'>";
echo "<a class='puhdas' target='main' href='".$palvelin2."logout.php?toim=change'><img border='0' src='$logo' alt='logo' $koko ></a>"; // top right bottom left
echo "</div>";

echo "<div style='margin:0px 0px 10px 8px'>";  // top right bottom left
echo $yhtio_nimi;
echo "<font class='info'>$kukarow[nimi]</font>";
echo "</div>";

// estetään errorit tyhjästä arraystä
if (!isset($menu)) $menu = array();
if (!isset($tultiin)) $tultiin = "";

if ($kukarow["extranet"] != "") {
  if ($tultiin == "futur") {
    $extralisa = " and sovellus = 'Extranet Futursoft' ";
  }
  else {
    $extralisa = " and sovellus = 'Extranet' ";
  }
}
else {
  $extralisa = " and sovellus not like 'Extranet%'";
}

// mitä sovelluksia käyttäjä saa käyttää
$query = "SELECT distinct sovellus
          FROM oikeu use index (oikeudet_index)
          WHERE yhtio = '$kukarow[yhtio]'
          and kuka    = '$kukarow[kuka]'
          $extralisa
          ORDER BY sovellus";
$result = mysql_query($query) or pupe_error($query);

// löytyi usea sovellus
if (mysql_num_rows($result) > 1) {

  // jos ollaan tulossa loginista, valitaan oletussovellus...
  if (isset($goso) and $goso != "") {
    $query = "SELECT sovellus
              FROM oikeu use index (oikeudet_index)
              WHERE yhtio = '$kukarow[yhtio]' and
              kuka        = '$kukarow[kuka]' and
              sovellus    = '$goso'
              ORDER BY sovellus, jarjestys
              LIMIT 1";
    $gores = mysql_query($query) or pupe_error($query);
    $gorow = mysql_fetch_array($gores);
    $sovellus = $gorow["sovellus"];
  }

  echo "  <form name='vaihdaSovellus' method='POST' action='indexvas.php'>
      <select name='sovellus' onchange='submit()' ".js_alasvetoMaxWidth("sovellus", 140)." >"; // top right bottom left

  $sovellukset = array();

  while ($orow = mysql_fetch_array($result)) {
    $sovellukset[$orow['sovellus']] = t($orow['sovellus']);
  }

  //sortataan array phpssä jotta se menee kielestä riippumatta oikeeseen järjestykseen
  //käyetään asort funktiota koska se ei riko mun itse antamia array-indexejä
  asort($sovellukset, SORT_STRING);

  foreach ($sovellukset as $key => $val) {
    $sel = '';
    if (isset($sovellus) and $sovellus == $key) $sel = "SELECTED";

    echo "<option value='$key' $sel>$val</option>";

    // sovellus on tyhjä kun kirjaudutaan sisään, ni otetaan eka..
    if (!isset($sovellus) or $sovellus == '') $sovellus = $key;
  }

  echo "</select></form><br><br>";
}
else {
  // löytyi vaan yksi sovellus, otetaan se
  $orow = mysql_fetch_array($result);
  $sovellus = $orow['sovellus'];
}

  echo "<table style='padding:0; margin:0; width:135px;'>";

// Mitä käyttäjä saa tehdä?
// Valitaan ensin vain ylätaso jarjestys2='0'

$query = "SELECT nimi, jarjestys
          FROM oikeu use index (sovellus_index)
          WHERE yhtio    = '$kukarow[yhtio]'
          and kuka       = '$kukarow[kuka]'
          and sovellus   = '$sovellus'
          and jarjestys2 = '0'
          and hidden     = ''
          ORDER BY jarjestys";
$result = mysql_query($query) or pupe_error($query);

while ($orow = mysql_fetch_array($result)) {

  // tutkitaan onko meillä alamenuja
  $query = "SELECT nimi, nimitys, alanimi
            FROM oikeu use index (sovellus_index)
            WHERE yhtio   = '$kukarow[yhtio]'
            and kuka      = '$kukarow[kuka]'
            and sovellus  = '$sovellus'
            and jarjestys = '$orow[jarjestys]'
            and hidden    = ''
            ORDER BY jarjestys, jarjestys2";
  $xresult = mysql_query($query) or pupe_error($query);
  $mrow = mysql_fetch_array($xresult);

  $nimitys_lukumaara = "";

  if ($mrow['nimi'] == 'extranet_tarjoukset_ja_ennakot.php' and stristr($mrow['alanimi'], "EXTENNAKKO")) {
    $ennakoiden_lukumaara = hae_kayttajaan_liitetyn_asiakkaan_extranet_ennakot($kukarow['oletus_asiakas']);
    if ($ennakoiden_lukumaara > 0) {
      $nimitys_lukumaara = " <span style='font-weight: bold;'>({$ennakoiden_lukumaara})</span>";
    }
  }

  if ($mrow['nimi'] == 'extranet_tarjoukset_ja_ennakot.php' and stristr($mrow['alanimi'], "EXTTARJOUS")) {
    $tarjousten_lukumaara = hae_kayttajaan_liitetyn_asiakkaan_extranet_tarjoukset($kukarow['oletus_asiakas']);
    if ($tarjousten_lukumaara > 0) {
      $nimitys_lukumaara = " <span style='font-weight: bold;'>({$tarjousten_lukumaara})</span>";
    }
  }

  // alamenuja löytyy, eli tämä on menu
  if (mysql_num_rows($xresult) > 1) {

    // jos ykkönen niin näytetään avattu menu itemi
    if (isset($mrow['nimitys']) and isset($menu[$mrow['nimitys']]) and $menu[$mrow['nimitys']] == 1) {
      echo "<tr><td class='back' style='padding:0px; margin:0px;'><a class='menu' href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=0'>- ".t("$mrow[nimitys]")."</a></td></tr>";

      // tehdään submenu itemit
      while ($mrow = mysql_fetch_array($xresult)) {
        echo "<tr><td class='back' style='padding:0px; margin:0px;'><a class='menu' href='$mrow[nimi]";

        if (strpos($mrow['nimi'], '?') === FALSE) {
          echo "?";
        }
        else {
          echo "&";
        }

        if ($mrow['alanimi'] != '') {
          echo "toim=$mrow[alanimi]&indexvas=1";
        }
        else {
          echo "indexvas=1";
        }

        echo "' target='main'>  &bull; ".t("$mrow[nimitys]")."</a></td></tr>";
      }
    }
    else {
      // muuten näytetään suljettu menuotsikko
      echo "<tr><td class='back' style='padding:0px; margin:0px;'><a class='menu' href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=1'>+ ".t("$mrow[nimitys]")."</a></td></tr>";
    }
  }
  else {
    // normaali menuitem

    // voidaan käyttää kukarow muuttujia osoitteissa
    $mrow["nimi"] = str_replace('$kukarow[kuka]',     "$kukarow[kuka]",     $mrow["nimi"]);
    $mrow["nimi"] = str_replace('$kukarow[yhtio]',    "$kukarow[yhtio]",    $mrow["nimi"]);
    $mrow["nimi"] = str_replace('$kukarow[salasana]', "$kukarow[salasana]", $mrow["nimi"]);
    $target = "";

    // jos ollaan menossa ulkopuolelle, niin alanimessä voidaan passata hreffin target
    if (substr($mrow["nimi"], 0, 4) == "http" and $mrow["alanimi"] != "") {
      $target = "target='$mrow[alanimi]'";
      $mrow["alanimi"] = "";
    }

    echo "<tr><td class='back' style='padding:0px; margin:0px;'><a class='menu' $target href='$mrow[nimi]";

    if (strpos($mrow['nimi'], '?') === FALSE) {
      echo "?";
    }
    else {
      echo "&";
    }

    if ($mrow['alanimi'] != '') {
      echo "toim=$mrow[alanimi]&indexvas=1";

      if ($tultiin == "futur") {
        echo "&ostoskori=$ostoskori&tultiin=$tultiin";
      }
    }
    elseif ($mrow['alanimi'] == '' and $tultiin == "futur") {
      echo "ostoskori=$ostoskori&tultiin=$tultiin&indexvas=1";
    }
    else {
      echo "indexvas=1";
    }

    echo "' target='main'>".t("$mrow[nimitys]")."{$nimitys_lukumaara}</a></td></tr>";
  }

}

//Näytetään aina exit-nappi
echo "<tr><td class='back' style='padding:0px; margin:0px;'><br></td></tr>";
echo "<tr><td class='back' style='padding:0px; margin:0px;'><a class='menu' href='logout.php' target='main'>".t("Kirjaudu ulos")."</a></td></tr>";

echo "</table>";
echo "</body></html>";
