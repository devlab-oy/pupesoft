<?php

require "../../inc/parametrit.inc";
require "rajapinnat/smarten/smarten-functions.php";

if (!isset($tee)) $tee = '';

echo "<font class='head'>", t("Synkronoi tuotteet ulkoiseen järjestelmään"), "</font><hr><br />";

if (empty($ulkoinen_jarjestelma)) {
  echo "<form action='' method='post'>";
  echo "<table>";

  echo "<tr>";
  echo "<th>", t("Valitse ulkoinen järjestelmä"), "</th>";
  echo "<td>";
  echo "<select name='ulkoinen_jarjestelma'>";
  echo "<option value='S'>Smarten</option>";
  echo "</select>";
  echo "</td>";
  echo "<td class='back'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Tyyppi"), "</th>";
  echo "<td>";
  echo "<select name='lahetystyyppi'>";
  echo "<option value='U'>".t("Uudet tuotteet")."</option>";
  echo "<option value='K'>".t("Kaikki tuotteet. Vain omaan sähköpostiin")."</option>";
  echo "</select>";
  echo "</td>";
  echo "<td class='back'>";
  echo "<button type='submit'>", t("Lähetä"), "</button>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";

  require "inc/footer.inc";
  exit;
}

$havinglisa = "";

if ($lahetystyyppi == "U") {
  $havinglisa = " HAVING (ta.tunnus IS NOT NULL AND ta.selite = '') OR
                  # jos avainsanaa ei ole olemassa ja status P niin ei haluta näitä tuotteita jatkossakaan
                  (ta.tunnus IS NULL AND tuote.status != 'P')";
}

$query = "SELECT tuote.*, ta.selite AS synkronointi, ta.tunnus AS ta_tunnus
          FROM tuote
          LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi')
          WHERE tuote.yhtio   = '{$kukarow['yhtio']}'
          AND tuote.ei_saldoa = ''
          AND tuote.tuotetyyppi NOT IN ('A', 'B')
          AND tuote.tuoteno != ''
          and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
          GROUP BY tuoteno
          {$havinglisa}";
$res = pupe_query($query);

if (mysql_num_rows($res) > 0) {

  if (empty($tee)) {
    echo "<font class='message'>", t("Tuotteet joita ei ole synkronoitu"), "</font><br />";
    echo "<font class='message'>", t("Yhteensä %d kappaletta", "", mysql_num_rows($res)), "</font><br /><br />";

    echo "<form action='' method='post'>";
    echo "<input type='hidden' name='ulkoinen_jarjestelma' value='{$ulkoinen_jarjestelma}' />";
    echo "<input type='hidden' name='lahetystyyppi' value='{$lahetystyyppi}' />";
    echo "<table>";
    echo "<tr><td class='back' colspan='2'>";
    echo "<input type='submit' name='tee' value='", t("Lähetä"), "' />";
    echo "</td></tr>";
    echo "<tr>";
    echo "<th>", t("Tuotenumero"), "</th>";
    echo "<th>", t("Nimitys"), "</th>";
    echo "</tr>";
  }
  else {
    $uj_nimi = "Smarten";

    include 'inc/pupeExcel.inc';

    $worksheet   = new pupeExcel();
    $excelrivi   = 0;
    $excelsarake = 0;

    $worksheet->writeString($excelrivi, $excelsarake++,"Versioon");
    $worksheet->writeString($excelrivi, $excelsarake++,"Artikliklass");
    $worksheet->writeString($excelrivi, $excelsarake++,"Tootekood");
    $worksheet->writeString($excelrivi, $excelsarake++,"Nimetus");
    $worksheet->writeString($excelrivi, $excelsarake++,"Yhik");
    $worksheet->writeString($excelrivi, $excelsarake++,"EAN");
    $worksheet->writeString($excelrivi, $excelsarake++,"KastiEAN");
    $worksheet->writeString($excelrivi, $excelsarake++,"Kastis");
    $worksheet->writeString($excelrivi, $excelsarake++,"Alusel");
    $worksheet->writeString($excelrivi, $excelsarake++,"KogusKihis");
    $worksheet->writeString($excelrivi, $excelsarake++,"HankijaKood");
    $worksheet->writeString($excelrivi, $excelsarake++,"BaashindEEK");
    $worksheet->writeString($excelrivi, $excelsarake++,"HankijaTooteKood");
    $worksheet->writeString($excelrivi, $excelsarake++,"SvtJaehindEEK");
    $worksheet->writeString($excelrivi, $excelsarake++,"KNKood");
    $worksheet->writeString($excelrivi, $excelsarake++,"ParitoluRiik");
    $worksheet->writeString($excelrivi, $excelsarake++,"BrutomassKG");
    $worksheet->writeString($excelrivi, $excelsarake++,"NetomassKG");
    $worksheet->writeString($excelrivi, $excelsarake++,"MahtM3");
    $worksheet->writeString($excelrivi, $excelsarake++,"ParimPaeviOst");
    $worksheet->writeString($excelrivi, $excelsarake++,"ParimPaeviVarud");
    $worksheet->writeString($excelrivi, $excelsarake++,"ParimPaeviMyyk");
    $worksheet->writeString($excelrivi, $excelsarake++,"Ostuladu");
    $worksheet->writeString($excelrivi, $excelsarake++,"Varudeladu");
    $worksheet->writeString($excelrivi, $excelsarake++,"Myygiladu");
    $worksheet->writeString($excelrivi, $excelsarake++,"HankijaNimetus");
    $worksheet->writeString($excelrivi, $excelsarake++,"VeebVarjatud");
    $worksheet->writeString($excelrivi, $excelsarake++,"Peatatud");
    $worksheet->writeString($excelrivi, $excelsarake++,"Kleebitav");
    $worksheet->writeString($excelrivi, $excelsarake++,"SisseostjaGrupp");
    $worksheet->writeString($excelrivi, $excelsarake++,"Aktiivkoht");
    $worksheet->writeString($excelrivi, $excelsarake++,"VootkoodiTyyp");
    $worksheet->writeString($excelrivi, $excelsarake++,"KastiVootkoodiTyyp");
    $worksheet->writeString($excelrivi, $excelsarake++,"Kaubagrupp");
    $worksheet->writeString($excelrivi, $excelsarake++,"Laomudeligrupp");
    $worksheet->writeString($excelrivi, $excelsarake++,"DimgruppJalg");
    $worksheet->writeString($excelrivi, $excelsarake++,"Kaibemaksugrupp");
    $worksheet->writeString($excelrivi, $excelsarake++,"OstuHind");
    $worksheet->writeString($excelrivi, $excelsarake++,"OstuhinnaValuuta");
    $worksheet->writeString($excelrivi, $excelsarake++,"EluigaOstes");
    $worksheet->writeString($excelrivi, $excelsarake++,"EluigaPaevades");
    $worksheet->writeString($excelrivi, $excelsarake++,"Tekst");
    $worksheet->writeString($excelrivi, $excelsarake++,"VaikimisiPartii");
    $worksheet->writeString($excelrivi, $excelsarake++,"LisakaubagruppOst");
    $worksheet->writeString($excelrivi, $excelsarake++,"LisakaubagruppMyyk");
    $worksheet->writeString($excelrivi, $excelsarake++,"AlkLiik");
    $worksheet->writeString($excelrivi, $excelsarake++,"AlkGrupp");
    $worksheet->writeString($excelrivi, $excelsarake++,"AlkRegNr");
    $worksheet->writeString($excelrivi, $excelsarake++,"AlkProtsent");
    $worksheet->writeString($excelrivi, $excelsarake++,"AlkVarvus");
    $worksheet->writeString($excelrivi, $excelsarake++,"AlkMahtLiiter");
    $worksheet->writeString($excelrivi, $excelsarake++,"AlkTkUhikus");
    $worksheet->writeString($excelrivi, $excelsarake++,"AlkMaksumark");
    $worksheet->writeString($excelrivi, $excelsarake++,"Pooltoodang");
    $worksheet->writeString($excelrivi, $excelsarake++,"Pakend");
    $worksheet->writeString($excelrivi, $excelsarake++,"TootjaNimetus");
    $worksheet->writeString($excelrivi, $excelsarake++,"NoppeLisaInfo");
    $worksheet->writeString($excelrivi, $excelsarake++,"Aastakaik");
    $worksheet->writeString($excelrivi, $excelsarake++,"PartneriKaubagrupp1");
    $worksheet->writeString($excelrivi, $excelsarake++,"PartneriKaubagrupp2");
    $worksheet->writeString($excelrivi, $excelsarake++,"KastiSygavus");
    $worksheet->writeString($excelrivi, $excelsarake++,"KastiLaius");
    $worksheet->writeString($excelrivi, $excelsarake++,"KastiKorgus");
    $worksheet->writeString($excelrivi, $excelsarake++,"Plokis");
    $worksheet->writeString($excelrivi, $excelsarake++,"Loomne");
    $worksheet->writeString($excelrivi, $excelsarake++,"Mahetoode");
    $worksheet->writeString($excelrivi, $excelsarake++,"DimgruppReserv");
    $worksheet->writeString($excelrivi, $excelsarake++,"PartiiValik");
    $worksheet->writeString($excelrivi, $excelsarake++,"Suunamine");
    $worksheet->writeString($excelrivi, $excelsarake++,"Filter1");
    $worksheet->writeString($excelrivi, $excelsarake++,"Filter2");
    $worksheet->writeString($excelrivi, $excelsarake++,"Filter3");
    $worksheet->writeString($excelrivi, $excelsarake++,"Filter4");
    $worksheet->writeString($excelrivi, $excelsarake++,"TsooniId");
    $worksheet->writeString($excelrivi, $excelsarake++,"Yhikugrupp");
    $worksheet->writeString($excelrivi, $excelsarake++,"YhikugruppKompl");
  }

  while ($row = mysql_fetch_assoc($res)) {
    if ($tee == '') {
      echo "<tr>";
      echo "<td>{$row['tuoteno']}</td>";
      echo "<td>{$row['nimitys']}</td>";
      echo "</tr>";
    }
    else {
      // statuskoodi
      switch ($row['status']) {
      case 'A':
        $status = 1;
        break;
      case 'P':
        $status = 9;
        break;
      default:
        $status = 0;
        break;
      }

      // tyyppi
      if (!is_null($row['synkronointi']) and $row['synkronointi'] == '') {
        $type = 'M';
      }
      else {
        $type = 'U';
      }

      $excelsarake = 0;
      $excelrivi++;

      $worksheet->writeString($excelrivi, $excelsarake++, "112" /*"Versioon"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "BNNB" /*"Artikliklass"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, $row['tuoteno'] /*"Tootekood"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, $row['nimitys'] /*"Nimetus"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Yhik"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, $row['eankoodi'] /*"EAN"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"KastiEAN"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, $row['myynti_era'] /*"Kastis"*/);         // CHECK: quantity of pieces
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Alusel"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"KogusKihis"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "5511" /*"HankijaKood"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"BaashindEEK"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, $row['tuoteno'] /*"HankijaTooteKood"*/);  // CHECK: supplier's item/aritcle code
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"SvtJaehindEEK"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"KNKood"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"ParitoluRiik"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, $row['tuotemassa'] /*"BrutomassKG"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"NetomassKG"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"MahtM3"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"ParimPaeviOst"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"ParimPaeviVarud"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"ParimPaeviMyyk"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Ostuladu"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Varudeladu"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Myygiladu"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"HankijaNimetus"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"VeebVarjatud"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Peatatud"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, $row['purkukommentti'] /*"Kleebitav"*/);   // CHECK: this should indicate if the product needs labeling (value: 0 or 1)
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"SisseostjaGrupp"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Aktiivkoht"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"VootkoodiTyyp"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"KastiVootkoodiTyyp"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Kaubagrupp"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Laomudeligrupp"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"DimgruppJalg"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Kaibemaksugrupp"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"OstuHind"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"OstuhinnaValuuta"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"EluigaOstes"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"EluigaPaevades"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Tekst"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"VaikimisiPartii"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"LisakaubagruppOst"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"LisakaubagruppMyyk"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"AlkLiik"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"AlkGrupp"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"AlkRegNr"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"AlkProtsent"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"AlkVarvus"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"AlkMahtLiiter"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"AlkTkUhikus"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"AlkMaksumark"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Pooltoodang"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Pakend"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"TootjaNimetus"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, $row['vakkoodi'] /*"NoppeLisaInfo"*/);       // CHECK: this is instructions field for picking, not printed, remark for warehouse (for example: fragile or something)
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Aastakaik"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"PartneriKaubagrupp1"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"PartneriKaubagrupp2"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"KastiSygavus"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"KastiLaius"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"KastiKorgus"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Plokis"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Loomne"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Mahetoode"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"DimgruppReserv"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"PartiiValik"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Suunamine"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Filter1"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Filter2"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Filter3"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Filter4"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"TsooniId"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"Yhikugrupp"*/);
      $worksheet->writeString($excelrivi, $excelsarake++, "" /*"YhikugruppKompl"*/);

      if ($lahetystyyppi == "U") {
        if (is_null($row['synkronointi'])) {
          $query = "INSERT INTO tuotteen_avainsanat SET
                    yhtio      = '{$kukarow['yhtio']}',
                    tuoteno    = '{$row['tuoteno']}',
                    kieli      = '{$yhtiorow['kieli']}',
                    laji       = 'synkronointi',
                    selite     = 'x',
                    laatija    = '{$kukarow['kuka']}',
                    luontiaika = now(),
                    muutospvm  = now(),
                    muuttaja   = '{$kukarow['kuka']}'";
          pupe_query($query);
        }
        else {
          $query = "UPDATE tuotteen_avainsanat SET
                    selite      = 'x'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$row['tuoteno']}'
                    AND laji    = 'synkronointi'";
          pupe_query($query);
        }
      }
    }
  }

  if (isset($worksheet)) {
    $excelnimi = $worksheet->close();
  }

  if ($tee == '') {
    echo "<tr><td class='back' colspan='2'>";
    echo "<input type='submit' name='tee' value='", t("Lähetä"), "' />";
    echo "</td></tr>";
    echo "</table>";
    echo "</form>";
  }
  else {

    $smartmail = $smarten['product_email'];

    if ($lahetystyyppi == "K") {
      $smartmail = $kukarow['eposti'];
    }

    // Smarten tuotedatasähköpostit löytyy parametrist
    if (empty($smartmail)) {
      echo "<font class='error'>", t("Smarten sähköpostiosoitetta ei löydy"), "!</font><br />";
    }
    else {
      // Sähköpostin lähetykseen parametrit
      $parametri = array(
        "to" => $smartmail,
        "cc" => "",
        "subject" => t("Smarten Product Catalogue"),
        "ctype" => "text",
        "body" => "Smarten Product Catalogue.",
        "attachements" => array(0 =>
          array(
            "filename" => "/tmp/".$excelnimi,
            "newfilename" => "ProductCatalogue.xlsx",
            "ctype" => "EXCEL"
          ),
        )
      );
      $boob = pupesoft_sahkoposti($parametri);

      echo "<font class='message'>", t("Smarten tuoteluettelo lähetetty osoitteeseen"), ": {$smartmail}</font><br />";
    }
  }
}
else {
  echo "<font class='message'>", t("Kaikki tuotteet synkronoitu"), "!</font><br />";
}

require "inc/footer.inc";
