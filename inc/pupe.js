
function yllapito(url, tag, submitOnSelect) {
	select=document.getElementById(tag);
	
	toim=url.substr(0,url.indexOf("&"));
	//	Jos meillä on valintana uusi, siirrytään tekemään uusi tietue!
	if(select.options[select.selectedIndex].value=='uusi') {
		
		var width=0;
		var height=0;
		
		//	Koitetaan tehdä aina kaunis ja sopiva poppari
		switch (toim) {
			case 'yhteyshenkilo':
				width=400;
				height=400;
				break;
				
			case 'asiakkaan_positio':
				width=380;
				height=450;
				break;	
			case 'asiakkaan_kohde':
				width=400;
				height=400;
				break;
		}
		
		if(height==0 || width==0) {
			alert('Ylläpitotoimintoa '+toim+' ei tunneta!');
		}
		else {
			newwindow=window.open('../yllapito.php?popparista=JOO&uusi=1&suljeYllapito='+tag+'&toim='+url, 'yllapito', 'width='+height+',height='+height+',top=100,left=100,scrollbars=no,resizable=yes');
		}		
	}
	else if (submitOnSelect) {
		document.forms[submitOnSelect].submit();
	}
}

function suljeYllapito(sID,value,text) {

	if(sID=='yhteyshenkilo_kaupallinen' || sID=='yhteyshenkilo_tekninen') {
		
		//	Päivitetään tekninen yhteyshenkilo
		var newOptt=document.createElement('option');
		newOptt.text=text;
		newOptt.value=value;
		
		selt=window.opener.document.getElementById('yhteyshenkilo_tekninen');
		try {
			selt.add(newOptt, selt.options[1]);
		}
		catch(ex) {
			selt.add(newOptt, 1);
		}

		//	Päivitetään kaupallinen yhteyshenkilo		
		var newOptk=document.createElement('option');
		newOptk.text=text;
		newOptk.value=value;
		
		selk=window.opener.document.getElementById('yhteyshenkilo_kaupallinen');
		try {
			selk.add(newOptk, selk.options[1]);
		}
		catch(ex) {
			selk.add(newOptk, 1);
		}
		
		//	merkataan oikea valituksi
		if(sID=='yhteyshenkilo_kaupallinen') {
			selk.selectedIndex=1;
		}
		else {
			selt.selectedIndex=1;			
		}
	}
	else {
		
		//	Paivitetaan ja valitaan select option
		var newOpt=document.createElement('option');
		newOpt.text=text;
		newOpt.value=value;

		sel=window.opener.document.getElementById(sID);

		try {
			sel.add(newOpt, sel.options[1]);
		}
		catch(ex) {
			sel.add(newOpt, 1);
		}

		//	Valitaan uusi arvo
		sel.selectedIndex=1;
	}
			
	window.close();
	
}


function toimehtoTarkenne(toimehto) {
	tehto=document.getElementById(toimehto);
	tehtoLisa=document.getElementById(toimehto+'Lisa');
	
	teksti=tehto.options[tehto.selectedIndex].text;
	arvo=tehto.options[tehto.selectedIndex].value;	
	
	i=teksti.indexOf("-");
	if(i>0) {
		tarkenne=teksti.substr((i+1));
		tehtoLisa.value=tarkenne;
	}
	else {
		tehtoLisa.value='';
	}
}
