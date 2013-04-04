<?php

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
	}
	if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
		$_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
	}
}

require ("inc/parametrit.inc");

if ($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if (file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	else {
		echo "<font class='error'>".t("Tiedostoa ei ole olemassa")."</font>";
	}
	exit;
}

//Tänne tämän tiedoston ajax requestit
if ($ajax_request) {

}

echo "<font class='head'>".t("Laite hallinta")."</font><hr>";
?>
<style>
	.paikka_tr_hidden {
		display: none;
	}

	.laitteet_table_hidden {
		display: none;
	}

	.paikka_tr_not_hidden {
	}

	.laiteet_table_not_hidden {
	}
</style>
<script>
	$(document).ready(function() {
		bind_kohde_tr_click();
		bind_paikka_tr_click();

		bind_poista_kohde_button();
		bind_poista_paikka_button();
	});

	function bind_kohde_tr_click() {
		$('.kohde_tr').bind('click', function(event) {
			if (event.target.nodeName === 'TD') {
				var paikat = '.paikat_' + $(this).find('.kohde_tunnus').val();

				if ($(this).hasClass('hidden')) {
					//TODO yhteinäistä bind_tr_click logiikka. ei yhdistä vaan _yhtenäistä_
					$(this).parent().find(paikat).removeClass('paikka_tr_hidden');
					$(this).parent().find(paikat).addClass('paikka_tr_not_hidden');

					$(this).addClass('not_hidden');
					$(this).removeClass('hidden');

					$(this).find('.porautumis_img').attr('src', $('#right_arrow').val());
				}
				else {
					$(this).parent().find(paikat).removeClass('paikka_tr_not_hidden');
					$(this).parent().find(paikat).addClass('paikka_tr_hidden');

					$(this).addClass('hidden');
					$(this).removeClass('not_hidden');

					$(this).find('.porautumis_img').attr('src', $('#down_arrow').val());
				}
			}


		});
	}

	function bind_paikka_tr_click() {
		$('.paikat_tr').bind('click', function(event) {
			//paikat_tr sisällä on buttoni uuden laitteen luomiseen paikalle. jos sitä klikataan, emme halua triggeröidä tätä.
			if (event.target.nodeName === 'TD') {
				if ($(this).children().find('.laitteet_table_hidden').length > 0) {
					$(this).children().find('.laitteet_table_hidden').addClass('laitteet_table_not_hidden');
					$(this).children().find('.laitteet_table_hidden').removeClass('laitteet_table_hidden');

					$(this).find('.porautumis_img').attr('src', $('#right_arrow').val());
				}
				else {
					$(this).children().find('.laitteet_table_not_hidden').addClass('laitteet_table_hidden');
					$(this).children().find('.laitteet_table_hidden').removeClass('laitteet_table_not_hidden');

					$(this).find('.porautumis_img').attr('src', $('#down_arrow').val());
				}
			}

		});
	}

	function bind_poista_kohde_button() {
		$('.poista_kohde').click(function() {
			var ok = confirm($('#oletko_varma_confirm_message').val());
			if (ok) {
				var button = $(this);
				var kohde_tunnus = button.parent().find('.kohde_tunnus').val();
				$.ajax({
					type: 'GET',
					url: 'yllapito.php?toim=kohde&del=1&tunnus=' + kohde_tunnus,
					success: function() {
						//poistetaan kohde_tr:n paikka_tr:t
						button.parent().parent().parent().find('.paikat_' + kohde_tunnus).remove();

						//poistetaan itse kohde_tr
						button.parent().parent().remove();
					},
					async: false
				});
			}
		});
	}

	function bind_poista_paikka_button() {
		$('.poista_paikka').click(function() {
			var ok = confirm($('#oletko_varma_confirm_message').val());
			if (ok) {
				
			}
		});
	}

</script>
<?php

$request = array(
	'ytunnus'	 => $ytunnus,
	'asiakasid'	 => $asiakasid
);

if ($tee == 'hae_asiakas' or ($tee == '' and !empty($valitse_asiakas))) {
	$request['haettu_asiakas'] = hae_asiakas($request);
}


if (!empty($request['haettu_asiakas'])) {
	$kohteet = hae_asiakkaan_kohteet_joissa_laitteita($request);

	echo_kohteet_table($kohteet, $request['haettu_asiakas']);
}

echo "<div id='wrapper'>";
echo_kayttoliittyma($request);
echo "</div>";

function echo_kayttoliittyma($request = array()) {
	global $kukarow, $yhtiorow, $palvelin2;

	echo "<input type='hidden' id='down_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
	echo "<input type='hidden' id='right_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />";
	echo "<input type='hidden' id='oletko_varma_confirm_message' value='".t("Oletko varma")."' />";

	echo "<form method='POST' action='' name='asiakas_haku'>";

	echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";
	echo "<input type='text' id='ytunnus' name='ytunnus' />";
	echo "<input type='submit' value='".t("Hae")."' />";

	echo "</form>";
}

function echo_kohteet_table($kohteet = array(), $haettu_asiakas = array()) {
	global $palvelin2;

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Kohteen nimi")."</th>";
	echo "<th>".t("Paikan nimi")."</th>";
	echo "<th>".t("Laitteet")."</th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>";
	echo "<a href='yllapito.php?toim=kohde&uusi=1&valittu_asiakas={$haettu_asiakas['tunnus']}'><button>".t("Luo uusi kohde")."</button></a>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "</tr>";

	foreach ($kohteet as $kohde_index => $kohde) {
		kohde_tr($kohde_index, $kohde);
	}

	echo "</table>";
}

function kohde_tr($kohde_index, $kohde) {
	global $palvelin2;

	echo "<tr class='kohde_tr hidden'>";

	echo "<td>";
	echo "<input type='hidden' class='kohde_tunnus' value='{$kohde_index}' />";
	echo $kohde['kohde_nimi'];
	echo "&nbsp";
	echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
	echo "<button class='poista_kohde'>".t("Poista kohde")."</button>";
	echo "</td>";

	echo "<td>";
	//paikan nimi tyhjä
	echo "</td>";

	echo "<td>";
	//laitteet tyhjä
	echo "</td>";

	echo "</tr>";
	if(count($kohde['paikat']) > 0) {
		paikka_tr($kohde_index, $kohde['paikat']);
	}
}

function paikka_tr($kohde_index, $paikat) {
	echo "<tr class='paikka_tr_hidden paikat_{$kohde_index}'>";
	echo "<td>";
	echo "<a href='yllapito.php?toim=paikka&uusi=1&valittu_kohde={$kohde_index}'><button>".t("Luo kohteelle uusi paikka")."</button></a>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "</tr>";
	foreach ($paikat as $paikka_index => $paikka) {
		echo "<tr class='paikat_tr paikka_tr_hidden paikat_{$kohde_index}'>";

		echo "<td>";
		echo "</td>";

		echo "<td>";
		echo $paikka['paikka_nimi'];
		echo "&nbsp";
		echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
		echo "</td>";

		echo "<td>";
		echo "<a href='yllapito.php?toim=laite&uusi=1&valittu_paikka={$paikka_index}'><button>".t("Luo paikkaan uusi laite")."</button></a>";
		echo "<br/>";
		laitteet_table($paikka['laitteet']);
		echo "</td>";

		echo "</tr>";
	}
}

function laitteet_table($laitteet) {
	echo "<table class='laitteet_table_hidden'>";
	echo "<tr>";
	echo "<th>".t("Tuotenumero")."</th>";
	echo "<th>".t("Tuotteen nimi")."</th>";
	echo "<th>".t("Sijainti")."</th>";
	echo "</tr>";

	foreach ($laitteet as $laite) {
		echo "<tr>";

		echo "<td>";
		echo $laite['tuoteno'];
		echo "</td>";

		echo "<td>";
		echo $laite['tuote_nimi'];
		echo "</td>";

		echo "<td>";
		echo $laite['sijainti'];
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";
}

function hae_asiakas($request) {
	global $kukarow, $yhtiorow;

	if ($request['ytunnus'] != '') {
		$ytunnus = $request['ytunnus'];
		$asiakasid = $request['asiakasid'];
		require("inc/asiakashaku.inc");
	}

	return $asiakasrow;
}

function hae_asiakkaan_kohteet_joissa_laitteita($request) {
	global $kukarow;

	$query = "	SELECT kohde.tunnus as kohde_tunnus,
				kohde.nimi as kohde_nimi,
				paikka.tunnus as paikka_tunnus,
				paikka.nimi as paikka_nimi,
				tuote.nimitys as tuote_nimi,
				laite.*
				FROM kohde
				LEFT JOIN paikka
				ON ( paikka.yhtio = kohde.yhtio
					AND paikka.kohde = kohde.tunnus)
				LEFT JOIN laite
				ON ( laite.yhtio = paikka.yhtio
					AND laite.paikka = paikka.tunnus )
				LEFT JOIN tuote
				ON ( tuote.yhtio = laite.yhtio
					AND tuote.tuoteno = laite.tuoteno )
				WHERE kohde.yhtio = '{$kukarow['yhtio']}'
				AND kohde.asiakas = {$request['haettu_asiakas']['tunnus']}";
	$result = pupe_query($query);

	$kohteet = array();
	while ($kohde = mysql_fetch_assoc($result)) {
		$kohteet[$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['laitteet'][] = $kohde;
		$kohteet[$kohde['kohde_tunnus']]['kohde_nimi'] = $kohde['kohde_nimi'];
		$kohteet[$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['paikka_nimi'] = $kohde['paikka_nimi'];
	}

	return $kohteet;
}
require ("inc/footer.inc");