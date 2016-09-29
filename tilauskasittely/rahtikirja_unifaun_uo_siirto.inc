<?php

require_once 'rajapinnat/unifaun/unifaun-functions.php';

// Pupesoft -> Unifaun Online datasiirto

// jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($yhtiorow['kerayserat'] != 'K' and $kollityht > 0) {

  if ($unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {

    require_once "inc/unifaun_send.inc";

    if ($phpnimi == "rahtikirja_custom.php") {
      $postirow = $osoitelappurow;
      $rakir_row = $osoitelappurow;
    }
    else {
      $query = "SELECT *
                from lasku
                where yhtio = '$kukarow[yhtio]'
                and tunnus  in ($otunnukset)
                order by tunnus
                limit 1";
      $tempr = pupe_query($query);
      $postirow = mysql_fetch_assoc($tempr);
    }

    // Maksuehdon tiedot
    $query = "SELECT *
              FROM maksuehto
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$postirow['maksuehto']}'";
    $mehto_res = pupe_query($query);
    $mehto = mysql_fetch_assoc($mehto_res);

    // Kirjoittimen unifaun-nimi
    $query = "SELECT unifaun_nimi
              FROM kirjoittimet
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$kirjoitin_tunnus}'";
    $kirjoitin_res = pupe_query($query);
    $kirjoitin_row = mysql_fetch_assoc($kirjoitin_res);

    // Rahtikirjatiedot
    //$pakkaus[]
    //$pakkauskuvaus[]
    //$pakkauskuvaustark[]
    //$kilot[]
    //$kollit[]
    //$kuutiot[]
    //$lavametri[]
    //$shipment_idt[]

    $vak_tiedot = unifaun_tilauksen_vak_koodit($postirow['tunnus']);

    $unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);

    $postirow['shipment_unique_id'] = $postirow['tunnus'];
    $postirow['kuljetusohjeet']   = $kuljetusohjeet;

    $unifaun->setYhtioRow($yhtiorow);
    $unifaun->setKukaRow($kukarow);
    $unifaun->setPostiRow($postirow);
    $unifaun->setToimitustapaRow($toitarow);
    $unifaun->setMehto($mehto);
    $unifaun->setRahtikirjaRow($rakir_row);
    $unifaun->setYhteensa($laskurow['summa']);

    if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
      $unifaun->setViite($laskurow['viite']);
    }
    else {
      $unifaun->setViite($laskurow['viesti']);
    }

    $unifaun->setKirjoitin($kirjoitin_row['unifaun_nimi']);
    $unifaun->_getXML();

    foreach ($pakkaus as $indeksi => $pakkaus_selite) {

      $kilot[$indeksi]   = round($kilot[$indeksi]/$kollit[$indeksi], 2);
      $kuutiot[$indeksi] = round($kuutiot[$indeksi]/$kollit[$indeksi], 2);

      // Napataan pakkauksen tiedot
      $query = "SELECT pakkaus.leveys, pakkaus.korkeus, pakkaus.syvyys, pakkaus.pakkauskuvaus, ifnull(pakkauskoodit.koodi, pakkaus.pakkaus) koodi, pakkaus.pakkaus
                FROM pakkaus
                LEFT JOIN pakkauskoodit ON (pakkaus.yhtio=pakkauskoodit.yhtio AND pakkaus.tunnus=pakkauskoodit.pakkaus AND pakkauskoodit.rahdinkuljettaja = '{$toitarow['rahdinkuljettaja']}')
                WHERE pakkaus.yhtio       = '$kukarow[yhtio]'
                and pakkaus.pakkaus       = '{$pakkaus[$indeksi]}'
                and pakkaus.pakkauskuvaus = '{$pakkauskuvaus[$indeksi]}'";
      $pakkaus_res = pupe_query($query);
      $pakkaus_row = mysql_fetch_assoc($pakkaus_res);

      if ($keraysera_row['pakkaus'] == '999') {
        $pakkaus_row['leveys'] = $pakkaus_row['leveys'] < 0.1 ? 0.1 : $pakkaus_row['leveys'];
        $pakkaus_row['korkeus'] = $pakkaus_row['korkeus'] < 0.1 ? 0.1 : $pakkaus_row['korkeus'];
        $pakkaus_row['syvyys'] = $pakkaus_row['syvyys'] < 0.1 ? 0.1 : $pakkaus_row['syvyys'];
      }

      $kollitiedot = array(
        'maara'         => $kollit[$indeksi],
        'paino'         => $kilot[$indeksi],
        'pakkauskuvaus' => $pakkaus_row['pakkauskuvaus'],
        'pakkauskoodi'  => $pakkaus_row['koodi'],
        'leveys'        => $pakkaus_row['leveys'],
        'korkeus'       => $pakkaus_row['korkeus'],
        'syvyys'        => $pakkaus_row['syvyys'],
        'vakkoodi'      => $vak_tiedot,
        'kuutiot'       => $kuutiot[$indeksi],
        'lavametri'     => $lavametri[$indeksi]
      );

      /* VOIDAAN LÄHETTÄÄ MYÖS MONTA CONTAINER-ROWTA PER SANOMA! YKSI HEADER */
      $unifaun->setContainerRow($kollitiedot);
    }

    $unifaun->ftpSend();
  }
  else {
    echo "<br><font class='error'>".t("UNIFAUN-aineiston luontiin tarvittavia parametreja puuttuu!")."</font><br>";
  }
}
