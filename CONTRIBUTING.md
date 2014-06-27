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
* Käytä funktioiden ja muuttujien nimissä `snake_case`.
* Käytä konstattien nimissä `SCREAMING_SNAKE_CASE`.
* Kirjoita aina kaikki PHP [avainsanat](http://php.net/manual/en/reserved.keywords.php) pienillä kirjaimilla. Esim. `and`, `or`, `if`, `while`, jne..
* Kirjoita PHP konstantit `true`, `false` ja `null` pienillä kirjaimilla.
* Käytä välilyöntejä operaattorien, pilkkujen, kaksoispisteiden sekä puolipisteiden ympärillä.
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

```php
// Sallitaan vain pienet kirjaimet ja numerot, koska
// rajapinta hajoaa isoista kirjaimista sekä erikoismerkeistä.
$value = preg_replace("/[^a-z0-9]/i", "", $string);
```
