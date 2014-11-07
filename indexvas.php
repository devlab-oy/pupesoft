<?php

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once "inc/parametrit.inc");
elseif (@include_once "parametrit.inc");
else exit;

// estet‰‰n errorit tyhj‰st‰ arrayst‰
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

// mit‰ sovelluksia k‰ytt‰j‰ saa k‰ytt‰‰
$query = "SELECT distinct sovellus
          FROM oikeu use index (oikeudet_index)
          WHERE yhtio = '$kukarow[yhtio]'
          and kuka    = '$kukarow[kuka]'
          $extralisa
          ORDER BY sovellus";
$result = pupe_query($query);

// lˆytyi usea sovellus
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
    $gores = pupe_query($query);
    $gorow = mysql_fetch_array($gores);
    $sovellus = $gorow["sovellus"];
  }

  echo "<form name='vaihdaSovellus' method='POST' action='indexvas.php'>
      <select name='sovellus' class='indexvas' onchange='submit()'>"; // top right bottom left

  $sovellukset = array();

  while ($orow = mysql_fetch_array($result)) {
    $sovellukset[$orow['sovellus']] = t($orow['sovellus']);
  }

  //sortataan array phpss‰ jotta se menee kielest‰ riippumatta oikeeseen j‰rjestykseen
  //k‰yet‰‰n asort funktiota koska se ei riko mun itse antamia array-indexej‰
  asort($sovellukset, SORT_STRING);

  foreach ($sovellukset as $key => $val) {
    $sel = '';
    if (isset($sovellus) and $sovellus == $key) $sel = "SELECTED";

    echo "<option class='menu' value='$key' $sel>$val</option>";

    // sovellus on tyhj‰ kun kirjaudutaan sis‰‰n, ni otetaan eka..
    if (!isset($sovellus) or $sovellus == '') $sovellus = $key;
  }

  echo "</select></form><br><br>";
}
else {
  // lˆytyi vaan yksi sovellus, otetaan se
  $orow = mysql_fetch_array($result);
  $sovellus = $orow['sovellus'];
}

echo "<table class='indexvas'>";

// Mit‰ k‰ytt‰j‰ saa tehd‰?
// Valitaan ensin vain yl‰taso jarjestys2='0'

$query = "SELECT nimi, jarjestys
          FROM oikeu use index (sovellus_index)
          WHERE yhtio    = '$kukarow[yhtio]'
          and kuka       = '$kukarow[kuka]'
          and sovellus   = '$sovellus'
          and jarjestys2 = '0'
          and hidden     = ''
          ORDER BY jarjestys";
$result = pupe_query($query);

while ($orow = mysql_fetch_array($result)) {

  // tutkitaan onko meill‰ alamenuja
  $query = "SELECT nimi, nimitys, alanimi
            FROM oikeu use index (sovellus_index)
            WHERE yhtio   = '$kukarow[yhtio]'
            and kuka      = '$kukarow[kuka]'
            and sovellus  = '$sovellus'
            and jarjestys = '$orow[jarjestys]'
            and hidden    = ''
            ORDER BY jarjestys, jarjestys2";
  $xresult = pupe_query($query);
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

  // alamenuja lˆytyy, eli t‰m‰ on menu
  if (mysql_num_rows($xresult) > 1) {

    // jos ykkˆnen niin n‰ytet‰‰n avattu menu itemi
    if (isset($mrow['nimitys']) and isset($menu[$mrow['nimitys']]) and $menu[$mrow['nimitys']] == 1) {
      echo "<tr><td><a href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=0'>- ".t("$mrow[nimitys]")."</a></td></tr>";

      // tehd‰‰n submenu itemit
      while ($mrow = mysql_fetch_array($xresult)) {
        echo "<tr><td><a href='$mrow[nimi]";

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

        echo "' target='mainframe'>  &bull; ".t("$mrow[nimitys]")."</a></td></tr>";
      }
    }
    else {
      // muuten n‰ytet‰‰n suljettu menuotsikko
      echo "<tr><td><a href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=1'>+ ".t("$mrow[nimitys]")."</a></td></tr>";
    }
  }
  else {
    // normaali menuitem

    // voidaan k‰ytt‰‰ kukarow muuttujia osoitteissa
    $mrow["nimi"] = str_replace('$kukarow[kuka]',     "$kukarow[kuka]",     $mrow["nimi"]);
    $mrow["nimi"] = str_replace('$kukarow[yhtio]',    "$kukarow[yhtio]",    $mrow["nimi"]);
    $mrow["nimi"] = str_replace('$kukarow[salasana]', "$kukarow[salasana]", $mrow["nimi"]);
    $target = "";

    // jos ollaan menossa ulkopuolelle, niin alanimess‰ voidaan passata hreffin target
    if (substr($mrow["nimi"], 0, 4) == "http" and $mrow["alanimi"] != "") {
      $target = "target='$mrow[alanimi]'";
      $mrow["alanimi"] = "";
    }

    echo "<tr><td><a $target class='indexvaslink' href='$mrow[nimi]";

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

    echo "' target='mainframe'>".t("$mrow[nimitys]")."{$nimitys_lukumaara}</a></td></tr>";
  }

}

//N‰ytet‰‰n aina exit-nappi
echo "<tr><td><br></td></tr>";
echo "<tr><td><a href='logout.php' target='mainframe'>".t("Kirjaudu ulos")."</a></td></tr>";
echo "</table><br>";

echo "<div class='showhide_vasen' id='maaginen_vasen'><img src='{$palvelin2}pics/lullacons/switch_gray.png'></div>";

echo "
  <script>
      
      $('.indexvaslink').click(function(){
        $('.indexvaslink').removeClass('indexvaslink_aktivoitu');
        $(this).addClass('indexvaslink_aktivoitu');
      });

      $(document).ready(function(){
        $(\"#maaginen_vasen\").click(function(){
           if (parent.document.getElementsByTagName('frameset')[1].cols==\"345,*\") {
             parent.document.getElementsByTagName('frameset')[1].cols=\"20,*\";
           }
           else {
             parent.document.getElementsByTagName('frameset')[1].cols=\"345,*\";
           }
        });
      });
      </script>";

echo "</body></html>";
