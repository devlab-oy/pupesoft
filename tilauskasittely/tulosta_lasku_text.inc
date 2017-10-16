<?php


// defaultteja
$sivu = 1;

if (!function_exists('text_alku')) {
  function text_alku() {
    global $sisalto, $laskurow, $yhtiorow, $kukarow, $sivu, $maksuehto, $kieli, $factoringlask;


    $sisalto .= sprintf('%-55.55s', t("Lasku alkaa", $kieli));
    $sisalto .= sprintf('%-15.15s', t("LASKU", $kieli));
    $sisalto .= sprintf('%-10.10s', t("Sivu", $kieli)." ".$sivu);



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    $sisalto .= sprintf('%-45.45s', $yhtiorow["nimi"]);



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', t("Ostaja", $kieli).":");
    $sisalto .= sprintf('%-25.25s', t("Laskun pvm", $kieli).":");
    $sisalto .= sprintf('%-20.20s', t("Er‰p‰iv‰", $kieli).":");



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', $laskurow["nimi"]." ".$laskurow["nimitark"]);
    $sisalto .= sprintf('%-25.25s', $laskurow["tapvm"]);
    $sisalto .= sprintf('%-20.20s', $laskurow["erpcm"]);



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', $laskurow["osoite"]);
    $sisalto .= sprintf('%-25.25s', t("Viitenumero", $kieli).":");
    $sisalto .= sprintf('%-20.20s', t("Laskun numero", $kieli).":");



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', $laskurow["postino"]." ".$laskurow["postitp"]." ".$laskurow["maa"]);
    $sisalto .= sprintf('%-25.25s', $laskurow["viite"]);
    $sisalto .= sprintf('%-20.20s', $laskurow["laskunro"]);



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    $sisalto .= sprintf('%-25.25s', t("Maksuehto", $kieli).":");
    $sisalto .= sprintf('%-20.20s', t("Asiaa hoitaa", $kieli).":");



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', t("Toimitusosoite", $kieli).":");
    $sisalto .= sprintf('%-25.25s', $maksuehto);
    //etsit‰‰n myyj‰n nimi
    $query  = "  select nimi
          from kuka
          where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
    $myyresult = pupe_query($query);
    $myyrow = mysql_fetch_array($myyresult);
    $sisalto .= sprintf('%-20.20s', $myyrow["nimi"]);


    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', $laskurow["toim_nimi"]." ".$laskurow["toim_nimitark"]);
    $sisalto .= sprintf('%-25.25s', t("Viiv‰styskorko", $kieli).":");
    $sisalto .= sprintf('%-20.20s', t("Valuutta", $kieli).":");



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', $laskurow["toim_osoite"]);
    $sisalto .= sprintf('%-25.25s', $laskurow["viikorkopros"]);
    $sisalto .= sprintf('%-20.20s', $laskurow["valkoodi"]);



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', $laskurow["toim_postino"]." ".$laskurow["toim_postitp"]." ".$laskurow["toim_maa"]);
    $sisalto .= sprintf('%-25.25s', t("Kassa-alennus pvm", $kieli).":");
    $sisalto .= sprintf('%-20.20s', t("Kassa-alennus", $kieli)." ".$laskurow["valkoodi"].":");



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    if ($laskurow["kapvm"] != '0000-00-00') {
      $sisalto .= sprintf('%-25.25s', $laskurow["kapvm"]);
    }
    else {
      $sisalto .= sprintf('%-20.20s', '');
    }
    if ($laskurow["kasumma"] != 0.00) {
      $sisalto .= sprintf('%-20.20s', $laskurow["kasumma"]);
    }
    else {
      $sisalto .= sprintf('%-20.20s', '');
    }


    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    $sisalto .= sprintf('%-25.25s', t("Asiakkaan Y-tunnus", $kieli).":");
    $sisalto .= sprintf('%-20.20s', t("Asiakasnumero", $kieli).":");



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    $sisalto .= sprintf('%-25.25s', $laskurow["ytunnus"]);
    $sisalto .= sprintf('%-20.20s', $laskurow["ytunnus"]);



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    $sisalto .= sprintf('%-25.25s', t("Toimitustapa", $kieli).":");
    $sisalto .= sprintf('%-20.20s', t("Toimituspvm", $kieli).":");



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    $sisalto .= sprintf('%-25.25s', t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli));

    //  Onko meill‰ monta toimitusp‰iv‰‰ vai ei?
    if ($laskurow["tila"] == 'U') {
      $where = " uusiotunnus='$laskurow[tunnus]' ";
    }
    else {
      $where = " otunnus='$laskurow[tunnus]' ";
    }

    if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K') {
      $toimitettuaikak = " if(keratty = 'saldoton' and toimaika != '0000-00-00', toimaika, toimitettuaika) ";
    }
    elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
      $toimitettuaikak = " if(toimaika != '0000-00-00', toimaika, toimitettuaika) ";
    }
    else {
      $toimitettuaikak = " toimitettuaika ";
    }

    $query = "SELECT date_format($toimitettuaikak, '%Y-%m-%d') toimitettupaiva
              FROM tilausrivi
              WHERE yhtio='$kukarow[yhtio]'
              and $where
              and toimitettuaika!='0000-00-00'
              GROUP BY toimitettupaiva";
    $toimaikares=pupe_query($query);

    if (mysql_num_rows($toimaikares) > 1) {
      $sisalto .= t("useita");
    }
    else {
      $sisalto .= sprintf('%-20.20s', $laskurow["toimaika"]);
    }

    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    $sisalto .= sprintf('%-25.25s', t("Tilausviite", $kieli).":");



    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-35.35s', '');
    $sisalto .= sprintf('%-50.50s', $laskurow["viesti"]);
    $sisalto .= "\r\n";

    //Laskurivien otsikkotiedot
    //eka rivi
    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-25.25s', t("Tuotenumero", $kieli));
    $sisalto .= sprintf('%-8.8s',   t("M‰‰r‰", $kieli));
    $sisalto .= sprintf('%-12.12s', t("A-hinta", $kieli));

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      $sisalto .= sprintf('%-15.15s', t("Alennus", $kieli)."-%");
    }

    $sisalto .= sprintf('%-15.15s', t("Veroton arvo", $kieli));
    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-25.25s', t("Tuotenimi", $kieli));
    $sisalto .= sprintf('%-8.8s',   t("Alv", $kieli)."-%");
    $sisalto .= sprintf('%-12.12s', t("Yksikkˆ", $kieli));
    $sisalto .= sprintf('%-15.15s', t("Toimituspvm", $kieli));
    $sisalto .= sprintf('%-15.15s', t("Verollinen arvo", $kieli));
    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-25.25s', t("Tilausnumero", $kieli)."/".t("Lis‰tietoja", $kieli));
    $sisalto .= "\r\n";
    $sisalto .= "\r\n";

    $factoringlask = 24;

  }
}

if (!function_exists('text_rivi')) {
  function text_rivi() {
    global $sisalto, $row, $sivu, $factoringlask, $kieli;


    if ($factoringlask >= 68) {
      $sivu++;
      text_loppu();
      text_alku();
    }

    $sisalto .= sprintf('%-25.25s', $row["tuoteno"]);
    $sisalto .= sprintf('%-8.8s', $row["kpl"]);
    $sisalto .= sprintf('%-12.12s', $row["hinta"]);

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      $sisalto .= sprintf('%-15.15s', $row["ale{$alepostfix}"]);
    }

    $sisalto .= sprintf('%-15.15s', $row["rivihinta"]);
    $sisalto .= "\r\n";

    $sisalto .= sprintf('%-25.25s', substr($row["nimitys"], 0, 22));
    $sisalto .= sprintf('%-8.8s', $row["alv"]);
    $sisalto .= sprintf('%-12.12s', t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite"));
    $sisalto .= sprintf('%-15.15s', substr($row["toimitettuaika"], 0, 10));
    $verollinenhinta = round($row["rivihinta"]*(1+($row["alv"]/100)), 2);
    $verollinenhinta = sprintf('%.2f', $verollinenhinta );
    $sisalto .= sprintf('%-15.15s', $verollinenhinta);
    $sisalto .= "\r\n";

    if ($row["kommentti"] != '') {
      $sisalto .= sprintf('%-80.80s', $row["otunnus"]."/".$row["kommentti"]);
    }
    else {
      $sisalto .= sprintf('%-80.80s', $row["otunnus"]);
    }
    $sisalto .= "\r\n\r\n";

    $factoringlask += 4;
  }
}

if (!function_exists('text_loppu')) {
  function text_loppu() {

    global $sisalto, $laskurow, $yhtiorow, $kukarow, $sivu, $kieli, $factoringlask, $frow;


    while ($factoringlask < 71) {
      $sisalto .= "\r\n";
      $factoringlask++;
    }

    //Factoringkommentti
    $sisalto .= trim($frow["teksti_se"]);
    $sisalto .= "\r\n";

    //Yhteens‰rivi
    $sisalto .= sprintf('%-25.25s', t("Er‰p‰iv‰", $kieli).": ".$laskurow["erpcm"]);
    $sisalto .= sprintf('%-25.25s', t("Viitenumero", $kieli).": ".$laskurow["viite"]);
    $sisalto .= sprintf('%-25.25s', t("Yhteens‰", $kieli).": ".$laskurow["summa"]." ".$laskurow["valkoodi"]);
    $sisalto .= "\r\n";

    //Pankkiyhteystiedot
    $sisalto .= sprintf('%-25.25s', $frow["pankkinimi1"]." ".$frow["pankkitili1"]);
    $sisalto .= sprintf('%-25.25s', $frow["pankkinimi2"]." ".$frow["pankkitili2"]);
    $sisalto .= "\r\n";
    $sisalto .= "\r\n";

    //Alimmat kolme laatikkoa, yhtiˆtietoja
    $sisalto .= sprintf('%-25.25s', $yhtiorow["nimi"]);
    $sisalto .= sprintf('%-50.50s', t("Puhelin", $kieli).": ".$yhtiorow["puhelin"]);

    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-25.25s', $yhtiorow["osoite"]);
    $sisalto .= sprintf('%-50.50s', t("Fax", $kieli).": ".$yhtiorow["fax"]);
    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-25.25s', $yhtiorow["postino"]."  ".$yhtiorow["postitp"]);
    $sisalto .= sprintf('%-50.50s', t("S‰hkˆposti", $kieli).": ".$yhtiorow["email"]);
    $sisalto .= "\r\n";

    $sisalto .= sprintf('%-25.25s', $yhtiorow["maa"]);
    $sisalto .= sprintf('%-50.50s', t("Y-tunnus", $kieli).": ".$yhtiorow["maa"].$yhtiorow["ytunnus"]."   ".t("Alv.Rek"));
    $sisalto .= "\r\n";
  }
}

if (!function_exists('text_alvierittely')) {
  function text_alvierittely() {
    global $sisalto, $laskurow, $yhtiorow, $kukarow, $sivu, $factoringlask, $kieli;

    //loppusummat vain vikalle sivulle
    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-22.22s', '');
    $sisalto .= sprintf('%-30.30s', t("Veroton arvo yhteens‰", $kieli).":");
    $sisalto .= sprintf('%-15.15s', $laskurow["arvo"]);
    $sisalto .= sprintf('%-8.8s', $laskurow["valkoodi"]);

    $alvquery = "SELECT distinct alv
                 FROM tilausrivi
                 WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
                 ORDER BY alv";
    $alvresult = pupe_query($alvquery);

    $factoringlask++;

    while ($alvrow = mysql_fetch_array($alvresult)) {
      $aquery = "SELECT round(sum(rivihinta*(alv/100)),2) alvrivihinta, sum(rivihinta) rivihinta
                 FROM tilausrivi
                 WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'
                 ORDER BY alv";
      $aresult = pupe_query($aquery);
      $arow = mysql_fetch_array($aresult);
      $sisalto .= "\r\n";
      $sisalto .= sprintf('%-22.22s', '');
      $sisalto .= sprintf('%-30.30s', t("Arvonlis‰vero", $kieli)." (".$arow["rivihinta"]."): ");
      $sisalto .= sprintf('%-15.15s', $arow["alvrivihinta"]);
      $sisalto .= sprintf('%-8.8s', $alvrow["alv"]."%");

      $factoringlask++;
    }
    $sisalto .= "\r\n";
    $sisalto .= sprintf('%-22.22s', '');
    $sisalto .= sprintf('%-30.30s', t("Lasku Yhteens‰", $kieli).":");
    $sisalto .= sprintf('%-15.15s', $laskurow["summa"]);
    $sisalto .= sprintf('%-8.8s', $laskurow["valkoodi"]);

    $factoringlask++;

    if ($loppumyos == 1) {
      text_loppu();
    }
  }
}
