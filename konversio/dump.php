<?php

function dump_seed_data() {
  global $kukarow, $yhtiorow;

  echo "Ajetaan.....";
  echo "<br/>";
  $query = "DELETE FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji = 'SAMMUTIN_KOKO'";
  pupe_query($query);
  $query = "
INSERT INTO `avainsana` (`yhtio`, `perhe`, `kieli`, `laji`, `nakyvyys`, `selite`, `selitetark`, `selitetark_2`, `selitetark_3`, `selitetark_4`, `selitetark_5`, `jarjestys`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`)
VALUES
  ('{$kukarow['yhtio']}', 382090, 'fi', 'SAMMUTIN_KOKO', '', '1', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:00', '2013-11-18 11:13:00', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '2', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:17', '2013-11-18 11:13:17', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '3', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:45', '2013-11-18 11:13:45', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '4', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:48', '2013-11-18 11:13:48', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '5', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:49', '2013-11-18 11:13:49', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '6', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:50', '2013-11-18 11:13:50', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '7', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:52', '2013-11-18 11:13:52', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '8', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:54', '2013-11-18 11:13:54', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '9', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:55', '2013-11-18 11:13:55', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '10', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:57', '2013-11-18 11:13:57', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '11', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:13:58', '2013-11-18 11:13:58', 'joonas'),
  ('{$kukarow['yhtio']}', 382091, 'fi', 'SAMMUTIN_KOKO', '', '12', '', '', '', '', NULL, 0, 'joonas', '2013-11-18 11:14:00', '2013-11-18 11:14:00', 'joonas');";
  pupe_query($query);
  $query = "DELETE FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji IN ('TYOM_TYOSTATUS','LAITE_TILA');";
  pupe_query($query);
  $query = "
INSERT INTO `avainsana` (`yhtio`, `perhe`, `kieli`, `laji`, `nakyvyys`, `selite`, `selitetark`, `selitetark_2`, `selitetark_3`, `selitetark_4`, `selitetark_5`, `jarjestys`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`)
VALUES
  ('{$kukarow['yhtio']}', 7, 'fi', 'TYOM_TYOSTATUS', '', 'A', 'Aikataulutettu', '#00ff00', '', '', NULL, 0, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 8, 'fi', 'TYOM_TYOSTATUS', '', 'S', 'Suunniteltu', '#ffff00', '', '', NULL, 0, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 9, 'fi', 'TYOM_TYOSTATUS', '', 'T', 'Työlista tulostettu', '#000000', '', '', NULL, 0, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 10, 'fi', 'TYOM_TYOSTATUS', '', 'X', 'Tehty', '', '', '', NULL, 0, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 12, 'fi', 'TYOM_TYOSTATUS', '', 'V', 'Laite vaihdettu', '#FFFB00', '', '', NULL, 0, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 12, 'fi', 'TYOM_TYOSTATUS', '', 'K', 'Laite kadonnut', '#cd7f31', '', '', NULL, 0, 'import', NOW(), NOW(), 'import');";
  pupe_query($query);
  $query = "
INSERT INTO `avainsana` (`yhtio`, `perhe`, `kieli`, `laji`, `nakyvyys`, `selite`, `selitetark`, `selitetark_2`, `selitetark_3`, `selitetark_4`, `selitetark_5`, `jarjestys`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`)
VALUES
  ('{$kukarow['yhtio']}', 382085, 'fi', 'LAITE_TILA', '', 'N', 'Normaali', '', '', '', NULL, 1, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 382086, 'fi', 'LAITE_TILA', '', 'P', 'Poistettu', '', '', '', NULL, 2, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 382087, 'fi', 'LAITE_TILA', '', 'V', 'Varalaite', '', '', '', NULL, 3, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 382087, 'fi', 'LAITE_TILA', '', 'K', 'Kateissa', '', '', '', NULL, 4, 'import', NOW(), NOW(), 'import'),
  ('{$kukarow['yhtio']}', 382089, 'fi', 'LAITE_TILA', '', 'H', 'Huollossa', '', '', '', NULL, 5, 'import', NOW(), NOW(), 'import');";
  pupe_query($query);

  if (!isset($yhtiorow['laite_huolto'])) {
    $query =  "ALTER TABLE yhtion_parametrit ADD COLUMN laite_huolto CHAR(1) NOT NULL DEFAULT '' AFTER vastaavat_tuotteet_esitysmuoto;";
    pupe_query($query);
    $query =  "UPDATE yhtion_parametrit SET laite_huolto = 'X' WHERE yhtio = '{$kukarow['yhtio']}';";
    pupe_query($query);
  }

  $query = "DELETE FROM avainsana where yhtio = '{$kukarow['yhtio']}' AND laji = 'OLOSUHDE';";
  pupe_query($query);
  $query = "
INSERT INTO `avainsana` (`yhtio`, `perhe`, `kieli`, `laji`, `nakyvyys`, `selite`, `selitetark`, `selitetark_2`, `selitetark_3`, `selitetark_4`, `selitetark_5`, `jarjestys`, `laatija`, `muuttaja`)
VALUES
  ('{$kukarow['yhtio']}', 3, 'fi', 'OLOSUHDE', '', 'X', 'Ulkona / tärinässä', '', '', '', NULL, 0, 'import', 'import'),
  ('{$kukarow['yhtio']}', 4, 'fi', 'OLOSUHDE', '', 'A', 'Sisällä', '', '', '', NULL, 0, 'import', 'import');";
  pupe_query($query);

  $query = "DELETE FROM oikeu WHERE yhtio = '{$kukarow['yhtio']}' AND sovellus = 'Sammutinhuolto';";
  pupe_query($query);
  $query = "
INSERT INTO `oikeu` (`kuka`, `sovellus`, `nimi`, `alanimi`, `paivitys`, `lukittu`, `nimitys`, `jarjestys`, `jarjestys2`, `profiili`, `yhtio`, `hidden`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`)
VALUES
  ('', 'Sammutinhuolto', 'tyomaarays/tyojono2.php', '', '', '', 'Työjono', 10, 0, '', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'tyomaarays/tyojono2.php', 'TEHDYT_TYOT', '', '', 'Tehdyt työ', 20, 0, '', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'tyomaarays/tulevat_tyot.php', '', '', '', 'Tulevat työt', 21, 0, '', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'asiakkaan_laite_hallinta.php', '', '', '', 'Asiakkaan laitehallinta', 30, 0, '', '{$kukarow['yhtio']}', '', 'import',NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'asiakas', '', '', 'Asiakasylläpito', 40, 0, '', '{$kukarow['yhtio']}', '', 'import',NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'kohde', '', '', 'Kohteet', 50, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'paikka', '', '', 'Paikat', 60, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'laite', '', '', 'Laitteet', 80, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'tuote.php', '', '', '', 'Tuotekysely', 81, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'tuote', '', '', 'Tuotteet', 90, 0, '', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'huoltosykli', '', '', 'Huoltosyklit', 100, 0, '', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'tilauskasittely/tuote_selaus_haku.php', '', '', '', 'Hae ja selaa', 300, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'tuotteen_avainsanat', '', '', 'Tuotteen_avainsanat', 370, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'huoltosyklit_laitteet', '', '', 'Laitteiden huoltosyklit', 380, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'tilauskasittely/tilaus_myynti.php', 'TYOMAARAYS', '', '', 'Työmääräys', 390, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'tilauskasittely/laitteen_vaihto.php', '', '', '', 'Laitteenvaihto', 400, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'tyomaaraysten_generointi.php', '', '', '', 'Työmääräysten generointi', 410, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'lasku_siivo.php', '', '', '', 'Työmääräysten poistaminen', 420, 0, '', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'yllapito.php', 'liitetiedostot', '', '', 'Liitetiedostot', 430, 0, '', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('', 'Sammutinhuolto', 'tilauskasittely/kustannusarvio.php', '', '', '', 'Kustannusarvio', 440, 0, '', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import');";
//  pupe_query($query);
  $query = "
INSERT INTO `oikeu` (`kuka`, `sovellus`, `nimi`, `alanimi`, `paivitys`, `lukittu`, `nimitys`, `jarjestys`, `jarjestys2`, `profiili`, `yhtio`, `hidden`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`)
VALUES
  ('Sammutinhuolto', 'Sammutinhuolto', 'tyomaarays/tyojono2.php', '', '1', '', 'Työjono', 10, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'tyomaarays/tyojono2.php', 'TEHDYT_TYOT', '1', '', 'Tehdyt työ', 20, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'asiakkaan_laite_hallinta.php', '', '1', '', 'Asiakkaan laitehallinta', 30, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'asiakas', '1', '', 'Asiakasylläpito', 40, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'kohde', '1', '', 'Kohteet', 50, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'paikka', '1', '', 'Paikat', 60, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'laite', '1', '', 'Laitteet', 80, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'tuote.php', '', '1', '', 'Tuotekysely', 81, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'tuote', '1', '', 'Tuotteet', 90, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'huoltosykli', '1', '', 'Huoltosyklit', 100, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'tilauskasittely/tuote_selaus_haku.php', '', '1', '', 'Hae ja selaa', 300, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'tuotteen_avainsanat', '1', '', 'Tuotteen_avainsanat', 370, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'huoltosyklit_laitteet', '1', '', 'Laitteiden huoltosyklit', 380, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'tilauskasittely/tilaus_myynti.php', 'TYOMAARAYS', '1', '', 'Työmääräys', 390, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'tilauskasittely/laitteen_vaihto.php', '', '1', '', 'Laitteenvaihto', 400, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'tyomaaraysten_generointi.php', '', '1', '', 'Työmääräysten generointi', 410, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'lasku_siivo.php', '', '1', '', 'Työmääräysten poistaminen', 420, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', 'H', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'yllapito.php', 'liitetiedostot', '', '', 'Liitetiedostot', 430, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import'),
  ('Sammutinhuolto', 'Sammutinhuolto', 'tilauskasittely/kustannusarvio.php', '', '', '', 'Kustannusarvio', 440, 0, 'Sammutinhuolto', '{$kukarow['yhtio']}', '', 'import', NOW(), NOW(), 'import');";
//  pupe_query($query);
  echo "Ajettu";
}
