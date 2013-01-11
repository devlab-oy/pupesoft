<?php

/** Vastaavat ketjut
 *	Hakee vastaavat ketjuja tuotenumerolla
 */
class Vastaavat {

	// Ketjun id
	private $id;

	// Tuote jolla ketju on alunperin haettu
	private $tuote;

	// Tuotteen tyyppi vastaava/vaihtoehtoinen
	private $tyyppi;

	// Ketjun päätuote
	private $paatuote;

	// Ketjut, joita voi olla useampia
	private $ketjut = array();

	/** Ketju tarvitsee aina tuotenumeron minkä perusteella ketju luodaan.
	 */
	function __construct($tuoteno) {
		global $kukarow;

		if (!empty($tuoteno)) {
			// Haetaan haetun tuotteen tiedot korvaavuusketjusta
			$query = "SELECT * FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuoteno}' LIMIT 1";
			$tuote_result = pupe_query($query);
			$tuote = mysql_fetch_assoc($tuote_result);

			// Ketju ja tuote talteen
			$this->id = $tuote['id'];
			$this->tyyppi = $tuote['vaihtoehtoinen'];
			$this->tuote = $tuote;
		}
	}

	/* Hakee kaikkien ketjujen id:t joihin haettu tuote kuuluu.
	 */
	function ketjut() {
		global $kukarow;
		$this->ketjut = array();

		$query = "SELECT id FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$this->tuote['tuoteno']}'";
		$ketjut_result = pupe_query($query);

		while ($ketju = mysql_fetch_assoc($ketjut_result)) {
			$this->ketjut[] = $ketju['id'];
		}

		return $this->ketjut;
	}

	function getTyyppi() {
		return $this->tyyppi;
	}

	/** Palauttaa ketjun tuotteet ketjun id:n mukaan
	 */
	function find_by_ketju($id, $options = array()) {
		global $kukarow;

		$tuotteet = array();

		$conditions = '';
		if (!empty($options)) {
			// Heitetään nolla järjestykset ketjun viimeiseksti
			if ($this->tuote['jarjestys'] == 0) $this->tuote['jarjestys'] = 9999;

			// Tsekataan tarvittavat parametrit
			if($options['vastaavuusketjun_jarjestys'] == 'K') {
				$conditions = "HAVING jarjestys >= {$this->tuote['jarjestys']}";
			}
		}

		$query = "SELECT 'vastaava' as tyyppi, if(jarjestys=0, 9999, jarjestys) jarjestys, vaihtoehtoinen, tuote.*
					FROM vastaavat
					JOIN tuote ON vastaavat.yhtio=tuote.yhtio AND vastaavat.tuoteno=tuote.tuoteno
					WHERE vastaavat.yhtio='{$kukarow['yhtio']}'
					AND id='{$id}'
					#AND vaihtoehtoinen = '{$this->tyyppi}'
					$conditions
					ORDER BY jarjestys, tuoteno";
		$result = pupe_query($query);

		while ($tuote = mysql_fetch_assoc($result)) {
			$tuotteet[] = $tuote;
		}

		return $tuotteet;

	}

	/** Palauttaa ketjun kaikki tuotteet
	 */
	function tuotteet($options = array()) {
		global $kukarow;

		$tuotteet = array();

		$conditions = '';
		if (!empty($options)) {
			// Heitetään nolla järjestykset ketjun viimeiseksti
			if ($this->tuote['jarjestys'] == 0) $this->tuote['jarjestys'] = 9999;

			// Tsekataan tarvittavat parametrit
			if($options['vastaavuusketjun_jarjestys'] == 'K') {
				$conditions = "HAVING jarjestys >= {$this->tuote['jarjestys']}";
			}
		}

		// Haetaan korvaavat ketju ja tuotteiden tiedot
		$query = "SELECT 'vastaava' as tyyppi, if(jarjestys=0, 9999, jarjestys) jarjestys, vaihtoehtoinen, tuote.*
					FROM vastaavat
					JOIN tuote ON vastaavat.yhtio=tuote.yhtio AND vastaavat.tuoteno=tuote.tuoteno
					WHERE vastaavat.yhtio='{$kukarow['yhtio']}'
					AND id='{$this->id}'
					#AND vaihtoehtoinen = '{$this->tyyppi}'
					$conditions
					ORDER BY jarjestys, tuoteno";
		$result = pupe_query($query);

		while ($tuote = mysql_fetch_assoc($result)) {
			$tuotteet[] = $tuote;
		}

		return $tuotteet;
	}

	/** Palauttaa ketjun päätuotteen
	 */
	function paatuote() {
		global $kukarow;

		$query = "SELECT * FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND id={$this->id}
					ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno LIMIT 1";
		$result = pupe_query($query);

		$ketju = mysql_fetch_assoc($result);
		$this->paatuote = $ketju;

		return $this->paatuote['tuoteno'];
	}

	function lisaa_tuote($tuote, $jarjestys=0) {
		global $kukarow;
		$query  = "INSERT INTO vastaavat (yhtio, tuoteno, jarjestys, id, laatija, luontiaika, muutospvm, muuttaja)
		            VALUES ('$kukarow[yhtio]', '{$tuote}', '{$jarjestys}', '$this->id', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
		echo $query;
	}
}
