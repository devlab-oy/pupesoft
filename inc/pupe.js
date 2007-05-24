
function yllapito(url, tag, submitOnSelect) {
	select=document.getElementById(tag);
	
	toim=url.substr(0,url.indexOf("&"));
	valinta=select.options[select.selectedIndex].value;

	//	Jos meillä on valintana uusi, siirrytään tekemään uusi tietue!
	if(isNaN(valinta)) {
		
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
			//	Tehdään uutta
			if(valinta=='uusi') {
				newwindow=window.open('../yllapito.php?popparista=JOO&uusi=1&suljeYllapito='+tag+'&toim='+url, 'yllapito', 'width='+height+',height='+height+',top=100,left=100,scrollbars=no,resizable=yes');
			}
			//	Muokataan valittua
			else if (valinta.indexOf('\muokkaa#[0-9]+\b')) {
				tunnus=valinta.slice(valinta.indexOf("#")+1);
				newwindow=window.open('../yllapito.php?popparista=JOO&tunnus='+tunnus+'&suljeYllapito='+tag+'&toim='+url, 'yllapito', 'width='+height+',height='+height+',top=100,left=100,scrollbars=no,resizable=yes');
			}
			else {
				//alert('DONT FUCK THE SYSTEM \"'+valinta+'\"');
			}
		}		
	}
	else {
		
		// Onko meillä muokkaus?		
		sain='en';
		for(i=0;i<=select.options.length-1;i++) {
			if(select.options[i].value.substring(0,7)=='muokkaa') {
				if(valinta=='') {
					select.options[i]=null;
					sain='JOO';					
				}
				else {
					select.options[i].value='muokkaa#'+valinta;
					sain='JOO';					
				}
			}
		}
		
		//	Joudutaan duusaamaan kokonaan uusi..
		if(sain=='en') {
			var newOpt=document.createElement('option');
			newOpt.text='Muokkaa';
			newOpt.value='muokkaa#'+valinta;

			try {
				select.add(newOpt, select.options[select.options.length-1]);
			}
			catch(ex) {
				select.add(newOpt, select.options[select.options.length-1]);
			}
			
		}
		 if (submitOnSelect) {
			document.forms[submitOnSelect].submit();
		}
	}
}

function suljeYllapito(sID,value,text) {
	
	//	Wanhaa on muokattu, submittoidaan formi tarvittaessa..
	if(sID.substring(0,2)=='P_') {
		sID=sID.substr(2);
	
		sel=window.opener.document.getElementById(sID);
		
		//	merkataan oikea valituksi
		for(i in sel.options) {
			if(sel.options[i].value==value) {
				sel.selectedIndex=i;
				sel.options[i].text=text;
			}
		}
	
		if(sID=='asiakkaan_positio') {
			window.opener.document.forms[sID].submit();
		}
		window.close();
	}
	else {

		//	Paivitetaan ja valitaan select option
		var newOpt=document.createElement('option');
		newOpt.text=text;
		newOpt.value=value;

		//	Jos päivitettiin yhteyshenkilöitä meidän pitää listätä ne kaikkiin valikkoihin..			
		sela='EI'
		if(sID=='yhteyshenkilo_kaupallinen') {
			sela=window.opener.document.getElementById('yhteyshenkilo_tekninen');
		}
		else if (sID=='yhteyshenkilo_tekninen') {
			sela=window.opener.document.getElementById('yhteyshenkilo_kaupallinen');
		}
		
		if(sela!='EI') {
			var newOpt2=document.createElement('option');			
			newOpt2.text=text;
			newOpt2.value=value;
			
			try {
				sela.add(newOpt2, sela.options[1]);
			}
			catch(ex) {
				sela.add(newOpt2, 1);
			}
		}

		sel=window.opener.document.getElementById(sID);

		try {
			sel.add(newOpt, sel.options[1]);
		}
		catch(ex) {
			sel.add(newOpt, 1);
		}

		//	Valitaan uusi arvo
		sel.selectedIndex=1;

		
		
		window.close();
		
	}	
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
