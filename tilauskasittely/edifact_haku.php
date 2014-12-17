<?php
die;

require '../inc/edifact_functions.inc';

require "../inc/parametrit.inc";

if ($task == 'B1') {

  $sanoma = "UNA:+.? '
UNB+UNOC:2+003708274241:30+003706664131:30+141212:1111+112++++++'
UNH+1+IFTMBF:D:97B:UN:EXWL12'
BGM+335+KTKHAM05018+9'
DTM+137:201412121111:203'
RFF+VON:4506635310'
RFF+CU:TLKR-400030:1'
TDT+20++1++COSCOFI+++V7DF9:103::LANTAU ARROW'
LOC+5+FIKTMU::86:KOTKA'
DTM+133:201501050000:203'
LOC+8+DEHAM::86:DEEXPEDITOR'
DTM+132:201501100000:203'
RFF+VON:KTKHAM05018'
TDT+30++1++COSCOFI+++2FLR9:103::HANJIN EUROPE'
LOC+5++DEHAM::86'
DTM+133:201501100000:203'
LOC+8+KRPUS::86'
DTM+132:201503040000:203'
RFF+VON:HAMKR14018'
NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'
NAD+CZ+003708274241:100'
NAD+FX+DONGWHAC++DONGWHAC+148-9 Gajwa-dong+INCHEON++404-250+KR'
NAD+TR+003706664131'
GID++59:RL'
LOC+20+DEHAM:16'
PIA+1+4804 31:HS'
FTX+ZSM++LOLO'
FTX+AAA+++PAPER'
FTX+PRD+++TL352_045_0'
FTX+TRA+++Tasainen kontitus, -Max 23 t /40 HC-Rullan halkaisija 930 mm-HUOM?: YHT:EEN KONTTIIN EI MIELELLÄÄN ERI LEVEYKSIÄ, ELI VÄLTETÄÄN SEKAKONTTEJA:.-KONTIT LASTATAAN KUITENKIN MAHD. TÄYTEEN.--'
FTX+TRA+++Shipment in 40 HC containers, max 23 tons/container-HS CODE 4804 3158-:Stuffing place?: Kotka Mussalo-Port of discharge?: BUSAN-Place of delive:ry?: INCHEON-Sea Waybill'
MEA+AAE+CT+RO:1'
MEA+AAE+WD+MMT:2530'
MEA+AAE+DI+MMT:879'
MEA+AAE+G+KGM:60000'
RFF+CU:TLKR-400030:1'
EQD+CN++C40HC'
EQN+6'
RFF+AKC:4506635310'
NAD+CW+COSCOFI'
NAD+ZST+FIKTMU'
UNT+39+1'
UNZ+1+112'
";

  kasittele_bookkaussanoma($sanoma);

}


if ($task == 'B2') {

  $sanoma = "UNA:+.? '
UNB+UNOC:2+003708274241:30+003706664131:30+141212:1111+113++++++'
UNH+1+IFTMBF:D:97B:UN:EXWL12'
BGM+335+KTKHAM05018+9'
DTM+137:201412121111:203'
RFF+VON:4506635310'
RFF+CU:TLKR-400030:3'
TDT+20++1++COSCOFI+++V7DF9:103::LANTAU ARROW'
LOC+5+FIKTMU::86:KOTKA'
DTM+133:201501050000:203'
LOC+8+DEHAM::86:DEEXPEDITOR'
DTM+132:201501100000:203'
RFF+VON:KTKHAM05018'
TDT+30++1++COSCOFI+++2FLR9:103::HANJIN EUROPE'
LOC+5++DEHAM::86'
DTM+133:201501100000:203'
LOC+8+KRPUS::86'
DTM+132:201503040000:203'
RFF+VON:HAMKR14018'
NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'
NAD+CZ+003708274241:100'
NAD+FX+DONGWHAC++DONGWHAC+148-9 Gajwa-dong+INCHEON++404-250+KR'
NAD+TR+003706664131'
GID++55:RL'
LOC+20+DEHAM:16'
PIA+1+4804 31:HS'
FTX+ZSM++LOLO'
FTX+AAA+++PAPER'
FTX+PRD+++TL352_045_0'
FTX+TRA+++Tasainen kontitus, -Max 23 t /40 HC-Rullan halkaisija 930 mm-HUOM?: YHT:EEN KONTTIIN EI MIELELLÄÄN ERI LEVEYKSIÄ, ELI VÄLTETÄÄN SEKAKONTTEJA:.-KONTIT LASTATAAN KUITENKIN MAHD. TÄYTEEN.--'
MEA+AAE+CT+RO:1'
MEA+AAE+WD+MMT:2490'
MEA+AAE+DI+MMT:879'
MEA+AAE+G+KGM:55000'
RFF+CU:TLKR-400030:3'
EQD+CN++C40HC'
EQN+6'
RFF+AKC:4506635310'
NAD+CW+COSCOFI'
NAD+ZST+FIKTMU'
UNT+38+1'
UNZ+1+113'";

  kasittele_bookkaussanoma($sanoma);

}

if ($task == 'B3') {

  $sanoma = "UNA:+.? '
UNB+UNOC:2+003708274241:30+003706664131:30+141212:1111+114++++++'
UNH+1+IFTMBF:D:97B:UN:EXWL12'
BGM+335+KTKHAM05018+9'
DTM+137:201412121111:203'
RFF+VON:4506635310'
RFF+CU:TLKR-400030:4'
TDT+20++1++COSCOFI+++V7DF9:103::LANTAU ARROW'
LOC+5+FIKTMU::86:KOTKA'
DTM+133:201501050000:203'
LOC+8+DEHAM::86:DEEXPEDITOR'
DTM+132:201501100000:203'
RFF+VON:KTKHAM05018'
TDT+30++1++COSCOFI+++2FLR9:103::HANJIN EUROPE'
LOC+5++DEHAM::86'
DTM+133:201501100000:203'
LOC+8+KRPUS::86'
DTM+132:201503040000:203'
RFF+VON:HAMKR14018'
NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'
NAD+CZ+003708274241:100'
NAD+FX+DONGWHAC++DONGWHAC+148-9 Gajwa-dong+INCHEON++404-250+KR'
NAD+TR+003706664131'
GID++20:RL'
LOC+20+DEHAM:16'
PIA+1+4804 31:HS'
FTX+ZSM++LOLO'
FTX+AAA+++PAPER'
FTX+PRD+++TL352_045_0'
FTX+TRA+++Tasainen kontitus, -Max 23 t /40 HC-Rullan halkaisija 930 mm-HUOM?: YHT:EEN KONTTIIN EI MIELELLÄÄN ERI LEVEYKSIÄ, ELI VÄLTETÄÄN SEKAKONTTEJA:.-KONTIT LASTATAAN KUITENKIN MAHD. TÄYTEEN.--'
MEA+AAE+CT+RO:1'
MEA+AAE+WD+MMT:2560'
MEA+AAE+DI+MMT:879'
MEA+AAE+G+KGM:20000'
RFF+CU:TLKR-400030:4'
EQD+CN++C40HC'
EQN+6'
RFF+AKC:4506635310'
NAD+CW+COSCOFI'
NAD+ZST+FIKTMU'
UNT+38+1'
UNZ+1+114'
";

  kasittele_bookkaussanoma($sanoma);

}


if ($task == 'R1') {

  $sanoma = "UNA:+.? '
UNB+UNOC:2+003708274241:30+003706664131:30+141217:1027+148++++++'
UNH+1+IFTMBF:D:97B:UN:EXWL12'
BGM+335+KTKHAM220133+9'
DTM+137:201412171027:203'
RFF+VON:HSK0148090'
RFF+CU:KAIN-400101:1'
TDT+20++1++CMACGMFI+++D5DM7:103::CHARLOTTA B'
LOC+5+FIKTMU::86:KOTKA'
DTM+133:201501220000:203'
LOC+8+DEHAM::86:DEEXPEDITOR'
DTM+132:201501260000:203'
RFF+VON:KTKHAM220133'
TDT+30++1++CMACGMFI+++9HA2360:103::CMA CGM RABELAIS'
LOC+5++DEHAM::86'
DTM+133:201501260000:203'
LOC+8+INMUN::86'
DTM+132:201503080000:203'
RFF+VON:HAMIN080366'
NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'
NAD+CZ+003708274241:100'
NAD+FX+RUSH/SID++RUSH/SID+1,KRINKAL APARTMENT,MAHALAXMI SOCIE+AHMEDABAD++380007+IN'
NAD+TR+003706664131'
GID++62:RL'
LOC+20+DEHAM:16'
PIA+1+4804 41:HS'
FTX+ZSM++LOLO'
FTX+AAA+++PAPER'
FTX+PRD+++Absorbex Kraft Paper 208 g/m2'
FTX+TRA+++PUUTAVARAN KÄYTTÖ KONTITUKSESSA KIELLETTY.TASAINEN KONTITUS.-Shipment :in 2x40?'HC containers,'
FTX+TRA+++PARTIAL AND TRANSSHIPMENT ALLOWED.Vessel carrying shipment should be s:ea worthy.-15 DAYS MERGED FREE DETENTION AND DEMURRAGE PERIOD TO BE AL:LOWED AT ICD AHMEDABAD.--INLAND HAULAGE FROM MUNDRA PORT TO ICD AHMEDA:BAD IS ON CUSTOMER?'S RISK AND ACCOUNT.-Shipment in 2x40?'HC containers,:-'
MEA+AAE+CT+RO:1'
MEA+AAE+WD+MMT:1010'
MEA+AAE+DI+MMT:1200'
MEA+AAE+G+KGM:50000'
RFF+CU:KAIN-400101:1'
EQD+CN++C40HC'
EQN+2'
RFF+AKC:HSK0148090'
NAD+CW+CMACGMFI'
NAD+ZST+FIKTMU'
UNT+39+1'
UNZ+1+148'
";

  kasittele_bookkaussanoma($sanoma);

}

if ($task == 'R2') {

  $sanoma ="UNB+UNOC:2+003708274241:30+003706800420:30+141024:1032+KM0005++++++'UNH+1+DESADV:D:97B:UN:EXWL15'BGM+15+RK0002+9'DTM+137:201410241020:203'NAD+FX+STEVECO::86++Steveco+Steveco+Kotka++48101+FI'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+10++3++RP::86:RP Logistics Oy+++XYP-555'LOC+5+FIKTKM::86:KOTKA'DTM+180:201410300000:203'DTM+143:201410300605:203'LOC+8+FIKTKC::86:KOTKA, MUSSALO'DTM+132:201410101600:203'CPS+MOL'PAC+8++RL'MEA+AAE+AAL+KGM:30000'MEA+AAE+G+KGM:30000'MEA+AAE+WD+MMT:1050'MEA+AAE+DI+MMT:1250'PCI+16+MERINOPA-EMAIL 08.06.2013-'LIN+1'PIA+1+4810 55:HS'NAD+OS+003708274241:100++Kotkamills Oy'NAD+SB+SEINDIA::86++SE INDIA'NAD+FX+MERINOPA::86++MERINOPA'NAD+CZ+003708274241:100++Kotkamills Oy'TDT+20++1+++++D5DM7:::CHARLOTTA B'FTX+AAA+++PAPER'RFF+CU:TILAUS-0002:1'RFF+VON:MATKA123'DTM+ZEL:201407010000:203'LOC+5+FIKTKM::86:Kotka warehouse'LOC+8+FIKTKC::86:Kotka'LOC+20+MERINOPA::86:HARYANA'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0008'GIN+ZPI+1'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0009'GIN+ZPI+2'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0010'GIN+ZPI+3'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0011'GIN+ZPI+4'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0012'GIN+ZPI+5'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0013'GIN+ZPI+6'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0014'GIN+ZPI+7'CPS+PKG'PAC+999++RL'MEA+AAE+G+KGM:3750'MEA+AAE+AAL+KGM:3750'MEA+AAE+DI+MMT:1250'MEA+AAE+WD+MMT:1050'PCI+999'GIN+ZUN+0015'GIN+ZPI+8'UNT+117+1'UNZ+1+KM0005'";

  kasittele_rahtikirjasanoma($sanoma);

}

if ($task == 'B4') {

  $sanoma = "UNA:+.? '
UNB+UNOC:2+003708274241:30+003706664131:30+141217:1025+144++++++'
UNH+1+IFTMBF:D:97B:UN:EXWL12'
BGM+335+KTKANR14011V+5'
DTM+137:201412171025:203'
RFF+VON:20222577'
RFF+CU:KATR-400163:1'
TDT+20++1++MSCFI+++H3JN:103::MSC IRIS'
LOC+5+FIKTMU::86:KOTKA'
DTM+133:201501140000:203'
LOC+8+BEANR::86:ANTWERPEN'
DTM+132:201501170000:203'
RFF+VON:KTKANR14011V'
TDT+30++1++MSCFI+++OXLD2:103::TBN'
LOC+5++BEANR::86'
DTM+133:201501170000:203'
LOC+8+TRYPO::86'
DTM+132:201502010000:203'
RFF+VON:ANRTR24011V'
NAD+OS+003708274241:100++Kotkamills Oy:Norskankatu 6:FI-48101:FI+++++FI'
NAD+CZ+003708274241:100'
NAD+FX+ORMANTR++ORMANTR+Keresteciler Sitesi 3.+ISTANBUL++34306+TR'
NAD+TR+003706664131'
GID++64:RL'
LOC+20+BEANR:16'
PIA+1+4804 41:HS'
FTX+ZSM++LOLO'
FTX+AAA+++PAPER'
FTX+PRD+++Absorbex Performer Kraft Paper 164 g/m2'
FTX+TRA+++x 40 HC.-Max payload 24 tons. Puutavaran'
FTX+TRA+++x 40 HC. Destination Yilport. Max 24 ton'
MEA+AAE+CT+RO:1'
MEA+AAE+WD+MMT:1330'
MEA+AAE+DI+MMT:1000'
MEA+AAE+G+KGM:48000'
RFF+CU:KATR-400163:1'
EQD+CN++C40HC'
EQN+13'
RFF+AKC:20222577'
NAD+CW+MSCFI'
NAD+ZST+FIKTMU'
UNT+39+1'
UNZ+1+144'";

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

