# Kuinka avustan

* Tee omasta ominaisuudestasi uusi git feature branch Pupesoftin `master` branchistä.
* Nimeä oma branchisi `omanimi/ominaisuus_lyhytkuvaus`.
* Branchin nimessä sallitaan vain kirjaimet `a-z`, numerot `0-9` sekä merkit `_` ja `/`.
* Kun ominaisuutesi on valmis, tee branchistäsi Pull Request Pupesoft `master` branchiin.

# Pull Request

* Pidä huoli, että ominaisuutesi branch on ajantasalla kun teet Pull Requestin.
* Pull Requestin otsikon tulee selkeästi kuvata muutos. Otsikon maksimipituus 50 merkkiä.
* Pull Requestin kommentissa tulee kuvata ominaisuus tarkemmin. Kommenttiriveillä ei ole maksimipituutta.
* Tarvittaessa siisti branchisi commitit ennen Pull Requestiä `git rebase -i master`.

# Commit

* Committoi kehitysvaiheessa muutoksesi mahdollisimman usein, pieninä kokonaisuuksina, omaan branchiisi.
* Committoi asiakokonaisuuksia, kuvaa tehty muutos commit viestissä selkeästi.
* Commitin ensimmäisen rivin maksimipituus 50, jossa kuvaataan asiakokonaisuus.
* Commitin seuraavilla riveillä ei ole maksimipituutta, vaan niillä voi kuvata vapaasti ominaisuuden tarkemmin.

# Koodityyli

* Käytä soft-tabeja, kahden välilyönnin sisennys.
* Pidä rivipituus alle 100 merkkiä.
* Varmista, että rivin lopussa ei ole välilyöntejä.
* Varmista, että tiedoston lopussa on yksi tyhjä rivi, eikä sulkevaa PHP tagia `?>`.
* Älä avaa/sulje PHP tageja kesken tiedoston `<?php` / `?>`.
* Käytä funktioiden ja muuttujien nimissä `snake_case`.
* Käytä konstattien nimissä `SCREAMING_SNAKE_CASE`.
* Kirjoita PHP [avainsanat](http://php.net/manual/en/reserved.keywords.php) pienillä kirjaimilla. Esim. `and`, `or`, `if`, `while`, jne..
* Kirjoita PHP konstantit `true`, `false` ja `null` pienillä kirjaimilla.
* Käytä kommentoimiseen `//` notaatiota.
* Käytä välilyöntejä operaattorien, pisteiden sekä kaksoispisteiden ympärillä.
* Käytä välilöyntiä pilkkujen jälkeen.
* Käytä välilyöntiä PHP [kontrolli](http://www.php.net/manual/en/language.control-structures.php) avainsanojen jälkeen. Esim. `if`, `while`, `for`, `return`, jne..
* Käytä välilyöntiä ennen avaavaa aaltosulkua `{`.
* Kirjoita koodiblockin avaava aaltosulku `{` samalle riville.
* Kirjoita `else` sekä sulkeva aaltosulku `}` omalle riville.

```php
$total_sum = 1 + 2;

const MAX_VALUE = 99;

$value = $total_sum < 2 ? true : false;

if ($value === true) {
  echo "red", "blue";
}
else {
  echo "blue", "red";
}
```

* Ei välilyöntejä funktionimen ja avaavan kaarisulun `(` väliin.
* Ei välilyöntejä avaavien kaari-/hakasulkujen `(`, `[` jälkeen eikä ennen sulkevia kaari-/hakasulkuja `]`, `)`.

```php
strlen("internationalization");
var_dump($value);
$value[1] = "red";
```

* Älä kirjoita `if` lausekkeita ilman aaltosulkuja.
* Älä kirjoita `if` lausekkeita yhdelle riville.

```php
// bad
if ($value === true) echo "brown";

// bad(ish)
if ($value === true) { echo "brown"; }

// good
if ($value === true) {
  echo "brown";
}
```

* Käytä ternary operatoria vain yksinkertaisissa tapauksissa.

```php
// good
$value = 1 < 2 ? true : false;

// bad
$value = 1 < 2 ? (3 < 4) ? true : "kissa" : false;
```

* Käytä mielummin string interpolaatiota kun konkatenaatiota.

```php
// bad
$email_with_name = $name . " <" . $email . ">";

// good
$email_with_name = "${name} <${email}>";

// bad
$output = "date is ".date("Ymd");

// bad(ish)
$output = "date is " . date("Ymd");

// good
$_today = date("Ymd");
$output = "date is {$_today}";
```

* Sisennä switch lauseessa case samalle tasolle switchin kanssa.

```php
switch ($value) {
case 0:
  echo "zero";
  break;
case 1:
  echo "one";
  break;
default:
  echo "two";
}
```

* Käytä tyhjiä rivejä erottelemaan koodi loogisiin kappaleisiin.

```php
function do_stuff($params) {
  $data = check_params($params);

  $data = manipulate($data);

  return $data["result"];
}
```

* Kommentoi **miksi** logiikka suoritetaan.
* Kommentoi **mitä** monimutkainen logiikka tekee.
* Käytä välilyöntiä `//` jälkeen.

```php
// Sallitaan vain pienet kirjaimet ja numerot, koska
// rajapinta hajoaa isoista kirjaimista sekä erikoismerkeistä.
$value = preg_replace("/[^a-z0-9]/i", "", $string);
```

* Kirjoita SQL lausekkeiden [avainsanat](http://dev.mysql.com/doc/mysql/en/sql-syntax.html) isoilla kirjaimilla.
* Kirjoita SQL [funktiot](http://dev.mysql.com/doc/mysql/en/functions.html) pienillä kirjaimilla.
* Kirjoita SQL kyselyissä yksi avainsana/ehto/arvo per rivi.
* Kirjoita SELECT -lausekkeissa aluksi groupattavat kentät, sen jälkeen group funktiot.
* Käytä AS -avainsanaa jos haluat antaa kentälle/tietokantataululle aliaksen.
* Sisennä joinien ehdot kahdella välilyönnillä.
* Joineissa avaavat ja sulkevat kaarisulut samalle riville.
* Käytä välilyöntejä operaattorien ympärillä.
* Käytä välilyöntiä pilkun jälkeen.
* Älä käytä välilyöntiä funktion ja avaavan kaarisulun välissä.
* Älä käytä välilyöntiä avaavan kaarisulun jälkeen `(` eikä ennen sulkevaa kaarisulkua `)`.

```sql
SELECT lasku.laskunro,
concat(asiakas.maa, asiakas.ryhma) AS maaryhma,
count(*) AS kpl
FROM lasku
INNER JOIN asiakas ON (asiakas.id = lasku.asiakas_id
  AND asiakas.nimi LIKE '%abc%'
  AND asiakas.ryhma > 10)
WHERE lasku.tunnus = 123
AND lasku.tila = 'A'
GROUP BY lasku.laskunro,
maaryhma
ORDER BY kpl
```

```sql
UPDATE asiakas SET
nimi = 'Abc',
myyjanro = 12
WHERE yhtio = 'demo'
AND tunnus = 123
```

```sql
INSERT INTO asiakas SET
nimi = 'Abc',
myyjanro = 12,
yhtio = 'demo'
```

# Testaus

Pupesoftissa ei ole käytössä testikehystä ([vielä](https://github.com/devlab-oy/pupenext)). Testaus tulee tehdä manuaalisesti.

* Aja tiedosto, johon teit muutokset. Varmista, että testaat koodia jonka itse kirjoitit.
* Tarkista missä ko. tiedostoa käytetään, etsimällä tiedostonimellä kaikista Pupesoft projekin tiedostoista. Varmista, että et muuta ohjelman vaatimuksia.
