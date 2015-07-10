<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut yhtiötä!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio    = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$query = "SELECT tunnus
          FROM lasku
          WHERE yhtio = '{$kukarow["yhtio"]}'
          AND ohjelma_moduli = 'EXTRANET'
          AND tila = 'N'
          AND alatila = 'F'";

$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {
  $tilausnumerot = array();

  while ($tilaus = mysql_fetch_assoc($result)) {
    array_push($tilausnumerot, $tilaus["tunnus"]);
  }

  $tilausnumerot   = implode(", ", $tilausnumerot);
  $email_osoitteet = $yhtiorow["hyvaksyttavia_tilauksia_email"];

  $email_params = array(
    "to"      => $email_osoitteet,
    "cc"      => "",
    "subject" => "Hyväksyttäviä Extranet-tilauksia",
    "body"    => "Hei,\n\n" .
                 "Seuraavat Extranet-tilaukset ovat valmiita hyväksyttäväksi: {$tilausnumerot}"
  );

  pupesoft_sahkoposti($email_params);
}
