<?php

function luo_kaato_tiedot() {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = 'kaato_tuote'";
  $result = pupe_query($query);

  //Ei ajeta kaato-tietoja uudelleen yhtiölle
  if (mysql_num_rows($result) > 0) {
    return false;
  }

  $query = "INSERT INTO tuote
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            tuoteno = 'kaato_tuote',
            nimitys = 'Kaato-tuote',
            ei_saldoa = '',
            status = 'N',
            tuotetyyppi = ''";
  pupe_query($query);

  $query = "INSERT INTO asiakas
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            ytunnus = 'kaato-asiakas',
            nimi = 'Kaato-tuote',
            maa = 'FI'";
  pupe_query($query);

  $asiakas_tunnus = mysql_insert_id();

  $query = "INSERT INTO kohde
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            asiakas = {$asiakas_tunnus},
            nimi = 'Kaato-kohde'";
  pupe_query($query);

  $kohde_tunnus = mysql_insert_id();

  $query = "INSERT INTO paikka
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            kohde = {$kohde_tunnus},
            nimi = 'Kaato-paikka'";
  pupe_query($query);

  $paikka_tunnus = mysql_insert_id();

  $query = "INSERT INTO laite
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            tuoteno = 'kaato_tuote',
            tila = 'N',
            paikka = {$paikka_tunnus}";
  pupe_query($query);

  $laite_tunnus = mysql_insert_id();

  $query = "INSERT INTO lasku
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            yhtio_nimi = '{$yhtiorow['nimi']}',
            yhtio_osoite = '{$yhtiorow['osoite']}',
            yhtio_postino = '{$yhtiorow['postino']}',
            yhtio_postitp = '{$yhtiorow['postitp']}',
            yhtio_ovttunnus = '{$yhtiorow['ytunnus']}',
            yhtio_kotipaikka = '{$yhtiorow['kotipaikka']}',
            nimi = 'Kaato-asiakas',
            maa = 'FI',
            toim_nimi = 'Kaato-asiakas',
            toim_maa = 'FI'";
  pupe_query($query);

  $lasku_tunnus = mysql_insert_id();

  $query = "INSERT INTO tyomaarays
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            tyostatus = 'X',
            otunnus = {$lasku_tunnus}";
  pupe_query($query);

  $query = "INSERT INTO laskun_lisatiedot
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            laskutus_nimi = 'Kaato-asiakas',
            otunnus = {$lasku_tunnus}";
  pupe_query($query);

  $query = "INSERT INTO tilausrivi
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            laadittu = NOW(),
            tyyppi = 'L',
            tuoteno = 'kaato_tuote',
            nimitys = 'Poikkeamarivi',
            tilkpl = 1.00";
  pupe_query($query);

  $tilausrivi_tunnus = mysql_insert_id();

  $query = "INSERT INTO tilausrivin_lisatiedot
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            tilausrivitunnus = {$tilausrivi_tunnus},
            asiakkaan_positio = {$laite_tunnus}
            ";
  pupe_query($query);

  $query = "INSERT INTO tuote
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            tuoteno = 'MUISTUTUS',
            nimitys = 'Muistutus',
            ei_saldoa = 'o',
            status = 'N',
            tuotetyyppi = 'K'";
  pupe_query($query);

  $query = "INSERT INTO tuote
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            tuoteno = 'KAYNTI',
            nimitys = 'Käynti',
            ei_saldoa = 'o',
            status = 'N',
            tuotetyyppi = 'K'";
  pupe_query($query);

  $query = "INSERT INTO tuotteen_avainsanat
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            tuoteno = 'MUISTUTUS',
            kieli = 'fi',
            laji= 'sammutin_tyyppi',
            selite = 'muistutus'";
  pupe_query($query);


  $query = "INSERT INTO tuotteen_avainsanat
            SET yhtio = '{$kukarow['yhtio']}',
            laatija = 'import',
            luontiaika = NOW(),
            muuttaja = 'import',
            muutospvm = NOW(),
            tuoteno = 'KAYNTI',
            kieli = 'fi',
            laji= 'tyomaarayksen_ryhmittely',
            selite = 'tarkastus',
            selitetark = '3',
            jarjestys = '3'";
  pupe_query($query);
}
?>
