<?php

/** Vastaavat ketjut
 *  Hakee vastaavat ketjuja tuotenumerolla
 */
class Vastaavat {

  // Ketjun id
  private $idt;

  // Tuote jolla ketju on alunperin haettu
  private $tuote;

  /** Ketju tarvitsee aina tuotenumeron minkä perusteella ketju luodaan.
   */
  function __construct($tuoteno, $options = array()) {
    global $kukarow;

    $conditions = '';

    if (!empty($options)) {
      // Tsekataan tarvittavat parametrit
      if ($options['skippaa_vaihtoehtoiset']) {
        $conditions .= " AND vaihtoehtoinen = '' ";
      }
    }

    if (!empty($tuoteno)) {
      // Haetaan haetun tuotteen tiedot vastaavuusketjusta
      $query = "SELECT group_concat(DISTINCT id order by id) idt
                FROM vastaavat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$tuoteno}'
                $conditions";
      $tuote_result = pupe_query($query);
      $tuote = mysql_fetch_assoc($tuote_result);

      // Ketju ja tuote talteen
      $this->idt    = $tuote['idt'];
      $this->tuote  = $tuoteno;
    }
  }

  function onkovastaavia() {
    return $this->idt != "";
  }

  function getIDt() {
    return $this->idt;
  }

  /** Palauttaa ketjun kaikki tuotteet
   */
  function tuotteet($ketju, $options = array()) {
    global $kukarow;

    $tuotteet = array();
    $conditions = '';

    if (!empty($options)) {

      if ($options['skippaa_vaihtoehtoiset']) {
        $conditions .= " AND vaihtoehtoinen = '' ";
      }

      // Tsekataan tarvittavat parametrit
      if ($options['vastaavuusketjun_jarjestys'] == 'K') {

        // Haetaan tuotteen järjestys jolla ketju on alunperin haettu
        $query = "SELECT if (jarjestys=0, 9999, jarjestys) jarjestys
                  FROM vastaavat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND id      = '{$ketju}'
                  AND tuoteno = '{$this->tuote}'";
        $result = pupe_query($query);
        $jarjestys = mysql_fetch_assoc($result);

        $conditions .= "HAVING jarjestys >= {$jarjestys['jarjestys']}";
      }
    }

    // Haetaan korvaavat ketju ja tuotteiden tiedot
    $query = "SELECT 'vastaava' as tyyppi, if (vastaavat.jarjestys=0, 9999, vastaavat.jarjestys) jarjestys, vastaavat.vaihtoehtoinen, vastaavat.tunnus as vastaavat_tunnus, tuote.*
              FROM vastaavat
              JOIN tuote ON vastaavat.yhtio=tuote.yhtio AND vastaavat.tuoteno=tuote.tuoteno
              WHERE vastaavat.yhtio = '{$kukarow['yhtio']}'
              AND vastaavat.id      = '{$ketju}'
              $conditions
              ORDER BY jarjestys, tuoteno";
    $result = pupe_query($query);

    while ($tuote = mysql_fetch_assoc($result)) {
      $tuotteet[] = $tuote;
    }

    return $tuotteet;
  }
}
