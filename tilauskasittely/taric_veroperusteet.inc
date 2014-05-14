<?php

// Sisään otetaan
// $tuorow jossa on tuotteen kaikki tiedot.
// $laskurow jossa on saapumisen tiedot.

// palauttaa $tulliprossa jossa on veroperusteprosentti tai euromäärä/kp tai jotain sinne päin

$nimike_taso1 = $tuorow["tullinimike1"].$tuorow["tullinimike2"];
$nimike_taso2 = $tuorow["tullinimike1"]."00";
$nimike_taso3 = substr($tuorow["tullinimike1"],0,6)."0000";
$nimike_taso4 = substr($tuorow["tullinimike1"],0,4)."000000";
$nimike_taso5 = substr($tuorow["tullinimike1"],0,2)."00000000";

$nimike_taso = $nimike_taso1;

//Tarkistetaan, että nimikkeemme on ilmoituskelpoinen
$query  = "  SELECT *
      from taric_veroperusteet
      where laji = 'nim.dat'
      and nimike = '$nimike_taso1'
      and voim_alkupvm  <= now()
      and voim_loppupvm >= now()";
$tv_res = pupe_query($query);

if (mysql_num_rows($tv_res) == 0) {

  $nimike_taso = $nimike_taso2;

  $query  = "  SELECT *
        from taric_veroperusteet
        where laji = 'nim.dat'
        and nimike = '$nimike_taso2'
        and voim_alkupvm  <= now()
        and voim_loppupvm >= now()";
  $tv_res = pupe_query($query);
}

if (mysql_num_rows($tv_res) == 0) {

  $nimike_taso = $nimike_taso3;

  $query  = "  SELECT *
        from taric_veroperusteet
        where laji = 'nim.dat'
        and nimike = '$nimike_taso3'
        and voim_alkupvm  <= now()
        and voim_loppupvm >= now()";
  $tv_res = pupe_query($query);
}

if (mysql_num_rows($tv_res) == 0) {

  $nimike_taso = $nimike_taso4;

  $query  = "  SELECT *
        from taric_veroperusteet
        where laji = 'nim.dat'
        and nimike = '$nimike_taso4'
        and voim_alkupvm  <= now()
        and voim_loppupvm >= now()";
  $tv_res = pupe_query($query);
}

if (mysql_num_rows($tv_res) == 0) {

  $nimike_taso = $nimike_taso5;

  $query  = "  SELECT *
        from taric_veroperusteet
        where laji = 'nim.dat'
        and nimike = '$nimike_taso5'
        and voim_alkupvm  <= now()
        and voim_loppupvm >= now()";
  $tv_res = pupe_query($query);
}

if (mysql_num_rows($tv_res) == 0) {
  echo "<font class='error'>".t("Nimike ei ole ilmoituskelpoinen").": $nimike_taso1/$nimike_taso ($tuorow[tuoteno]) ".t("Korjaa tuotteen tullinimike")."!</font><br>";
}
else {

  //haetaan maakoodit/-ryhmät jonka toimenpiteitä tulee tarkistaa
  $alkuperamaa = trim($laskurow["maa_lahetys"]);
  $maat = "";

  $query  = "  SELECT *
        FROM maat
        WHERE koodi in ('$alkuperamaa')";
  $tv_maares = pupe_query($query);

  if (mysql_num_rows($tv_maares) > 0) {

    while ($tv_maarow = mysql_fetch_assoc($tv_maares)) {
      if ($tv_maarow["ryhma_tunnus"] != '') {
        $maat .= "'$tv_maarow[ryhma_tunnus]',";
      }
    }

    $maat .= "'".$alkuperamaa."'";
  }
  else {
    $maat = "''";
  }


  //Haetaan oikeat toimenpiteet nimikeelle prioriteettijärjestyksessä
  $query  = "  SELECT *
        from taric_veroperusteet use index (nimike_index)
        where laji = 'tp.dat'
        and nimike = '$nimike_taso1'
        and maa_ryhma in ($maat)
        and voim_alkupvm  <= now()
        and voim_loppupvm >= now()
        ORDER BY maara desc, maa_ryhma";
  $tv_nimres = pupe_query($query);

  if (mysql_num_rows($tv_nimres) == 0) {
    $query  = "  SELECT *
          from taric_veroperusteet use index (nimike_index)
          where laji = 'tp.dat'
          and nimike = '$nimike_taso2'
          and maa_ryhma in ($maat)
          and voim_alkupvm  <= now()
          and voim_loppupvm >= now()
          ORDER BY maara desc, maa_ryhma";
    $tv_nimres = pupe_query($query);
  }

  if (mysql_num_rows($tv_nimres) == 0) {
    $query  = "  SELECT *
          from taric_veroperusteet use index (nimike_index)
          where laji = 'tp.dat'
          and nimike = '$nimike_taso3'
          and maa_ryhma in ($maat)
          and voim_alkupvm  <= now()
          and voim_loppupvm >= now()
          ORDER BY maara desc, maa_ryhma";
    $tv_nimres = pupe_query($query);
  }

  if (mysql_num_rows($tv_nimres) == 0) {
    $query  = "  SELECT *
          from taric_veroperusteet use index (nimike_index)
          where laji = 'tp.dat'
          and nimike = '$nimike_taso4'
          and maa_ryhma in ($maat)
          and voim_alkupvm  <= now()
          and voim_loppupvm >= now()
          ORDER BY maara desc, maa_ryhma";
    $tv_nimres = pupe_query($query);
  }

  if (mysql_num_rows($tv_nimres) == 0) {
    $query  = "  SELECT *
          from taric_veroperusteet use index (nimike_index)
          where laji = 'tp.dat'
          and nimike = '$nimike_taso5'
          and maa_ryhma in ($maat)
          and voim_alkupvm  <= now()
          and voim_loppupvm >= now()
          ORDER BY maara desc, maa_ryhma";
    $tv_nimres = pupe_query($query);
  }

  // Toivottavasti löytyi jokin toimenpide
  if (mysql_num_rows($tv_nimres) == 0) {
    if ($mista != 'tuote.php') {
      echo "<font class='error'>".t("Ei löytynyt yhtään toimenpidettä tuotteelle/nimikkeelle").": $tuorow[tuoteno]/$nimike_taso1 ".t("Alkuperämaa/maaryhmät").": $alkuperamaa/$maat ".t("Korjaa tuotteen tullinimike")."!</font><br>";
    }
  }
  else {
    ///* Luupataan löydetyt toimentpiteet *///
    while ($tv_nimrow = mysql_fetch_assoc($tv_nimres)) {

      // Haetaan toimepiteestä poissuljetut maat
      $query  = "  SELECT *
            from taric_veroperusteet use index (fyysinen_avain_index)
            where laji = 'exc.dat'
            and toimenpide_id= '$tv_nimrow[toimenpide_id]'
            and maa_ryhma in ('$alkuperamaa')";
      $tv_res = pupe_query($query);

      //jos maamme ei ole poissuljettujen maiden joukossa, niin olemme löytäneet sopivan toimenpiteen
      if (mysql_num_rows($tv_res) == 0) {
        // Haetaan toimenpiteen alaviitteet
        $query  = "  SELECT *
              from taric_veroperusteet use index (fyysinen_avain_index)
              where laji = 'alav.dat'
              and toimenpide_id= '$tv_nimrow[toimenpide_id]'";
        $tv_alavres = pupe_query($query);


        $tulliprossa = $tv_nimrow["maara"];

        //echo "Löytyi toimenpide:  $tv_nimrow[nimike], $tv_nimrow[maara] $tv_nimrow[rahayksikko] ($tv_nimrow[voim_alkupvm] $tv_nimrow[voim_loppupvm])<br>";

        //lopetetaan luuppi ekaan sopivaan
        break;
      }
    }
  }
}
