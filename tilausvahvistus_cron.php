<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:stä
if (!$php_cli) {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

if (trim($argv[1]) == '') {
  die ("Et antanut lähettävää yhtiötä!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("memory_limit", "2G");

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "inc/sahkoinen_tilausliitanta.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);

$toimittajat = array();

$headers = array(
  "method" => "POST",
  "url" => $futursoft_rest_http[$yhtio],
  "headers" => array('Accept:text/xml', 'Content-Type: text/xml'),
  "posttype" => "xml",
);

if (GetServerInfo($headers)) {

  $xml = GetResponses($headers, $yhtio);

  if (is_object($xml) and $xml->Orders != '') {

    foreach ($xml->Orders->Supplier as $supplier) {

      foreach ($supplier->Order as $order) {

        $xml_tilausvahvistus = GetResponse($headers, $order->ResponseID);

        if (is_object($xml_tilausvahvistus)) {

          $pupen_tilausnumero = $xml_tilausvahvistus->Order->OrderNumber;
          $suppliernumber = $xml_tilausvahvistus->Order->SupplierNumber;
          $responseid = $xml_tilausvahvistus->Order->ResponseID;

          $query = "SELECT laatija, nimi, tunnus
                    FROM lasku
                    WHERE yhtio = '{$yhtio}'
                    AND tunnus  = '{$pupen_tilausnumero}'
                    AND tila    = 'O'
                    AND alatila = 'A'";
          $laskures = pupe_query($query);

          if (mysql_num_rows($laskures) == 1) {

            $laskurow = mysql_fetch_assoc($laskures);

            foreach ($xml_tilausvahvistus->Order->Products->Product as $product) {

              $tuoteno = $product->ProductCode;
              $tilaajanrivinro = (int) $product->RowNumber;
              $toimitettu_maara = (float) $product->DeliveredQuantity;
              $kommentti = (isset($product->Comment) and trim($product->Comment) != '') ? trim($product->Comment) : '';

              if ($kommentti != "") {
                $kommenttilisa = ", vahvistettu_kommentti = '{$kommentti}'";
              }
              else {
                $kommenttilisa = "";
              }

              $query = "SELECT tunnus
                        FROM tilausrivi
                        WHERE yhtio         = '{$yhtio}'
                        AND tilaajanrivinro = '{$tilaajanrivinro}'
                        AND tyyppi          = 'O'
                        AND otunnus         = '{$pupen_tilausnumero}'";
              $tilriv_res = pupe_query($query);
              $tilriv_row = mysql_fetch_assoc($tilriv_res);

              $query = "UPDATE tilausrivi SET
                        jaksotettu        = 1,
                        vahvistettu_maara = {$toimitettu_maara}
                        {$kommenttilisa}
                        WHERE yhtio       = '{$yhtio}'
                        AND tunnus        = '{$tilriv_row['tunnus']}'";
              pupe_query($query);

              $query = "UPDATE tilausrivin_lisatiedot SET
                        toimitusaika_paivitetty = now()
                        WHERE yhtio             = '{$yhtio}'
                        AND tilausrivitunnus    = '{$tilriv_row['tunnus']}'";
              pupe_query($query);

              if (DeleteResponse($headers, $responseid)) {
                // tilaus poistettiin onnistuneesti
              }
            }

            $message = t("%s ostotilaus %d vahvistettu! Käy tarkistamassa ostotilaus", "", $laskurow['nimi'], $laskurow['tunnus']);

            $query = "INSERT INTO messenger SET
                      yhtio      = '{$yhtio}',
                      kuka       = '{$laskurow['laatija']}',
                      vastaanottaja='{$laskurow['laatija']}',
                      viesti     = '{$message}',
                      status     = 'X',
                      luontiaika = now()";
            pupe_query($query);
          }
        }
      }
    }
  }
}
else {
  echo t("Palvelimeen ei saada yhteyttä\n");
}
