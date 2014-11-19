<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli' or isset($editil_cli)) {
  $php_cli = TRUE;
}

if (!$php_cli) {
  echo "Voidaan ajaa vain komentoriviltä!!!\n";
  die;
}

if (!`which recode`) {
  echo "Tarvitaan recode -ohjelma!\n";
  die;
}

$vain_recode = FALSE;

if (isset($argv[1]) and $argv[1] != '') {
  $vain_recode = TRUE;
}

// Pupeasennuksen root
$pupe_root_polku = dirname(__FILE__);

require_once "inc/functions.inc";

// Konvertoidaan kaikki filet:
$files = listdir($pupe_root_polku);

$finfo1 = finfo_open(FILEINFO_MIME_TYPE);
$finfo2 = finfo_open(FILEINFO_MIME_ENCODING);

foreach ($files as $file) {

  if (strpos($file, "UTF8_konversio.php") !== FALSE) {
    continue;
  }

  $mime = finfo_file($finfo1, $file);
  $encd = finfo_file($finfo2, $file);

  if (substr($mime, 0, 4) == "text" and $encd != "utf-8" and $encd != "us-ascii") {
    $mitenmeni = system("recode ISO_8859-1..UTF8 $file");
    echo "$file, $encd\n";
  }

  if ($vain_recode) {
    continue;
  }

  $koodi = file_get_contents($file);

  if (substr($koodi, 0, 5) == "<?php" or substr($koodi, 0, 14) == "#!/usr/bin/php") {

    // Otetaan käyttöön prime ja doubleprime
    $koodi = str_replace('{DOULEPRIME}', '″', $koodi);
    $koodi = str_replace('{PRIME}', '′', $koodi);

    // Vaihdetaan encoding
    $koodi = str_replace('ln = "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>', 'ln = "<?xml version=\"1.0\" encoding=\"utf-8\"?>', $koodi);
    $koodi = str_replace("= '<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>';", "= '<?xml version=\"1.0\" encoding=\"utf-8\"?>';", $koodi);

    // Muutetaan iconv-funkkarin parameja
    $koodi = str_replace('iconv("ISO-8859-1", "Windows-1252"', 'iconv("UTF-8", "Windows-1252"', $koodi);

    // PREG REPLACE --> {}-substringit
    // Find: (\$[a-z0-9_]+?)\{([0-9]*)\}
    // Replace: substr($1, $2, 1)
    $koodi = preg_replace('/(\$[a-z0-9_]+?)\{([0-9]*)\}/i', 'substr($1, $2, 1)', $koodi);

    // Etsitään laikki mysql_set_charset-koodit
    // Find: mysql_set_charset\(["'][a-z0-9]*["']
    // Replace: mysql_set_charset("utf8"
    $koodi = preg_replace('/mysql_set_charset\(["\'][a-z0-9]*["\']/', 'mysql_set_charset("utf8"', $koodi);

    // Find: charset=iso-8859-15
    // Find: charset=iso-8859-1
    // Replace: charset=utf-8
    $koodi = preg_replace('/charset=iso-8859-15?/i', 'charset=utf-8', $koodi);
    $koodi = preg_replace('/charset=\\\\"iso-8859-15?\\\\"/i', 'charset=\"utf-8\"', $koodi);
    $koodi = preg_replace('/charset = "iso-8859-15?"/i', 'charset = "utf-8"', $koodi);

    // Find: (drawText.*?)\'LATIN1\'
    // Replace: $1
    $koodi = preg_replace('/(drawText.*?)\'LATIN1\'/', '$1\'UTF-8\'', $koodi);

    // Find: (mb_encode_mimeheader\(.*?")ISO-8859-1"
    // Replace: $1
    $koodi = preg_replace('/(mb_encode_mimeheader\(.*?")ISO-8859-1"/', '$1UTF-8"', $koodi);

    // Find: mysql_query("set group_concat_max_len=1000000", $XXXlink);
    // Replace: mysql_query("set collation_connection=\"utf8_unicode_ci\", collation_database=\"utf8_unicode_ci\", group_concat_max_len=1000000", $XXXlink);
    $koodi = preg_replace('/mysql_query\("set group_concat_max_len=1000000/', 'mysql_query("set collation_connection=\"utf8_unicode_ci\", collation_database=\"utf8_unicode_ci\", group_concat_max_len=1000000', $koodi);

    // Vaihdetaan string-funkkarit mb_string-funkkareiks
    if (strpos($file, "phppdflib.class.php") === FALSE) {
      $rivit = explode("\n", $koodi);

      $php = TRUE;
      $jsc = FALSE;

      foreach ($rivit as $rivinro => &$rivi) {

        if (stripos($rivi, "<?php") !== FALSE and stripos($rivi, "?>") === FALSE and !preg_match('/<\?xml.*?\?>/i', $rivi)) {
          $php = TRUE;
        }

        if (stripos($rivi, "<script") !== FALSE) {
          $jsc = TRUE;
        }

        if (stripos($rivi, "<?php") === FALSE and stripos($rivi, "?>") !== FALSE and !preg_match('/<\?xml.*?\?>/i', $rivi)) {
          $php = FALSE;
        }

        if (stripos($rivi, "</script") !== FALSE) {
          $jsc = FALSE;
        }

        if ($php and !$jsc and stripos($rivi, "NO_MB_OVERLOAD") === FALSE) {
          $rivi = preg_replace('/utf8_encode\(([^\)]+?)\)/', '$1', $rivi);
          $rivi = preg_replace('/utf8_decode\(([^\)]+?)\)/', '$1', $rivi);

          $rivi = preg_replace('/([^_])strlen ?\(/',        '$1mb_strlen(',        $rivi);
          $rivi = preg_replace('/([^_])strpos ?\(/',        '$1mb_strpos(',        $rivi);
          $rivi = preg_replace('/([^_])strrpos ?\(/',       '$1mb_strrpos(',       $rivi);
          $rivi = preg_replace('/([^_])substr ?\(/',        '$1mb_substr(',        $rivi);
          $rivi = preg_replace('/([^_])strtolower ?\(/',    '$1mb_strtolower(',    $rivi);
          $rivi = preg_replace('/([^_])strtoupper ?\(/',    '$1mb_strtoupper(',    $rivi);
          $rivi = preg_replace('/([^_])stripos ?\(/',       '$1mb_stripos(',       $rivi);
          $rivi = preg_replace('/([^_])strripos ?\(/',      '$1mb_strripos(',      $rivi);
          $rivi = preg_replace('/([^_])strstr ?\(/',        '$1mb_strstr(',        $rivi);
          $rivi = preg_replace('/([^_])stristr ?\(/',       '$1mb_stristr(',       $rivi);
          $rivi = preg_replace('/([^_])strrchr ?\(/',       '$1mb_strrchr(',       $rivi);
          $rivi = preg_replace('/([^_])substr_count ?\(/',  '$1mb_substr_count(',  $rivi);
          $rivi = preg_replace('/([^_])ereg ?\(/',          '$1mb_ereg(',          $rivi);
          $rivi = preg_replace('/([^_])eregi ?\(/',         '$1mb_eregi(',         $rivi);
          $rivi = preg_replace('/([^_])ereg_replace ?\(/',  '$1mb_ereg_replace(',  $rivi);
          $rivi = preg_replace('/([^_])eregi_replace ?\(/', '$1mb_eregi_replace(', $rivi);
          $rivi = preg_replace('/([^_])split ?\(/',         '$1mb_split(',         $rivi);

          // Meidän omat multibytefunkkarit
          $rivi = preg_replace('/([^_])str_pad ?\(/',       '$1mb_str_pad(',       $rivi);
          $rivi = preg_replace('/([^_])str_split ?\(/',     '$1mb_str_split(',     $rivi);
        }
      }

      $koodi = implode("\n", $rivit);
    }

    file_put_contents($file, $koodi);
  }
}

finfo_close($finfo1);
finfo_close($finfo2);
