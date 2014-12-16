<?php
exit;

require '../inc/edifact_functions.inc';

require "../inc/parametrit.inc";

if ($task == 'B1') {

  $sanoma = "UNA:+.? 'UNB+UNOC:2+003708274241:30+003706664131:30+141209:1327+78++++++'UNH+1+IFTMBF:D:97B:UN:EXWL12'BGM+335+KTKHAM22019+9'DTM+137:201412091327:203'RFF+VON:3110609890'RFF+CU:KTJP-400099:1'TDT+20++1++OOCL+++D5DM7:103::CHARLOTTA B'LOC+5+FIKTMU::86:Kotka'DTM+133:201501220000:203'LOC+8+DEHAM::86:DEEXPEDITOR'DTM+132:201501270000:203'RFF+VON:KTKHAM22019'TDT+30++1++OOCL+++3EOS7:103::NYK OLYMPUS'LOC+5++DEHAM::86'DTM+133:201501270000:203'LOC+8+JPTYO::86'DTM+132:201503110000:203'RFF+VON:HAMJP29013'NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'NAD+CZ+003708274241:100'NAD+FX+NISSHO++NISSHO+AKASAKA 1 CHOME, CENTER BLDG, 11F+TOKYO++107-0052+JP'NAD+TR+003706664131'GID++388:RL'LOC+20+DEHAM:16'PIA+1+4810 22:HS'FTX+ZSM++LOLO'FTX+AAA+++PAPER'FTX+PRD+++Solaris (New) 1.55 51 g/m2'FTX+TRA+++Max netto 26 t / 40DC/HC kontti'FTX+TRA+++8  x 40 DC/ HC, ETA maaliskuun puoleen väliin mennessä'MEA+AAE+CT+RO:1'MEA+AAE+WD+MMT:875'MEA+AAE+DI+MMT:1000'MEA+AAE+G+KGM:200000'RFF+CU:KTJP-400099:1'EQD+CN++C40HC'EQN+8'RFF+AKC:3110609890'NAD+CW+OOCL'NAD+ZST+FIKTMU'UNT+39+1'UNZ+1+78'";

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

