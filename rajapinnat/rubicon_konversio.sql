UPDATE yhtion_toimipaikat
JOIN kustannuspaikka m1 on (m1.yhtio = yhtion_toimipaikat.yhtio and m1.tunnus = yhtion_toimipaikat.kustp)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET yhtion_toimipaikat.kustp = if(m2.tunnus is null, 0, m2.tunnus)
WHERE yhtion_toimipaikat.yhtio = 'atarv';

UPDATE yhtion_toimipaikat SET yhtio = 'artr' WHERE yhtio = 'atarv';

INSERT into oikeu SET kuka= '', sovellus= 'Ylläpito', nimi= 'yllapito.php', alanimi= 'toimitustavat_toimipaikat', paivitys= '', lukittu= '', nimitys= 'Toimitustavat toimipaikat', jarjestys= '111', jarjestys2= '0', profiili= '', yhtio= 'artr', hidden= 'H', laatija = 'admin', luontiaika = now(), muutospvm = now(), muuttaja = 'artr';
INSERT into oikeu SET kuka= 'Admin profiili', sovellus= 'Ylläpito', nimi= 'yllapito.php', alanimi= 'toimitustavat_toimipaikat', paivitys= '', lukittu= '', nimitys= 'Toimitustavat toimipaikat', jarjestys= '111', jarjestys2= '0', profiili= 'Admin profiili', yhtio= 'artr', hidden= 'H', laatija = 'admin', luontiaika = now(), muutospvm = now(), muuttaja = 'artr';

INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '151', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '152', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '152', selitetark_2 = '968', laatija = 'konversio', luontiaika = now(), jarjestys = "2";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '154', selitetark_2 = '444', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '155', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '155', selitetark_2 = '968', laatija = 'konversio', luontiaika = now(), jarjestys = "2";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '156', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '157', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '157', selitetark_2 = '969', laatija = 'konversio', luontiaika = now(), jarjestys = "2";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '158', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '158', selitetark_2 = '969', laatija = 'konversio', luontiaika = now(), jarjestys = "2";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '159', selitetark_2 = '503', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '160', selitetark_2 = '510', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '161', selitetark_2 = '444', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '162', selitetark_2 = '503', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '164', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '165', selitetark_2 = '517', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '166', selitetark_2 = '503', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '167', selitetark_2 = '503', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '168', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '168', selitetark_2 = '969', laatija = 'konversio', luontiaika = now(), jarjestys = "2";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '169', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '170', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '171', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '172', selitetark_2 = '503', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '173', selitetark_2 = '503', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '174', selitetark_2 = '503', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '175', selitetark_2 = '515', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
# Jmalmi - takuuvarasto eri toimitustavoilla
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '123', selitetark_2 = '495', laatija = 'konversio', luontiaika = now(), jarjestys = "1";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '123', selitetark_2 = '503', laatija = 'konversio', luontiaika = now(), jarjestys = "2";
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'SIIRTOVARASTOT', selite = '139', selitetark = '123', selitetark_2 = '969', laatija = 'konversio', luontiaika = now(), jarjestys = "3";

UPDATE yhtion_parametrit SET jt_toimitus_varastorajaus = 'K' WHERE yhtio in ('artr','atarv');
UPDATE yhtion_parametrit SET myyntitilauksen_toimipaikka = 'A' WHERE yhtio in ('artr','atarv');
UPDATE yhtion_parametrit SET tee_siirtolista_myyntitilaukselta = 'K' WHERE yhtio in ('artr','atarv');
UPDATE yhtion_parametrit SET automaattinen_jt_toimitus_siirtolista = 'S' WHERE yhtio  in ('artr','atarv');
UPDATE yhtion_parametrit SET siirtolistan_tulostustapa = 'U' WHERE yhtio = 'artr';
UPDATE yhtion_parametrit SET siirtolistan_tulostustapa = 'A' WHERE yhtio = 'atarv';
UPDATE yhtion_parametrit SET takuuvarasto = '123' WHERE yhtio = 'artr';
UPDATE yhtion_parametrit SET kirjanpidollinen_varastosiirto_myyntitilaukselta = 'K' WHERE yhtio in ('artr','atarv');
UPDATE yhtion_parametrit SET tee_automaattinen_siirto_myyntitilaukselta = 'K' WHERE yhtio  in ('artr','atarv');
UPDATE yhtion_parametrit SET siirtolistat_vastaanotetaan_per_lahto = 'K' WHERE yhtio = 'atarv';
UPDATE yhtion_parametrit SET Toimipaikkakasittely = 'L' WHERE yhtio  in ('artr','atarv');
UPDATE yhtion_parametrit SET Tarkenteiden_prioriteetti = 'T' WHERE yhtio  in ('artr','atarv');
UPDATE yhtion_parametrit SET pakollinen_varasto = 'K' WHERE yhtio  in ('artr','atarv');

INSERT INTO oikeu SET yhtio = 'artr', kuka = 'Admin profiili', sovellus = 'Ylläpito', nimi = 'yllapito.php', alanimi = 'yhtion_toimipaikat_parametrit', paivitys = 1, nimitys = 'Toimipaikan parametrit', jarjestys = 35, profiili = 'Admin profiili', laatija = 'konversio', luontiaika = now();
INSERT INTO oikeu SET yhtio = 'artr', kuka = '', sovellus = 'Ylläpito', nimi = 'yllapito.php', alanimi = 'yhtion_toimipaikat_parametrit', paivitys = 1, nimitys = 'Toimipaikan parametrit', jarjestys = 35, profiili = '', laatija = 'konversio', luontiaika = now();
INSERT INTO oikeu SET yhtio = 'artr', kuka = '', sovellus = 'Tuotehallinta', nimi = 'yllapito.php', alanimi = 'tuote&status=E', paivitys = 1, nimitys = 'Tarkistettavat tuotteet', jarjestys = 25, profiili = '', laatija = 'konversio', luontiaika = now();

UPDATE oikeu SET yhtio = 'artr' WHERE yhtio = 'atarv' AND sovellus = 'Myyntireskontra' and nimi like 'Futursoft%' AND kuka = '';
UPDATE oikeu SET hidden = '' WHERE yhtio = 'artr' AND sovellus in ('Kirjanpito','Ylläpito') and nimi = 'yllapito.php' AND alanimi = 'yhtion_toimipaikat';

UPDATE puun_alkio SET puun_tunnus = '14464', yhtio = 'artr' WHERE yhtio = 'atarv' AND laji = 'asiakas' AND puun_tunnus = '15381';
UPDATE puun_alkio SET kutsuja = laji WHERE yhtio = 'artr' AND kutsuja = '';

# Kirjoittimet kuntoon
# Sähköpostiin 295 --> 59 (täs vaihees käyttäjät ja varastopaikat jo siirretty, joten muokataan suoraan kaikille örumissa, koska 295 on vain atarvilla)
UPDATE kuka SET kirjoitin = 59 WHERE yhtio = 'artr' AND kirjoitin = 295;
UPDATE varastopaikat SET printteri0 = 59 WHERE yhtio = 'artr' AND printteri0 = 295;
UPDATE varastopaikat SET printteri1 = 59 WHERE yhtio = 'artr' AND printteri1 = 295;
UPDATE varastopaikat SET printteri2 = 59 WHERE yhtio = 'artr' AND printteri2 = 295;
UPDATE varastopaikat SET printteri3 = 59 WHERE yhtio = 'artr' AND printteri3 = 295;
UPDATE varastopaikat SET printteri4 = 59 WHERE yhtio = 'artr' AND printteri4 = 295;
UPDATE varastopaikat SET printteri5 = 59 WHERE yhtio = 'artr' AND printteri5 = 295;
UPDATE varastopaikat SET printteri6 = 59 WHERE yhtio = 'artr' AND printteri6 = 295;
UPDATE varastopaikat SET printteri7 = 59 WHERE yhtio = 'artr' AND printteri7 = 295;
UPDATE varastopaikat SET printteri9 = 59 WHERE yhtio = 'artr' AND printteri9 = 295;
UPDATE varastopaikat SET printteri10 = 59 WHERE yhtio = 'artr' AND printteri10 = 295;

UPDATE kirjoittimet SET yhtio = 'artr' WHERE yhtio = 'atarv' AND kirjoitin not in ('Sähköpostiin', 'Edi-ostotilaus');

# Tiliöintisääntöjen ja tiliotesääntöjen kustannuspaikat koitetaan päivittää tässä samalla kustannuspaikan koodin avulla oikeaksi, jos vaan löytyy sopiva toisesta yhtiöstä samalla koodilla.
UPDATE tiliointisaanto
JOIN kustannuspaikka m1 on (m1.yhtio = tiliointisaanto.yhtio and m1.tunnus = tiliointisaanto.kustp)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET tiliointisaanto.kustp = if(m2.tunnus is null, 0, m2.tunnus)
WHERE tiliointisaanto.yhtio = 'atarv';

UPDATE tiliotesaanto
JOIN kustannuspaikka m1 on (m1.yhtio = tiliotesaanto.yhtio and m1.tunnus = tiliotesaanto.kustp)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET tiliotesaanto.kustp = if(m2.tunnus is null, 0, m2.tunnus)
WHERE tiliotesaanto.yhtio = 'atarv';

UPDATE tiliotesaanto
JOIN kustannuspaikka m1 on (m1.yhtio = tiliotesaanto.yhtio and m1.tunnus = tiliotesaanto.kustp2)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET tiliotesaanto.kustp2 = if(m2.tunnus is null, 0, m2.tunnus)
WHERE tiliotesaanto.yhtio = 'atarv';

# HUOM muista tehdä ennen tiliointisaanto ja toimi -konversioita toimittaja-konversio, missä tunnukset voi muuttua. OK!
UPDATE tiliointisaanto SET yhtio = 'artr' WHERE yhtio = 'atarv' AND ttunnus != 27371;
UPDATE tiliotesaanto SET yhtio = 'artr' WHERE yhtio = 'atarv';

# Matkustajan kustannuspaikka koitetaan päivittää tässä samalla kustannuspaikan koodin avulla oikeaksi, jos vaan löytyy sopiva toisesta yhtiöstä samalla koodilla.
UPDATE toimi
JOIN kustannuspaikka m1 on (m1.yhtio = toimi.yhtio and m1.tunnus = toimi.kustannuspaikka)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET toimi.kustannuspaikka = if(m2.tunnus is null, 0, m2.tunnus)
WHERE toimi.yhtio = 'atarv' AND toimi.tyyppi = 'K' AND toimi.nimi != 'mataa';

UPDATE toimi
JOIN kustannuspaikka m1 on (m1.yhtio = toimi.yhtio and m1.tunnus = toimi.projekti)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET toimi.projekti = if(m2.tunnus is null, 0, m2.tunnus)
WHERE toimi.yhtio = 'atarv' AND toimi.tyyppi = 'K' AND toimi.nimi != 'mataa';

UPDATE toimi
JOIN kustannuspaikka m1 on (m1.yhtio = toimi.yhtio and m1.tunnus = toimi.kohde)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET toimi.kohde = if(m2.tunnus is null, 0, m2.tunnus)
WHERE toimi.yhtio = 'atarv' AND toimi.tyyppi = 'K' AND toimi.nimi != 'mataa';

UPDATE toimi SET yhtio = 'artr' WHERE yhtio = 'atarv' AND tyyppi = 'K' AND nimi != 'mataa';

# Muutama Örumin tuote pointtaa nyt Örumin toimittajiin, mut pitää pointata Masista tuotuihin toimittajiin. Tehään se tässä
UPDATE tuotteen_toimittajat SET liitostunnus = '26793' WHERE yhtio = 'artr' AND liitostunnus = '20410';
# Ei käännetä Koivusta ja paria muuta (toistaiseksi dokumentoituna tässä, just in case)
#UPDATE tuotteen_toimittajat SET liitostunnus = '26758' WHERE yhtio = 'artr' AND liitostunnus = '32691';
#UPDATE tuotteen_toimittajat SET liitostunnus = '31779' WHERE yhtio = 'artr' AND liitostunnus = '32692';
#UPDATE tuotteen_toimittajat SET liitostunnus = '26766' WHERE yhtio = 'artr' AND liitostunnus = '31109';
#UPDATE tuotteen_toimittajat SET liitostunnus = '26759' WHERE yhtio = 'artr' AND liitostunnus = '29164';
#UPDATE tuotteen_toimittajat SET liitostunnus = '27502' WHERE yhtio = 'artr' AND liitostunnus = '29349';
#UPDATE tuotteen_toimittajat SET liitostunnus = '26795' WHERE yhtio = 'artr' AND liitostunnus = '32690';

UPDATE avainsana SET selitetark = '32691' WHERE yhtio = 'atarv' AND laji = 'SAHKTILTUN' AND selitetark = '26758';
UPDATE avainsana SET selitetark = '32692' WHERE yhtio = 'atarv' AND laji = 'SAHKTILTUN' AND selitetark = '31779';
UPDATE avainsana SET selitetark = '31109' WHERE yhtio = 'atarv' AND laji = 'SAHKTILTUN' AND selitetark = '26766';
UPDATE avainsana SET selitetark = '29164' WHERE yhtio = 'atarv' AND laji = 'SAHKTILTUN' AND selitetark = '26759';
UPDATE avainsana SET selitetark = '29349' WHERE yhtio = 'atarv' AND laji = 'SAHKTILTUN' AND selitetark = '27502';
UPDATE avainsana SET selitetark = '32690' WHERE yhtio = 'atarv' AND laji = 'SAHKTILTUN' AND selitetark = '26795';

# as.avainsana masi pks ohjausmerkit
UPDATE asiakkaan_avainsanat SET avainsana = 'Ma-Si PKS', tarkenne = trim(substr(tarkenne, 2,999)) WHERE yhtio = 'atarv' AND avainsana = 'Oma kuljetus 1' AND laji = 'OHJAUSMERKKI';

# PKS lähdöt, laitetaan sinne ohjausmerkkilisä
UPDATE toimitustavan_lahdot SET ohjausmerkki = '1' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '09:30:00';
UPDATE toimitustavan_lahdot SET ohjausmerkki = '2' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '11:30:00';
UPDATE toimitustavan_lahdot SET ohjausmerkki = '3' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '12:45:00';
UPDATE toimitustavan_lahdot SET ohjausmerkki = '4' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '14:00:00';
UPDATE toimitustavan_lahdot SET ohjausmerkki = '5' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '15:15:00';
UPDATE toimitustavan_lahdot SET ohjausmerkki = '6' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '23:30:00';

UPDATE lahdot SET ohjausmerkki = '1' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '09:30:00' AND aktiivi != 'S';
UPDATE lahdot SET ohjausmerkki = '2' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '11:30:00' AND aktiivi != 'S';
UPDATE lahdot SET ohjausmerkki = '3' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '12:45:00' AND aktiivi != 'S';
UPDATE lahdot SET ohjausmerkki = '4' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '14:00:00' AND aktiivi != 'S';
UPDATE lahdot SET ohjausmerkki = '5' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '15:15:00' AND aktiivi != 'S';
UPDATE lahdot SET ohjausmerkki = '6' WHERE yhtio = 'artr' AND liitostunnus = '446' AND varasto = '139' AND lahdon_kellonaika = '23:30:00' AND aktiivi != 'S';

# Pks varasto erikoisvarastoksi
UPDATE varastopaikat SET tyyppi = 'E' WHERE yhtio = 'artr' AND tunnus = '153';

# Pks spessu myyntioikeudet
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '27', selitetark = '139', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '27', selitetark = '153', jarjestys = '2', laatija = 'admin', luontiaika = now();

# muut toimipaikat
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '14', selitetark = '173', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '15', selitetark = '164', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '16', selitetark = '169', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '17', selitetark = '170', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '18', selitetark = '171', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '19', selitetark = '152', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '20', selitetark = '155', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '21', selitetark = '161', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '22', selitetark = '156', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '23', selitetark = '163', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '24', selitetark = '157', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '25', selitetark = '158', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '26', selitetark = '166', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '28', selitetark = '160', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '29', selitetark = '165', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '30', selitetark = '165', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '31', selitetark = '162', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '32', selitetark = '151', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '33', selitetark = '154', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '34', selitetark = '168', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '35', selitetark = '159', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '36', selitetark = '167', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '38', selitetark = '172', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '40', selitetark = '174', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '41', selitetark = '175', jarjestys = '1', laatija = 'admin', luontiaika = now(), selitetark_2 = 'x';
# muille myös toiseksi myyntioikeudeksi Jmalmi (oma on default, jmalmi järjestyksessä toinen).
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '14', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '15', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '16', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '17', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '18', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '19', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '20', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '21', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '22', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '23', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '24', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '25', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '26', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '28', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '29', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '30', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '31', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '32', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '33', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '34', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '35', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '36', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '38', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '40', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();
INSERT INTO avainsana SET yhtio = 'artr', kieli = 'fi', laji = 'TOIMMYYNTI', selite = '41', selitetark = '139', jarjestys = '2', laatija = 'admin', luontiaika = now();

# Pks käyttäjille myyntioikeudet myös Jmalmille käyttäjähallinnassa. Täs kohtaa ne on jo artr:ssä.
UPDATE kuka SET varasto = concat(varasto, ',', '139'), oletus_varasto = '139', oletus_ostovarasto = '139' where yhtio = 'artr' and toimipaikka = 27;

# Laitetaan kaikille toimipaikoille reklamaatiovarastoksi oma varasto
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '14', arvo = '173', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();  
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '15', arvo = '164', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '16', arvo = '169', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '17', arvo = '170', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '18', arvo = '171', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '19', arvo = '152', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '20', arvo = '155', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '21', arvo = '161', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '22', arvo = '156', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '23', arvo = '163', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '24', arvo = '157', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '25', arvo = '158', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '26', arvo = '166', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '28', arvo = '160', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '29', arvo = '165', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '30', arvo = '165', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '31', arvo = '162', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '32', arvo = '151', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '33', arvo = '154', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '34', arvo = '168', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '35', arvo = '159', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '36', arvo = '167', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '38', arvo = '172', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '40', arvo = '174', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();
INSERT INTO yhtion_toimipaikat_parametrit SET yhtio = 'artr', toimipaikka = '41', arvo = '175', parametri = 'reklamaation_vastaanottovarasto', laatija = 'konversio', luontiaika = now();

# toimipaikoille omat toimitustavat
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '14';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '14';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '15';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '15';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '16';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '16';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '17';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '17';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '18';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '18';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '19';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '19';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '20';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '20';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '24';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '24';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '25';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '25';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '26';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '26';

# pks on 27
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '27';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '27';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '27';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '27';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '27';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '446', toimipaikka_tunnus = '27';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '28';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '28';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '30';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '30';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '31';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '31';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '32';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '32';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '33';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '33';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '34';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '34';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '35';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '35';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '36';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '36';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '37';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '37';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '38';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '38';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '40';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '40';

INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '951', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '960', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '961', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '967', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '952', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '953', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '954', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '955', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '956', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '957', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '958', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '959', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '949', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '963', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '965', toimipaikka_tunnus = '41';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '966', toimipaikka_tunnus = '41';

# örum
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '443', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '532', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '533', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '943', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '525', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '524', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '948', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '468', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '466', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '465', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '469', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '467', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '462', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '461', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '464', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '463', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '478', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '483', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '480', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '481', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '482', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '479', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '484', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '487', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '485', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '486', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '456', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '515', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '514', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '516', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '517', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '518', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '527', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '950', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '446', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '474', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '475', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '472', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '473', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '471', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '470', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '477', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '476', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '964', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '447', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '451', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '452', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '450', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '962', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '444', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '445', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '449', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '513', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '498', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '492', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '494', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '495', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '493', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '497', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '945', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '496', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '490', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '507', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '509', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '510', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '508', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '512', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '947', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '511', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '500', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '502', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '503', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '501', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '505', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '946', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '504', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '519', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '520', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '529', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '528', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '968', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '969', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '453', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '530', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '944', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '460', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '457', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '458', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '459', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '526', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '455', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '454', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '531', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '448', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '488', toimipaikka_tunnus = '0';
INSERT INTO toimitustavat_toimipaikat SET yhtio = 'artr', toimitustapa_tunnus = '489', toimipaikka_tunnus = '0';

UPDATE avainsana SET yhtio = 'artr' WHERE yhtio = 'atarv' AND laji in ('ASAVAINSANA','INVEN_LAJI','KATEISOTTO','MYYNNISTA_OSTO','PAIKALLISVARAST','SAHKTILTUN','TOIMITUSTAPA_OS','TOIMVAHVISTUS');
UPDATE avainsana SET yhtio = 'artr', selitetark = concat('Jakelu - ', selitetark) WHERE yhtio = 'atarv' AND laji = 'ASIAKASRYHMA';
UPDATE avainsana SET yhtio = 'artr' WHERE yhtio = 'atarv' AND laji = 'TV' AND selite in ('SPVR10', 'SPR10');
UPDATE avainsana SET jarjestys = jarjestys+10 WHERE yhtio = 'artr' AND laji = 'LAHETETYYPPI';
UPDATE avainsana SET yhtio = 'artr' WHERE yhtio = 'atarv' AND laji = 'LAHETETYYPPI';
UPDATE avainsana SET yhtio = 'artr', selitetark = 'Normaalitilaus käsin' WHERE yhtio = 'atarv' AND laji = 'OSTOTIL_TILTYYP' AND tunnus = '630432';
INSERT INTO avainsana SET yhtio = 'artr', laji = 'OSTOTIL_TILTYYP', kieli = 'fi', selite = '2', selitetark = 'Normaalitilaus', jarjestys = '0';
INSERT INTO avainsana SET yhtio = 'artr', laji = 'OSTOTIL_TILTYYP', kieli = 'fi', selite = '1', selitetark = 'Pikalähetys (Maahantuonti)', jarjestys = '1';
INSERT INTO avainsana SET yhtio = 'artr', laji = 'ASAVAINSANA', kieli = 'fi', selite = 'oletusmyyntivarasto', selitetark = 'Oletusmyyntivarasto', jarjestys = '0', laatija = 'konversio', luontiaika = now();
UPDATE toimitustapa SET selite = concat('Jakelu_', selite) WHERE yhtio = 'atarv' AND selite in ('Nouto', 'Suoratoimitus');
UPDATE toimitustapa SET yhtio = 'artr', kuljetusvakuutus_tyyppi = 'E' WHERE yhtio = 'atarv';

# Asiakkaan kustannuspaikka koitetaan päivittää tässä samalla kustannuspaikan koodin avulla oikeaksi, jos vaan löytyy sopiva toisesta yhtiöstä samalla koodilla.
UPDATE asiakas
JOIN kustannuspaikka m1 on (m1.yhtio = asiakas.yhtio and m1.tunnus = asiakas.kustannuspaikka)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET asiakas.kustannuspaikka = if(m2.tunnus is null, 0, m2.tunnus)
WHERE asiakas.yhtio = 'atarv';

UPDATE asiakas
JOIN kustannuspaikka m1 on (m1.yhtio = asiakas.yhtio and m1.tunnus = asiakas.projekti)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET asiakas.projekti = if(m2.tunnus is null, 0, m2.tunnus)
WHERE asiakas.yhtio = 'atarv';

UPDATE asiakas
JOIN kustannuspaikka m1 on (m1.yhtio = asiakas.yhtio and m1.tunnus = asiakas.kohde)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET asiakas.kohde = if(m2.tunnus is null, 0, m2.tunnus)
WHERE asiakas.yhtio = 'atarv';

# Osa asiakkaista laji P:ksi
UPDATE asiakas SET laji = 'P' WHERE yhtio = 'atarv' AND tunnus in
('285855','285959','282782','284586','288141','288195','285949','281802','288079','283475','287910','281253','288066','281076','288086','284090','288211','281724','288197','287415','284125','283160','288182','284268','280655','287901','283074','288202','283102','283576','284875','284081','281953','282462','288065','287866','287917','286387','282835','287914','283953','288019','284060','284940','287895','287988','283164','285982','287916','284338','288162','287908','281363','282887','286382','288096','288198','288128','288174','288130','287826','285980','288145','288014','288224','287307','287411','288103','288287','288049','288038','283122','288121','288179','284772','288037','284024','288068','288024','280830','288373','283260','288266','288185','288283','283871','285990','287929','284010','284373','288261','288381','285044','287928','284639','287942','288370','284199','288106','288022','280754','288264','288144','284410','287885','282892','286710','283692','286740','285968','288039','288100','280953','285360','288067','288098','288180','288147','287940','288016','288099','287927','288074','283261','288078','282977','287905','288167','286066','283721','288386','288040','283864','288199','288216','287990','288095','288122','288196','288030','281825','288072','288183','288227','288140','288076','287319','286506','288291','280985','288184','287939','282049','288050','288028','281034','281879','280986','88179','280840','90325','281026','281011','283780','284135','282929','283963','285066','284590','281915','94103','281006','284957','277459','280551','280566','280569','280575','280577','280578','280580','280581','280582','280584','280585','280586','280587','280590','280594','280596','280598','280599','280600','280602','280604','280606','280607','280608','280611','280622','280626','280635','280637','280638','280640','280642','280643','280646','280649','280652','280656','280665','280666','280667','280673','280674','280678','280680','280681','280689','280700','280703','280706','280710','280714','280718','280720','280722','280723','280732','280738','280739','280743','280747','280756','280757','280766','280767','280770','280774','280775','280776','280777','280781','280783','280784','287923','280786','280788','280789','280791','280792','280793','280795','280798','280799','280800','280801','280803','280816','280817','280818','280827','280829','280831','280839','287919','280845','280847','280849','280851','280853','280854','280855','280856','280857','280858','280859','280861','280867','280869','280870','280871','280874','280875','280881','280883','280884','280891','280892','280894','280901','280903','280908','280914','280917','288165','280923','280932','280933','280940','280960','280961','280965','280968','280973','280979','280983','280987','280990','280991','280992','280993','280996','280997','281000','281002','281004','281005','281007','281008','281013','281015','281017','281019','281020','281021','281027','281029','281031','281035','281037','281039','281042','281045','281046','281047','281048','281050','281053','281058','281059','281061','281063','281065','281066','281069','281070','281073','281074','281075','281078','281079','281080','281083','281085','281087','281088','281092','281093','281095','281096','281097','281099','281100','281101','281104','281106','281107','281108','281109','281110','281111','281112','281113','281116','281117','281122','281125','281131','281133','281136','281138','281139','281140','281141','281142','281143','281144','281146','281147','281148','281150','281153','281154','281155','281159','281161','281167','281168','281172','281175','281176','281179','281180','281181','281182','281184','281189','281191','281192','281193','281194','281201','281206','281208','281209','281210','281215','281217','281220','281221','281222','281225','281227','281228','281229','281230','281231','281232','281233','281234','281235','281236','281238','281239','281241','281242','281243','281245','281248','281250','281251','281252','281254','281256','281260','281261','281263','281266','281267','281269','281271','281272','281273','281274','281275','281276','281277','281278','281279','281280','281281','281282','281283','281284','281285','281286','281287','281289','281290','281291','281292','281294','281295','281296','281297','281298','281301','281304','281305','281306','281307','281308','281309','281310','281312','281313','281314','281315','281316','281317','281318','281320','281321','281324','281326','281327','281328','281331','281332','281333','281339','281341','281342','281343','281344','281345','281346','281348','281350','281352','281354','281355','281356','281360','281361','281362','281364','281365','281367','281368','281369','281371','281372','281373','281375','281379','281380','281382','281384','281385','281387','281389','281391','281392','281393','281394','281397','281398','281399','281400','281402','281404','281405','281406','281407','281409','281410','281411','281412','281413','281414','281417','281418','281419','281422','281423','281424','281425','281426','281427','281428','281429','281431','281432','281433','281434','281435','281436','281437','281438','281440','281441','281446','281447','281450','281453','281454','281457','281458','281459','281460','281462','281466','281467','281468','281469','281470','281472','281474','281476','281478','281479','281481','281482','281483','281484','281488','281489','281490','281494','281495','281496','281498','281501','281504','281506','281511','281513','281514','281515','281517','281522','281523','281524','281525','281529','281530','281531','281532','281534','281537','281540','281543','281544','281546','281549','281550','281556','281559','281560','281563','281565','281570','281575','281576','281578','281579','281581','281582','281584','281588','281596','281597','281606','281607','281609','281610','281611','281612','281615','281634','281638','281643','281644','281646','281653','281654','281665','281666','281668','281669','281672','281673','281676','281682','281685','281689','281691','281696','281699','281703','281706','281714','281721','281722','281727','281733','281736','281737','281739','281741','281742','281743','281749','281750','281752','281753','281756','281757','281759','281760','281762','281764','281767','281768','281771','281772','281773','281776','281780','281781','281782','281784','281792','281796','281808','281811','281812','281813','281815','281819','281822','281826','281831','281833','281839','281846','281849','281860','281863','281865','281866','281867','281868','281870','281872','281873','281875','281876','281877','281880','281881','281882','281883','281884','281887','281888','281889','281894','281896','281902','281912','281914','281916','281919','281921','281924','281928','281932','281933','281940','281944','281946','281955','281960','281963','281980','281985','281986','281988','281992','281995','281999','282001','282004','282009','282010','282011','282012','282016','282028','282031','282034','282036','282043','282045','282047','282054','282060','282062','282063','282068','282074','282102','282110','282114','282117','282122','282132','282158','282163','282197','282198','282206','282210','282218','282229','282233','282245','282254','282259','282262','282269','282428','282432','282434','282457','282482','282752','282753','282770','282771','282772','282773','282778','282795','282798','282804','282806','282810','282815','282820','282822','282824','282825','282826','282827','282829','282830','282836','282839','282842','282848','282849','282850','282852','282859','282863','282864','282872','282875','282881','282885','282886','282894','282900','282903','282906','282907','282908','282909','282910','282914','282918','282919','282920','282923','282925','282927','282930','282931','282932','282933','282934','282935','282936','282937','282938','282939','282941','282942','282946','282948','282951','282955','282957','282959','282962','282969','282976','282980','282982','282983','282984','282985','282986','282987','282988','282989','282990','282991','282992','282994','282996','282999','283000','283001','283003','283007','283008','283011','283016','283020','283021','283022','283023','283025','283026','283028','283029','283032','283033','283036','283037','283038','283041','283042','283043','283045','283048','283050','283052','283054','283055','283056','283059','283060','283061','283063','283065','283066','283068','283069','283071','283076','283077','283079','283080','283081','283086','283089','283090','283096','283099','283104','283111','283112','283115','283123','283126','283127','283129','283130','283131','283132','283135','283136','283139','283140','283143','283144','283148','283151','283152','283154','283159','283161','283169','283177','283186','283188','283190','283193','283195','283201','283203','283205','283206','283211','283212','283213','283214','283220','283221','283222','283224','283225','283226','283230','283233','283234','283237','283241','283243','283244','283246','283247','283248','283250','283252','283253','283254','283256','283263','283265','283267','283271','283272','283273','283274','283275','283278','283279','283280','283283','283284','283286','283287','283291','283293','283295','283298','283299','283301','283302','283304','283309','283313','283319','283320','283323','283325','283326','283327','283328','283330','283331','283332','283333','283336','283341','283344','283348','283349','283350','283352','283353','283357','283360','283362','283367','283368','283369','283381','283382','283386','283387','283391','283393','283396','283398','283400','283401','283405','283407','283411','283413','283419','283424','283427','283428','283430','283432','283434','283437','283449','283450','283454','283458','283461','283463','283465','283468','283470','283471','283474','283476','283477','283480','283482','283484','283489','283492','283495','283497','283498','283501','283502','283507','283508','283510','283511','283513','283514','283516','283517','283518','283520','283521','283523','283529','283535','283536','283539','283541','283542','283543','283544','283546','283548','283549','283550','283553','283554','283558','283559','283560','283561','283568','283570','283572','283580','283582','283583','283584','283585','283586','283587','283588','283590','283594','283598','283600','283602','283603','283605','283606','283607','283608','283609','283613','283615','283616','283617','283618','283619','283625','283627','283629','283630','283632','283635','283637','283643','283644','283645','283646','283648','283653','283661','283662','283665','283667','283668','283670','283675','283676','283681','283682','283684','283686','283687','283693','283694','283696','283700','283701','283707','283709','283714','283715','283717','283720','283723','283725','283730','283741','283742','283744','283746','283747','283748','283750','283757','283758','283767','283768','283784','283785','283789','283797','283801','283803','283804','283811','283817','283824','283825','283836','283840','283849','283858','283865','283866','283867','283868','283872','283875','283876','283884','283885','283890','283891','283894','283895','283898','283899','283905','283906','283908','283921','283922','283926','283931','283934','283935','283938','283943','283945','283948','283950','283951','283955','283957','283961','283964','283967','283969','283974','283975','283981','283983','283985','283988','283989','283990','283993','284000','284008','284009','284012','284016','284017','284020','284022','284025','284026','284027','284031','284032','284042','284065','284067','284068','284074','284078','284083','284087','284091','284098','284100','284101','284108','284112','284113','284115','284123','284124','284129','284130','284131','284133','284134','284138','284140','284142','284143','284144','284150','284152','284161','284171','284173','284179','284180','284183','284184','284185','284187','284190','284194','284195','284196','284197','284198','284200','284202','284207','284208','284211','284212','284215','284219','284223','284225','284228','284234','284249','284251','284252','284254','284278','284288','284289','284290','284291','284313','284316','284319','284320','284322','284323','284330','284333','284344','284351','284353','284361','284364','284366','284367','284374','284376','284378','284379','284381','284383','284388','284392','284394','284395','284400','284406','284415','284422','284427','284429','284430','284432','284436','284437','284440','284441','284442','284444','284446','284449','284450','284451','284458','284459','284460','284461','284463','284464','284465','284468','284469','284470','284473','284478','284479','284480','284482','284484','284491','284492','284497','284499','284502','284504','284505','284507','284508','284509','284510','284511','284513','284515','284524','284569','284571','284572','284573','284580','284582','284583','284584','284585','284597','284598','284609','284617','284620','284631','284632','284634','284641','284642','284645','284646','284662','284668','284672','284678','284680','284683','284684','284685','284693','284698','284709','284711','284719','284720','284723','284726','284734','284735','284736','284738','284743','284744','284749','284750','284751','284752','284753','284754','284760','284762','284763','284764','284775','284781','284786','284790','284794','284801','284802','284810','284813','284816','284821','284832','284836','284843','284846','284848','284852','284854','284856','284863','284865','284868','284869','284870','284883','284886','284889','284892','284893','284896','284899','284904','284906','284908','284909','284911','284914','284915','284918','284921','284923','284936','284938','284945','284949','284954','284958','284961','284962','284965','284967','284971','284976','284978','284982','284984','284985','284986','284987','284990','284992','284993','285008','285009','285016','285026','285027','285028','285032','285051','285052','285074','285075','285078','285081','285082','285088','285090','285092','285097','285101','285102','285107','285110','285112','285113','285116','285120','285125','285126','285127','285131','285136','285141','285143','285146','285153','285157','285209','285405','285416','285428','285483','285508','285519','285521','285525','285545','285585','285594','285620','285637','285644','285690','285716','285717','285757','285760','285799','285800','285805','285814','285835','285839','285844','285849','285850','285853','285854','285858','285877','285882','285883','285887','285895','285902','285904','285908','285921','285944','285951','285969','285970','285971','285973','285975','285981','285986','285993','285994','285995','286007','286010','286015','286025','286036','286039','286042','286045','286046','286047','286048','286053','286057','286060','286065','286297','286339','286380','286381','286384','286385','286388','286389','286518','286644','286652','286667','286671','286680','286683','286684','286709','286741','286768','286770','287272','287273','287286','287287','287321','287332','287333','287335','287360','287378','287400','287412','287767','287768','287775','287835','287838','287840','287869','287890','287893','287894','287898','287900','287906','287909','287911','287913','287915','287918','287920','287921','287922','287924','287925','287926','287930','287931','287932','287945','287978','287986','287987','287989','288011','288017','288020','288021','288027','288044','288047','288060','288063','288064','288071','288073','288075','288080','288081','288082','288083','288085','288087','288088','288089','288090','288091','288092','288094','288097','288107','288127','288129','288131','288134','288135','288137','288142','288149','288160','288161','288163','288181','288186','288194','288200','288201','288203','288208','288215','288217','288219','288223','288225','288226','288262','288265','288267','288271','288272','288282','288285','288286','288372','288371','288375','288379','288382','288384','288388','288389','288390','288391','288392','288393','288394','288395','281520','281415','281586','281572','281548','283062','284604','283257','283277','281636','283976','281451','281202','283904','287818','285840','283018','281258','281486','281970','281690','281735','285841','283537','284717','288102','288101','288104','281539','282993','281533','281623','282007','282997','281509','282463','283035','284628','285412','285612','285780','281602','282201','287820','282945','281503','281526','281585','282922','281545','283057','283791','285431','283337','281758','285083','280671','285724','283986','283980','283030','283966','283009','281056','283266','284588','281185','282912','281237','283258','284309','283494','282315','283421','282905','283483','283464','285023','284659','284650','285140','284653','284661','284651','281595','282096','281044','281270','284337','283845','286245','284258','284302','284386','283690','283800','281207','283650','283880','283184','283345','287896','281599','282921','281566','281959','283919','282944','283118','281518','283944','281766','285152','283937','283842','288045','285155','284715','281028','287819','283039','283958','281060','281629','288084','283636','285960','283927','281499','285775','287823','282477','286156','283047','281390','281359','287409','285957','282998','283067','283909','284003','284575','287971','283525','287980','285950','283053','284050','284001','286721','283936','280583','281803','281908','283200','283091','283044','283962','284756','281338','282911','282899','281124','281177','281378','283624','283631','283851','285085','286438','288398','288397','284079','281779','284845','283014','281265','282952','288109','280693','280682','282066','283031','281268','281366','282940','283940','283821','284467','280704','280676','284304','284263','284679','282759','283928','283010','283914','283827','285086','287303','287304','287305','287306','287308','287309','287310','287311','285118','282975','287822','281491','281293','283610','281856','282550','281857','288002','288003','280613','283034','284737','283764','283488','285478','281216','283556','281783','281247','284097','284148','281538','281583','284056','280890','282249','283019','283933','284089','282915','281311','284193','281763','281383','283869','283870','284593','284621','284871','283006','281325','283197','281604','281528','281527','283282','281552','288170','283024','284677','285138','283639','284930','287899','287331','283634','283671','283551','282191','283930','281551','284326','280610','281024','86683','283015','284578','281555','281554','281564','281553','283688','280688','280685','93121','283733','280971','285953','285804','285809','282065','285868','282448','283698','285151','282371','283228','281858','280772','281401','283312','281353','281334','284243','283994','284147','283245','283204','281800','281830','284052','284141','280632','283064','285076','284589','281090','87740','283318','283049','283970','280712','282901','281769','283002','283017','281571','281770','281824','283005','283912','283843','284944','283829','284729','282454','282291','285991','283881','281519','283447','284054','281040','288222','285150','283051','283973','282137','283979','284606','286525','286526','286527','283058','283997','283314','281569','281064','281062','283013','281196','281485','284799','281439','283621','285408','284405','288187','281145','283158','283593','284084','284049','281608','285134','284292','285161','286675','286676','283996','283915','283924','284035','284244','283270','281751','283264','284635','285792','280708','282871','282870','281025','281102','281213','284204','284203','282244','285436','281814','285824','283826','288025','282891','288004','288005','288006','283027','283942','281618','285095','284673','284015','282459','283229','284172','281051','281187','283281','283240','283249','283297','283107','283156','283218','284694','282916','281652','284972');

# Extranet-käyttäjille pieni jekku
UPDATE extranet_kayttajan_lisatiedot
JOIN kuka ON (kuka.yhtio = extranet_kayttajan_lisatiedot.yhtio AND kuka.tunnus = extranet_kayttajan_lisatiedot.liitostunnus AND kuka.extranet = 'X' AND kuka.oletus_asiakas in 
(55723,103433,104125,104303,274954,104004,104013,100216,103509,103636,104050,103282,56247,109,104262,103974,103879,104315,104916,104397,103937,104197,103182,57741,103633,104034,103970,743,103332,104290,103724,103392,59361,104925,103635,56131,103333,103459,55718,60620,104162,103900,274941,103931,274930,103212,103371,61464,103585,103263,104392,104339,104967,104342,103316,59161,103170,55843,103208,61102,103186,104116,275116,103328,104126,104185,104930,62091,85279,95561,103997,104410,103611,103580,103681,103793,104171,103520,104170,104018,103089,274609,103989,103935,103398,103857,274921,104177,104924,104348,287973,2035,280239,103899,103471,103323,280531,274979,103068,103143,103616,286748,103364,275009,103617,104205,103856,104007,103814,104008,288705,104084,55540,104145,103619,103618,288715,104091,103412,104066,104464,104068,104083,104192,104142,103838,99851,274942,103596,103866,103836,103915,275321,103890,281832,104214,104005,105267,101800,103986,103858,103592,103837,103854,103575,103574,104258,103951,104475,103336,274776,104905,103628,275221,103630,104071,104236,103863,103787,103819,103870,104414,103488,103990,103877,103114,104284,62074,288034,103158,103996,103929,104325,103306,103955,280257,274437,275113,55912,103071,103968,104278,103820,55955,102851,104039,102845,102848,102844,102849,104078,103874,103972,103952,104327,274489,103920,103085,103102,103992,104300,103584,103991,103307,102046,104435,104023,104026,103487,103458,103491,103587,103177,103369,104123,103905,274618,103978,274488,104312,103809,103895,104200,104030,104198,275331,103304,275224,102159,2021,103612,275363,103181,274676,103348,105088,103365,104412,104109,104128,104124,104959,104122,104425,274802,274758,274670,104207,104156,275320,274725,104363,103994,104178,104181,104459,104184,104190,104189,103153,103511,103207,103253,103367,104222,103081,104343,103297,104247,104243,275380,104252,103397,103334,285906,103597,274751,104263,103305,103366,103264,104298,104297,104302,104301,275218,104407,274639,104318,274943,103325,103310,286751,275290,104330,274450,103320,104895,104378,275143,103346,288212,104441,104344,2143,274629,104460,104367,104382,274951,104373,103947,104401,104416,104409,104424,274926,103291,104436,103524,275219,104456,103245,104480,104466,104463,103462,103535,103530,103501,103527,103522,103515,56177,103512,275136,274528,274740,103313,103492,103490,103489,103410,103476,103386,103260,274560,103678,100518,274556,282106,103402,103419,103437,103434,103418,103415,104961,274116,103399,103396,103700,103327,274550,103065,286713,103668,103360,103321,104997,104980,103374,275137,103376,103322,103266,103342,103051,103337,103355,103352,103341,103340,104326,103053,274816,103298,103281,103280,103286,286056,103190,105261,103234,103244,103237,103232,103235,103302,103174,103222,103069,274563,274549,275150,103176,103171,103165,103159,103140,103157,103121,103129,104926,103446,103087,103118,103732,103086,103077,104989,97053,104899,103061,103055,103054,103056,288708,104919,274743,288598,104912,104892,103977,104893,280328,99790,104953,104942,104936,104950,104947,104944,104948,105036,275235,104958,103356,274562,104970,274755,104981,104982,104983,104987,104991,104998,105000,105005,105007,105009,105024,105027,104994,105032,105028,104986,104999,105023,104993,104995,104996,105020,105021,105033,105034,105022,105030,105006,105008,105012,105015,105018,105011,105016,105017,105019,103096,282276,274733,274156,274998,274127,274654,104136,275076,288594,274167,274512,274854,274149,274531,274158,104152,274159,280249,274128,274150,104975,105269,287340,105263,105268,60397,56518,441,55766,56152,55959,60255,57743,62146,103525,275370,274552,274054,274063,274060,274099,274452,274110,274653,274461,274081,103981,274448,274853,103175,274664,274932,274879,274102,274551,97036,103695,274572,274505,103378,103949,280937,274511,275083,103517,103658,280233,274574,274117,274864,274575,274589,100725,274577,274762,274583,274611,274600,274606,274610,103824,274677,274683,274684,274689,59334,274731,274761,280226,274727,274775,274728,275372,274717,102799,274661,274711,274652,274658,275065,280309,274712,274688,274718,274721,275316,275152,274777,288171,102295,274794,274756,274754,288112,274759,274772,274803,274779,274790,274791,274793,288596,274817,274826,274950,274891,274850,274856,274994,274971,280306,274917,274912,275039,275151,274947,275119,274955,274956,275026,275024,287274,275192,275046,275051,275085,104163,275064,275107,275078,275082,275122,275095,280513,275114,275132,275178,275181,280240,275206,287863,275203,275196,275213,275230,275231,275232,280543,275324,275277,275220,280337,275281,275296,275313,275330,275237,275346,280291,288114,288052,280206,280210,280214,280277,280350,280260,280261,280267,280288,288706,280282,280294,280290,288657,280297,103542,280313,280326,280330,280341,280339,286464,280351,104964,280519,280521,286462,288599,280314,286501,280550,280548,104048,103178,103871,103878,57616,104335,103432,103265,104426,103855,103590,103642,103707,103817,103796,103550,103214,104469,104111,103686,103956,104096,104182,103852,103643,103604,103571,103792,103859,103759,103685,103892,103194,103577,288506,103380,99994,280307,103362,104209,103808,103273,103950,103103,103193,104028,103344,286679,103903,104362,103387,275228,104433,104081,103948,275135,59953,275315,103936,103938,104387,103946,103873,274592,103483,103944,103345,275317,103293,103417,104376,103932,103934,103382,103939,103982,103940,104305,274536,104377,104052,103532,102130,287974,103906,103910,104336,104099,103933,274576,288274,104428,103869,104931,104406,103547,104079,103526,283901,280931,103927,104331,103998,104032,104364,103891,103761,104009,275225,103213,274972,274090,104092,275105,274169,103851,104289,274959,275227,104089,274472,104294,101034,104100,103902,103881,104241,103275,103914,274672,275322,104218,104020,104415,104088,103377,104199,275249,288036,275124,103830,103057,103827,286447,288209,288280,275248,61088,288608,274462,274555,103741,275293,288058,280216,103845,101135,275241,275118,103843,275314,103842,286645,103829,274911,103848,104259,275244,287364,104279,288435,104266,275121,280295,104448,104928,286440,274495,274834,274805,275072,275202,288601,104075,104334,275385,104143,104077,103569,104044,104308,104065,104345,102697,280312,103886,286707,104121,103615,104288,280227,104167,104086,442,103942,104310,286565,103361,104087,103907,104274,104105,103885,104045,104062,104316,104309,274513,104955,103510,288189,280318,104206,104054,104231,104046,104268,104118,104187,101499,104120,104173,104322,104481,104169,104061,287365,103394,104375,103485,104186,104051,104106,105001,41775,285002,104940,104394,103368,104398,288597,103460,103461,103409,104165,103269,103267,103166,105270,280327,286530,274491,105013,105026,104276,104113,104117,275357,288607,104103,104172,104202,104211,104245,104228,104227,275055,104257,104393,104419,104443,104437,104977,103423,103426,103401,104269,103272,103421,104049,103452,287855,103144,103259,103094,103112,103090,103038,275012,274596,104968,274121,274094,280266,60204,103442,280285,104246,104286,287841,104250,275117,104270,274466,104249,103400,275056,104976,104216,103705,104217,103924,104280,274118,104396,274454,56202,100015,103941,104311,56019,274510,104261,274453,274456,274736,103350,103699,104979,274519,288482,103095,274520,280250,103690,103076,274521,274545,103271,275236,288616,103498,103149,57671,103150,274709,103354,103697,281805,274522,104988,103200,102998,103047,103257,104927,103197,103552,103046,50742,103169,288520,103251,100681,274471,103499,103130,103254,283999,104911,288609,274892,57678,57681,103117,275180,274607,103101,274823,288653,280336,103767,92639,288688,274808,288655,274642,288175,103753,104370,3642,288687,103049,280238,104029,274726,103646,103756,103156,275011,275338,287857,280524,288656,103754,103676,274880,274440,280322,103911,104353,103331,103454,288654,103283,275355,104317,54653,103250,288595,274666,103185,104031,103274,103210,104356,104359,274724,103227,103261,103248,103084,280319,274868,274426,280352,103698,103763,285635,275223,104158,280353,103072,103239,103242,103358,274147,103062,104951,103438,103420,103479,103240,280332,103184,103192,104954,103218,103922,103478,104922,103100,280338,103243,274801,103241,103226,103225,103217,103450,274797,274089,103060,102144,103221,103220,103167,104166,103155,104903,103154,103098,103113,103131,103082,103074,104904,274738,104056,103106,280333,104949,274542,274428,280224,274429,287367,288694,274460,274540,282376,104990,280243,275364,104175,275384,275383,280223,287842,288117,280320,280525,280544,103591,103439,284526,280287,275347,287975,275015,280220,103556,274931,275158,275108,288190,288431,280281,287282,101379,286633,103559,275209,286483,274940,275211,103557,275007,280334,103562,275195,274938,287994,274977,275097,280209,59364,280273,280237,274997,274996,275031,288712,288518,274983,274991,275014,275070,103560,280208,275030,287995,275160,105003,282372,275098,274851,274871,275161,274969,288522,275348,287291,286615,280292,56184,280537,280280,274807,280241,275149,274937,275018,275029,275096,275243,275345,280539,280236,274852,274870,275020,286664,275362,275208,274857,280335,280510,288592,280331,274858,274859,274844,274734,280542,274833,288277,275278,286479,286505,286576,275115,55994,286524,286502,286523,104337,274517,286522,274088,285984,104923,275350,274584,274573,286617,61678,286666,275323,286743,286477,286579,286533,286711,274671,286746,274939,103507,61443,103687,104465,103324,103472,288606,275197,275112,286537,287277,287314,287329,287337,104901,287362,287847,287872,287561,287366,288519,288275,288693,57691,104014,288056,280265,287861,288458,286663,288658,288113,286585,288054,288035,288061,104388,103246,288158,288120,288159,288505,288455,288191,288278,288429,288600,288485,288483,288456,288457,288480,288484,288521,288516,288507,288517,288540,288523,288593,288544,288603,288602,288604,288605,288610,288611,288613,288666,288614,288615,288539,288665,288691,288667,288689,288711)
)
SET extranet_kayttajan_lisatiedot.selite = 'JAKELU'
WHERE extranet_kayttajan_lisatiedot.yhtio = 'artr' AND extranet_kayttajan_lisatiedot.laji = 'VARAOSASELAIN_TILAUS' AND extranet_kayttajan_lisatiedot.selite = 'KORJAAMO';

UPDATE asiakas SET ryhma = '31' WHERE yhtio = 'atarv' AND ryhma in ('31M','31T');
UPDATE asiakas SET yhtio = 'artr' WHERE yhtio = 'atarv';
# Siirretään kaikki asiakkaan_avainsanat paitsi jos laji OHJAUSMERKKI ja avainsanassa Oma Kuljetus tai avainsana = OLETUS.
UPDATE asiakkaan_avainsanat SET yhtio = 'artr' WHERE yhtio = 'atarv' AND ((avainsana not like 'Oma kuljetus%' AND avainsana != 'OLETUS' AND laji = 'OHJAUSMERKKI') or (laji != 'OHJAUSMERKKI'));
UPDATE asiakkaan_avainsanat SET yhtio = 'artr' WHERE yhtio = 'atarv';
UPDATE yhteyshenkilo SET yhtio = 'artr' WHERE yhtio = 'atarv' AND tyyppi = 'A';
UPDATE kalenteri SET yhtio = 'artr' WHERE yhtio = 'atarv';
UPDATE liitetiedostot SET yhtio = 'artr' WHERE yhtio = 'atarv' AND liitos in ('asiakas','kalenteri','memo', 'Yllapito');

# ei siirretä asiakas_ryhmia jotka mainittu, eikä myöskään ryhmaan tehtyjä sidoksia, mikäli ei ole 6 merkkiä numeroiden kanssa (Örumin aleryhmat)
UPDATE asiakasalennus SET yhtio = 'artr' WHERE yhtio = 'atarv' AND asiakas_ryhma not in ('31', '32','11','11V','11R','31J','31M','31T') and ((ryhma REGEXP '^-?[0-9]+$' and length(ryhma) = 6) or tuoteno != '');
UPDATE asiakashinta SET yhtio = 'artr' WHERE yhtio = 'atarv';

# Kassalippaan kustannuspaikka koitetaan päivittää tässä samalla kustannuspaikan koodin avulla oikeaksi, jos vaan löytyy sopiva toisesta yhtiöstä samalla koodilla.
UPDATE kassalipas
LEFT JOIN kustannuspaikka m1 on (m1.yhtio = kassalipas.yhtio and m1.tunnus = kassalipas.kustp)
LEFT JOIN kustannuspaikka m2 on (m2.yhtio = 'artr' and m1.koodi = m2.koodi)
SET kassalipas.kustp = if(m2.tunnus is null, 0, m2.tunnus), kassalipas.yhtio = 'artr'
WHERE kassalipas.yhtio = 'atarv';

# Tuotteen tapahtumat semmoisenaan, mutta katotaan et rivitunnus ei pointtaa tilausriviin (jälkilaskennan vuoksi, eli nää on konvertoituja ns historiatietoja)
UPDATE tapahtuma
SET tapahtuma.yhtio = 'artr', tapahtuma.rivitunnus = if(tapahtuma.rivitunnus < 0, tapahtuma.rivitunnus, tapahtuma.rivitunnus * -1)
WHERE tapahtuma.yhtio = 'atarv';

# Myhis rivit
UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND tilausrivi.uusiotunnus = lasku.tunnus AND lasku.tila = 'U' AND lasku.alatila = 'X')
JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
SET tilausrivi.yhtio = 'artr', tilausrivin_lisatiedot.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'L'
AND tilausrivi.var not in ('P');

UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila = 'L' AND lasku.alatila = 'X')
JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
SET tilausrivi.yhtio = 'artr', tilausrivin_lisatiedot.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'L'
AND tilausrivi.uusiotunnus = 0
AND tilausrivi.var not in ('P');

UPDATE tilausrivi
JOIN lasku ON
(lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila = 'N' AND (lasku.alatila = '' OR lasku.alatila = 'U') AND (lasku.tilaustyyppi != '9' OR (lasku.tilaustyyppi = '9' AND lasku.liitostunnus not in ('281057', '287770'))))
JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
SET tilausrivi.yhtio = 'artr', tilausrivin_lisatiedot.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'L'
AND tilausrivi.var not in ('P');

UPDATE tilausrivi
JOIN lasku ON
(lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila in ('C', 'T') AND lasku.alatila in ('', 'A'))
JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
SET tilausrivi.yhtio = 'artr', tilausrivin_lisatiedot.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'L'
AND tilausrivi.var not in ('P');

# Ilman tilrivin lisatietoja:
UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND tilausrivi.uusiotunnus = lasku.tunnus AND lasku.tila = 'U' AND lasku.alatila = 'X')
SET tilausrivi.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'L'
AND tilausrivi.var not in ('P');

UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila = 'L' AND lasku.alatila = 'X')
SET tilausrivi.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'L'
AND tilausrivi.uusiotunnus = 0
AND tilausrivi.var not in ('P');

UPDATE tilausrivi
JOIN lasku ON
(lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila = 'N' AND (lasku.alatila = '' OR lasku.alatila = 'U') AND (lasku.tilaustyyppi != '9' OR (lasku.tilaustyyppi = '9' AND lasku.liitostunnus not in ('281057', '287770'))))
SET tilausrivi.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'L'
AND tilausrivi.var not in ('P');

UPDATE tilausrivi
JOIN lasku ON
(lasku.yhtio = 'artr' AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila in ('C', 'T') AND lasku.alatila in ('', 'A'))
SET tilausrivi.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'L'
AND tilausrivi.var not in ('P');

# Ostohis rivit:
UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.otunnus AND lasku.tila = 'O' AND lasku.alatila = '' AND liitostunnus != 27371)
JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
SET tilausrivi.yhtio = 'artr', tilausrivin_lisatiedot.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'O'
AND tilausrivi.uusiotunnus = 0;

UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.otunnus AND lasku.tila = 'O' AND lasku.alatila != '' AND liitostunnus != 27371)
JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
SET tilausrivi.yhtio = 'artr', tilausrivin_lisatiedot.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'O'
AND tilausrivi.uusiotunnus = 0;

UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.uusiotunnus AND lasku.tila = 'K' AND lasku.alatila in ('X', '') AND vanhatunnus = 0)
JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
SET tilausrivi.yhtio = 'artr', tilausrivin_lisatiedot.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'O'
AND tilausrivi.uusiotunnus != 0
AND tilausrivi.laskutettuaika != '0000-00-00';

# Ilman lisätietoja:
UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.otunnus AND lasku.tila = 'O' AND lasku.alatila = '' AND liitostunnus != 27371)
SET tilausrivi.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'O'
AND tilausrivi.uusiotunnus = 0;

UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.otunnus AND lasku.tila = 'O' AND lasku.alatila != '' AND liitostunnus != 27371)
SET tilausrivi.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'O'
AND tilausrivi.uusiotunnus = 0;

UPDATE tilausrivi
JOIN lasku ON (lasku.yhtio = 'artr' AND lasku.tunnus = tilausrivi.uusiotunnus AND lasku.tila = 'K' AND lasku.alatila in ('X', '') AND vanhatunnus = 0)
SET tilausrivi.yhtio = 'artr'
WHERE tilausrivi.yhtio = 'atarv'
AND tilausrivi.tyyppi = 'O'
AND tilausrivi.uusiotunnus != 0
AND tilausrivi.laskutettuaika != '0000-00-00';
