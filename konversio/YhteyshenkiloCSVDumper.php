<?php

require_once('CSVDumper.php');

class YhteyshenkiloCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'tyyppi'		 => 'KOODI', //tyyppi hardcodataan konvertoi_rivi funktiossa 'A'
			'nimi'			 => 'YHTHENK1',
			'liitostunnus'	 => 'KOODI', //Liitos tunnus kentt��n m�p�t��n KOODI, koska sit� k�ytet��n konvertoi_rivi funktiossa asiakas.tunnus hakuun
		);
		$required_fields = array(
			'nimi',
			'liitostunnus'
		);
		$columns_to_be_utf8_decoded = array(
			'nimi',
		);

		$this->setFilepath("/tmp/turvanasi_asiakas.csv");
		$this->setSeparator(';');
		$this->setKonversioArray($konversio_array);


		$this->setRequiredFields($required_fields);
		$this->setColumnsToBeUtf8Decoded($columns_to_be_utf8_decoded);
		$this->setTable('yhteyshenkilo');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			if ($rivi['YHTHENK1'] != '') {
				//Asiakas aineistossa on kahta data. Asiakkaita sek� yhteyshenkil�it�.
				//$rivi['YHTEYSHENK1'] == '' niin kyseess� on asiakas
				$rivi = $this->konvertoi_rivi($rivi, $index);
				$rivi = $this->decode_to_utf8($rivi);
				$rivi = $this->lisaa_pakolliset_kentat($rivi);

				//index + 2, koska eka rivi on header ja laskenta alkaa rivilt� 0
				$valid = $this->validoi_rivi($rivi, $index + 2);

				if (!$valid) {
					unset($this->rivit[$index]);
				}
			}
			else {
				unset($this->rivit[$index]);
			}

			$progressbar->increase();
		}
	}

	protected function konvertoi_rivi($rivi, $index) {
		$rivi_temp = array();

		foreach ($this->konversio_array as $konvertoitu_header => $csv_header) {
			if (array_key_exists($csv_header, $rivi)) {
				if ($konvertoitu_header == 'tyyppi') {
					$rivi_temp[$konvertoitu_header] = 'A';
				}
				else if ($konvertoitu_header == 'liitostunnus') {
					$asiakas_tunnus = $this->hae_asiakas_tunnus($rivi['KOODI']);
					if ($asiakas_tunnus == 0) {
						$this->errors[$index][] = t('Asiakasta')." {$rivi['KOODI']} ".t('ei l�ydy');
					}
					$rivi_temp[$konvertoitu_header] = $asiakas_tunnus;
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
			if ($rivi[$required_field] == '' and $required_field != 'liitostunnus') {
				$this->errors[$index][] = t('Pakollinen kentt�')." $required_field ".t('puuttuu');
				$valid = false;
			}
			else if ($required_field == 'liitostunnus' and $rivi[$required_field] == 0) {
//				$this->errors[$index][] = t('Pakollinen kentt�')." $required_field ".t('asiakasta ei l�ydy');
				$valid = false;
			}
		}

		return $valid;
	}

	private function hae_asiakas_tunnus($asiakasnro) {
		$query = "	SELECT tunnus
					FROM asiakas
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND asiakasnro = '{$asiakasnro}'";
		$result = pupe_query($query);
		$asiakasrow = mysql_fetch_assoc($result);

		if (!empty($asiakasrow)) {
			return $asiakasrow['tunnus'];
		}

		return 0;
	}

}