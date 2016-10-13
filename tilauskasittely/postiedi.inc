<?php

if ($postiedihost != "" and $postiediuser != "" and $postiedipass != "" and $postiedipath != "") {

  // uniikki filenimi
  $file = "dataout/postiedi-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true)).".txt";

  // avataan faili
  if (!$handle = fopen($file, "w")) die("Filen '$file' luonti epäonnistui!");

  if (empty($postirow["ytunnus"])) {
    $postirow["ytunnus"] = '999999';
  }

  // tehdään EDI-aineisto
  $ulos  = "";
  $ulos .= "ICHGSTART:$postirow[tunnus]\n";                    // tilauksen numero
  $ulos .= "ICHG_DATA:EIH-1.4.1\n";
  $ulos .= "ICHG_INFO:$postirow[laatija]\n";                    // laatija
  $ulos .= "SMSGSTART:$postirow[tunnus]\n";                    // rahtikirjan numero
  $ulos .= "SBGMITYPE:700\n";
  $ulos .= "SBGMINUMB:$postirow[ytunnus]\n";                    // pelkkä ytunnus, niin niputtuu paremmin
  $ulos .= "SMGMMFUNC:7\n";
  $ulos .= "SCNT11_TL:1@PCE\n";                          // kollit, aina yksi. koska aina file per kolli
  $ulos .= "SCNT7__TL:$kilotyht@KGM\n";                      // kilot
  $ulos .= "SRFFCT_NU:$sopnro\n";                          // meidän rahtisopimusnro
  $ulos .= "SRFFFN_NU:\n";
  $ulos .= "STDT20_TT:\n";
  $ulos .= "STDT20_DT:143@".date("YmdHi")."@203\n";
  $ulos .= "STDT5__LC:5@$yhtiorow[postino]@16@@$yhtiorow[postitp]\n";        // lähettäjän postino ja postitp
  $ulos .= "STDT8__LC:8@$rakir_row[toim_postino]@16@@$rakir_row[toim_postitp]\n";  // vastaanottajan postino ja postitp
  $ulos .= "SNADCZ_PC:$yhtiorow[ytunnus]@100\n";                  // lähettäjän ytunnus
  $ulos .= "SNADCZ_NA:$yhtiorow[nimi]\n";                      // lähettäjän nimi
  $ulos .= "SNADCZ_NX:\n";                            // lähettäjän nimitarkenne
  $ulos .= "SNADCZ_SA:$yhtiorow[osoite]\n";                    // lähettäjän osoite
  $ulos .= "SNADCZ_CI:$yhtiorow[postitp]\n";                    // lähettäjän postitp
  $ulos .= "SNADCZ_PO:$yhtiorow[postino]\n";                    // lähettäjän postino
  $ulos .= "SNADCZ_CN:$yhtiorow[maa]\n";                      // lähettäjän maa
  $ulos .= "SNADCN_PC:\n";
  $ulos .= "SNADCN_NA:$rakir_row[toim_nimi]\n";                  // vastaanottajan nimi
  $ulos .= "SNADCN_NX:$rakir_row[toim_nimitark]\n";                // vastaanottan nimitarkenne
  $ulos .= "SNADCN_SA:$rakir_row[toim_osoite]\n";                  // vastaanottajan osoite
  $ulos .= "SNADCN_CI:$rakir_row[toim_postitp]\n";                // vastaanottajan postitp
  $ulos .= "SNADCN_PO:$rakir_row[toim_postino]\n";                // vastaanottajan postino

  if ($rakir_row['merahti'] == 'K') {
    // Käytetään lähettäjän rahtisopimusnumeroa
    $ulos .= "SNADFP_PC:$toitarow[sopimusnro]@100\n";              // rahdinmaksajan sopimusnumero
    $ulos .= "SNADFP_NA:$yhtiorow[nimi]\n";                    // rahdinmaksajan nimi
    $ulos .= "SNADFP_NX:\n";                          // rahdinmaksajan nimitarkenne
    $ulos .= "SNADFP_SA:$yhtiorow[osoite]\n";                  // rahdinmaksajan osoite
    $ulos .= "SNADFP_CI:$yhtiorow[postitp]\n";                  // rahdinmaksajan postitp
    $ulos .= "SNADFP_PO:$yhtiorow[postino]\n";                  // rahdinmaksajan postino
    $ulos .= "SNADFP_CN:$yhtiorow[maa]\n";                    // rahdinmaksajan maa
  }
  else {
    // Käytetään vastaanottajan rahtisopimusnumeroa
    $ulos .= "SNADFP_PC:$postirow[rahtisopimus]@100\n";              // rahdinmaksajan sopimusnumero
    $ulos .= "SNADFP_NA:$postirow[nimi]\n";                    // rahdinmaksajan nimi
    $ulos .= "SNADFP_NX:$postirow[nimitark]\n";                  // rahdinmaksajan nimitarkenne
    $ulos .= "SNADFP_SA:$postirow[osoite]\n";                  // rahdinmaksajan osoite
    $ulos .= "SNADFP_CI:$postirow[postitp]\n";                  // rahdinmaksajan postitp
    $ulos .= "SNADFP_PO:$postirow[postino]\n";                  // rahdinmaksajan postino
    $ulos .= "SNADFP_CN:$postirow[maa]\n";                    // rahdinmaksajan maa
  }

  $ulos .= "SCNISTART:1\n";
  $ulos .= "SCNI___CN:9999\n";
  $ulos .= "SGID___GI:@1@PC@@@PAKETTI@1\n";                    // pakkauskoodit.. tämä pitää koodata paremmin!! nyt aina paketti
  $ulos .= "SGIDHANPI:$tuoteviiva1\n";                      // postin kuljetuskoodi
  $ulos .= "SGIDHANPI:2Z3102\n";                          // monipakettilähetys koodi, lisätään aina

  if (in_array("3", $x)) $ulos .= "SGIDHANPI:2Z3103\n";                // vastaanottaja maksaa lisäpalvelu
  if (in_array("4", $x)) $ulos .= "SGIDHANPI:2Z3104\n";                // erikoiskäsiteltävä
  if (in_array("6", $x)) $ulos .= "SGIDHANPI:2Z3106\n";                // lauantaijaleku

  $ulos .= "SGIDPA_PC:$viivaedi\n";                        // viivakoodi

  // jos kyseessä on postiennakko...
  if ($postiennakkomaara != '') {
    $ulos .= "SGIDHANPI:2Z3101\n";                        // lisäpalvelu postiennakko
    $ulos .= "SGID22_TC:@@@POSTIENNAKKO@C\n";                  // postiennakkoa
    $ulos .= "SGID22_MA:$postiennakkomaara@EUR\n";                // postiennakkomäärä
    $ulos .= "SGIDADERF:$postiennakkotilino\n";                  // postiennakkotilinumero
    $ulos .= "SGIDPQ_RF:$postiennakkoviite\n";                  // postiennakkoviitenumero
  }

  $ulos .= "SCNI__END:1\n";
  $ulos .= "SMSG__END:$postirow[tunnus]\n";                    // rahtikirjan numero
  $ulos .= "ICHG__END:$postirow[tunnus]\n";                    // tilauksen numero

  // kirjotetaan aineisto failiin
  if (fwrite($handle, $ulos) === FALSE)
    die("failin kirjoitus epäonnistui");

  // suljetaan file
  fclose($handle);

  // tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
  // palautetaan $palautus ja $syy

  $ftphost = $postiedihost;
  $ftpuser = $postiediuser;
  $ftppass = $postiedipass;
  $ftppath = $postiedipath;
  $ftpfile = realpath($file);

  require "inc/ftp-send.inc";
}
