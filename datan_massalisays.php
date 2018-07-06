<?php

require "inc/parametrit.inc";

if (!isset($thumb_size_x, $thumb_size_y, $normaali_size_x, $normaali_size_y)) {
  echo "<font class='error'>".t("Kuvakokoja ei ole m‰‰ritelty. Ei voida jatkaa!")."</font>";
  exit;
}

echo "<font class='head'>".t("Kuvien sis‰‰nluku")."</font><hr>";

if ($yhtiorow['kuvapankki_polku'] == '') {
  echo "<font class='error'>".t("Kuvapankkia ei ole m‰‰ritelty. Ei voida jatkaa!")."</font>";
  exit;
}

ini_set("memory_limit", "5G");
ini_set("post_max_size", "100M");
ini_set("upload_max_filesize", "100M");
ini_set("mysql.connect_timeout", 600);
ini_set("max_execution_time", 18000);

// Ei k‰ytet‰ pakkausta
$compression = FALSE;

$kasitellaan_tiedosto = false;
$filearray = array();

function listdir($start_dir = '.') {

  $files = array();

  if (is_dir($start_dir)) {

    $fh = opendir($start_dir);

    while (($file = readdir($fh)) !== false) {
      if (strcmp($file, '.') == 0 or strcmp($file, '..') == 0 or substr($file, 0, 1) == ".") {
        continue;
      }
      $filepath = $start_dir . '/' . $file;

      if (is_dir($filepath)) {
        $files = array_merge($files, listdir($filepath));
      }
      else {
        array_push($files, $filepath);
      }
    }
    closedir($fh);
    sort($files);
  }
  else {
    $files = false;
  }

  return $files;
}

function konvertoi($ykoko, $xkoko, $type, $taulu, $kuva, $dirri, $upfile1) {

  global $kukarow, $yhtiorow;

  // uniikki nimi
  list($usec, $sec) = explode(" ", microtime());
  $nimi = $usec+$sec;

  // extensio
  $path_parts = pathinfo($upfile1);
  $ext = strtolower($path_parts['extension']);

  // filekoko
  $image = getimagesize($upfile1);
  $leve = $image[0];
  $kork = $image[1];

  // tmpfile
  $upfilesgh = strtolower("/tmp/$nimi"."1.".$ext);
  $uusnimi = $dirri."/".$taulu."/".$type."/".$kuva;

  if ($ykoko > 0 and $ykoko < $kork and ($kork >= $leve or $xkoko == 0)) {
    // Haetaan kuvan v‰riprofiili
    exec("nice -n 20 identify -format %[colorspace] \"$upfile1\"", $identify);

    $colorspace = "sRGB";
    if ($identify[0] != "") $colorspace = $identify[0];

    // skaalataan kuva oikenakokoiseksi y:n mukaan
    exec("nice -n 20 convert -resize x$ykoko -quality 90 -colorspace $colorspace -strip \"$upfile1\" \"$upfilesgh\"", $output, $error);
  }
  elseif ($xkoko > 0 and $xkoko < $leve and ($leve > $kork or $ykoko == 0)) {
    // Haetaan kuvan v‰riprofiili
    exec("nice -n 20 identify -format %[colorspace] \"$upfile1\"", $identify);

    $colorspace = "sRGB";
    if ($identify[0] != "") $colorspace = $identify[0];

    // skaalataan kuva oikenakokoiseksi x:n mukaan
    exec("nice -n 20 convert -resize $xkoko -quality 90 -colorspace $colorspace -strip \"$upfile1\" \"$upfilesgh\"", $output, $error);
  }
  else {
    exec("cp -f \"$upfile1\" \"$upfilesgh\"");
    $error = 0;
  }

  if ($error != 0) {
    echo " &raquo; <font class='error'>".t("Virhe %s kuvan skaalauksessa", "", $type)."</font>";
  }
  else {

    unlink($uusnimi);
    $copy_boob = copy($upfilesgh, $uusnimi);

    if ($copy_boob === FALSE) {
      echo t("Kopiointi ep‰onnistui")." {$upfilesgh} {$uusnimi} {$upfile1} <br>";
      $upfileall = "";
    }
    else {
      $upfileall = "$uusnimi";
    }
  }

  // poistetaan file
  unlink($upfilesgh);

  return $upfileall;

}

// testausta varten staattinen
//$dirri = "kuvapankki";
$dirri = $yhtiorow['kuvapankki_polku']."/".$kukarow['yhtio'];

$alkupituus = strlen($dirri) + 1;

if (!is_writable($dirri)) {
  echo "<font class='error'>";
  echo t("Kuvapankkiin (%s) ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!", "", $dirri);
  echo "</font>";
  echo "<br>";
  exit;
}

if ($tee == 'GO') {

  $_kasittelyyn = ($kasittele_kuvat + $thumb_kuvat + $normaali_kuvat + $paino_kuvat + $muut_kuvat + $tuoteinfo_kuvat + $ktt_kuvat > 0);

  if (!$_kasittelyyn) {
    echo "<font class='message'>".t("Et valinnut mit‰‰n k‰sitelt‰v‰‰!")."</font>";
    exit;
  }

  // k‰yd‰‰n l‰pi ensin k‰sitelt‰v‰t kuvat
  $files = listdir($dirri);

  if (isset($kasittele_kuvat) and $kasittele_kuvat == '1') {

    echo "<br>";
    echo "<font class='message'>".t("K‰sitell‰‰n konvertoitavia kuvia").":</font>";
    echo "<br><br>";

    foreach ($files as $file) {

      $polku = substr($file, $alkupituus);
      list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

      if (strtolower($taulu) != 'tuote') {
        echo "<font class='message'>".t("Toistaiseksi voidaan vaan lukea tuotekuvia!")."</font>";
        exit;
      }

      $path_parts = pathinfo($kuva);
      $ext = $path_parts['extension'];

      $size = getimagesize($file);
      list($mtype, $crap) = explode("/", $size["mime"]);

      echo "<font class='message'>{$kuva}</font>";

      if ($size !== FALSE and $toiminto == 'kasittele' and $mtype == "image") {

        if (file_exists($file)) {

          // konvertoidaan thumb kuva ja siirret‰‰n thumb hakemistoon
          $thumbi = konvertoi($thumb_size_y, $thumb_size_x, 'thumb', $taulu, $kuva, $dirri, $file);

          if ($thumbi != '') {
            echo " &raquo; ".t("luotiin thumb-kuva").".";
          }

          // konvertoidaan normaali kuva ja siirret‰‰n normaali hakemistoon
          $normi = konvertoi($normaali_size_y, $normaali_size_x, 'normaali', $taulu, $kuva, $dirri, $file);

          if ($normi != '') {
            echo " &raquo; ".t("luotiin normaali-kuva").".";
          }

          if ($normi != '' and $thumbi != '') {
            // poistetaan orkkisfile
            unlink($file);
          }

          echo "<br>";

        }
      }
      else {
        echo " &raquo; ";
        echo "<font class='error'>".t("Virhe! Voidaan k‰sitell‰ vain kuvia!")."<br>";
      }

    }

    echo "<br>";
  }

  echo "<font class='message'>".t("P‰ivitet‰‰n tuotekuvat j‰rjestelm‰‰n").":</font>";
  echo "<br><br>";

  if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === true) {

    $kasitellaan_tiedosto = true;

    if ($_FILES['userfile']['size'] == 0) {
      echo "<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>";
      $kasitellaan_tiedosto = false;
    }

    echo "<font class='message'>".t("Tarkastetaan l‰hetetty tiedosto")."...<br><br></font>";

    $retval = tarkasta_liite("userfile", array("CSV","TXT"));

    if ($retval !== true) {
      echo "<font class='error'><br>".t("V‰‰r‰ tiedostomuoto")."!</font>";
      $kasitellaan_tiedosto = false;
    }

    if ($kasitellaan_tiedosto) {
      $kasiteltava_tiedoto_path = $_FILES['userfile']['tmp_name'];

      $filerivit = pupeFileReader($kasiteltava_tiedoto_path, $ext);

      foreach ($filerivit as $rivi) {
        list($filetuoteno, $filename, $_kayttotarkoitus) = explode(";", $rivi[0]);

        $filetuoteno      = pupesoft_cleanstring($filetuoteno);
        $filename         = pupesoft_cleanstring($filename);
        $_kayttotarkoitus  = pupesoft_cleanstring($_kayttotarkoitus);

        $filearray[$filename]['filetuoteno'][] = $filetuoteno;
        $filearray[$filename]['kayttotarkoitus'] = $_kayttotarkoitus;
      }
    }
  }

  // k‰yd‰‰n l‰pi dirikka nyt uudestaan
  $files = listdir($dirri);

  foreach ($files as $file) {

    $polku = substr($file, $alkupituus);
    list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

    if (strtolower($taulu) != 'tuote') {
      echo "<font class='message'>".t("Toistaiseksi voidaan vaan lukea tuotekuvia!")."</font>";
      exit;
    }

    // jos ei olla ruksattu thumbeja niin ohitetaan ne
    if ($toiminto == 'thumb' and $thumb_kuvat != "1") {
      continue;
    }
    // jos ei olla ruksattu normaaleja niin ohitetaan ne
    elseif ($toiminto == 'normaali' and $normaali_kuvat != "1") {
      continue;
    }
    // jos ei olla ruksattu painokuvia niin ohitetaan ne
    elseif ($toiminto == 'paino' and $paino_kuvat != "1") {
      continue;
    }
    // jos ei olla ruksattu muita niin ohitetaan ne
    elseif ($toiminto == 'muut' and $muut_kuvat != "1") {
      continue;
    }
    // jos ei olla ruksattu tuoteinfoja niin ohitetaan ne
    elseif ($toiminto == 'tuoteinfo' and $tuoteinfo_kuvat != "1") {
      continue;
    }
    // jos ei olla ruksattu ktt niin ohitetaan ne
    elseif ($toiminto == 'kayttoturvatiedote' and $ktt_kuvat != "1") {
      continue;
    }
    // ohitetaan aina k‰sitelt‰v‰t kuvat, koska ne on hoidettu jo ylh‰‰ll‰
    elseif ($toiminto == "kasittele") {
      continue;
    }

    // tuntematon toiminto
    if (!in_array($toiminto, array('thumb', 'normaali', 'paino', 'kasittele', 'muut', 'tuoteinfo', 'kayttoturvatiedote'))) {
      echo "<font class='error'>";
      echo t("Tuntematon toiminto %s %s!", "", $toiminto, $thumb_kuvat);
      echo "</font><br>";
      continue;
    }

    $path_parts = pathinfo($kuva);
    $ext = $path_parts['extension'];

    $koko = getimagesize($file);
    $leve = $koko[0];
    $kork = $koko[1];
    $apukuva = $kuva;

    echo "<font class='message'>{$kuva}</font> ";

    // jos saimme jonkun imagesizen, katsellaan, ett‰ se on ok
    if ($koko !== FALSE) {
      if ($toiminto == 'thumb' and (($kork > $thumb_size_y and $thumb_size_y > 0) or ($leve > $thumb_size_x and $thumb_size_x > 0))) {
        // konvertoidaan thumb kuva ja siirret‰‰n thumb hakemistoon
        $thumbi = konvertoi($thumb_size_y, $thumb_size_x, 'thumb', $taulu, $kuva, $dirri, $file);

        if ($thumbi != "") {
          echo " &raquo; ";
          echo t("Skaalattiin thumb-kuva");
        }
        else {
          echo " &raquo; ";
          echo "<font class='error'>";
          echo t("Ohitetaan thumb-kuva, koska resoluutio %d x %d on liian suuri ja skaalaus ep‰onnistui!", "", $leve, $kork);
          echo "</font>";
          echo "<br>";
          continue;
        }
      }

      if ($toiminto == 'normaali' and (($kork > $normaali_size_y and $normaali_size_y > 0) or ($leve > $normaali_size_x and $normaali_size_x > 0))) {
        // konvertoidaan normaali kuva ja siirret‰‰n normaali hakemistoon
        $normi = konvertoi($normaali_size_y, $normaali_size_x, 'normaali', $taulu, $kuva, $dirri, $file);

        if ($normi != "") {
          echo " &raquo; ";
          echo t("Skaalattiin normaalikuva-kuva");
        }
        else {
          echo " &raquo; ";
          echo "<font class='error'>";
          echo t("Ohitetaan normaali-kuva, koska resoluutio %d x %d on liian suuri ja skaalaus ep‰onnistui!", "", $leve, $kork);
          echo "</font>";
          echo "<br>";
          continue;
        }
      }
    }

    unset($apuresult);

    $path_parts = pathinfo($kuva);
    $ext = $path_parts['extension'];
    $jarjestys = 1;

    // pit‰‰ kattoo onko nimess‰ h‰shsi‰, otetaan j‰rjestys vikan hashin j‰lkene
    if (strpos($kuva, "#") !== false) {
      $kuva = explode("#", $kuva);
      $jarjestys = array_pop($kuva);
      $jarjestys = str_replace(".{$ext}", "", $jarjestys);
      $kuva = implode('#', $kuva).".$ext";
    }

    // katotaan josko nimess‰ olisi alaviiva, katkaistaan siit‰
    if (strpos($kuva, "_") !== FALSE and $kukarow["yhtio"] == "filla") {
      list($kuva, $jarjestys) = explode("_", $kuva);
      $kuva = "$kuva.$ext";
    }

    $apuselite = "";
    $mikakieli = "fi";
    $kayttotarkoitus_custom = '';

    // wildcard
    if (strpos($kuva, "%") !== FALSE) {

      $mihin = strpos($kuva, "%");
      $kuvanalku = substr($kuva, 0, $mihin);

      //kyseess‰ on k‰yttˆturvatiedot ja tuotekortti
      if (strpos($kuva, "%ktt") !== FALSE) {
        $mistakieli = strpos($kuva, "%ktt") + 4;
        $mikakieli = substr($kuva, $mistakieli, 2);

        if (strpos($mikakieli, "fi") !== FALSE or
          strpos($mikakieli, "se") !== FALSE or
          strpos($mikakieli, "en") !== FALSE or
          strpos($mikakieli, "ru") !== FALSE or
          strpos($mikakieli, "ee") !== FALSE or
          strpos($mikakieli, "no") !== FALSE or
          strpos($mikakieli, "de") !== FALSE) {

          $apuselite = t("K‰yttˆturvatiedote", $mikakieli);
        }
        else {
          $apuselite = t("K‰yttˆturvatiedote");
          $mikakieli = "fi";
        }

        $kayttotarkoitus_custom = "KT";
      }
      elseif (strpos($kuva, "%tko") !== FALSE) {
        $mistakieli = strpos($kuva, "%tko" ) + 4;
        $mikakieli = substr($kuva, $mistakieli, 2);

        if (strpos($mikakieli, "fi") !== FALSE or
          strpos($mikakieli, "se") !== FALSE or
          strpos($mikakieli, "en") !== FALSE or
          strpos($mikakieli, "ru") !== FALSE or
          strpos($mikakieli, "no") !== FALSE or
          strpos($mikakieli, "ee") !== FALSE or
          strpos($mikakieli, "de") !== FALSE) {

          $apuselite = t("Info", $mikakieli);
        }
        else {
          $apuselite = t("Info");
          $mikakieli = "fi";
        }

        $kayttotarkoitus_custom = "IN";
      }

      // haetaan tuoteno vain jos ei ole tiedostosta k‰‰ntˆ‰
      if (count($filearray) == 0) {
        $query = "SELECT tuoteno, tunnus
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno LIKE '{$kuvanalku}%'";
        $apuresult = pupe_query($query);
      }
    }

    if (file_exists($file)) {

      $filesize = filesize($file);

      $query = "SHOW variables LIKE 'max_allowed_packet'";
      $result = pupe_query($query);
      $paketti = mysql_fetch_array($result);

      //echo "Kuvan koko:$filesize ($paketti[0]) ($paketti[1])<br>";

      if ($filesize > $paketti[1]) {
        echo " &raquo; ";
        echo "<font class='error'>";
        echo t("Ohitetaan kuva, koska tiedostokoko on liian suuri!");
        echo "</font>";
        echo "<br>";
        continue;
      }

      if ($filesize == 0) {
        echo " &raquo; ";
        echo "<font class='error'>";
        echo t("Ohitetaan kuva, koska tiedosto on tyhj‰!");
        echo "</font>";
        echo "<br>";
        continue;
      }

      $filee = fopen($file, 'r');
      $data = addslashes(fread($filee, $filesize));

      if ($data === FALSE) {
        echo " &raquo; ";
        echo "<font class='error'>";
        echo t("Ohitetaan kuva, koska tiedoston luku ep‰onnistui!");
        echo "</font>";
        echo "<br>";
        continue;
      }

      if (!isset($apuresult)) {
        $mihin   = strpos($kuva, ".$ext");
        $tuoteno = substr($kuva, 0, "$mihin");

        if (strpos($tuoteno, "=") !== false and empty($kayttotarkoitus_custom)) {
          list($tuoteno, $kayttotarkoitus_custom) = explode("=", $tuoteno);
        }

        $tuotenolisa = "AND tuoteno = '{$tuoteno}'";

        # Tiedostossa voi olla tuotenumeron k‰‰ntˆ / lis‰tietoja
        if (count($filearray) > 0) {
          if (isset($filearray[$kuva])) {
            $tuotenolisa = "AND tuoteno IN ('".implode("','", $filearray[$kuva]['filetuoteno'])."')";
            $kayttotarkoitus_custom = !empty($filearray[$kuva]['kayttotarkoitus']) ? $filearray[$kuva]['kayttotarkoitus'] : $kayttotarkoitus_custom;
          }
          else {
            echo " &raquo; ";
            echo "<font class='error'>";
            echo t("Ohitetaan kuva, koska ei lˆytynyt sis‰‰nluettavasta tiedostosta!");
            echo "</font>";
            echo "<br>";

            continue;
          }
        }

        $query = "SELECT tuoteno, tunnus
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  {$tuotenolisa}";
        $apuresult = pupe_query($query);
      }

      if (mysql_num_rows($apuresult) > 0) {

        echo " &raquo; ";
        echo t("Lis‰ttiin liitetiedosto tuotteelle");
        echo " ";

        // lis‰t‰‰n file
        while ($apurow = mysql_fetch_array($apuresult)) {

          $kuvaselite = strtolower($ext) == 'pdf' ? 'Liitetiedosto' : 'Tuotekuva';
          $kayttotarkoitus = "MU";

          if ($toiminto == 'thumb' and $apuselite == "") {
            $kayttotarkoitus = 'TH';
            $kuvaselite = "Tuotekuva pieni";
          }
          elseif ($toiminto == 'normaali' and $apuselite == "") {
            $kayttotarkoitus = 'TK';
            $kuvaselite = "Tuotekuva normaali";
          }
          elseif ($toiminto == 'paino' and $apuselite == "") {
            $kayttotarkoitus = 'HR';
            $kuvaselite = "Tuotekuva painokuva";
          }
          elseif ($toiminto == 'kayttoturvatiedote' and $apuselite == "") {
            $kayttotarkoitus = 'KT';
            $kuvaselite = "K‰yttˆturvatiedote";
          }
          elseif ($toiminto == 'tuoteinfo' and $apuselite == "") {
            $kayttotarkoitus = 'IN';
            $kuvaselite = "Info";
          }
          elseif ($apuselite != "") {
            $kuvaselite = $apuselite;
          }

          if (trim($kayttotarkoitus_custom) != '') {
            $kayttotarkoitus = strtoupper($kayttotarkoitus_custom);
          }

          // poistetaan vanhat kuvat ja ...
          $query = "DELETE FROM liitetiedostot
                    WHERE yhtio         = '{$kukarow['yhtio']}'
                    AND liitos          = '{$taulu}'
                    AND liitostunnus    = '{$apurow['tunnus']}'
                    AND kayttotarkoitus = '{$kayttotarkoitus}'
                    AND filename        = '{$apukuva}'";
          $delresult = pupe_query($query);

          tallenna_liite("{$dirri}/{$polku}", $taulu, $apurow['tunnus'], $kuvaselite, $kayttotarkoitus, 0, $jarjestys, $mikakieli);

          $query = "UPDATE {$taulu} SET
                    muutospvm   = now(),
                    muuttaja    = '{$kukarow['kuka']}'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$apurow['tunnus']}'";
          $insre = pupe_query($query);

          echo "{$apurow['tuoteno']} ";

        }

        echo "<br>";
        unlink($file);
      }
      else {
        echo " &raquo; ";
        echo "<font class='error'>";
        echo t("Ohitetaan kuva, koska kuvalle ei lˆytynyt tuotetta!");
        echo "</font>";
        echo "<br>";
      }
    }
  }
  echo "<br>";
}

if ($tee == 'DUMPPAA') {

  if (!is_writable($dirri."/tuote")) {
    die(t("Kuvapankkiin/%s ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!", "", 'tuote')."<br>");
  }

  $query = "SELECT liitetiedostot.*, tuote.tuoteno
            FROM liitetiedostot
            LEFT JOIN tuote ON tuote.yhtio = liitetiedostot.yhtio and tuote.tunnus = liitetiedostot.liitostunnus
            WHERE liitetiedostot.yhtio = '{$kukarow['yhtio']}'
            and liitetiedostot.liitos  = 'tuote'";
  $result = pupe_query($query);

  $dumpattuja = 0;
  $dellattuja = 0;

  while ($row = mysql_fetch_assoc($result)) {

    if ($row["liitos"] == '' or $row["kayttotarkoitus"] == '' or $row["filename"] == '') {
      echo t("Ohitetaan kuva (%d), koska tarvittavia tietoja ei oltu tallennettu! %s / %s / %s", "", $row['tunnus'], $row['liitos'], $row['filename'], $row['kayttotarkoitus']);
      echo "<br>";
      continue;
    }

    if ($row["kayttotarkoitus"] == "TH") {
      $toiminto = 'thumb';
    }
    elseif ($row["kayttotarkoitus"] == "TK") {
      $toiminto = 'normaali';
    }
    elseif ($row["kayttotarkoitus"] == "HR") {
      $toiminto = 'paino';
    }
    elseif ($row["kayttotarkoitus"] == "MU") {
      $toiminto = "muut";
    }
    elseif ($row["kayttotarkoitus"] == "KT") {
      $toiminto = "kayttoturvatiedote";
    }
    elseif ($row["kayttotarkoitus"] == "IN") {
      $toiminto = "tuoteinfo";
    }
    else {
      echo "<font class='message'>";
      echo t("Tuntematon k‰yttˆtarkoitus %s!", "", $row['kayttotarkoitus']);
      echo "</font>";
      continue;
    }

    $kokohak = "{$dirri}/{$row["liitos"]}/{$toiminto}";

    if (!is_writable($kokohak)) {
      echo "<font class='error'>";
      echo t("Hakemistolle %s ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida tallentaa kuvaa!", "", $kokonimi);
      echo "</font>";
      echo "<br>";
      continue;
    }

    // jos meill‰ on tuote
    if (!empty($row["tuoteno"])) {
      $path_parts = pathinfo($row['filename']);
      $ext = $path_parts['extension'];
      $kokonimi = "{$kokohak}/{$row["tuoteno"]}.{$ext}";
      $kala = 1;

      while (file_exists($kokonimi)) {
        $kala++;
        $kokonimi = "{$kokohak}/{$row["tuoteno"]}#{$kala}.{$ext}";
      }

      if (file_put_contents($kokonimi, $row["data"]) !== false) {
        $dumpattuja++;
      }
      else {
        echo "<font class='error'>";
        echo t("Tiedoston %s kirjoitus ep‰onnistui!", "", $kokonimi);
        echo "</font>";
        echo "<br>";
      }
    }

    if (isset($dumppaajapoista) and $dumppaajapoista == '1') {
      $query = "DELETE FROM liitetiedostot
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND liitos = 'tuote'
                AND tunnus = '{$row['tunnus']}'";
      $delresult = pupe_query($query);
      $dellattuja++;
    }
  }

  echo "<br>";
  echo "<font class='message'>";
  echo t("Vietiin %d kuvaa kuvapankkiin", "", $dumpattuja);
  echo "</font>";
  echo "<br>";

  if ($dellattuja > 0) {
    echo "<br>";
    echo "<font class='message'>";
    echo t("Poistettiin %d kuvaa j‰rjestelm‰st‰", "", $dellattuja);
    echo "</font>";
    echo "<br>";
  }

  echo "<br>";
}

$files = listdir($dirri);

$lukuthumbit   = 0;
$lukunormit   = 0;
$lukutconvertit = 0;
$lukupainot   = 0;
$lukumuut     = 0;
$lukutuoteinfo = 0;
$lukuktt = 0;

foreach ($files as $file) {

  $polku = substr($file, $alkupituus);
  list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

  if ($toiminto == 'thumb' and $kuva != '') {
    $lukuthumbit++;
  }
  if ($toiminto == 'paino' and $kuva != '') {
    $lukupainot++;
  }
  if ($toiminto == 'normaali' and $kuva != '') {
    $lukunormit++;
  }
  if ($toiminto == 'muut' and $kuva != '') {
    $lukumuut++;
  }
  if ($toiminto == 'tuoteinfo' and $kuva != '') {
    $lukutuoteinfo++;
  }
  if ($toiminto == 'kayttoturvatiedote' and $kuva != '') {
    $lukuktt++;
  }
  if ($toiminto == 'kasittele' and $kuva != '') {
    $lukutconvertit++;
  }
}

// k‰yttˆliittym‰
echo "<form name='uliuli' method='post' enctype='multipart/form-data'>";
echo "<input type='hidden' name='tee' value='GO'>";

echo "<table>";
echo "<tr><th colspan='3'>".t("Tuo kuvakuvapankista")."</th></tr>";

echo "<tr>";
echo "<td>".t("Thumb")."</td>";
echo "<td>{$lukuthumbit} ".t("kpl")."</td>";
echo "<td><input type='checkbox' name='thumb_kuvat' value='1'></td>";
echo "</tr>";

echo "<tr>";
echo "<td>".t("Normaali")."</td>";
echo "<td>{$lukunormit} ".t("kpl")."</td>";
echo "<td><input type='checkbox' name='normaali_kuvat' value='1'></td>";
echo "</tr>";

echo "<tr>";
echo "<td>".t("Paino")."</td>";
echo "<td>{$lukupainot} ".t("kpl")."</td>";
echo "<td><input type='checkbox' name='paino_kuvat' value='1'></td>";
echo "</tr>";

echo "<tr>";
echo "<td>".t("Muut")."</td>";
echo "<td>{$lukumuut} ".t("kpl")."</td>";
echo "<td><input type='checkbox' name='muut_kuvat' value='1'></td>";
echo "</tr>";

if ($lukutuoteinfo > 0) {
  echo "<tr>";
  echo "<td>".t("Tuoteinfo")."</td>";
  echo "<td>{$lukutuoteinfo} ".t("kpl")."</td>";
  echo "<td><input type='checkbox' name='tuoteinfo_kuvat' value='1'></td>";
  echo "</tr>";
}

if ($lukuktt > 0) {
  echo "<tr>";
  echo "<td>".t("K‰yttˆturvatiedote")."</td>";
  echo "<td>{$lukuktt} ".t("kpl")."</td>";
  echo "<td><input type='checkbox' name='ktt_kuvat' value='1'></td>";
  echo "</tr>";
}

echo "<tr>";
echo "<td>".t("K‰sittele")."</td>";
echo "<td>{$lukutconvertit} ".t("kpl")."</td>";
echo "<td><input type='checkbox' name='kasittele_kuvat' value='1'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Kohdista tuotekuvat tiedostosta <br>(tuoteno;tiedostonimi;k‰yttˆtarkoitus,csv-tiedosto)").":</th>";
echo "<td colspan='2'><input name='userfile' type='file'></td>";
echo "</tr>";

echo "</table>";

echo "<br>";
echo "<input type='submit' value='".t("Tuo")."'>";
echo "</form>";

if ($lukuthumbit + $lukunormit + $lukupainot + $lukumuut + $lukutconvertit + $lukutuoteinfo + $lukuktt == 0) {

  echo "<br><br>";
  echo "<font class='head'>".t("Kuvien uloskirjoitus")."</font><hr>";

  echo "<form name='dumppi' method='post'>";
  echo "<input type='hidden' name='tee' value='DUMPPAA'>";

  echo "<table>";
  echo "<tr><th colspan='2'>".t("Vie kuvat takaisin kuvapankkiin")."</th></tr>";
  echo "<tr>";
  echo "<td>".t("Poistetaanko j‰rjestelm‰st‰")."</td>";
  echo "<td><input type='checkbox' name='dumppaajapoista' value='1'></td>";
  echo "</tr>";
  echo "</table>";

  echo "<br>";
  echo "<input type='submit' value='".t("Vie")."'>";
  echo "</form>";
}

require "inc/footer.inc";
