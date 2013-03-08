<?php

  // lisätään includepathiin pupe-root
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
  error_reporting(E_ALL);
  ini_set("display_errors", 1);

  // otetaan tietokanta connect ja funktiot
  require("inc/connect.inc");
  require("inc/functions.inc");

  function alv_update_lisaa_avainsanat($query_where_lisa = "") {

    // Loopataan läpi kaikki asennuksen suomalaiset yhtiot
    $query = "  SELECT yhtio
                FROM yhtio
                WHERE maa = 'FI'
                $query_where_lisa";
    $yhtio_result = mysql_query($query) or die($query);

    // Lisätään uuset verokannat
    while ($row = mysql_fetch_assoc($yhtio_result)) {

      $yhtio = $row['yhtio'];
      $yhtiorow = hae_yhtion_parametrit($yhtio);

      echo date("H:i:s d.m.Y"), ": Avainsanat yritykselle $yhtio\n";

      // Poistetaan uuden alvit jos käyttäjät on itse lisännyt niitä
      $query = "  DELETE from avainsana
                  where yhtio = '$yhtio'
                  and laji = 'ALV'
                  and selite in ('10', '14', '24')";
      $result = pupe_query($query);

      // Lisätään 10 verokanta
      $query = "  INSERT into avainsana SET
                  yhtio       = '$yhtio',
                  kieli       = 'fi',
                  laji        = 'ALV',
                  selite      = '10',
                  jarjestys   = '10',
                  laatija     = 'devlab',
                  luontiaika  = now()";
      $result = pupe_query($query);

      // Lisätään 14 verokanta
      $query = "  INSERT into avainsana SET
                  yhtio       = '$yhtio',
                  kieli       = 'fi',
                  laji        = 'ALV',
                  selite      = '14',
                  jarjestys   = '14',
                  laatija     = 'devlab',
                  luontiaika  = now()";
      $result = pupe_query($query);

      // Lisätään 24 verokanta
      $query = "  INSERT into avainsana SET
                  yhtio       = '$yhtio',
                  kieli       = 'fi',
                  laji        = 'ALV',
                  selite      = '24',
                  jarjestys   = '24',
                  laatija     = 'devlab',
                  luontiaika  = now()";
      $result = pupe_query($query);

      // Poistetaan oletus verokanta ja päivitetään perheet
      $query = "  UPDATE avainsana SET
                  selitetark = '',
                  perhe = tunnus
                  where yhtio = '$yhtio'
                  and laji = 'ALV'";
      $result = pupe_query($query);

      // Päivitetään uusi oletusverokanta
      $query = "  UPDATE avainsana SET
                  selitetark = 'o'
                  where yhtio = '$yhtio'
                  and laji = 'ALV'
                  and selite = '24'";
      $result = pupe_query($query);
    }
  }

  function alv_update_paivita_tuote_ja_asiakas($query_where_lisa = "") {

    // Loopataan läpi kaikki asennuksen suomalaiset yhtiot
    $query = "  SELECT yhtio
                FROM yhtio
                WHERE maa = 'FI'
                $query_where_lisa";
    $yhtio_result = mysql_query($query) or die($query);

    // Lisätään uuset verokannat
    while ($row = mysql_fetch_assoc($yhtio_result)) {

      $yhtio = $row['yhtio'];
      $yhtiorow = hae_yhtion_parametrit($yhtio);
      $update_count = 0;

      echo date("H:i:s d.m.Y"), ": Tuote-/asiakasmuutos yritykselle $yhtio\n";

      $query = "  UPDATE asiakas
                  SET alv = 24
                  WHERE yhtio = '$yhtio'
                  AND alv != 0";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();
      
      $query = "  UPDATE tuote
                  SET alv = 10
                  WHERE yhtio = '$yhtio'
                  AND alv = 9";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();
      
      $query = "  UPDATE tuote
                  SET alv = 14
                  WHERE yhtio = '$yhtio'
                  AND alv = 13";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();
      
      $query = "  UPDATE tuote
                  SET alv = 24
                  WHERE yhtio = '$yhtio'
                  AND alv = 23";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();
      
      echo date("H:i:s d.m.Y"), ": Muutettiin $update_count tietuetta\n";      
    }
  }

  function alv_update_paivita_avoimet($query_where_lisa = "") {

    // Loopataan läpi kaikki asennuksen suomalaiset yhtiot
    $query = "  SELECT yhtio
                FROM yhtio
                WHERE maa = 'FI'
                $query_where_lisa";
    $yhtio_result = mysql_query($query) or die($query);

    // päivitetään uudet verokannat
    while ($row = mysql_fetch_assoc($yhtio_result)) {

      $yhtio = $row['yhtio'];
      $yhtiorow = hae_yhtion_parametrit($yhtio);      
      $update_count = 0;
      
      echo date("H:i:s d.m.Y"), ": Avoimet tapahtumat yritykselle $yhtio\n";

      // Tarjous
      $query = "  UPDATE tilausrivi
                  JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
                    AND lasku.tunnus = tilausrivi.otunnus
                    AND lasku.tila = 'T'
                    AND lasku.alatila NOT IN ('X', 'B'))
                  SET tilausrivi.alv = if(tilausrivi.alv = 23, 24, if(tilausrivi.alv = 13, 14, if(tilausrivi.alv = 9, 10, tilausrivi.alv)))
                  WHERE tilausrivi.yhtio = '$yhtio'
                  AND tilausrivi.alv in (23, 13, 9)
                  AND tilausrivi.tyyppi = 'T'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // Ylläpitosopimus
      $query = "  UPDATE tilausrivi
                  JOIN lasku on (lasku.yhtio = tilausrivi.yhtio
                    AND lasku.tunnus = tilausrivi.otunnus
                    AND lasku.tila = '0'
                    AND lasku.alatila != 'D')
                  SET tilausrivi.alv = if(tilausrivi.alv = 23, 24, if(tilausrivi.alv = 13, 14, if(tilausrivi.alv = 9, 10, tilausrivi.alv)))
                  WHERE tilausrivi.yhtio = '$yhtio'
                  AND tilausrivi.alv in (23, 13, 9)
                  AND tilausrivi.tyyppi = '0'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // Työmääräys, Reklamaatio, Myyntitilaus, Myyntitilaus kesken, Ennakkotilaus, Tallennettu web-tilaus/tarjous
      $query = "  UPDATE tilausrivi
                  JOIN lasku on (lasku.yhtio = tilausrivi.yhtio
                    AND lasku.tunnus = tilausrivi.otunnus
                    AND lasku.tila IN ('A', 'C', 'L', 'N', 'E', 'F')
                    AND lasku.alatila != 'X')
                  SET tilausrivi.alv = if(tilausrivi.alv = 23, 24, if(tilausrivi.alv = 13, 14, if(tilausrivi.alv = 9, 10, tilausrivi.alv)))
                  WHERE tilausrivi.yhtio = '$yhtio'
                  AND tilausrivi.tyyppi IN ('L', 'V', 'W', 'E', 'F')
                  AND tilausrivi.alv in (23, 13, 9)
                  AND tilausrivi.laskutettuaika = '0000-00-00'
                  AND (tilausrivi.toimitettuaika = '0000-00-00 00:00:00' OR tilausrivi.toimitettuaika >= '2013-01-01 00:00:00')";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // Valmistus
      $query = "  UPDATE tilausrivi
                  JOIN lasku on (lasku.yhtio = tilausrivi.yhtio
                    AND lasku.tunnus = tilausrivi.otunnus
                    AND lasku.tila = 'V'
                    AND lasku.alatila != 'V')
                  SET tilausrivi.alv = if(tilausrivi.alv = 23, 24, if(tilausrivi.alv = 13, 14, if(tilausrivi.alv = 9, 10, tilausrivi.alv)))
                  WHERE tilausrivi.yhtio = '$yhtio'
                  AND tilausrivi.tyyppi in ('V', 'W', 'M', 'L')
                  AND tilausrivi.alv in (23, 13, 9)
                  AND tilausrivi.laskutettuaika = '0000-00-00'
                  AND (tilausrivi.toimitettuaika = '0000-00-00 00:00:00' OR tilausrivi.toimitettuaika >= '2013-01-01 00:00:00')";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // Kaikkien ym. tyyppien otsikot
      $query = "  UPDATE lasku
                  SET lasku.alv = 24
                  WHERE lasku.yhtio = '$yhtio'
                  AND lasku.alv = 23
                  AND ((lasku.tila = 'V' AND lasku.alatila != 'V')
                    OR (lasku.tila = 'T' AND lasku.alatila NOT IN ('X', 'B'))
                    OR (lasku.tila = '0' AND lasku.alatila != 'D')
                    OR (lasku.tila IN ('A', 'C', 'L', 'N', 'E', 'F') AND lasku.alatila != 'X'))";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();
      
      echo date("H:i:s d.m.Y"), ": Muutettiin $update_count tietuetta\n";      
    }
  }

  function alv_update_paivita_hinnat($query_where_lisa = "") {

    // HUOM!! Tämä funktio olettaa, että tuotteiden verokantaa EI OLE vielä päivitetty!

    // Loopataan läpi kaikki asennuksen suomalaiset yhtiot, joilla on verolliset myyntihinnat
    $query = "  SELECT yhtio.yhtio
                FROM yhtio
                JOIN yhtion_parametrit on (yhtion_parametrit.yhtio = yhtio.yhtio
                  AND yhtion_parametrit.alv_kasittely = '')
                WHERE yhtio.maa = 'FI'
                $query_where_lisa";
    $yhtio_result = mysql_query($query) or die($query);

    // Lisätään uuset verokannat
    while ($row = mysql_fetch_assoc($yhtio_result)) {

      $yhtio = $row['yhtio'];
      $yhtiorow = hae_yhtion_parametrit($yhtio);
      $update_count = 0;

      echo date("H:i:s d.m.Y"), ": Hintamuutos yritykselle $yhtio\n";

      $query = "  UPDATE tuote set
                  tuote.myyntihinta  = round(tuote.myyntihinta  / 1.09 * 1.10, {$yhtiorow['hintapyoristys']}),
                  tuote.myymalahinta = round(tuote.myymalahinta / 1.09 * 1.10, {$yhtiorow['hintapyoristys']}),
                  tuote.nettohinta   = round(tuote.nettohinta   / 1.09 * 1.10, {$yhtiorow['hintapyoristys']})
                  WHERE tuote.yhtio = '$yhtio'
                  AND tuote.alv = 9";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      $query = "  UPDATE tuote set
                  tuote.myyntihinta  = round(tuote.myyntihinta  / 1.13 * 1.14, {$yhtiorow['hintapyoristys']}),
                  tuote.myymalahinta = round(tuote.myymalahinta / 1.13 * 1.14, {$yhtiorow['hintapyoristys']}),
                  tuote.nettohinta   = round(tuote.nettohinta   / 1.13 * 1.14, {$yhtiorow['hintapyoristys']})
                  WHERE tuote.yhtio = '$yhtio'
                  AND tuote.alv = 13";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      $query = "  UPDATE tuote set
                  tuote.myyntihinta  = round(tuote.myyntihinta  / 1.23 * 1.24, {$yhtiorow['hintapyoristys']}),
                  tuote.myymalahinta = round(tuote.myymalahinta / 1.23 * 1.24, {$yhtiorow['hintapyoristys']}),
                  tuote.nettohinta   = round(tuote.nettohinta   / 1.23 * 1.24, {$yhtiorow['hintapyoristys']})
                  WHERE tuote.yhtio = '$yhtio'
                  AND tuote.alv = 23";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      $query = "  UPDATE hinnasto
                  JOIN tuote on (tuote.yhtio = hinnasto.yhtio
                    AND tuote.tuoteno = hinnasto.tuoteno
                    AND tuote.alv = 9)
                  SET hinnasto.hinta = round(hinnasto.hinta / 1.09 * 1.10, {$yhtiorow['hintapyoristys']})
                  WHERE hinnasto.yhtio = '$yhtio'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      $query = "  UPDATE hinnasto
                  JOIN tuote on (tuote.yhtio = hinnasto.yhtio
                    AND tuote.tuoteno = hinnasto.tuoteno
                    AND tuote.alv = 13)
                  SET hinnasto.hinta = round(hinnasto.hinta / 1.13 * 1.14, {$yhtiorow['hintapyoristys']})
                  WHERE hinnasto.yhtio = '$yhtio'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      $query = "  UPDATE hinnasto
                  JOIN tuote on (tuote.yhtio = hinnasto.yhtio
                    AND tuote.tuoteno = hinnasto.tuoteno
                    AND tuote.alv = 23)
                  SET hinnasto.hinta = round(hinnasto.hinta / 1.23 * 1.24, {$yhtiorow['hintapyoristys']})
                  WHERE hinnasto.yhtio = '$yhtio'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // Oletetaan, että toimitustapojen JV-kulut ovat alv 23% (pyöristys aina kaksi)
      $query = "  UPDATE toimitustapa set
                  toimitustapa.jvkulu = round(toimitustapa.jvkulu / 1.23 * 1.24, 2)
                  WHERE toimitustapa.yhtio = '$yhtio'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      $query = "  UPDATE asiakashinta
                  JOIN tuote on (tuote.yhtio = asiakashinta.yhtio
                    AND tuote.tuoteno = asiakashinta.tuoteno
                    AND tuote.alv = 9)
                  SET asiakashinta.hinta = round(asiakashinta.hinta / 1.09 * 1.10, {$yhtiorow['hintapyoristys']})
                  WHERE asiakashinta.yhtio = '$yhtio'
                  AND asiakashinta.tuoteno != ''";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      $query = "  UPDATE asiakashinta
                  JOIN tuote on (tuote.yhtio = asiakashinta.yhtio
                    AND tuote.tuoteno = asiakashinta.tuoteno
                    AND tuote.alv = 13)
                  SET asiakashinta.hinta = round(asiakashinta.hinta / 1.13 * 1.14, {$yhtiorow['hintapyoristys']})
                  WHERE asiakashinta.yhtio = '$yhtio'
                  AND asiakashinta.tuoteno != ''";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      $query = "  UPDATE asiakashinta
                  JOIN tuote on (tuote.yhtio = asiakashinta.yhtio
                    AND tuote.tuoteno = asiakashinta.tuoteno
                    AND tuote.alv = 23)
                  SET asiakashinta.hinta = round(asiakashinta.hinta / 1.23 * 1.24, {$yhtiorow['hintapyoristys']})
                  WHERE asiakashinta.yhtio = '$yhtio'
                  AND asiakashinta.tuoteno != ''";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // HUOM!! Oletetaan, että kaikki asiakashinnat, jota ei olla liitetty tuotteisiin on 23%
      $query = "  UPDATE asiakashinta set
                  asiakashinta.hinta = round(asiakashinta.hinta / 1.23 * 1.24, {$yhtiorow['hintapyoristys']})
                  WHERE asiakashinta.yhtio = '$yhtio'
                  AND asiakashinta.tuoteno = ''";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // HUOM!! Oletetaan, että kaikki rahtimaksut on 23% (pyöristys aina kaksi)
      $query = "  UPDATE rahtimaksut set
                  rahtimaksut.rahtihinta = round(rahtimaksut.rahtihinta / 1.23 * 1.24, 2)
                  WHERE rahtimaksut.yhtio = '$yhtio'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // HUOM!! Oletetaan, että rajasummat ovat 23%
      $query = "  UPDATE yhtion_parametrit set
                  yhtion_parametrit.suoratoim_ulkomaan_alarajasumma        = round(yhtion_parametrit.suoratoim_ulkomaan_alarajasumma / 1.23 * 1.24, 2),
                  yhtion_parametrit.erikoisvarastomyynti_alarajasumma      = round(yhtion_parametrit.erikoisvarastomyynti_alarajasumma / 1.23 * 1.24, 2),
                  yhtion_parametrit.erikoisvarastomyynti_alarajasumma_rivi = round(yhtion_parametrit.erikoisvarastomyynti_alarajasumma_rivi / 1.23 * 1.24, 2),
                  yhtion_parametrit.rahtivapaa_alarajasumma                = round(yhtion_parametrit.rahtivapaa_alarajasumma / 1.23 * 1.24, 2),
                  yhtion_parametrit.laskutuslisa                           = if (yhtion_parametrit.laskutuslisa_tyyppi not in ('L', 'K', 'N'), 
                                                                                  round(yhtion_parametrit.laskutuslisa / 1.23 * 1.24, 2), 
                                                                                  yhtion_parametrit.laskutuslisa),
                  yhtion_parametrit.kuljetusvakuutus                       = if (yhtion_parametrit.kuljetusvakuutus_tyyppi not in ('B', 'G'), 
                                                                                  round(yhtion_parametrit.kuljetusvakuutus / 1.23 * 1.24, 2), 
                                                                                  yhtion_parametrit.kuljetusvakuutus)
                  WHERE yhtion_parametrit.yhtio = '$yhtio'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();

      // HUOM!! Oletetaan että asiakkaan rajasumma on 23%
      $query = "  UPDATE asiakas set
                  asiakas.rahtivapaa_alarajasumma = round(asiakas.rahtivapaa_alarajasumma / 1.23 * 1.24, 2)
                  WHERE asiakas.yhtio = '$yhtio'";
      $result = pupe_query($query);
      $update_count += mysql_affected_rows();
      
      echo date("H:i:s d.m.Y"), ": Muutettiin $update_count tietuetta\n";      
    }
  }

  echo "\n";
  echo date("H:i:s d.m.Y"), ": Aloitetaan alv-update\n";

  // Lisätään uudet verokannat (kaikille)
  # alv_update_lisaa_avainsanat();

  // Nostetaan hintoja, mikäli käytössä on verolliset myyntihinnat
  // HUOM1! Tämä pitää ajaa ennenkuin tuotteet päivitetään!
  // HUOM2! Tämä EI nosta tuotteiden myyntihintoja avoimilta tilauksilta, tarjouksilta, sopimuksilta, jne...
  # alv_update_paivita_hinnat("and yhtio.yhtio in ('mast', 'flmar')");

  // Päivitetään tuotteiden ja asiakkaiden oletus alv
  # alv_update_paivita_tuote_ja_asiakas("and yhtio.yhtio in ('savt', 'allr', 'mast', 'flmar', 'colly', 'vipme', 'oga', 'tkp', 'maant', 'harda', 'makia', 'mmg', 'tawas', 'digi', 'optra', 'vizu', 'ota', 'resta', 'mara ', 'osuus', 'jarj ', 'henri', 'etela', 'suome', 'kahvi', 'matka', 'signa', 'srs', 'kike', 'kikes', 'artr', 'atarv', 'turva', 'asifi', 'merca', 'mertr', 'kiilt', '1', '2', 'aino', 'datap', 'demo', 'demo9', 'gift', 'hanki', 'hasse', 'iadv2', 'iadve', 'ieasy', 'ielon', 'ifinn', 'ifunh', 'iguar', 'ihoht', 'iholm', 'ihuon', 'iinte', 'ileca', 'ilii2', 'iliik', 'imapi', 'ipoin', 'ispee', 'itime', 'iunel', 'ivend', 'kaupp', 'khaal', 'kolma', 'kunta', 'kyyti', 'liima', 'media', 'moope', 'myyra', 'nelio', 'orien', 'pankk', 'posti', 'prii', 'roiko', 'saik', 'saikk', 'tadfl', 'tanto', 'tarom', 'tclem', 'tclus', 'tcoce', 'tekof', 'teles', 'terv.', 'tfeim', 'tgift', 'tgrav', 'tideo', 'tinno', 'tjoba', 'tkalu', 'tkoko', 'tkybi', 'tmaal', 'tmaka', 'tmapp', 'tmeri', 'tmydr', 'tnice', 'todec', 'toffi', 'tpiha', 'tpint', 'tpres', 'treil', 'tsafe', 'tseif', 'tspla', 'tsund', 'tswan', 'tsyne', 'ttert', 'ttoim', 'tturv', 'turma', 'tvest', 'twant', 'twear', 'vero', 'virta')");

  // Päivitetään verokannat avoimilta tilauksilta, tarjouksilta, sopimuksilta, jne...
  alv_update_paivita_avoimet("and yhtio.yhtio in ('allr', 'mast', 'flmar', 'colly', 'vipme', 'oga', 'tkp', 'maant', 'harda', 'makia', 'mmg', 'tawas', 'digi', 'optra', 'vizu', 'ota', 'resta', 'mara ', 'osuus', 'jarj ', 'henri', 'etela', 'suome', 'kahvi', 'matka', 'signa', 'srs', 'kike', 'kikes', 'artr', 'atarv', 'turva', 'asifi', 'merca', 'mertr', 'kiilt', '1', '2', 'aino', 'datap', 'demo', 'demo9', 'gift', 'hanki', 'hasse', 'iadv2', 'iadve', 'ieasy', 'ielon', 'ifinn', 'ifunh', 'iguar', 'ihoht', 'iholm', 'ihuon', 'iinte', 'ileca', 'ilii2', 'iliik', 'imapi', 'ipoin', 'ispee', 'itime', 'iunel', 'ivend', 'kaupp', 'khaal', 'kolma', 'kunta', 'kyyti', 'liima', 'media', 'moope', 'myyra', 'nelio', 'orien', 'pankk', 'posti', 'prii', 'roiko', 'saik', 'saikk', 'tadfl', 'tanto', 'tarom', 'tclem', 'tclus', 'tcoce', 'tekof', 'teles', 'terv.', 'tfeim', 'tgift', 'tgrav', 'tideo', 'tinno', 'tjoba', 'tkalu', 'tkoko', 'tkybi', 'tmaal', 'tmaka', 'tmapp', 'tmeri', 'tmydr', 'tnice', 'todec', 'toffi', 'tpiha', 'tpint', 'tpres', 'treil', 'tsafe', 'tseif', 'tspla', 'tsund', 'tswan', 'tsyne', 'ttert', 'ttoim', 'tturv', 'turma', 'tvest', 'twant', 'twear', 'vero', 'virta')");

  echo date("H:i:s d.m.Y"), ": Valmis\n\n";
