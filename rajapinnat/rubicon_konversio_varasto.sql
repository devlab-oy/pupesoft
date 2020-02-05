UPDATE varastopaikat 
JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = varastopaikat.yhtio and yhtion_toimipaikat.tunnus = varastopaikat.toimipaikka) 
SET varastopaikat.nimi = yhtion_toimipaikat.nimi, 
varastopaikat.osoite = yhtion_toimipaikat.osoite, 
varastopaikat.postino = yhtion_toimipaikat.postino,
varastopaikat.postitp = yhtion_toimipaikat.postitp,
varastopaikat.maa = yhtion_toimipaikat.maa
WHERE varastopaikat.yhtio = 'atarv';
UPDATE varastopaikat SET yhtio = 'artr', alkuhyllyalue = concat('#M',alkuhyllyalue), loppuhyllyalue = concat('#M',loppuhyllyalue) WHERE yhtio = 'atarv';
UPDATE tuotepaikat SET hyllyalue = concat('#M',hyllyalue), yhtio = if(saldo = 0, 'artr', yhtio), oletus = '' WHERE yhtio = 'atarv';
UPDATE tapahtuma SET hyllyalue = concat('#M', hyllyalue) WHERE yhtio = 'atarv';
UPDATE tilausrivi SET hyllyalue = concat('#M', hyllyalue) WHERE yhtio = 'atarv';
UPDATE suuntalavat SET alkuhyllyalue = concat('#M', alkuhyllyalue) WHERE yhtio = 'atarv';
UPDATE suuntalavat SET loppuhyllyalue = concat('#M', loppuhyllyalue) WHERE yhtio = 'atarv';
UPDATE varaston_tulostimet SET alkuhyllyalue = concat('#M', alkuhyllyalue) WHERE yhtio = 'atarv';
UPDATE varaston_tulostimet SET loppuhyllyalue = concat('#M', loppuhyllyalue) WHERE yhtio = 'atarv';
UPDATE sarjanumeroseuranta SET hyllyalue = concat('#M', hyllyalue) WHERE yhtio = 'atarv';
# Ennenku kustannuspaikkoi korjaillaan eri paikoissa, niin konvertoidaan muutama. Teh‰‰ t‰‰ t‰‰ll‰ niin tulee tehty‰ ennen mit‰‰n muuta
UPDATE kustannuspaikka SET koodi = '5580' WHERE yhtio = 'atarv' AND koodi = '7080';
UPDATE kustannuspaikka SET koodi = '5000' WHERE yhtio = 'atarv' AND koodi = '7000';
# Ennenku k‰ytt‰j‰t on konvertoitu, laitetaan ÷rumin k‰ytt‰jille myyntioikeudet kuntoon, jos heil ei oo mit‰‰ ennest‰‰n (on saaneet myyd‰ kaikista normaaleista = 139,121 eli jmalmi ja veikkola)
UPDATE kuka SET varasto = '139,121' where yhtio = 'artr' and varasto = '' and extranet = '';
