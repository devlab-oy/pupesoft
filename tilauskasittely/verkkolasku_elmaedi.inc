<?php

if (!function_exists('elmaedi_otsik')) {
  function elmaedi_otsik($tootedi, $lasrow, $masrow, $tyyppi, $timestamppi) {
    global $yhtiorow;

    $fstat = fstat($tootedi);

    if ($fstat["size"] == 0) {
      fputs($tootedi, "ICHGSTART:$timestamppi\n");
      fputs($tootedi, "ICHG_TYPE:POS\n");
      fputs($tootedi, "ICHG_SNDR:".sprintf("%-35.35s", str_replace('-', '', $yhtiorow["ovttunnus"]))."@30\n");
      fputs($tootedi, "ICHG_RCPT:003705655815                       @30\n");
      fputs($tootedi, "ICHG_DATA:EIH-1.4.0\n");
      fputs($tootedi, "ICHG_TEST:1\n");
      fputs($tootedi, "ICHG_INFO:TESTITULOKSET PUPESOFTILLE\n");
    }

    // Kirjotetaan laskun tietoja Elman inhouse EDI muotoon
    fputs($tootedi, "IMSGSTART:".substr($lasrow["tunnus"], -6)."\n");
    fputs($tootedi, "IHDRSTART:".substr($lasrow["tunnus"], -6)."\n");
    fputs($tootedi, "IBGMITYPE:$tyyppi\n");
    fputs($tootedi, "IBGMINUMB:$lasrow[laskunro]\n");
    fputs($tootedi, "IDTM3__DT:".sprintf("%-35.35s", vlas_dateconv($lasrow["tapvm"]))."@102\n");
    fputs($tootedi, "IDTM171DT:".sprintf("%-35.35s", vlas_dateconv($lasrow["tapvm"]))."@102\n");
    fputs($tootedi, "IRFFPK_NU:$lasrow[tunnus]\n");
    fputs($tootedi, "IRFFVN_NU:$lasrow[viesti]\n");
    fputs($tootedi, "INADSE_CC:$lasrow[viesti]\n");
    if ($yhtiorow["pankkitili1"] != "") fputs($tootedi, "INADSE_FI:BF @$yhtiorow[pankkitili1]\n");
    if ($yhtiorow["pankkitili2"] != "") fputs($tootedi, "INADSE_FI:BF @$yhtiorow[pankkitili2]\n");
    if ($yhtiorow["pankkitili3"] != "") fputs($tootedi, "INADSE_FI:BF @$yhtiorow[pankkitili3]\n");
    fputs($tootedi, "IRFFCO_NU:$lasrow[asiakkaan_tilausnumero]\n");
    fputs($tootedi, "IFTXAAITX:$lasrow[ytunnus]\n");
    fputs($tootedi, "IFTXAAITX:$lasrow[ovttunnus]\n");
    fputs($tootedi, "IFTXAAITX:\n");
    fputs($tootedi, "IFTXAAITX:\n");
    fputs($tootedi, "INADSE_PC:".sprintf("%-17.17s", $yhtiorow["ovttunnus"])."@100\n");
    fputs($tootedi, "INADSE_PC:".sprintf("%-17.17s", "7002479")."@92\n");
    fputs($tootedi, "INADSE_NA:$yhtiorow[nimi]\n");
    fputs($tootedi, "INADSE_SA:$yhtiorow[osoite]\n");
    fputs($tootedi, "INADSE_CI:$yhtiorow[postitp]\n");
    fputs($tootedi, "INADSE_PO:$yhtiorow[postino]\n");
    fputs($tootedi, "INADPL_RF:IT @$lasrow[toim_ovttunnus]\n");
    fputs($tootedi, "INADPL_RF:ZZ @$lasrow[ytunnus]\n");
    fputs($tootedi, "INADPL_NA:$lasrow[toim_nimi]\n");
    fputs($tootedi, "INADPL_NX:$lasrow[toim_nimitark]\n");
    fputs($tootedi, "INADPL_SA:$lasrow[toim_osoite]\n");
    fputs($tootedi, "INADPL_CI:$lasrow[toim_postitp]\n");
    fputs($tootedi, "INADPL_PO:$lasrow[toim_postino]\n");
    fputs($tootedi, "INADIV_NA:$lasrow[nimi]\n");
    fputs($tootedi, "INADIV_NX:$lasrow[nimitark]\n");
    fputs($tootedi, "INADIV_SA:$lasrow[osoite]\n");
    fputs($tootedi, "INADIV_CI:$lasrow[postitp]\n");
    fputs($tootedi, "INADIV_PO:$lasrow[postino]\n");
    fputs($tootedi, "INADDP_NA:$lasrow[toim_nimi]\n");
    fputs($tootedi, "INADDP_NX:$lasrow[toim_nimitark]\n");
    fputs($tootedi, "INADDP_SA:$lasrow[toim_osoite]\n");
    fputs($tootedi, "INADDP_CI:$lasrow[toim_postitp]\n");
    fputs($tootedi, "INADDP_PO:$lasrow[toim_postino]\n");
    fputs($tootedi, "ICUX1__CR:$yhtiorow[valkoodi]\n");
    fputs($tootedi, "IPAT1__DT:13 @".sprintf("%-35.35s", vlas_dateconv($lasrow["erpcm"]))."@102\n");
    fputs($tootedi, "IPAT1__PC:15 @$lasrow[viikorkopros]\n");
    fputs($tootedi, "IPAT1__TP:$masrow[teksti]\n");

    if ($lasrow["kasumma"] != 0) {
      fputs($tootedi, "IPAT8__DT:12 @".sprintf("%-35.35s", vlas_dateconv($lasrow["kapvm"]))."@102\n");
      fputs($tootedi, "IPAT8__PC:12 @$masrow[kassa_alepros]\n");
      fputs($tootedi, "IPAT8__MA:12 @".sprintf("%018.2f", $lasrow["kasumma"])."@$yhtiorow[valkoodi]\n");
    }

    fputs($tootedi, "IRFFPQ_NU:$lasrow[viite]\n");
    fputs($tootedi, "IMOA39_MA:$lasrow[summa]\n");                // laskun verollinen loppusumma
    fputs($tootedi, "IMOA125MA:$lasrow[arvo]\n");                // laskun veroton loppusumma
    fputs($tootedi, "IMOA124MA:".($lasrow["summa"] - $lasrow["arvo"])."\n");  // veron osuus

  }
}

if (!function_exists('elmaedi_alvierittely')) {
  function elmaedi_alvierittely($tootedi, $alvrow) {
    fputs($tootedi, "ITAXVATTX:{$alvrow['alv']}\n");
    fputs($tootedi, "ITAXVATMA:125@{$alvrow['rivihinta']}\n");
    fputs($tootedi, "ITAXVATMA:150@{$alvrow['alvrivihinta']}\n");
  }
}

if (!function_exists('elmaedi_otsikko_loput')) {
  function elmaedi_otsikko_loput($tootedi, $lasrow) {

    fputs($tootedi, "IHDR__END:".substr($lasrow["tunnus"], -6)."\n");
  }
}

if (!function_exists('elmaedi_rivi')) {
  function elmaedi_rivi($tootedi, $tilrow, $rivinumero) {
    global $yhtiorow;

    $query = "SELECT eankoodi
              from tuote
              where yhtio='$yhtiorow[yhtio]' and tuoteno='$tilrow[tuoteno]'";
    $eanres = pupe_query($query);
    $eanrow = mysql_fetch_assoc($eanres);

    fputs($tootedi, "ILINSTART:$rivinumero\n");
    fputs($tootedi, "ILINEN_NU:$eanrow[eankoodi]\n");

    if ($yhtiorow["yhtio"] == 'mast' and $tilrow['tuoteno'] == $yhtiorow["rahti_tuotenumero"]) {
      fputs($tootedi, "ILINMF_PI:".sprintf("%-35.35s", '9943')."@5\n");
    }
    else {
      fputs($tootedi, "ILINMF_PI:".sprintf("%-35.35s", $tilrow['tuoteno'])."@5\n");
    }

    fputs($tootedi, "ILIN8__IF:       @   @   @   @".sprintf("%-35.35s", $tilrow['nimitys'])."\n");
    fputs($tootedi, "ILIN47_QT:".sprintf("%017.2f", $tilrow["kpl"])."@$tilrow[yksikko]\n");
    fputs($tootedi, "ILININVIV:".sprintf("%017.2f", $tilrow["hinta"])."@   @   @   @          1@PCE\n");
    fputs($tootedi, "ILIN203MA:$tilrow[rivihinta]\n");

    $alennukset_yhteensa = generoi_alekentta_php($tilrow, 'M', 'plain');

    if ($alennukset_yhteensa > 0) {
      fputs($tootedi, "ILIN___AL:\n");
      fputs($tootedi, "ILIN___PA:1  @$alennukset_yhteensa\n");
    }

    fputs($tootedi, "ILINVATTX:$tilrow[alv]\n");
    fputs($tootedi, "ILIN69_DT:" . sprintf("%-35.35s", vlas_dateconv($tilrow["toimitettuaika"])) . "@102\n");
    fputs($tootedi, "ILIN__END:$rivinumero\n");
  }
}

if (!function_exists('elmaedi_lasku_loppu')) {
  function elmaedi_lasku_loppu($tootedi, $lasrow) {

    fputs($tootedi, "IMSG__END:".substr($lasrow["tunnus"], -6)."\n");
  }
}

if (!function_exists('elmaedi_aineisto_loppu')) {
  function elmaedi_aineisto_loppu($tootedi, $timestamppi) {

    //Lopputägit mukaan, paitsi jos failit on tyhjiä
    $fstat = fstat($tootedi);

    if ($fstat["size"] > 0) {
      fputs($tootedi, "ICHG__END:$timestamppi\n");
    }
  }
}
