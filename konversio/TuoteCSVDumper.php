<?php

require_once('CSVDumper.php');

class TuoteCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'tuoteno'		 => 'KOODI',
			'nimitys'		 => 'NIMI',
			'try'			 => 'LUOKKA',
			'aleryhma'		 => 'LUOKKA',
			'myyntihinta'	 => 'HINTA',
			'tuotetyyppi'	 => 'RYHMA',
			'ei_saldoa'		 => 'RYHMA',
		);
		$required_fields = array(
			'tuoteno',
			'nimitys',
		);
		$columns_to_be_utf8_decoded = array(
			'nimitys',
		);

		$this->setFilepath("/tmp/turvanasi_tuote.csv");
		$this->setSeparator(';');
		$this->setKonversioArray($konversio_array);


		$this->setRequiredFields($required_fields);
		$this->setColumnsToBeUtf8Decoded($columns_to_be_utf8_decoded);
		$this->setTable('tuote');
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
				if ($konvertoitu_header == 'try' or $konvertoitu_header == 'aleryhma') {
					$rivi_temp[$konvertoitu_header] = substr($rivi[$csv_header], 0, 2);
				}
				else if ($konvertoitu_header == 'tuotetyyppi') {
					if (trim(strtolower($rivi[$csv_header])) == 'tuote') {
						$rivi_temp[$konvertoitu_header] = 'R';
					}
					else if (trim(strtolower($rivi[$csv_header])) == 'palvelutuote') {
						$rivi_temp[$konvertoitu_header] = 'K';
					}
					else {
						$rivi_temp[$konvertoitu_header] = '';
					}
				}
				else if ($konvertoitu_header == 'ei_saldoa') {
					if (trim(strtolower($rivi[$csv_header])) == 'palvelutuote') {
						$rivi_temp[$konvertoitu_header] = 'o';
					}
					else {
						$rivi_temp[$konvertoitu_header] = '';
					}
				}
				else if ($konvertoitu_header == 'nimitys') {
					$rivi_temp[$konvertoitu_header] = ucfirst($rivi[$csv_header]);
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

		if (!in_array($rivi['tuoteno'], $this->unique_values)) {
			$this->unique_values[] = $rivi['tuoteno'];
		}
		else {
			$this->errors[$index][] = t('Uniikki kenttä tuoteno')." <b>{$rivi['tuoteno']}</b> ".t('löytyy jo aineistosta');
			$valid = false;
		}

		return $valid;
	}

}