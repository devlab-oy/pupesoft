<?php
class Korvaavat {

	public $id;
	public $tuote;
	public $paatuote;

	function __construct($tuoteno) {
		global $kukarow;

		// Tuotenumero on pakko olla
		if (empty($tuoteno)) exit("Korvaavat ketjun haku tarvitsee tuotenumeron");

		// Haetaan haetun tuotteen tiedot korvaavuusketjusta
		$query = "SELECT * FROM korvaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuoteno}' LIMIT 1";
		$tuote_result = pupe_query($query);
		$tuote = mysql_fetch_assoc($tuote_result);

		// Ketju ja tuote talteen
		$this->id = $tuote['id'];
		$this->tuote = $tuote;
	}

	/** Haetaan koko korvaavuusketju
	 *
	 */
	function tuotteet($options = array()) {
		global $kukarow;

		$tuotteet = array();

		$conditions = '';
		if (!empty($options)) {
			// Tsekataan tarvittavat parametrit
			if($options['korvaavuusketjun_jarjestys'] == 'K') {
				if ($this->tuote['jarjestys'] == 0) $this->tuote['jarjestys'] = 9999;
				$conditions = "HAVING jarjestys <= {$this->tuote['jarjestys']}";
			}
		}

		if ($this->id) {
			// Haetaan korvaavat ketju ja tuotteiden tiedot
			$query = "SELECT 'korvaava' as tyyppi, if(jarjestys=0, 9999, jarjestys) jarjestys, tuote.*
						FROM korvaavat
						JOIN tuote ON korvaavat.yhtio=tuote.yhtio AND korvaavat.tuoteno=tuote.tuoteno
						WHERE korvaavat.yhtio='{$kukarow['yhtio']}'
						AND id={$this->id}
						$conditions
						ORDER BY jarjestys, tuoteno";
			$result = pupe_query($query);

			while ($tuote = mysql_fetch_assoc($result)) {
				$tuotteet[] = $tuote;
			}
		}

		return $tuotteet;
	}

	function paatuote() {
		global $kukarow;

		// Haetaan ketjun päätuote
		if ($this->id) {
			$query = "SELECT * FROM korvaavat WHERE yhtio='{$kukarow['yhtio']}' AND id={$this->id}
						ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno LIMIT 1";
			$result = pupe_query($query);

			$ketju = mysql_fetch_assoc($result);
			$this->paatuote = $ketju;
		}

		return $this->paatuote['tuoteno'];
	}
}