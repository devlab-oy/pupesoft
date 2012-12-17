<?php

require "inc/connect.inc";
require "inc/functions.inc";
require "valmistuslinjat.inc";

// Haetaan yhtiö
$yhtio = hae_yhtion_parametrit($_GET['yhtio']);
$kukarow['yhtio'] = $yhtio['yhtio'];

// Haetaan valmistuslinjat
$query = "SELECT selite as id, selitetark as name
			FROM avainsana
			WHERE yhtio='{$kukarow['yhtio']}'
			AND laji='VALMISTUSLINJA'
			ORDER BY selite";
$result = pupe_query($query);

$valmistuslinjat = array();
while($linja = mysql_fetch_assoc($result)) {
	$valmistuslinjat[] = $linja;
}

/**
* GET /valmistuslinjat/resurssit
* Haetaan valmistuslinjat
*/
if (isset($_GET['resurssit']) and $_GET['resurssit'] == 'true') {

	// Rakennetaan valmistuslinjat JSON viestiksi
	header('Content-type: application/json');
	echo json_encode($valmistuslinjat);
}

/**
* GET /valmistuslinjat/valmistukset
* Haetaan kaikki valmistuslinjoille laitetut valmistukset
*/
if (isset($_GET['valmistukset']) and $_GET['valmistukset'] == 'true') {

	// Kaikki valmistuslinjan tapahtumat
	$all_events = array();

	// Haetaan yhtiökohtaiset merkinnät
	// Muu työ tai Pyhä
	$query = "SELECT kalenteri.pvmalku,
					kalenteri.pvmloppu,
					kalenteri.kuka,
					kalenteri.henkilo,
					kalenteri.tyyppi,
					kalenteri.tunnus
				FROM kalenteri
				WHERE yhtio='{$kukarow['yhtio']}'
				AND henkilo = ''
				AND tyyppi IN ('PY', 'MT')";
	$result = pupe_query($query);

	$yhtiokohtaiset_tapahtumat = array();

	// Lomat ja muut yhtiökohtaiset merkinnät
	while ($pyha = mysql_fetch_assoc($result)) {
		$yhtiokohtaiset_tapahtumat[] = $pyha;
	}

	// Loopataan valmistuslinjat yksi kerrallaan
	foreach($valmistuslinjat as $linja) {

		// Lisätään yhtiokohtaiset tapahtumat
		foreach($yhtiokohtaiset_tapahtumat as $tapahtuma) {
			$json = array();
			$json['title'] 	= utf8_encode($tapahtuma['tyyppi']);
			$json['start'] 	= $tapahtuma['pvmalku'];
			$json['end'] 	= $tapahtuma['pvmloppu'];
			$json['allDay'] = false;
			$json['resource'] = $linja['id'];
			$json['color'] 	= '#666';
			$json['tunnus'] = $tapahtuma['tunnus'];
			$all_events[] 		= $json;
		}

		// Haetaan ja lisätään henkilökohtaiset tapahtumat
		// pekkanen, sairasloma, muu työ, loma, tai vapaa/poissa
		$query = "SELECT kalenteri.pvmalku as start,
						kalenteri.pvmloppu as end,
						kalenteri.kuka,
						kalenteri.henkilo as resource,
						kalenteri.tyyppi,
						kalenteri.tunnus
					FROM kalenteri
					WHERE yhtio='{$kukarow['yhtio']}'
					AND henkilo='{$linja['id']}'
					AND tyyppi IN ('PE', 'SA', 'MT', 'LO', 'PO')";
		$result = pupe_query($query);

		while ($row = mysql_fetch_assoc($result)) {
			$row['allDay'] = false;
			$row['title'] = $row['tyyppi'];
			$all_events[] = $row;
		}

		// Lisätään Valmistuslinjalla olevat valmistukset
		$valmistukset = hae_valmistuslinjan_valmistukset($linja);
		foreach($valmistukset as $valmistus) {
			#echo "valmistus: $valmistus[otunnus] $valmistus[pvmalku] $valmistus[pvmloppu]<br>";

			$json = array();

			$json['start'] = $valmistus['pvmalku'];
			$json['end'] = $valmistus['pvmloppu'];
			$json['kesto'] = valmistuksen_kesto($valmistus);

			$title = '';

			// Valmistuksella olevat tuotteet
			$tuotteet = hae_valmistuksen_tuotteet($valmistus);
			foreach($tuotteet as $tuote) {
				#echo "tuote: $tuote[nimitys] $tuote[tuoteno] $tuote[varattu] $tuote[yksikko]<br>";
				$title .= "$tuote[nimitys] $tuote[varattu] $tuote[yksikko]\n";
			}

			// JSON-rakenne
			/*
			{
				'start': '2012-12-12 12:12',	# kalenteri.pvmalku
				'end': '2012-12-12 12:12',		# kalenteri.pvmloppu
				'tila': 'OV',					# lasku.valmistuksen_tila
				'title': 'Otsikko',				# tilausrivi.nimitys
				'varattu': '12',				# tilausrivi.varattu
				'yksikko': 'KPL'				# tilausrivi.yksikko
				'kuka': ?,						# ?
				'resource': '1',				# kalenteri.henkilo
				'tunnus': '12345',				# kalenteri.tunnus
				'allDay': false,				# ei kokopäivän eventtejä
				'color': '#F00',				# väri
				'kesto': '20'					# valmistuksen_kesto()
			}
			*/

			$json['title'] = utf8_encode($title);
			$json['allDay'] = false;
			$json['tunnus'] = $valmistus['otunnus'];
			$json['resource'] = $linja['id'];
			$json['tila'] = $valmistus['valmistuksen_tila'];
			$json['tyyppi'] = $valmistus['tyyppi'];

			$puutteet = puuttuvat_raaka_aineet($valmistus['otunnus'], $valmistus['pvmalku']);

			if (!empty($puutteet)) {
				$json['color'] = '#833';
				$json['puutteet'] = $puutteet;
			}

			if ($valmistus['valmistuksen_tila'] == 'VT') {
				$json['color'] = '#555';
			} else if ($valmistus['valmistuksen_tila'] == 'VA') {
				$json['color'] = '#494';
			}


			$all_events[] = $json;
		}
	}

	// Vastaus
	header('Content-type: application/json');
	echo json_encode($all_events);
}