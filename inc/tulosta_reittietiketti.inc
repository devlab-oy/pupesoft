<?php

$keraa_myos_siirrot = false;
if ($yhtiorow['kerayserat'] != '' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
  $keraa_myos_siirrot = true;
}

if ($kerayseran_numero > 0) {

  if (($unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") or
    ($unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "")) {
    require_once "inc/unifaun_send.inc";
  }

  $pakkauskirjainlisa = (isset($uusi_pakkauskirjain) and trim($uusi_pakkauskirjain) != "") ? " AND kerayserat.pakkausnro = '{$uusi_pakkauskirjain}' " : "";
  $sscclisa = (isset($uusi_sscc) and trim($uusi_sscc) != "") ? " AND kerayserat.sscc = '{$uusi_sscc}' " : "";

  $query = "SELECT kerayserat.sscc, kerayserat.sscc_ulkoinen, min(kerayserat.otunnus) otunnus
            FROM kerayserat
            WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
            AND kerayserat.nro     = '{$kerayseran_numero}'
            {$pakkauskirjainlisa}
            {$sscclisa}
            GROUP BY 1,2
            ORDER BY 1";
  $chk_sscc_res = pupe_query($query);

  $eran_sscct = array();

  while ($chk_sscc_row = mysql_fetch_assoc($chk_sscc_res)) {

    $eran_sscct[$chk_sscc_row["sscc"]] = $chk_sscc_row["otunnus"];

    if ((is_numeric($chk_sscc_row['sscc_ulkoinen']) and (int) $chk_sscc_row['sscc_ulkoinen'] > 0) or (!is_numeric($chk_sscc_row['sscc_ulkoinen']) and (string) $chk_sscc_row['sscc_ulkoinen'] != "")) {

      $query = "SELECT toimitustavan_lahto, toimitustapa, ytunnus, toim_osoite, toim_postino, toim_postitp
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$chk_sscc_row['otunnus']}'";
      $lahto_chk_res = pupe_query($query);
      $lahto_chk_row = mysql_fetch_assoc($lahto_chk_res);

      // haetaan toimitustavan tiedot
      $query = "SELECT *
                FROM toimitustapa
                WHERE yhtio = '$kukarow[yhtio]'
                AND selite  = '{$lahto_chk_row['toimitustapa']}'";
      $toitares = pupe_query($query);
      $toitarow = mysql_fetch_assoc($toitares);

      if ($toitarow['tulostustapa'] == 'E' and
        (($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") or
          ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != ""))) {

        if ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc') {
          $unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
        }
        elseif ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc') {
          $unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
        }

        $mergeid = md5($lahto_chk_row["toimitustavan_lahto"].$lahto_chk_row["ytunnus"].$lahto_chk_row["toim_osoite"].$lahto_chk_row["toim_postino"].$lahto_chk_row["toim_postitp"]);

        $unifaun->_discardParcel($mergeid, $chk_sscc_row['sscc_ulkoinen']);
        $unifaun->ftpSend();
      }
    }
  }

  if (count($eran_sscct) > 0) {
    foreach ($eran_sscct as $eransscc => $ekatilaus) {

      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$ekatilaus}'";
      $res = pupe_query($query);
      $row = mysql_fetch_assoc($res);

      // haetaan toimitustavan tiedot
      $query = "SELECT *
                FROM toimitustapa
                WHERE yhtio = '$kukarow[yhtio]'
                AND selite  = '{$row['toimitustapa']}'";
      $toitares = pupe_query($query);
      $toitarow = mysql_fetch_assoc($toitares);

      $query = "SELECT *
                FROM maksuehto
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$row['maksuehto']}'";
      $mehto_res = pupe_query($query);
      $mehto = mysql_fetch_assoc($mehto_res);

      $unifaun_kaytossa = FALSE;

      if (($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") or
        ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "")) {

        $query = "SELECT unifaun_nimi
                  FROM kirjoittimet
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$reittietikettitulostin}'";
        $kirjoitin_res = pupe_query($query);
        $kirjoitin_row = mysql_fetch_assoc($kirjoitin_res);

        $unifaun_kaytossa = TRUE;
      }

      if ($keraa_myos_siirrot) {
        $asiakas_join = "LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)";
        $maksuehto_join = "LEFT JOIN maksuehto on (lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus)";
      }
      else {
        $asiakas_join = "JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)";
        $maksuehto_join = "JOIN maksuehto on (lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus)";
      }

      $query = "SELECT distinct lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
                lasku.maa, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.ovttunnus, lasku.postino, lasku.postitp,
                if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.alv, lasku.vienti,
                asiakas.toimitusvahvistus, if(asiakas.gsm != '', asiakas.gsm, if(asiakas.tyopuhelin != '', asiakas.tyopuhelin, if(asiakas.puhelin != '', asiakas.puhelin, ''))) puhelin,
                asiakas.kuljetusohje viesti
                FROM lasku
                {$asiakas_join}
                {$maksuehto_join}
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.tunnus  = '{$row['tunnus']}'";
      $rakir_res = pupe_query($query);
      $rakir_row = mysql_fetch_assoc($rakir_res);

      $query = "SELECT
                IFNULL(pakkaus.pakkaus, 'MUU KOLLI') pakkauskuvaus,
                IFNULL(pakkaus.pakkauskuvaus, 'MUU KOLLI') kollilaji,
                kerayserat.pakkausnro,
                kerayserat.pakkaus,
                kerayserat.sscc,
                COUNT(CONCAT(kerayserat.nro,kerayserat.pakkaus,kerayserat.pakkausnro)) rivimaara,
                COUNT(DISTINCT CONCAT(kerayserat.nro,kerayserat.pakkaus,kerayserat.pakkausnro)) AS maara
                FROM kerayserat
                JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
                WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                AND kerayserat.nro     = '{$kerayseran_numero}'
                AND kerayserat.sscc    = '{$eransscc}'
                {$pakkauskirjainlisa}
                {$sscclisa}
                GROUP BY 1,2,3,4,5";
      $keraysera_res = pupe_query($query);

      while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

        $query = "SELECT koodi
                  FROM pakkauskoodit
                  WHERE yhtio          = '{$kukarow['yhtio']}'
                  AND rahdinkuljettaja = '{$toitarow['rahdinkuljettaja']}'
                  AND pakkaus          = '{$keraysera_row['pakkaus']}'";
        $pakkauskoodit_res = pupe_query($query);
        $pakkauskoodit_row = mysql_fetch_assoc($pakkauskoodit_res);

        $selectlisa   = $keraysera_row['kollilaji'] == 'MUU KOLLI' ? "tuote.tuoteleveys AS leveys, tuote.tuotekorkeus AS korkeus, tuote.tuotesyvyys AS syvyys" : "pakkaus.leveys, pakkaus.korkeus, pakkaus.syvyys";
        $joinlisa     = $keraysera_row['kollilaji'] == 'MUU KOLLI' ? "" : "JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)";
        $puukotuslisa = $keraysera_row['kollilaji'] != 'MUU KOLLI' ? "* IF(pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1)" : "";

        $query = "SELECT {$selectlisa},
                  ROUND(SUM(tuote.tuotemassa * kerayserat.kpl) + IFNULL(pakkaus2.oma_paino, 0), 1) tuotemassa,
                  ROUND(SUM((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl) {$puukotuslisa}), 2) as kuutiot
                  FROM kerayserat
                  {$joinlisa}
                  JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
                  JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                  LEFT JOIN pakkaus AS pakkaus2 ON (pakkaus2.yhtio = kerayserat.yhtio AND pakkaus2.tunnus = kerayserat.pakkaus)
                  WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                  AND kerayserat.sscc    = '{$keraysera_row['sscc']}'
                  GROUP BY 1,2,3";
        $pakkaus_info_res = pupe_query($query);
        $pakkaus_info_row = mysql_fetch_assoc($pakkaus_info_res);

        $query = "SELECT SUM(tuote.tuotemassa) AS paino
                  FROM kerayserat
                  JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
                  JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.vakkoodi != '')
                  WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                  AND kerayserat.sscc    = '{$keraysera_row['sscc']}'";
        $paino_chk_res = pupe_query($query);
        $paino_chk_row = mysql_fetch_assoc($paino_chk_res);

        if ($unifaun_kaytossa) {
          $row['pakkausid'] = $keraysera_row['pakkausnro'];
          $row['kollilaji'] = $keraysera_row['kollilaji'];
          $row['sscc']     = $keraysera_row['sscc'];
          $row['rivimaara'] = $keraysera_row['rivimaara'];

          $row['shipment_unique_id'] = "{$kerayseran_numero}_{$eransscc}";

          if ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
            $unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
          }
          elseif ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
            $unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
          }
          else {
            continue;
          }

          $unifaun->setYhtioRow($yhtiorow);
          $unifaun->setKukaRow($kukarow);
          $unifaun->setPostiRow($row);
          $unifaun->setToimitustapaRow($toitarow);
          $unifaun->setMehto($mehto);

          $unifaun->setRahtikirjaRow($rakir_row);

          $unifaun->setYhteensa($row['summa']);
          $unifaun->setViite($row['viesti']);

          $unifaun->setKirjoitin($kirjoitin_row['unifaun_nimi']);

          $unifaun->_getXML();

          $vakkoodi = array();

          if ($paino_chk_row['paino'] != 0) {

            /*
             * DSV Roadilla, Kaukokiidolla sekä VR Transpointilla voi ilmoittaa eri VAK-tuotteet per paketti
             * Kiitolinjan kohdalla vain yksi UN-numero per kolli
             * HIT-palvelun kohdalla sallitaan vain rajoitettu määrä vaarallisia aineita
             */

            if ($toitarow['rahdinkuljettaja'] == 'DSV' or $toitarow['rahdinkuljettaja'] == 'KK' or $toitarow['rahdinkuljettaja'] == 'TP') {
              $limit = "";
            }
            elseif ($toitarow['rahdinkuljettaja'] == 'KL') {
              $limit = "LIMIT 1";
            }
            elseif ($toitarow['rahdinkuljettaja'] == 'HIT') {
              $limit = "LIMIT 1";
            }
            else {
              $limit = "LIMIT 1";
            }

            $query = "SELECT tuote.vakkoodi, tuote.vakmaara, tuote.tuoteno, tuote.nimitys, kerayserat.kpl, (kerayserat.kpl * tuote.tuotemassa) AS kpl_paino
                      FROM kerayserat
                      JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
                      JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.vakkoodi != '')
                      WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                      AND kerayserat.sscc    = '{$keraysera_row['sscc']}'
                      {$limit}";
            $vak_chk_res = pupe_query($query);

            $limited_qty = array(
              "LQ0"  => "0",
              "LQ1"  => "120",
              "LQ2"  => "1000",
              "LQ3"  => "500",
              "LQ4"  => "3000",
              "LQ5"  => "5000",
              "LQ6"  => "5000",
              "LQ7"  => "5000",
              "LQ8"  => "3000",
              "LQ9"  => "6000",
              "LQ10"  => "500",
              "LQ11"  => "500",
              "LQ12"  => "1000",
              "LQ13"  => "1000",
              "LQ14"  => "25",
              "LQ15"  => "100",
              "LQ16"  => "125",
              "LQ17"  => "500",
              "LQ18"  => "1000",
              "LQ19"  => "5000",
              "LQ20"  => "0",
              "LQ21"  => "0",
              "LQ22"  => "1000",
              "LQ23"  => "3000",
              "LQ24"  => "6000",
              "LQ25"  => "1000",
              "LQ26"  => "500",
              "LQ27"  => "6000",
              "LQ28"  => "3000"
            );

            while ($vak_chk_row = mysql_fetch_assoc($vak_chk_res)) {

              $query = "SELECT *, rajoitetut_maarat_ja_poikkeusmaarat_1 AS limited_qty
                        FROM vak
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$vak_chk_row['vakkoodi']}'";
              $vakkoodi_res = pupe_query($query);
              $vakkoodi_row = mysql_fetch_assoc($vakkoodi_res);

              $limited_qty_val = '';

              if ($vakkoodi_row['limited_qty'] != '' and $vak_chk_row['vakmaara'] != '' and isset($limited_qty[$vakkoodi_row["limited_qty"]])) {

                $ltq_yks = preg_replace("/[^a-z]/", "", strtolower($vak_chk_row["vakmaara"]));

                $ltq_maara = (float) preg_replace("/[^0-9,\.]/", "", str_replace(",", ".", $vak_chk_row["vakmaara"]));

                // Käytetään vain millilitroja ja grammoja
                if ($ltq_yks == 'l' or $ltq_yks == 'kg') {
                  $ltq_maara = $ltq_maara * 1000;
                }

                if ($ltq_maara > 0 and $ltq_maara <= $limited_qty[$vakkoodi_row["limited_qty"]]) {
                  $limited_qty_val = "LTD QTY";
                }
              }

              $vakkoodi[] = array(
                'tuoteno' => $vak_chk_row['tuoteno'],
                'tuotenimitys' => $vak_chk_row['nimitys'],
                'kpl' => $vak_chk_row['kpl'],
                'kpl_paino' => $vak_chk_row['kpl_paino'],
                'yk_nro' => $vakkoodi_row['yk_nro'],
                'nimi_ja_kuvaus' => $vakkoodi_row['nimi_ja_kuvaus'],
                'luokka' => $vakkoodi_row['luokka'],
                'luokituskoodi' => $vakkoodi_row['luokituskoodi'],
                'pakkausryhma' => $vakkoodi_row['pakkausryhma'],
                'lipukkeet' => $vakkoodi_row['lipukkeet'],
                'limited_qty' => $limited_qty_val,
                'paino' => $paino_chk_row['paino'],
              );
            }
          }

          if ($keraysera_row['pakkauskuvaus'] == 'MUU KOLLI') {
            $pakkaus_info_row['leveys'] = $pakkaus_info_row['leveys'] < 0.1 ? 0.1 : $pakkaus_info_row['leveys'];
            $pakkaus_info_row['korkeus'] = $pakkaus_info_row['korkeus'] < 0.1 ? 0.1 : $pakkaus_info_row['korkeus'];
            $pakkaus_info_row['syvyys'] = $pakkaus_info_row['syvyys'] < 0.1 ? 0.1 : $pakkaus_info_row['syvyys'];
          }

          $kollitiedot = array(
            'maara' => $keraysera_row['maara'],
            'paino' => $pakkaus_info_row['tuotemassa'],
            'pakkauskuvaus' => $keraysera_row['pakkauskuvaus'],
            'pakkauskoodi' => $pakkauskoodit_row['koodi'],
            'leveys' => $pakkaus_info_row['leveys'],
            'korkeus' => $pakkaus_info_row['korkeus'],
            'syvyys' => $pakkaus_info_row['syvyys'],
            'vakkoodi' => $vakkoodi,
            'kuutiot' => $pakkaus_info_row['kuutiot']
          );

          /* VOIDAAN LÄHETTÄÄ MYÖS MONTA CONTAINER-ROWTA PER SANOMA! YKSI HEADER */
          $unifaun->setContainerRow($kollitiedot);
          $unifaun->ftpSend();
        }
        else {

          if (trim($reittietikettitulostin) != "") {
            $query = "SELECT komento
                      FROM kirjoittimet
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$reittietikettitulostin}'";
            $kirjoitin_res = pupe_query($query);
            $kirjoitin_row = mysql_fetch_assoc($kirjoitin_res);

            // Tulostetaan Pupen oma kollitarra
            $params = array(
              'tilriv' => $row['tunnus'], //tilausnumero
              'pakkaus_kirjain' => excel_column_name($keraysera_row['pakkausnro']),
              'sscc' => $keraysera_row['sscc'],
              'toimitustapa' => $row['toimitustapa'],
              'rivit' => $keraysera_row['rivimaara'], //tilausrivien määrä
              'paino' => $pakkaus_info_row['tuotemassa'],
              'tilavuus' => $pakkaus_info_row['kuutiot'],
              'lask_nimi' => $rakir_row['toim_nimi'],
              'lask_nimitark' => $rakir_row['toim_nimitark'],
              'lask_osoite' => $rakir_row['toim_osoite'],
              'lask_postino' => $rakir_row['toim_postino'],
              'lask_postitp' => $rakir_row['toim_postitp'],
              'lask_viite' => $row['viesti'],
              'lask_merkki' => $row['ohjausmerkki'],
              'reittietikettitulostin' => $kirjoitin_row['komento'],
            );

            tulosta_reittietiketti($params);
          }
        }
      }
    }
  }
}
