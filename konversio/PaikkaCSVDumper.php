<?php

require_once('CSVDumper.php');

class PaikkaCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'kohde'	 => 'KUSTPAIKKA',
			'nimi'	 => 'LISASIJAINTI',
			'osoite' => 'SIJAINTI',
			'kuvaus' => 'SIJAINTI',
		);
		$required_fields = array(
			'kohde',
		);
		$columns_to_be_utf8_decoded = array(
			'kohde',
			'nimi',
			'osoite',
			'kuvaus',
		);

		$this->setFilepath("/tmp/turvanasi_laite.csv");
		$this->setSeparator(';');
		$this->setKonversioArray($konversio_array);


		$this->setRequiredFields($required_fields);
		$this->setColumnsToBeUtf8Decoded($columns_to_be_utf8_decoded);
		$this->setTable('paikka');
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
				if ($konvertoitu_header == 'kohde') {
					$kohde_tunnus = $this->hae_kohde_tunnus(utf8_decode($rivi['KUSTPAIKKA']));
					if ($kohde_tunnus == 0) {
						$this->errors[$index][] = t('Kohdetta')." <b>".utf8_decode($rivi['KUSTPAIKKA'])."</b> ".t('ei löydy');
					}
					$rivi_temp[$konvertoitu_header] = $kohde_tunnus;
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
			if ($rivi[$required_field] == '' and $required_field != 'kohde') {
				$this->errors[$index][] = t('Pakollinen kenttä')." <b>$required_field</b> ".t('puuttuu');
				$valid = false;
			}
			else if ($required_field == 'kohde' and $rivi[$required_field] == 0) {
//				$this->errors[$index][] = t('Pakollinen kenttä')." $required_field ".t('kohdetta ei löydy');
				$valid = false;
			}
		}

		//Valitoidaan löytyykö kohteelle jo LISASIJAINTI niminen paikka
		$paikat = $this->unique_values[$rivi['kohde']];
		if (!empty($paikat)) {
			foreach ($paikat as $paikka) {
				if ($paikka == $rivi['nimi']) {
					//kyseinen paikka on jo kohteella
					$valid = false;
					break;
				}
			}
		}

		//Jos paikka on validi niin se voidaan lisätä kohteen paikkoihin
		if ($valid) {
			$this->unique_values[$rivi['kohde']][] = $rivi['nimi'];
		}

		return $valid;
	}

	private function hae_kohde_tunnus($kohde_nimi) {
		$query = '	SELECT tunnus
					FROM kohde
					WHERE yhtio = "'.$this->kukarow['yhtio'].'"
					AND nimi = "'.$kohde_nimi.'"
					LIMIT 1';
		$result = pupe_query($query);
		$kohderow = mysql_fetch_assoc($result);

		if (!empty($kohderow)) {
			return $kohderow['tunnus'];
		}

		return 0;
	}

}