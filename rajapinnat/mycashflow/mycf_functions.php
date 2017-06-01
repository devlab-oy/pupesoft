<?php

function mycf_hae_paivitettavat_saldot() {
  global $kukarow, $yhtiorow, $mycf_varastot, $ajetaanko_kaikki;

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

  // Haetaan aika jolloin tämä skripti on viimeksi ajettu
  $datetime_checkpoint = cron_aikaleima("MYCF_SALDO_CRON");

  pupesoft_log("mycf_saldot", "Aloitetaan saldopäivitys {$aloitusaika}");

  if ($datetime_checkpoint != "" and $ajetaanko_kaikki == "NO") {
    pupesoft_log("mycf_saldot", "Haetaan {$datetime_checkpoint} jälkeen muuttuneet");

    $muutoslisa1 = "AND tapahtuma.laadittu  >= '{$datetime_checkpoint}'";
    $muutoslisa2 = "AND tilausrivi.laadittu >= '{$datetime_checkpoint}'";
    $muutoslisa3 = "AND tuote.muutospvm     >= '{$datetime_checkpoint}'";

    // Haetaan saldot tuotteille, joille on tehty tunnin sisällä tilausrivi tai tapahtuma
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

    // normituote
    list(, , $myytavissa) = saldo_myytavissa($tuoteno, '', $mycf_varastot);

    // lisätään saldon päivittämiseen tarvittavat tiedot
    $tuotteet[] = array(
      "saldo"   => $myytavissa,
      "status"  => $row['status'],
      "tuoteno" => $tuoteno,
    );
  }

  cron_aikaleima("MYCF_SALDO_CRON", $aloitusaika);

  return $tuotteet;
}

function mycf_tuoterajaus() {
  $tuoterajaus = " AND tuote.tuoteno != ''
                   AND tuote.ei_saldoa = ''
                   AND tuote.tuotetyyppi NOT in ('A','B')
                   AND tuote.status != 'P' ";

  return $tuoterajaus;
}

function mycf_ajetaanko_sykronointi($ajo, $ajolista) {
  // jos ajo ei ole ajolistalla, ei ajeta
  if (array_search(strtolower(trim($ajo)), $ajolista) === false) {
    return false;
  }

  // Sallitaan vain yksi instanssi tästä ajosta kerrallaan
  $lock_params = array(
    "lockfile" => "mycf-{$ajo}-flock.lock",
    "locktime" => 5400,
    "return"   => true,
  );

  $status = pupesoft_flock($lock_params);

  if ($status === false) {
    mycf_echo("{$ajo} -ajo on jo käynnissä, ei ajeta uudestaan.");
  }

  return $status;
}

function mycf_echo($string) {
  if ($GLOBALS['mycf_debug'] !== true) {
    return;
  }

  echo date("d.m.Y @ G:i:s")." - {$string}\n";
}
