<?php


function mycf_hae_paivitettavat_saldot() {
  global $kukarow, $yhtiorow, $mycf_varastot;

  mycf_echo("Haetaan kaikki tuotteet ja varastosaldot.");

  $tuoterajaus = mycf_tuoterajaus();

  if (!is_array($mycf_varastot)) {
    die('mycf varastot ei ole array!');
  }


  // Haetaan aika nyt
  $query = "SELECT now() as aika";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $aloitusaika = $row['aika'];

  // Haetaan aika jolloin t�m� skripti on viimeksi ajettu
  $datetime_checkpoint = cron_aikaleima("MYCF_SALDO_CRON");

  pupesoft_log("paivita_static", "Aloitetaan saldop�ivitys {$aloitusaika}");

  if ($datetime_checkpoint != "" and $ajetaanko_kaikki == "NO") {
    pupesoft_log("paivita_static", "Haetaan {$datetime_checkpoint} j�lkeen muuttuneet");

    $muutoslisa1 = "AND tapahtuma.laadittu  >= '{$datetime_checkpoint}'";
    $muutoslisa2 = "AND tilausrivi.laadittu >= '{$datetime_checkpoint}'";
    $muutoslisa3 = "AND tuote.muutospvm     >= '{$datetime_checkpoint}'";

    // Haetaan saldot tuotteille, joille on tehty tunnin sis�ll� tilausrivi tai tapahtuma
    $query =  "(SELECT tuote.tuoteno, tuote.ei_saldoa, tuote.status
                FROM tapahtuma
                JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
                  AND tuote.tuoteno = tapahtuma.tuoteno
                  {$tuoterajaus})
                WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}'
                {$muutoslisa1})

                UNION

                (SELECT tuote.tuoteno, tuote.ei_saldoa, tuote.status
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
                  AND tuote.tuoteno = tilausrivi.tuoteno
                  {$tuoterajaus})
                WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
                {$muutoslisa2})

                UNION

                (SELECT tuote.tuoteno, tuote.ei_saldoa, tuote.status
                FROM tuote
                WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
                {$tuoterajaus}
                {$muutoslisa3})

                ORDER BY 1";
  }
  else {
    $query = "SELECT tuote.tuoteno, tuote.ei_saldoa, tuote.status
              FROM tuote
              WHERE tuote.yhtio = '{$kukarow['yhtio']}'
              {$tuoterajaus}";
  }

  $res = pupe_query($query);

  $tuotteet = array();

  while ($row = mysql_fetch_array($res)) {
    $tuoteno = $row['tuoteno'];

    if ($row['ei_saldoa'] != '') {
      // saldottomille tuoteteilla null, jotta mycf tiet�� olla lis��m�tt� t�t� saldoa
      $myytavissa = null;
    }
    else {
      // normituote
      list(, , $myytavissa) = saldo_myytavissa($tuoteno, '', $mycf_varastot);
    }

    // lis�t��n saldon p�ivitt�miseen tarvittavat tiedot
    $tuotteet[] = array(
      "saldo"   => $myytavissa,
      "status"  => $row['status'],
      "tuoteno" => $tuoteno,
    );
  }

  return $tuotteet;
}

function mycf_tuoterajaus() {

  $tuoterajaus = " AND tuote.tuoteno = 'LOL-FP01'
                   AND tuote.tuoteno != ''
                   AND tuote.tuotetyyppi NOT in ('A','B')
                   #AND tuote.status != 'P'
                   #AND tuote.nakyvyys != ''
                  ";

  return $tuoterajaus;
}

function mycf_ajetaanko_sykronointi($ajo, $ajolista) {
  // jos ajo ei ole ajolistalla, ei ajeta
  if (array_search(strtolower(trim($ajo)), $ajolista) === false) {
    return false;
  }

  // Sallitaan vain yksi instanssi t�st� ajosta kerrallaan
  $lock_params = array(
    "lockfile" => "mycf-{$ajo}-flock.lock",
    "locktime" => 5400,
    "return"   => true,
  );

  $status = pupesoft_flock($lock_params);

  if ($status === false) {
    mycf_echo("{$ajo} -ajo on jo k�ynniss�, ei ajeta uudestaan.");
  }

  return $status;
}

function mycf_echo($string) {
  if ($GLOBALS['mycf_debug'] !== true) {
    return;
  }

  echo date("d.m.Y @ G:i:s")." - {$string}\n";
}
