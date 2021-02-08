<?php

function hae_maksutapahtumat($tilausnumero) {
  global $kukarow;

  $query = "SELECT *
            FROM maksupaatetapahtumat
            WHERE yhtio      = '{$kukarow["yhtio"]}'
            AND tilausnumero = {$tilausnumero}";

  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    return $result;
  }
  else {
    return false;
  }
}

function hae_kuitit($archive_id) {
  global $kukarow;

  require "rajapinnat/NetsCloudClient.php";

  // From salasanat.php
  global $nets_cloud_base_url, $nets_cloud_username, $nets_cloud_password;

  // Maksupäätteen id käyttäjän takaa
  $nets_cloud_terminal_id = $kukarow["maksupaate_id"];

  $client = new NetsCloudClient($nets_cloud_base_url, $nets_cloud_username, $nets_cloud_password, $nets_cloud_terminal_id);

  return $client->getPreviousReceipts($archive_id);
}

function hae_asiakkaan_kuitti($tunnus) {
  global $kukarow;

  $archive_id = hae_archive_id($tunnus);

  if ($archive_id) {
    $kuitit = hae_kuitit($archive_id);
    $asiakkaan_kuitti = $kuitit["asiakkaan_kuitti"];

    $query = "UPDATE maksupaatetapahtumat
              SET asiakkaan_kuitti = '{$asiakkaan_kuitti}'
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tunnus  = {$tunnus}";

    return pupe_query($query);
  }
  else {
    return false;
  }
}

function hae_kauppiaan_kuitti($tunnus) {
  global $kukarow;

  $archive_id = hae_archive_id($tunnus);

  if ($archive_id) {
    $kuitit = hae_kuitit($archive_id);
    $asiakkaan_kuitti = $kuitit["kauppiaan_kuitti"];

    $query = "UPDATE maksupaatetapahtumat
              SET kauppiaan_kuitti = '{$asiakkaan_kuitti}'
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tunnus  = {$tunnus}";

    return pupe_query($query);
  }
  else {
    return false;
  }
}

function hae_archive_id($tunnus) {
  global $kukarow;

  $query = "SELECT asiakkaan_kuitti,
            kauppiaan_kuitti
            FROM maksupaatetapahtumat
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus  = {$tunnus};";
  $result = pupe_query($query);

  $maksutapahtuma = mysql_fetch_assoc($result);

  if (!empty($maksutapahtuma["asiakkaan_kuitti"])) {
    $archive_id = explode("\n", $maksutapahtuma["asiakkaan_kuitti"]);
    $archive_id = $archive_id[5];
    $archive_id = explode(" ", $archive_id);
    $archive_id = $archive_id[1];
  }
  elseif (!empty($maksutapahtuma["kauppiaan_kuitti"])) {
    $archive_id = explode("\n", $maksutapahtuma["kauppiaan_kuitti"]);
    $archive_id = $archive_id[10];
    $archive_id = explode(" ", $archive_id);
    $archive_id = $archive_id[1];
  }
  else {
    $archive_id = false;
  }

  return $archive_id;
}
