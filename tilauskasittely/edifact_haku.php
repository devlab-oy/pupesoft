<?php

require "../inc/parametrit.inc";

if ($task == 'B1') {

  $sanoma = "UNB+UNOC:2+003708274241:30+003706664131:30+140916:0839+KM0001++++++'UNH+1+IFTMBF:D:97B:UN:EXWL12'BGM+335+KTKANT24091+9'DTM+137:201409160839:203'RFF+VON:KV0001'RFF+CU:TILAUS-0001:1'TDT+20++1++MSCFI+++H3JN:103::MSC IRIS'LOC+5+FIKTKC::86:Kotka Mussalo'DTM+133:201411010000:203'LOC+8+BEANR::86:ANTWERPEN'DTM+132:201411080000:203'RFF+VON:KTKANT24091'TDT+30++1++MSCFI+++3FUT9:103::MSC LAURENCE'LOC+5++BEANR::86'DTM+133:201412010000:203'LOC+8+AUSYD::86'DTM+132:201412020000:203'RFF+VON:ANTAU05101'NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'NAD+CZ+003708274241:100'NAD+FX+SPOTPRESS++SPOTPRESS+24-26 Lilian Fowler Place+Marrickville++NSW 2204+AU'NAD+TR+STEVECO'GID++4:RL'LOC+20+BEANR:16'PIA+1+4810 22:HS'FTX+ZSM++LOLO'FTX+AAA+++PAPER'FTX+PRD+++Solaris (New) 1.55 57 g/m2'FTX+TRA+++max netto paino20to/ kontti (24to/kontti brutto)'FTX+TRA+++1  x 20?' kontti'MEA+AAE+CT+RO:1'MEA+AAE+WD+MMT:700'MEA+AAE+DI+MMT:1250'MEA+AAE+G+KGM:4000'RFF+CU:TILAUS-0001:1'EQD+CN++C20'EQN+1'RFF+AKC:KV0001'NAD+CW+MSCFI'NAD+ZST+FIMUSSALO'UNT+40+1'UNZ+1+KM0001'";

  kasittele_bookkaussanoma($sanoma);

}


if ($task == 'B2') {

  $sanoma = "UNB+UNOC:2+003708274241:30+003706664131:30+140916:0839+KM0002++++++'UNH+1+IFTMBF:D:97B:UN:EXWL12'BGM+335+KTKANT24091+9'DTM+137:201409160840:203'RFF+VON:KV0001'RFF+CU:TILAUS-0001:2'TDT+20++1++MSCFI+++H3JN:103::MSC IRIS'LOC+5+FIKTKC::86:Kotka Mussalo'DTM+133:201411010000:203'LOC+8+BEANR::86:ANTWERPEN'DTM+132:201411080000:203'RFF+VON:KTKANT24091'TDT+30++1++MSCFI+++3FUT9:103::MSC LAURENCE'LOC+5++BEANR::86'DTM+133:201412010000:203'LOC+8+AUSYD::86'DTM+132:201412020000:203'RFF+VON:ANTAU05101'NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'NAD+CZ+003708274241:100'NAD+FX+SPOTPRESS++SPOTPRESS+24-26 Lilian Fowler Place+Marrickville++NSW 2204+AU'NAD+TR+STEVECO'GID++3:RL'LOC+20+BEANR:16'PIA+1+4810 20:HS'FTX+ZSM++LOLO'FTX+AAA+++PAPER'FTX+PRD+++Solaris (New) 1.55 57 g/m2'FTX+TRA+++max netto paino20to/ kontti (24to/kontti brutto)'FTX+TRA+++1  x 20?' kontti'MEA+AAE+CT+RO:1'MEA+AAE+WD+MMT:800'MEA+AAE+DI+MMT:1250'MEA+AAE+G+KGM:4000'RFF+CU:TILAUS-0001:2'EQD+CN++C20'EQN+1'RFF+AKC:KV0001'NAD+CW+MSCFI'NAD+ZST+FIMUSSALO'UNT+40+1'UNZ+1+KM0001'";

  kasittele_bookkaussanoma($sanoma);

}

if ($task == 'B3') {

  $sanoma = "UNB+UNOC:2+003708274241:30+003706664131:30+140916:0839+KM0004++++++'UNH+1+IFTMBF:D:97B:UN:EXWL12'BGM+335+MATKA123+9'DTM+137:201409160839:203'RFF+VON:KV0002'RFF+CU:TILAUS-0002:1'TDT+20++1++MSCFI+++H3JN:103::MSC IRIS'LOC+5+FIKTKC::86:Kotka Mussalo'DTM+133:201411010000:203'LOC+8+BEANR::86:ANTWERPEN'DTM+132:201411080000:203'RFF+VON:MATKA123'TDT+30++1++MSCFI+++3FUT9:103::MSC LAURENCE'LOC+5++BEANR::86'DTM+133:201412010000:203'LOC+8+AUSYD::86'DTM+132:201412020000:203'RFF+VON:JATKO001'NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'NAD+CZ+003708274241:100'NAD+FX+SPOTPRESS++SPOTPRESS+24-26 Lilian Fowler Place+Marrickville++NSW 2204+AU'NAD+TR+STEVECO'GID++8:RL'LOC+20+BEANR:16'PIA+1+4810 22:HS'FTX+ZSM++LOLO'FTX+AAA+++PAPER'FTX+PRD+++Solaris (New) 1.55 57 g/m2'FTX+TRA+++max netto paino20to/ kontti (24to/kontti brutto)'FTX+TRA+++1  x 20?' kontti'MEA+AAE+CT+RO:1'MEA+AAE+WD+MMT:1050'MEA+AAE+DI+MMT:1250'MEA+AAE+G+KGM:30000'RFF+CU:TILAUS-0002:1'EQD+CN++C20'EQN+2'RFF+AKC:KV0002'NAD+CW+MSCFI'NAD+ZST+FIMUSSALO'UNT+40+1'UNZ+1+KM0004'";

  kasittele_bookkaussanoma($sanoma);

}


if ($task == 'R1') {

  $sanoma = "UNB+UNOC:2+003708274241:30+003706800420:30+141024:1032+KM0003++++++'UNH+1+DESADV:D:97B:UN:EXWL15'BGM+15+RK0001+9'DTM+137:201410241020:203'NAD+FX+STEVECO::86++Steveco+Steveco+Kotka++48101+FI'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+10++3++RP::86:RP Logistics Oy+++WLY-834'LOC+5+FIKTKM::86:KOTKA'DTM+180:201410300000:203'DTM+143:201410300605:203'LOC+8+FIKTKC::86:KOTKA, MUSSALO'DTM+132:201410101600:203'CPS+MOL'PAC+4++RL'MEA+AAE+AAL+KGM:4000'MEA+AAE+G+KGM:4000'MEA+AAE+WD+MMT:700'MEA+AAE+DI+MMT:1250'PCI+16+MERINOPA-EMAIL 08.06.2013-'LIN+1'PIA+1+4810 22:HS'NAD+OS+003708274241:100++Kotkamills Oy'NAD+SB+SEINDIA::86++SE INDIA'NAD+FX+MERINOPA::86++MERINOPA'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+20++1+++++D5DM7:::CHARLOTTA B'FTX+AAA+++PAPER'RFF+CU:TILAUS-0001:1'RFF+VON:KTKANT24091'DTM+ZEL:201407010000:203'LOC+5+FIKTKM::86:Kotka warehouse'LOC+8+FIKTKC::86:Kotka'LOC+20+MERINOPA::86:HARYANA'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:700'PCI+999'GIN+ZUN+0001'GIN+ZPI+1'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:700'PCI+999'GIN+ZUN+0002'GIN+ZPI+2'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:700'PCI+999'GIN+ZUN+0003'GIN+ZPI+3'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:700'PCI+999'GIN+ZUN+0004'GIN+ZPI+4'CPS+MOL'PAC+3++RL'MEA+AAE+AAL+KGM:3000'MEA+AAE+G+KGM:3000'MEA+AAE+WD+MMT:800'MEA+AAE+DI+MMT:1250'PCI+16+MERINOPA-EMAIL 08.06.2013-'LIN+1'PIA+1+4810 20:HS'NAD+OS+003708274241:100++Kotkamills Oy'NAD+SB+SEINDIA::86++SE INDIA'NAD+FX+MERINOPA::86++MERINOPA'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+20++1+++++D5DM7:::CHARLOTTA B'FTX+AAA+++PAPER'RFF+CU:TILAUS-0001:2'RFF+VON:KTKANT24091'DTM+ZEL:201407010000:203'LOC+5+FIKTKM::86:Kotka warehouse'LOC+8+FIKTKC::86:Kotka'LOC+20+MERINOPA::86:HARYANA'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:800'PCI+999'GIN+ZUN+0005'GIN+ZPI+5'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:800'PCI+999'GIN+ZUN+0006'GIN+ZPI+6'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:800'PCI+999'GIN+ZUN+0007'GIN+ZPI+7'UNT+117+1'UNZ+1+KM0003'";

  kasittele_rahtikirjasanoma($sanoma);

}

if ($task == 'R2') {

  $sanoma ="UNB+UNOC:2+003708274241:30+003706800420:30+141024:1032+KM0005++++++'UNH+1+DESADV:D:97B:UN:EXWL15'BGM+15+RK0002+9'DTM+137:201410241020:203'NAD+FX+STEVECO::86++Steveco+Steveco+Kotka++48101+FI'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+10++3++RP::86:RP Logistics Oy+++XYP-555'LOC+5+FIKTKM::86:KOTKA'DTM+180:201410300000:203'DTM+143:201410300605:203'LOC+8+FIKTKC::86:KOTKA, MUSSALO'DTM+132:201410101600:203'CPS+MOL'PAC+8++RL'MEA+AAE+AAL+KGM:30000'MEA+AAE+G+KGM:30000'MEA+AAE+WD+MMT:1050'MEA+AAE+DI+MMT:1250'PCI+16+MERINOPA-EMAIL 08.06.2013-'LIN+1'PIA+1+4810 55:HS'NAD+OS+003708274241:100++Kotkamills Oy'NAD+SB+SEINDIA::86++SE INDIA'NAD+FX+MERINOPA::86++MERINOPA'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+20++1+++++D5DM7:::CHARLOTTA B'FTX+AAA+++PAPER'RFF+CU:TILAUS-0002:1'RFF+VON:MATKA123'DTM+ZEL:201407010000:203'LOC+5+FIKTKM::86:Kotka warehouse'LOC+8+FIKTKC::86:Kotka'LOC+20+MERINOPA::86:HARYANA'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0008'GIN+ZPI+1'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0009'GIN+ZPI+2'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0010'GIN+ZPI+3'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0011'GIN+ZPI+4'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0012'GIN+ZPI+5'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0013'GIN+ZPI+6'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0014'GIN+ZPI+7'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0015'GIN+ZPI+8'UNT+117+1'UNZ+1+KM0005'";

  kasittele_rahtikirjasanoma($sanoma);

}

if ($task == 'B4') {

  $sanoma = "UNB+UNOC:2+003708274241:30+003706664131:30+140916:0839+KM0006++++++'UNH+1+IFTMBF:D:97B:UN:EXWL12'BGM+335+MATKA123+9'DTM+137:201409160839:203'RFF+VON:KV0002'RFF+CU:TILAUS-0003:1'TDT+20++1++MSCFI+++H3JN:103::MSC IRIS'LOC+5+FIKTKC::86:Kotka Mussalo'DTM+133:201411010000:203'LOC+8+BEANR::86:ANTWERPEN'DTM+132:201411080000:203'RFF+VON:MATKA123'TDT+30++1++MSCFI+++3FUT9:103::MSC LAURENCE'LOC+5++BEANR::86'DTM+133:201412010000:203'LOC+8+AUSYD::86'DTM+132:201412020000:203'RFF+VON:JATKO001'NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'NAD+CZ+003708274241:100'NAD+FX+SPOTPRESS++SPOTPRESS+24-26 Lilian Fowler Place+Marrickville++NSW 2204+AU'NAD+TR+STEVECO'GID++2:RL'LOC+20+BEANR:16'PIA+1+4810 22:HS'FTX+ZSM++LOLO'FTX+AAA+++PAPER'FTX+PRD+++Solaris (New) 1.55 57 g/m2'FTX+TRA+++max netto paino20to/ kontti (24to/kontti brutto)'FTX+TRA+++1  x 20?'kontti'MEA+AAE+CT+RO:1'MEA+AAE+WD+MMT:1050'MEA+AAE+DI+MMT:1250'MEA+AAE+G+KGM:30000'RFF+CU:TILAUS-0003:1'EQD+CN++C20'EQN+2'RFF+AKC:KV0002'NAD+CW+MSCFI'NAD+ZST+FIMUSSALO'UNT+40+1'UNZ+1+KM0006'";

  kasittele_bookkaussanoma($sanoma);

}

if ($task == 'R3') {

  $sanoma ="UNB+UNOC:2+003708274241:30+003706800420:30+141024:1032+KM0007++++++'UNH+1+DESADV:D:97B:UN:EXWL15'BGM+15+RK0003+9'DTM+137:201410241020:203'NAD+FX+STEVECO::86++Steveco+Steveco+Kotka++48101+FI'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+10++3++RP::86:RP Logistics Oy+++XYP-555'LOC+5+FIKTKM::86:KOTKA'DTM+180:201410300000:203'DTM+143:201410300605:203'LOC+8+FIKTKC::86:KOTKA, MUSSALO'DTM+132:201410101600:203'CPS+MOL'PAC+1++RL'MEA+AAE+AAL+KGM:2000'MEA+AAE+G+KGM:2000'MEA+AAE+WD+MMT:1050'MEA+AAE+DI+MMT:1250'PCI+16+MERINOPA-EMAIL 08.06.2013-'LIN+1'PIA+1+4810 55:HS'NAD+OS+003708274241:100++Kotkamills Oy'NAD+SB+SEINDIA::86++SE INDIA'NAD+FX+MERINOPA::86++MERINOPA'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+20++1+++++D5DM7:::CHARLOTTA B'FTX+AAA+++PAPER'RFF+CU:TILAUS-0003:1'RFF+VON:MATKA123'DTM+ZEL:201407010000:203'LOC+5+FIKTKM::86:Kotka warehouse'LOC+8+FIKTKC::86:Kotka'LOC+20+MERINOPA::86:HARYANA'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0016'GIN+ZPI+1'UNT+117+1'UNZ+1+KM0007'";

  kasittele_rahtikirjasanoma($sanoma);

}

if ($task == 'R4') {

  $sanoma ="UNB+UNOC:2+003708274241:30+003706800420:30+141024:1032+KM0008++++++'UNH+1+DESADV:D:97B:UN:EXWL15'BGM+15+RK0004+9'DTM+137:201410241020:203'NAD+FX+STEVECO::86++Steveco+Steveco+Kotka++48101+FI'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+10++3++RP::86:RP Logistics Oy+++XYP-555'LOC+5+FIKTKM::86:KOTKA'DTM+180:201410300000:203'DTM+143:201410300605:203'LOC+8+FIKTKC::86:KOTKA, MUSSALO'DTM+132:201410101600:203'CPS+MOL'PAC+1++RL'MEA+AAE+AAL+KGM:2000'MEA+AAE+G+KGM:2000'MEA+AAE+WD+MMT:1050'MEA+AAE+DI+MMT:1250'PCI+16+MERINOPA-EMAIL 08.06.2013-'LIN+1'PIA+1+4810 55:HS'NAD+OS+003708274241:100++Kotkamills Oy'NAD+SB+SEINDIA::86++SE INDIA'NAD+FX+MERINOPA::86++MERINOPA'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+20++1+++++D5DM7:::CHARLOTTA B'FTX+AAA+++PAPER'RFF+CU:TILAUS-0003:1'RFF+VON:MATKA123'DTM+ZEL:201407010000:203'LOC+5+FIKTKM::86:Kotka warehouse'LOC+8+FIKTKC::86:Kotka'LOC+20+MERINOPA::86:HARYANA'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:1000'MEA+AAE+AAL+KGM:1000'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0017'GIN+ZPI+1'UNT+117+1'UNZ+1+KM0008'
";

  kasittele_rahtikirjasanoma($sanoma);

}

if ($task == 'I1') {

  $sanoma ="UNB+UNOC:2+003708274241:30+003706800420:30+140919:0146+92477++++++'
UNH+2057831+IFTSTA:D:99B:UN:SE0015'
BGM+132+KTKANT24091+5'
DTM+137:201409190146:203'
LOC+5+FIKTKC'
CNI+1'
STS+1'
EQD+CN+KONTTI1'
RFF+CU:KTKANT24091'
RFF+ZMR:MRN001'
UNT+9+2057831'
UNZ+1+92477'";

  kasittele_iftsta($sanoma);

}


if ($task == 'I2') {

  $sanoma ="UNB+UNOC:2+003708274241:30+003706800420:30+140919:0146+92477++++++'
UNH+2057831+IFTSTA:D:99B:UN:SE0015'
BGM+132+MATKA123+5'
DTM+137:201409190146:203'
LOC+5+FIKTKC'
CNI+1'
STS+1'
EQD+CN+KONTTI2'
RFF+CU:MATKA123'
RFF+ZMR:MRN001'
UNT+9+2057831'
UNZ+1+92477'";

  kasittele_iftsta($sanoma);

}

if ($task == 'I3') {

  $sanoma ="UNB+UNOC:2+003708274241:30+003706800420:30+140919:0146+92477++++++'
UNH+2057831+IFTSTA:D:99B:UN:SE0015'
BGM+132+MATKA123+5'
DTM+137:201409190146:203'
LOC+5+FIKTKC'
CNI+1'
STS+1'
EQD+CN+KONTTI1'
RFF+CU:MATKA123'
RFF+ZMR:MRN002'
UNT+9+2057831'
UNZ+1+92477'";

  kasittele_iftsta($sanoma);

}


if ($task == 'nollaa') {
  $taulut = array(
      "tilausrivi",
      "tilausrivin_lisatiedot",
      "lasku",
      "laskun_lisatiedot",
      "sarjanumeroseuranta",
      "liitetiedostot");

  foreach ($taulut as $taulu) {
    $query = "TRUNCATE TABLE {$taulu}";
    pupe_query($query);
  }
}

if ($task == 'hae') {

  $host = $ftp_info['host'];
  $user = $ftp_info['user'];
  $pass = $ftp_info['pass'];

  // Connect to host
  $yhteys = ftp_connect($host);

  // Open a session to an external ftp site
  $login = ftp_login($yhteys, $user, $pass);

  // Check open
  if ((!$yhteys) || (!$login)) {
    echo t("Ftp-yhteyden muodostus epaonnistui! Tarkista salasanat."); die;
  }
  else {
    echo t("Ftp-yhteys muodostettu.")."<br/>";
  }

  ftp_chdir($yhteys, 'out-test');

  ftp_pasv($yhteys, true);

  $files = ftp_nlist($yhteys, ".");

  foreach ($files as $file) {

  if (ftp_mdtm($yhteys, $file) > 1414590822) {

    if (substr($file, -3) == 'IFF') {
      $bookkaukset[] = $file;
    }

    if (substr($file, -3) == 'DAD') {
      $rahtikirjat[] = $file;
    }


  }


/*

    if (substr($file, -3) == 'IFF') {
      $bookkaukset[] = $file;
    }

    if (substr($file, -3) == 'DAD') {
      $rahtikirjat[] = $file;
    }

    if (substr($file, -3) == 'IFT') {
      $iftstat[] = $file;
    }

    */

  }


  foreach ($bookkaukset as $bookkaus) {
    $temp_file = tempnam("/tmp", "IFF-");
    ftp_get($yhteys, $temp_file, $bookkaus, FTP_ASCII);
    $edi_data = file_get_contents($temp_file);
    kasittele_bookkaussanoma($edi_data);
    unlink($temp_file);
  }

  foreach ($rahtikirjat as $rahtikirja) {
    $temp_file = tempnam("/tmp", "DAD-");
    ftp_get($yhteys, $temp_file, $rahtikirja, FTP_ASCII);
    $edi_data = file_get_contents($temp_file);
    kasittele_rahtikirjasanoma($edi_data);
    unlink($temp_file);
  }

  foreach ($iftstat as $iftsta) {
    $temp_file = tempnam("/tmp", "IFT-");
    ftp_get($yhteys, $temp_file, $iftsta, FTP_ASCII);
    $edi_data = file_get_contents($temp_file);
    kasittele_iftsta($edi_data);
    unlink($temp_file);
  }

  ftp_close($yhteys);

}
else{



  echo "
  <font class='head'>".t("Sanomien haku")."</font>  <br><hr>
  <form action='' method='post'>
    <input type='hidden' name='task' value='hae' />
    <input type='submit' value='".t("Hae sanomat (ftp)")."'>
  </form>  <br><hr><br>";



  echo "
  <font class='head'>".t("Testaus")."</font>

  <br><br>

  TILAUS-0001<br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='B1' />
    <input type='submit' value='".t("Hae bookkaus 1.")."'>
  </form>


  <form action='' method='post'>
    <input type='hidden' name='task' value='B2' />
    <input type='submit' value='".t("Hae bookkaus 2.")."'>
  </form>



  <form action='' method='post'>
    <input type='hidden' name='task' value='R1' />
    <input type='submit' value='".t("Hae rahtikirja 1.")."'>
  </form>


  <br><hr><br>

  TILAUS-0002<br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='B3' />
    <input type='submit' value='".t("Hae bookkaus 3.")."'>
  </form>



  <form action='' method='post'>
    <input type='hidden' name='task' value='R2' />
    <input type='submit' value='".t("Hae rahtikirja 2.")."'>
  </form>

  <br><hr><br>

  TILAUS-0003<br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='B4' />
    <input type='submit' value='".t("Hae bookkaus 4.")."'>
  </form>



  <form action='' method='post'>
    <input type='hidden' name='task' value='R3' />
    <input type='submit' value='".t("Hae rahtikirja 3.")."'>
  </form>



  <form action='' method='post'>
    <input type='hidden' name='task' value='R4' />
    <input type='submit' value='".t("Hae rahtikirja 4.")."'>
  </form>

  <br><hr><br>

  TILAUS-0003<br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='I1' />
    <input type='submit' value='".t("Hae IFTSTA 1.")."'>
  </form>



  <form action='' method='post'>
    <input type='hidden' name='task' value='I2' />
    <input type='submit' value='".t("Hae IFTSTA 2.")."'>
  </form>



  <form action='' method='post'>
    <input type='hidden' name='task' value='I3' />
    <input type='submit' value='".t("Hae IFTSTA 3.")."'>
  </form>

  <br><hr><br>



  <form action='' method='post'>
    <input type='hidden' name='task' value='nollaa' />
    <input type='submit' value='".t("Nollaa tilanne")."'>
  </form>


  ";


}

require "inc/footer.inc";


function kasittele_iftsta($edi_data) {
  global $kukarow;

  $edi_data = str_replace("\n", "", $edi_data);
  $liitedata = $edi_data;
  $edi_data = str_replace("?'", "#%#", $edi_data);
  $edi_data = explode("'", $edi_data);

  $rivimaara = count($edi_data);


  foreach ($edi_data as $key => $rivi) {

    trim($rivi);

    $rivi = str_replace("#%#", "'", $rivi);

    // katsotaan onko viesti alkuperäinen vai korvaava (9 vai 5)
    // tulee ehkä olemaan oleellinen tieto
    if (substr($rivi, 0, 3) == 'BGM') {
      $osat = explode("+", $rivi);
      $matkakoodi = $osat[2];
      $tyyppi = $osat[3];
    }

    if (substr($rivi, 0, 6) == 'EQD+CN') {
      $osat = explode("+", $rivi);
      $konttinumero = $osat[2];
    }

    if (substr($rivi, 0, 7) == 'RFF+ZMR' and !isset($konttiviite)) {
      $osat = explode("+", $rivi);
      $mrn_info = $osat[1];
      $mrn_info_osat = explode(":", $mrn_info);
      $mrn = $mrn_info_osat[1];
    }
  }

  $query = "SELECT group_concat(otunnus)
            FROM laskun_lisatiedot
            WHERE matkakoodi = '{$matkakoodi}'";
  $result = pupe_query($query);
  $laskutunnukset = mysql_result($result, 0);

  $query = "SELECT group_concat(trlt.tunnus)
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tilausrivi.yhtio
              AND trlt.tilausrivitunnus = tilausrivi.tunnus
            WHERE tilausrivi.otunnus IN ({$laskutunnukset})
            AND trlt.konttinumero = '{$konttinumero}'";
  $result = pupe_query($query);
  $tunnukset = mysql_result($result, 0);

  $update_query = "UPDATE tilausrivin_lisatiedot SET
                   kontin_mrn  = '{$mrn}'
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus IN ({$tunnukset})";
  pupe_query($update_query);

}


function kasittele_bookkaussanoma($edi_data) {
  global $kukarow;

  $edi_data = str_replace("\n", "", $edi_data);
  $liitedata = $edi_data;
  $edi_data = str_replace("?'", "#%#", $edi_data);
  $edi_data = explode("'", $edi_data);

  $rivimaara = count($edi_data);

  $rahti = array();
  $pakkaukset = array();
  $tilaukset = array();

  foreach ($edi_data as $key => $rivi) {

    trim($rivi);

    $rivi = str_replace("#%#", "'", $rivi);

    if (substr($rivi, 0, 3) == 'UNB') {
      $osat = explode("+", $rivi);

      /* näillä ei nyt olekaan vielä käyttöä
      $vastaanottaja_ovt_info = $osat[3];
      $vastaanottaja_ovt_info_osat = explode(":", $vastaanottaja_ovt_info);
      $vastaanottaja_ovt = $vastaanottaja_ovt_info_osat[0];

      $lahettaja_ovt_info = $osat[2];
      $lahettaja_ovt_info_osat = explode(":", $lahettaja_ovt_info);
      $lahettaja_ovt = $lahettaja_ovt_info_osat[0];
      */

      $sanoma_id = $osat[5];
    }

    // katsotaan onko viesti alkuperäinen vai korvaava (9 vai 5)
    // tulee ehkä olemaan oleellinen tieto
    if (substr($rivi, 0, 3) == 'BGM') {
      $osat = explode("+", $rivi);
      $matkakoodi = $osat[2];
      $tyyppi = $osat[3];
    }

    if (substr($rivi, 0, 7) == 'RFF+VON' and !isset($konttiviite)) {
      $osat = explode("+", $rivi);
      $konttiviite_info = $osat[1];
      $konttiviite_info_osat = explode(":", $konttiviite_info);
      $konttiviite = $konttiviite_info_osat[1];
    }

    if (substr($rivi, 0, 6) == "RFF+CU" and !isset($tilausnro)) {
      $osat = explode("+", $rivi);
      $tilaus_info = $osat[1];
      $tilaus_info_osat = explode(":", $tilaus_info);
      $tilausnro = $tilaus_info_osat[1];
      $rivinro = $tilaus_info_osat[2];
    }

    if (substr($rivi, 0, 6) == 'TDT+20') {

      $osat = explode("+", $rivi);

      $carrier_id = $osat[5];

      $transport_info = $osat[8];
      $transport_info_osat = explode(":", $transport_info);
      $transport_id = $transport_info_osat[0];
      $transport_name = $transport_info_osat[3];

      $valmis = false;
      $luetaan = $key;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+5") {
          $osat = explode("+", $edi_data[$luetaan]);
          $lahtopaikka_info = $osat[2];
          $lahtopaikka_info_osat = explode(":", $lahtopaikka_info);
          $lahtopaikka_id = $lahtopaikka_info_osat[0];
          $lahtopaikka_nimi = $lahtopaikka_info_osat[3];
        }

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {
          $osat = explode("+", $edi_data[$luetaan]);
          $valisatama_info = $osat[2];
          $valisatama_info_osat = explode(":", $valisatama_info);
          $valisatama_id = $valisatama_info_osat[0];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 6) == "TDT+30" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }
    }


    if (substr($rivi, 0, 6) == 'TDT+30') {

      $osat = explode("+", $rivi);

      $jatko_transport_info = $osat[8];
      $jatko_transport_info_osat = explode(":", $jatko_transport_info);
      $jatko_transport_id = $jatko_transport_info_osat[0];
      $jatko_transport_name = $jatko_transport_info_osat[3];

      $valmis = false;
      $luetaan = $key;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {

          $osat = explode("+", $edi_data[$luetaan]);
          $maaranpaa_info = $osat[2];
          $maaranpaa_info_osat = explode(":", $maaranpaa_info);
          $maaranpaa_id = $maaranpaa_info_osat[0];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "TDT+30" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }
    }

    if (substr($rivi, 0, 7) == "DTM+133" and !isset($lahtopvm)) {
      $osat = explode("+", $rivi);
      $lahto_info = $osat[1];
      $lahto_info_osat = explode(":", $lahto_info);
      $lahtoaika = $lahto_info_osat[1];
      $vuosi = substr($lahtoaika, 0, 4);
      $kuu = substr($lahtoaika, 4, 2);
      $paiva = substr($lahtoaika, 6, 2);
      $lahtopvm = $vuosi."-".$kuu."-".$paiva;
    }

    if (substr($rivi, 0, 7) == "FTX+TRA" and !isset($ohje)) {
      $osat = explode("+", $rivi);
      $ohje = $osat[4];
    }

    if (substr($rivi, 0, 6) == 'EQD+CN') {
      $osat = explode("+", $rivi);
      $konttityyppi = $osat[3];
    }

    if (substr($rivi, 0, 3) == 'EQN') {
      $osat = explode("+", $rivi);
      $konttimaara = $osat[1];
    }

    if (substr($rivi, 0, 3) == 'GID') {
      $osat = explode("+", $rivi);
      $rulla_info = $osat[2];
      $rulla_info_osat = explode(":", $rulla_info);
      $rullamaara = $rulla_info_osat[0];
    }
  }

  $matkatiedot = array(
    'carrier_id' => $carrier_id,
    'transport_id' => $transport_id,
    'transport_name' => $transport_name,
    'lahtopaikka_id' => $lahtopaikka_id,
    'lahtopaikka_nimi' => $lahtopaikka_nimi,
    'valisatama_id' => $valisatama_id,
    'jatko_transport_id' => $jatko_transport_id,
    'jatko_transport_name' => $jatko_transport_name,
    'maaranpaa_id' => $maaranpaa_id
    );

  $matkatiedot = serialize($matkatiedot);
  $matkatiedot = mysql_real_escape_string($matkatiedot);

  // tässä vaiheessa vastaanottaja on aina steveco
  $asiakas_id = 106;

  // tarkistetaan onko tämä sanoma jostakin syystä jo käsitelty
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND filename = '{$sanoma_id}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {
    return false;
  }

  // katsotaan onko tilauksesta luotu jo myyntitilaus
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND asiakkaan_tilausnumero = '{$tilausnro}'
            AND tilaustyyppi = 'N'";
  $result = pupe_query($query);

  $kukarow['kesken'] = 0;

  if (mysql_num_rows($result) == 0) {

    require_once "../tilauskasittely/luo_myyntitilausotsikko.inc";

    $tunnus = luo_myyntitilausotsikko('RIVISYOTTO', $asiakas_id);

    $update_query = "UPDATE lasku SET
                     asiakkaan_tilausnumero = '{$tilausnro}',
                     sisviesti1 = '{$ohje}',
                     toimaika = '{$lahtopvm}'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
    pupe_query($update_query);

    $update_query = "UPDATE laskun_lisatiedot SET
                     konttiviite  = '{$konttiviite}',
                     konttimaara  = '{$konttimaara}',
                     konttityyppi = '{$konttityyppi}',
                     matkakoodi   = '{$matkakoodi}',
                     rullamaara   = '{$rullamaara}',
                     matkatiedot  = '{$matkatiedot}'
                     WHERE yhtio  = '{$kukarow['yhtio']}'
                     AND otunnus  = '{$tunnus}'";
    pupe_query($update_query);

    // katsotaan onko tilaukseen kuuluvia rullia ostotilauksilla
    $query = "SELECT *
              FROM tilausrivin_lisatiedot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tilausnro}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {

      $laskuquery = "SELECT *
                     FROM lasku
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
      $laskuresult = pupe_query($laskuquery);
      $laskurow = mysql_fetch_assoc($laskuresult, 0);

      $kukarow['kesken'] = $laskurow['tunnus'];

      // haetaan tuotteen tiedot
      $tuotequery = "SELECT *
                     FROM tuote
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tuoteno = '123'";
      $tuoteresult = pupe_query($tuotequery);

      $trow = mysql_fetch_assoc($tuoteres);
      $kpl = 1;
      $var = 'P';

      while ($rulla = mysql_fetch_assoc($result)) {

        require "lisaarivi.inc";

        $update_query = "UPDATE tilausrivi
                         SET var2 = 'OK'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $update_query = "UPDATE sarjanumeroseuranta
                         SET myyntirivitunnus = '{$lisatty_tun}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND ostorivitunnus = '{$rulla['tilausrivitunnus']}'";
        pupe_query($update_query);

        $update_query = "UPDATE tilausrivin_lisatiedot SET
                         juoksu = '{$rulla['juoksu']}',
                         tilauksen_paino = '{$rulla['tilauksen_paino']}',
                         asiakkaan_tilausnumero = '{$rulla['asiakkaan_tilausnumero']}'
                         asiakkaan_rivinumero = '{$rulla['asiakkaan_rivinumero']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tilausrivitunnus = '{$lisatty_tun}'";
        pupe_query($update_query);
      }
    }
  }
  else {
    $query = "SELECT lasku.tunnus,
              laskun_lisatiedot.rullamaara
              FROM lasku
              JOIN laskun_lisatiedot
                ON laskun_lisatiedot.yhtio = lasku.yhtio
                AND laskun_lisatiedot.otunnus = lasku.tunnus
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.asiakkaan_tilausnumero = '{$tilausnro}'
              AND lasku.tila = 'N'";
    $result = pupe_query($query);
    $laskuinfo = mysql_fetch_assoc($result);

    $tunnus = $laskuinfo['tunnus'];

    $rullamaara = $rullamaara + $laskuinfo['rullamaara'];

    $update_query = "UPDATE laskun_lisatiedot SET
                     konttiviite  = '{$konttiviite}',
                     konttimaara  = '{$konttimaara}',
                     konttityyppi = '{$konttityyppi}',
                     matkakoodi   = '{$matkakoodi}',
                     rullamaara   = '{$rullamaara}'
                     WHERE yhtio  = '{$kukarow['yhtio']}'
                     AND otunnus  = '{$tunnus}'";
    pupe_query($update_query);
  }

  $filesize = strlen($liitedata);
  $liitedata = mysql_real_escape_string($liitedata);

  // tarkistetaan onko vastaava sanoma jo liitetiedostona
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND selite = '{$tilausnro}:{$rivinro}'
            AND kayttotarkoitus = 'bookkaussanoma'";
  $vastaavuusresult = pupe_query($query);
  $osumia = mysql_num_rows($vastaavuusresult);

  if ($osumia == 0) {

    $query = "INSERT INTO liitetiedostot SET
              yhtio           = '{$kukarow['yhtio']}',
              liitos          = 'lasku',
              liitostunnus    = '$tunnus',
              selite          = '{$tilausnro}:{$rivinro}',
              laatija         = '{$kukarow['kuka']}',
              luontiaika      = NOW(),
              data            = '{$liitedata}',
              filename        = '{$sanoma_id}',
              filesize        = '$filesize',
              filetype        = 'text/plain',
              kayttotarkoitus = 'bookkaussanoma'";
    pupe_query($query);

  }
  elseif ($tyyppi == 5){

    $korvattava = mysql_result($vastaavuusresult, 0);

    $query = "UPDATE liitetiedostot SET
              data        = '$liitedata',
              muutospvm   = NOW(),
              muuttaja    = '{$kukarow['kuka']}',
              filename    = '{$sanoma_id}',
              filesize    = '$filesize'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND  tunnus = '{$korvattava}'";
    pupe_query($query);
  }

  $query = "UPDATE kuka
            SET kesken = 0
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND kuka = '{$kukarow['kuka']}'";
  pupe_query($query);

}

function kasittele_rahtikirjasanoma($edi_data) {
  global $kukarow;

  $edi_data = str_replace("\n", "", $edi_data);

  // otetaan talteen liitetiedoston lisäämistä varten
  $filesize = strlen($edi_data);
  $liitedata = mysql_real_escape_string($edi_data);

  $edi_data = explode("'", $edi_data);

  $rivimaara = count($edi_data);

  // luetaan kaikki rivit
  foreach ($edi_data as $rivi => $value) {

    if (substr($value, 0, 3) == 'UNB') {

      $osat = explode("+", $value);

      $lahettaja_id_info = $osat[2];
      $lahettaja_id_info_osat = explode(":", $lahettaja_id_info);
      $lahettaja_id = $lahettaja_id_info_osat[0];

      $vastaanottaja_id_info = $osat[3];
      $vastaanottaja_id_info_osat = explode(":", $vastaanottaja_id_info);
      $vastaanottaja_id = $vastaanottaja_id_info_osat[0];

      $sanoma_id = $osat[5];

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 3) == "BGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $rahtikirja_id = $osat[2];
          $tyyppi = $osat[3];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "NAD+FX") {
          $osat = explode("+", $edi_data[$luetaan]);
          $vastaanottaja_info = $osat[2];
          $vastaanottaja_info_osat = explode(":", $vastaanottaja_info);
          $vastaanottaja = $vastaanottaja_info_osat[0];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "NAD+CZ") {
          $osat = explode("+", $edi_data[$luetaan]);
          $lahettaja = $osat[4];
        }

        if (substr($edi_data[$luetaan], 0, 3) == "TDT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $kuljettaja_info = $osat[5];
          $kuljettaja_info_osat = explode(":", $kuljettaja_info);
          $kuljettaja = $kuljettaja_info_osat[3];
          $rekno = $osat[8];
        }

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paamaara_info = $osat[2];
          $paamaara_info_osat = explode(":", $paamaara_info);
          $paamaara = $paamaara_info_osat[3];

          // haetaan varaston tiedot
          $query = "SELECT tunnus
                    FROM varastopaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND locate(nimitys, '{$paamaara}') > 0
                    LIMIT 1";
          $varastores = pupe_query($query);
          $varasto_id = mysql_result($varastores,0);
        }

        if (substr($edi_data[$luetaan], 0, 7) == "DTM+132") {
          $osat = explode("+", $edi_data[$luetaan]);
          $toimitusaika_info = $osat[1];
          $toimitusaika_info_osat = explode(":", $toimitusaika_info);
          $toimitusaika = $toimitusaika_info_osat[1];
          $vuosi = substr($toimitusaika, 0,4);
          $kuu = substr($toimitusaika, 4,2);
          $paiva = substr($toimitusaika, 6,2);
          $toimitusaika = $vuosi.'-'.$kuu.'-'.$paiva;
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+MOL" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }

      $rahti = array(
        'sanoma_id' => $sanoma_id,
        'rahtikirja_id' => $rahtikirja_id,
        'tyyppi' => $tyyppi,
        'sender_id' => $lahettaja_id,
        'recipient_id' => $vastaanottaja_id,
        'vastaanottaja' => $vastaanottaja,
        'lahettaja' => $lahettaja,
        'kuljettaja' => $kuljettaja,
        'rekisterinumero' => $rekno,
        'paamaara' => $paamaara,
        'varasto_id' => $varasto_id,
        'toimitusaika' => $toimitusaika
        );
    }

    if (substr($value, 0, 7) == 'CPS+MOL') {

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 15) == "MEA+AAE+AAL+KGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paino_info = $osat[3];
          $paino_info_osat = explode(":", $paino_info);
          $_paino = $paino_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "RFF+CU") {
          $osat = explode("+", $edi_data[$luetaan]);
          $tilaus_info = $osat[1];
          $tilaus_info_osat = explode(":", $tilaus_info);
          $_tilausnro = $tilaus_info_osat[1];
          $_rivi = $tilaus_info_osat[2];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+PKG" or $luetaan >= $rivimaara) {
          $valmis = true;
        }

      }

    }

    if (substr($value, 0, 7) == 'CPS+PKG') {

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 15) == "MEA+AAE+AAL+KGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paino_info = $osat[3];
          $paino_info_osat = explode(":", $paino_info);
          $paino = $paino_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 14) == "MEA+AAE+DI+MMT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $halkaisija_info = $osat[3];
          $halkaisija_info_osat = explode(":", $halkaisija_info);
          $halkaisija = $halkaisija_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 14) == "MEA+AAE+WD+MMT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $leveys_info = $osat[3];
          $leveys_info_osat = explode(":", $leveys_info);
          $leveys = $leveys_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 7) == "GIN+ZUN") {
          $osat = explode("+", $edi_data[$luetaan]);
          $sarjanumero = $osat[2];
        }

        if (substr($edi_data[$luetaan], 0, 7) == "GIN+ZPI") {
          $osat = explode("+", $edi_data[$luetaan]);
          $juoksu = $osat[2];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+PKG" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }

      $tuoteno = '123';

      $rullat[] = array(
        'paino' => $paino,
        'halkaisija' => $halkaisija,
        'leveys' => $leveys,
        'tuoteno' => $tuoteno,
        'juoksu' => $juoksu,
        'sarjanumero' => $sarjanumero,
        'tilausnro' => $_tilausnro,
        'rivinro' => $_rivi,
        'tilauksen_paino' => $_paino
        );

    }
  }// rivit luettu

  $rahti['rullat'] = $rullat;

  $data = $rahti;


  // tarkistetaan onko tämä sanoma jostakin syystä jo käsitelty
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND filename = '{$data['sanoma_id']}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {
    return false;
  }

  require_once "../inc/luo_ostotilausotsikko.inc";

  // haetaan toimittajan tiedot
  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND REPLACE(nimi, ' ', '') = REPLACE('{$data['lahettaja']}', ' ', '')";
  $toimres = pupe_query($query);
  $toimrow = mysql_fetch_assoc($toimres);

  $params = array(
    'liitostunnus' => $toimrow['tunnus'],
    'nimi' => $toimrow['nimi'],
    'myytil_toimaika' => $data['toimitusaika'],
    'varasto' => $data['varasto_id'],
    'osoite' => $toimrow['osoite'],
    'postino' => $toimrow['postino'],
    'postitp' => $toimrow['postitp'],
    'maa' => $toimrow['maa'],
    'edi' => 'X'
  );

  $laskurow = luo_ostotilausotsikko($params);

  // tarkistetaan onko vastaava sanoma jo liitetiedostona
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND CONCAT(liitostunnus,selite) = '{$laskurow['tunnus']}{$data['rahtikirja_id']}'
            AND kayttotarkoitus = 'rahtikirjasanoma'";
  $vastaavuusresult = pupe_query($query);

  if (mysql_num_rows($vastaavuusresult) == 0) {

    $query = "INSERT INTO liitetiedostot set
              yhtio           = '{$kukarow['yhtio']}',
              liitos          = 'lasku',
              selite          = '{$data['rahtikirja_id']}',
              liitostunnus    = '{$laskurow['tunnus']}',
              laatija         = '{$kukarow['kuka']}',
              luontiaika      = now(),
              data            = '$liitedata',
              filename        = '{$data['sanoma_id']}',
              filesize        = '{$filesize}',
              filetype        = 'text/plain',
              kayttotarkoitus = 'rahtikirjasanoma'";
    pupe_query($query);

  }
  elseif ($data['tyyppi'] == 5) {

    $korvattava = mysql_result($vastaavuusresult, 0);

    $query = "UPDATE liitetiedostot SET
              data        = '$liitedata',
              muutospvm   = NOW(),
              muuttaja    = '{$kukarow['kuka']}',
              filename    = '{$data['sanoma_id']}',
              filesize    = '{$filesize}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND  tunnus = '{$korvattava}'";
    pupe_query($query);

  }

  $ostolaskurow = $laskurow;

  foreach ($data['rullat'] as $rulla) {

    $query = "SELECT tunnus
              FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND sarjanumero = '{$rulla['sarjanumero']}'";
    $sarjares = pupe_query($query);

    if (mysql_num_rows($sarjares) == 0) {

      // haetaan tuotteen tiedot
      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$rulla['tuoteno']}'";
      $tuoteres = pupe_query($query);

      $trow = mysql_fetch_assoc($tuoteres);
      $kpl = 1;
      $kerayspvm = $toimaika = $data['toimitusaika'];

      $laskurow = $ostolaskurow;
      $kukarow['kesken'] = $laskurow['tunnus'];
      unset($var);
      require "lisaarivi.inc";

      $query = "UPDATE tilausrivin_lisatiedot SET
                rahtikirja_id = '{$data['rahtikirja_id']}',
                juoksu = '{$rulla['juoksu']}',
                tilauksen_paino = '{$rulla['tilauksen_paino']}',
                kuljetuksen_rekno = '{$data['rekisterinumero']}',
                asiakkaan_tilausnumero = '{$rulla['tilausnro']}',
                asiakkaan_rivinumero = '{$rulla['rivinro']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tilausrivitunnus = '{$lisatty_tun}'";
      pupe_query($query);

      $query = "INSERT INTO sarjanumeroseuranta SET
                yhtio           = '{$kukarow['yhtio']}',
                tuoteno         = '{$rulla['tuoteno']}',
                sarjanumero     = '{$rulla['sarjanumero']}',
                massa           = '{$rulla['paino']}',
                leveys          = '{$rulla['leveys']}',
                halkaisija      = '{$rulla['halkaisija']}',
                ostorivitunnus  = '{$lisatty_tun}',
                era_kpl         = '1',
                laatija         = '{$kukarow['kuka']}',
                luontiaika      = NOW()";
      pupe_query($query);

      // katsotaan onko tilauksesta luotu jo myyntitilaus
      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND asiakkaan_tilausnumero = '{$rulla['tilausnro']}'
                AND tilaustyyppi = 'N'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) != 0) {

       $laskurow = mysql_fetch_assoc($result);
       $kukarow['kesken'] = $laskurow['tunnus'];
       $var = 'P';
       require "lisaarivi.inc";

       $update_query = "UPDATE tilausrivin_lisatiedot SET
                        juoksu = '{$rulla['juoksu']}',
                        tilauksen_paino = '{$rulla['tilauksen_paino']}',
                        asiakkaan_tilausnumero = '{$rulla['tilausnro']}',
                        asiakkaan_rivinumero = '{$rulla['rivinro']}'
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tilausrivitunnus = '{$lisatty_tun}'";
       pupe_query($update_query);

       $update_query = "UPDATE tilausrivi
                        SET var2 = 'OK'
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus = '{$lisatty_tun}'";
       pupe_query($update_query);

       $update_query = "UPDATE sarjanumeroseuranta
                        SET myyntirivitunnus = '{$lisatty_tun}'
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND sarjanumero = '{$rulla['sarjanumero']}'";
       pupe_query($update_query);

      }


    }
  }

  $query = "UPDATE kuka
            SET kesken = 0
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND kuka = '{$kukarow['kuka']}'";
  pupe_query($query);

}
