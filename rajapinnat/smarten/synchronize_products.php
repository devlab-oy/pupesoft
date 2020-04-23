<?php

require "../../inc/parametrit.inc";
require "rajapinnat/smarten/smarten-functions.php";

if (!isset($tee)) $tee = '';

echo "<font class='head'>", t("Synkronoi tuotteet ulkoiseen j‰rjestelm‰‰n"), "</font><hr><br />";

if (empty($ulkoinen_jarjestelma)) {
  echo "<form action='' method='post'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>", t("Valitse ulkoinen j‰rjestelm‰"), "</th>";
  echo "<td>";
  echo "<select name='ulkoinen_jarjestelma'>";
  echo "<option value='S'>Smarten</option>";
  echo "</select>";
  echo "</td>";
  echo "<td>";
  echo "<button type='submit' name='tee' value=''>", t("L‰het‰"), "</button>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";

  require "inc/footer.inc";
  exit;
}

$query = "SELECT tuote.*, ta.selite AS synkronointi, ta.tunnus AS ta_tunnus, toim_tuoteno
          FROM tuote
          LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi')
          LEFT JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = tuote.yhtio AND tt.tuoteno = tuote.tuoteno)
          WHERE tuote.yhtio   = '{$kukarow['yhtio']}'
          AND tuote.ei_saldoa = ''
          AND tuote.tuotetyyppi NOT IN ('A', 'B')
          AND tuote.tuoteno != ''
          GROUP BY tuoteno
          HAVING (ta.tunnus IS NOT NULL AND ta.selite = '') OR
                  # jos avainsanaa ei ole olemassa ja status P niin ei haluta n‰it‰ tuotteita jatkossakaan
                 (ta.tunnus IS NULL AND tuote.status != 'P')";
$res = pupe_query($query);

if (mysql_num_rows($res) > 0) {

  if ($tee == '') {
    echo "<font class='message'>", t("Tuotteet joita ei ole synkronoitu"), "</font><br />";
    echo "<font class='message'>", t("Yhteens‰ %d kappaletta", "", mysql_num_rows($res)), "</font><br /><br />";

    echo "<form action='' method='post'>";
    echo "<input type='hidden' name='ulkoinen_jarjestelma' value='{$ulkoinen_jarjestelma}' />";
    echo "<table>";
    echo "<tr><td class='back' colspan='2'>";
    echo "<input type='submit' name='tee' value='", t("L‰het‰"), "' />";
    echo "</td></tr>";
    echo "<tr>";
    echo "<th>", t("Tuotenumero"), "</th>";
    echo "<th>", t("Nimitys"), "</th>";
    echo "</tr>";
  }
  else {
    $uj_nimi = "Smarten";

    $worksheet   = new pupeExcel();
    $excelrivi   = 0;
    $excelsarake = 0;

    $worksheet->writeString($excelrivi, $excelsarake,"Versioon"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Artikliklass"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Tootekood"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Nimetus"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Yhik"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"EAN"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"KastiEAN"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Kastis"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Alusel"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"KogusKihis"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"HankijaKood"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"BaashindEEK"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"HankijaTooteKood"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"SvtJaehindEEK"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"KNKood"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"ParitoluRiik"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"BrutomassKG"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"NetomassKG"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"MahtM3"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"ParimPaeviOst"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"ParimPaeviVarud"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"ParimPaeviMyyk"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Ostuladu"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Varudeladu"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Myygiladu"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"HankijaNimetus"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"VeebVarjatud"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Peatatud"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Kleebitav"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"SisseostjaGrupp"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Aktiivkoht"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"VootkoodiTyyp"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"KastiVootkoodiTyyp"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Kaubagrupp"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Laomudeligrupp"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"DimgruppJalg"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Kaibemaksugrupp"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"OstuHind"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"OstuhinnaValuuta"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"EluigaOstes"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"EluigaPaevades"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Tekst"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"VaikimisiPartii"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"LisakaubagruppOst"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"LisakaubagruppMyyk"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"AlkLiik"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"AlkGrupp"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"AlkRegNr"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"AlkProtsent"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"AlkVarvus"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"AlkMahtLiiter"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"AlkTkUhikus"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"AlkMaksumark"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Pooltoodang"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Pakend"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"TootjaNimetus"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"NoppeLisaInfo"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Aastakaik"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"PartneriKaubagrupp1"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"PartneriKaubagrupp2"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"KastiSygavus"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"KastiLaius"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"KastiKorgus"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Plokis"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Loomne"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Mahetoode"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"DimgruppReserv"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"PartiiValik"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Suunamine"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Filter1"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Filter2"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Filter3"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Filter4"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"TsooniId"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"Yhikugrupp"); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake,"YhikugruppKompl"); $excelsarake++;

    $i = 1;
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

      $worksheet->writeString($excelrivi, $excelsarake, "112" /*"Versioon"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "BNNB" /*"Artikliklass"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row['tuoteno'] /*"Tootekood"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row['nimitys'] /*"Nimetus"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Yhik"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row['eankoodi'] /*"EAN"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"KastiEAN"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row['myynti_era'] /*"Kastis"*/); $excelsarake++;         // CHECK: quantity of pieces
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Alusel"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"KogusKihis"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "2818" /*"HankijaKood"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"BaashindEEK"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row['tuoteno'] /*"HankijaTooteKood"*/); $excelsarake++;  // CHECK: supplier's item/aritcle code
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"SvtJaehindEEK"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"KNKood"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"ParitoluRiik"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row['tuotemassa'] /*"BrutomassKG"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"NetomassKG"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"MahtM3"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"ParimPaeviOst"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"ParimPaeviVarud"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"ParimPaeviMyyk"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Ostuladu"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Varudeladu"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Myygiladu"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"HankijaNimetus"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"VeebVarjatud"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Peatatud"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row['purkukommentti'] /*"Kleebitav"*/); $excelsarake++;   // CHECK: this should indicate if the product needs labeling (value: 0 or 1)
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"SisseostjaGrupp"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Aktiivkoht"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"VootkoodiTyyp"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"KastiVootkoodiTyyp"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Kaubagrupp"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Laomudeligrupp"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"DimgruppJalg"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Kaibemaksugrupp"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"OstuHind"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"OstuhinnaValuuta"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"EluigaOstes"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"EluigaPaevades"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Tekst"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"VaikimisiPartii"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"LisakaubagruppOst"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"LisakaubagruppMyyk"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"AlkLiik"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"AlkGrupp"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"AlkRegNr"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"AlkProtsent"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"AlkVarvus"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"AlkMahtLiiter"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"AlkTkUhikus"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"AlkMaksumark"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Pooltoodang"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Pakend"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"TootjaNimetus"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"NoppeLisaInfo"*/); $excelsarake++;       // CHECK: this is instructions field for picking, not printed, remark for warehouse (for example: fragile or something)
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Aastakaik"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"PartneriKaubagrupp1"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"PartneriKaubagrupp2"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"KastiSygavus"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"KastiLaius"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"KastiKorgus"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Plokis"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Loomne"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Mahetoode"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"DimgruppReserv"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"PartiiValik"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Suunamine"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Filter1"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Filter2"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Filter3"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Filter4"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"TsooniId"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"Yhikugrupp"*/); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "" /*"YhikugruppKompl"*/); $excelsarake++;
    }
  }

  if (isset($worksheet)) {
    $excelnimi = $worksheet->close();
  }

  if ($tee == '') {
    echo "<tr><td class='back' colspan='2'>";
    echo "<input type='submit' name='tee' value='", t("L‰het‰"), "' />";
    echo "</td></tr>";
    echo "</table>";
    echo "</form>";
  }
  else {
    $_name = substr("tuote_".md5(uniqid()), 0, 25);
    $filename = $pupe_root_polku."/dataout/{$_name}.xml";

    if (file_put_contents($filename, $xml->asXML())) {
      echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";

      $palautus = smarten_send_file($filename);

      if ($palautus == 0) {
        pupesoft_log('smarten_synchronize_products', "Siirretiin synkronointitiedosto {$_name}.xml.");
      }
      else {
        pupesoft_log('smarten_synchronize_products', "Synkronointitiedoston {$_name}.xml siirt‰minen ep‰onnistui.");
      }
    }
    else {
      echo "<br /><font class='error'>", t("Tiedoston luonti ep‰onnistui"), "</font><br />";
    }
  }
}
else {
  echo "<font class='message'>", t("Kaikki tuotteet ovat synkronoitu"), "</font><br />";
}

require "inc/footer.inc";
