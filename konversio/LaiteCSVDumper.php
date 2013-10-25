<?php

require_once('CSVDumper.php');

class LaiteCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'tuoteno'	 => 'MALLI',
			'sarjanro'	 => 'MITAT',
			'valm_pvm'	 => 'TOIMPVM', //?? sarake on kauttaaltaan tyhjä
			'oma_numero' => 'DATA20',
			'paikka'	 => 'LISASIJAINTI',
			'sijainti'	 => 'LISASIJAINTI',
				//'sijainti'	 => 'TYYPPI', nämä pitää varmaan myös
				//'sijainti'	 => 'PAINO',
		);
		$required_fields = array(
			'tuoteno',
			'paikka',
		);
		$columns_to_be_utf8_decoded = array(
			'paikka',
			'sijainti',
		);

		$this->setFilepath("/tmp/laite.csv");
		$this->setSeparator(';');
		$this->setKonversioArray($konversio_array);


		$this->setRequiredFields($required_fields);
		$this->setColumnsToBeUtf8Decoded($columns_to_be_utf8_decoded);
		$this->setTable('laite');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->konvertoi_rivi($rivi, $index);
			$rivi = $this->decode_to_utf8($rivi);
			$rivi = $this->lisaa_pakolliset_kentat($rivi);

			//index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
			$valid = $this->validoi_rivi($rivi, $index + 2);

			if (!$valid) {
				unset($this->rivit[$index]);
			}

			$progressbar->increase();
		}
	}

	protected function konvertoi_rivi($rivi, $index) {
		$rivi_temp = array();

		foreach ($this->konversio_array as $konvertoitu_header => $csv_header) {
			if (array_key_exists($csv_header, $rivi)) {
				if ($konvertoitu_header == 'paikka') {
					$paikka_tunnus = $this->hae_paikka_tunnus($rivi[$csv_header]);
					if ($paikka_tunnus == 0) {
						$this->errors[$index][] = t('Paikkaa')." <b>".utf8_decode($rivi[$csv_header])."</b> ".t('ei löydy');
					}
					$rivi_temp[$konvertoitu_header] = $paikka_tunnus;
				}
				else {
					$rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
				}
			}
		}

		return $rivi_temp;
	}

	protected function validoi_rivi($rivi, $index) {
		$valid = true;
		foreach ($this->required_fields as $required_field) {
			if ($rivi[$required_field] == '') {
				$this->errors[$index][] = t('Pakollinen kenttä')." <b>$required_field</b> ".t('puuttuu');
				$valid = false;
			}
		}

		if ($valid) {
			$valid = $this->loytyyko_tuote($rivi['tuoteno']);
			if (!$valid) {
				$this->errors[$index][] = t('Tuote')." {$rivi['tuoteno']} ".t('puuttuu');
			}
			else {
				if ($rivi['paikka'] == 0) {
//					$this->errors[$index][] = t('Paikkaa')." {$rivi['paikka']} ".t('ei löydy');
					$valid = false;
				}
			}
		}

		return $valid;
	}

	private function loytyyko_tuote($tuoteno) {
		$query = "	SELECT tunnus
					FROM tuote
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);
		if (mysql_num_rows($result) == 1) {
			return true;
		}

		return false;
	}

	private function hae_paikka_tunnus($paikan_nimi) {
		$query = '	SELECT tunnus
					FROM paikka
					WHERE yhtio = "'.$this->kukarow['yhtio'].'"
					AND nimi = "'.$paikan_nimi.'"';
		$result = pupe_query($query);
		$paikkarow = mysql_fetch_assoc($result);

		if (!empty($paikkarow)) {
			return $paikkarow['tunnus'];
		}

		return 0;
	}

}