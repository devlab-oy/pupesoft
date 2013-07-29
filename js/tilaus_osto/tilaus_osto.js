function tarkasta_ostotilauksen_tilausrivien_toimittajien_saldot(tilausnumero, alert_viesti) {
	saldot_request_obj = hae_ostotilauksen_tilausrivien_toimittajien_saldot(tilausnumero);

	var viesti = '';
	saldot_request_obj.done(function(saldot) {
		$.each(saldot, function(index, saldo){
			alert_viesti_temp = alert_viesti.replace('*tuote*', saldo.tuoteno);
			alert_viesti_temp = alert_viesti_temp.replace('*kpl*', saldo.tehdas_saldo);
			viesti += alert_viesti_temp + '\n';
		});
	});

	return confirm(viesti);
}

function hae_ostotilauksen_tilausrivien_toimittajien_saldot(tilausnumero) {
	return $.ajax({
		async: false,
		type: 'GET',
		dataType: 'JSON',
		data: {
			ajax_request: 1,
			no_head: 'yes',
			hae_toimittajien_saldot: 1,
			tilausnumero: tilausnumero
		},
		url: 'tilaus_osto.php'
	}).done(function(data) {
		if (console && console.log) {
			console.log('Saldojen haku onnistui');
			//console.log(data);
		}
	}).fail(function(data){
		if (console && console.log) {
			console.log('Saldojen haku EPÄONNISTUI');
		}
	});
}