<?php

/*
  HOW TO:

  $aihe = utf8_encode($yhtiorow['nimi']." - Ostoseuranta ".date("d.m.Y"));
  $viesti = utf8_encode('Liitteenä löytyy ostoseuranta raportit zip-tiedostoon pakattuna.<br/><br/>');

  $liitetiedosto = array(
  'filename' => 'Ostoseuranta_raportit.zip',
  'path' => '/tmp/Ostoseuranta_raportit.zip',
  'mime' => mime_content_type($maaranpaa)
  );

  $email = new Email($aihe, lahettaja@example.com);
  $email->add_vastaanottaja('vastaanottaja@example.com');
  $email->add_liitetiedosto($liitetiedosto);
  $email->set_viesti($viesti);
  $email->laheta();
 */

class Email {

	private $aihe;
	private $lahettaja;
	private $vastaanottajat = array();
	private $liitetiedostot = array();
	private $viesti		 = null;
	private $html_viesti = null;

	public function __construct($aihe, $lahettaja) {
		$this->set_aihe($aihe);
		$this->set_lahettaja($lahettaja);
	}

	public function laheta() {
		global $yhtiorow;
		$lahettaja				 = $this->get_lahettaja();
		$boundary_mixed			 = uniqid('mixed', true);
		$boundary_alternative	 = uniqid('alternative', true);

		$aihe_encoded = $this->encode_otsikko($this->get_aihe());

		$plain_text		 = chunk_split(base64_encode($this->get_viesti()));
		$html_encoded	 = chunk_split(base64_encode($this->get_html_viesti()));

		$headers = <<<EOT
From: $lahettaja
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="$boundary_mixed"
EOT;

		$body = <<<EOT
--$boundary_mixed
Content-Type: multipart/alternative; boundary="$boundary_alternative"

--$boundary_alternative
Content-Type: text/plain; charset="utf-8"
Content-Transfer-Encoding: base64

$plain_text
--$boundary_alternative

EOT;
		if (!empty($html_encoded)) {
			$body .= <<<EOT
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: base64

$html_encoded
--$boundary_alternative--

EOT;
		}

		if (!empty($this->liitetiedostot)) {
			foreach ($this->liitetiedostot as $tiedosto) {
				$file_content	 = $this->get_file_boundary($tiedosto);
				$body .= <<<EOT
--$boundary_mixed
$file_content
EOT;
			}
		}
		$body .= <<<EOT
--$boundary_mixed--
EOT;
		$vastaanottajat			 = $this->get_vastaanottajat();
		foreach ($vastaanottajat as $to) {
			$ok = mail($to, $aihe_encoded, $body, $headers, "-f {$yhtiorow['postittaja_email']}");

			if ($ok === false) {
				echo "Mailin lähetys osoitteeseen {$to} epäonnistui :(";
			}
			else {
				echo "Mailin lähetys osoitteeseen {$to} onnistui :>";
			}
		}
	}

	public function set_aihe($aihe) {
		$this->aihe = (string)$aihe;
	}

	public function get_aihe() {
		return $this->aihe;
	}

	public function set_lahettaja($lahettaja) {
		$this->lahettaja = (string)$lahettaja;
	}

	public function get_lahettaja() {
		return $this->lahettaja;
	}

	public function add_vastaanottaja($vastaanottaja) {
		$this->vastaanottajat[] = $vastaanottaja;
	}

	public function add_vastaanottajat(array $vastaanottajat) {
		foreach ($vastaanottajat as $vastaanottaja) {
			$this->add_vastaanottaja($vastaanottaja);
		}
	}

	public function get_vastaanottajat() {
		return $this->vastaanottajat;
	}

	public function add_liitetiedosto(array $liitetiedosto) {
		if (empty($liitetiedosto['filename'])) {
			throw new Exception("Liitetiedostolla ei ole tiedostonimea");
		}
		if (empty($liitetiedosto['path'])) {
			throw new Exception("Liitetiedostolta ".$liitetiedosto['filename']." puuttuu polku");
		}
		if (!file_exists($liitetiedosto['path'])) {
			throw new Exception("Liitetiedostoa ei löytynyt annetusta polusta: ".$liitetiedosto['path']);
		}
		if (empty($liitetiedosto['mime'])) {
			throw new Exception("Liitetiedostolla ei ole tyyppia");
		}

		$this->liitetiedostot[] = $liitetiedosto;
	}

	public function add_liitetiedostot(array $liitetiedostot) {
		foreach ($liitetiedostot as $liitetiedosto) {
			$this->add_liitetiedosto($liitetiedosto);
		}
	}

	public function get_liitetiedostot() {
		return $this->liitetiedostot;
	}

	public function set_viesti($viesti) {
		$this->viesti = (string)$viesti;
	}

	public function get_viesti() {
		return $this->viesti;
	}

	public function set_html_viesti($html) {
		$this->html_viesti = (string)$html;
	}

	public function get_html_viesti() {
		return $this->html_viesti;
	}

	protected function get_file_boundary(array $file) {
		$tiedosto_sisalto	 = chunk_split(base64_encode(file_get_contents($file['path'])));
		$sisalto	 = <<<EOT
Content-Type: {$file['mime']} name="{$file['filename']}"
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="{$file['filename']}"

{$tiedosto_sisalto}
EOT;
		return $sisalto;
	}

	protected function encode_otsikko($otsikko) {
		return '=?UTF-8?B?'.base64_encode($otsikko).'?=';
	}

}

?>