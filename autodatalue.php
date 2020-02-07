<?php
/**
 * Autodatan sisäänlukuscripti
 *
 * Osaa lukea määräaikaishuollot (service guide), niiden sisällöt sekä
 * huoltojen kestot (repair times)
 *
 */


// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$root_path = '/var/www/html/pupesoft';

require $root_path.'/inc/connect.inc';
require $root_path.'/inc/functions.inc';

// Logitetaan ajo
cron_log();

// kaikki virheilmotukset
ini_set('error_reporting', E_ALL | E_STRICT);

// emuloidaan kukarow
$kukarow = array(
  'kuka'  => 'autodata',
  'yhtio' => 'artr',
);

// kaikki polut
$base = '/home/henri/autodata_data/';

$path_ss = array(
  'path'       => $base,
  'links'      => $base . 'MIDANDLINKS/',
  'guide'      => $base . 'SERVICE_GUIDE/',
  'operations' => $base . 'SCRIPTS/FIN/sg_fin.xml',
  'parts'      => $base . 'SCRIPTS/FIN/ServParts_fin.xml',
  'variants'   => $base . 'MISCELLANEOUS/FIN/varia_fin.xml',
  'string'     => $base . 'MISCELLANEOUS/FIN/string_fin.xml',
  'oildiesel'  => $base . 'TECHNICAL_DATA/DIESEL/',
  'oilpetrol'  => $base . 'TECHNICAL_DATA/PETROL/',
  'notes'      => $base . 'SERVICE_GUIDE/NOTES/FIN/sgnotes_fin.xml',
);

$path_rt = array(
  'path'    => $base,
  'links'   => $base . 'MIDANDLINKS/',
  'times'   => $base . 'REPAIR_TIMES/DATA/',
  'text'    => $base . 'SCRIPTS/FIN/rt_fin.xml',
  'opcodes' => $base . 'SCRIPTS/FIN/opcodes_fin.xml',
  'incjobs' => $base . 'RTINCJOB/',
);

$path_general = array(
  'middata' => $base.'middata_V45.txt',
  'tdlinks' => $base.'Ktyp_links_29_05_2014.txt',
  'technical_petrol' => $base.'SCRIPTS/FIN/pet_fin.xml',
  'technical_diesel' => $base.'SCRIPTS/FIN/die_fin.xml'
);

$errors = array();

// clear screen
echo chr(27)."[H".chr(27)."[2J";


// poistetaan kaikki vanhat
echo "Poistetaan vanhat autodata tiedot... \n";

remove_all_autodata();

// määräaikaishuollot
service_schedule();

// huoltoajat
repair_times();

// stringit
echo "Parseroidaan huoltotekstit... \n";
parse_strings();

// diesel
echo "Parseroidaan oil diesel... \n";
parse_oil_diesel();

// petrol
echo "Parseroidaan oil petrol... \n";
parse_oil_petrol();

// notes
echo "Parseroidaan lisahuollot... \n";
parse_notes();

// variantit
echo "Parseroidaan varianttien tiedot... \n";
parse_variants();

echo "Luetaan automallit... \n";
middata($path_general['middata']);

echo "Luetaan tecdoc -linkitykset... \n";
midtecdoc($path_general['tdlinks']);

echo "Luetaan teknisen datan otsikot (petrol)...\n";
td_strings($path_general['technical_petrol'], 'P');

echo "Luetaan teknisen datan otsikot (diesel)...\n";
td_strings($path_general['technical_diesel'], 'D');

echo "\n\n";
foreach ($errors as $error) {
  echo "$error";
}
echo "\n\n";

function omat_tyot() {
  global $kukarow;

  $query = "INSERT INTO autodata_sg_interval SET
            yhtio   = '{$kukarow['yhtio']}',
            link    = 'jakopaa',
            seq     = '0',
            kms     = '',
            months  = '',
            stdtime = '1.00',
            addserv = '127',
            dispstr = '0',
            tunnus  = '-1'";
  $insert_result = mysql_query($query) or pupe_error($query);

  $query = "INSERT INTO autodata_sg_string SET
            yhtio  = '{$kukarow['yhtio']}',
            id     = '999',
            text   = 'Jakopään työt',
            tunnus = '-1'";
  $insert_result = mysql_query($query) or pupe_error($query);

  $query = "INSERT INTO autodata_sg_guide SET
            yhtio   = '{$kukarow['yhtio']}',
            link    = 'jakopaa',
            seq     = '0',
            line    = '-1',
            noteref = '0',
            tunnus  = '-1'";
  $insert_result = mysql_query($query) or pupe_error($query);

  $query = "INSERT INTO autodata_sg_operation SET
            yhtio     = '{$kukarow['yhtio']}',
            line      = '-1',
            op_ref    = '99.9999',
            op_text   = 'Jakopään työt',
            part_text = '',
            tunnus='-1'";
  $insert_result = mysql_query($query) or pupe_error($query);

  $query = "INSERT INTO autodata_sg_interval SET
            yhtio   = '{$kukarow['yhtio']}',
            link    = 'dummymaaraaika',
            seq     = '0',
            kms     = '',
            months  = '',
            stdtime = '1.00',
            addserv = '126',
            dispstr = '0',
            tunnus  = '-2'";
  $insert_result = mysql_query($query) or pupe_error($query);

  $query = "INSERT INTO autodata_sg_string SET
            yhtio  = '{$kukarow['yhtio']}',
            id     = '998',
            text   = 'Oma Määräaikaishuolto',
            tunnus = '-2'";
  $insert_result = mysql_query($query) or pupe_error($query);

  $query = "INSERT INTO autodata_sg_guide SET
            yhtio   = '{$kukarow['yhtio']}',
            link    = 'dummymaaraaika',
            seq     = '0',
            line    = '-2',
            noteref = '0',
            tunnus  = '-2'";
  $insert_result = mysql_query($query) or pupe_error($query);

  $query = "INSERT INTO autodata_sg_operation SET
            yhtio     = '{$kukarow['yhtio']}',
            line      = '-2',
            op_ref    = '99.9998',
            op_text   = 'Määräaikaishuolto',
            part_text = '',
            tunnus    = '-2'";
  $insert_result = mysql_query($query) or pupe_error($query);


}


function check_all_paths($rypas) {

  $boob = 0;
  foreach ($rypas as $path => $value) {
    if (!is_dir($value) and !is_file($value)) {
      echo "Virheellinen polku/tiedosto $value\n";
      $boob++;
    }
  }

  if ($boob > 0) exit;

}


function service_schedule() {
  global $path_ss;

  check_all_paths($path_ss);

  // parseroidaan linkit
  echo "Parseroidaan SS linkkitiedostot... \n";
  parse_links($path_ss['links']);

  parse_service_operations();

  // parseroidaan service guide
  echo "Parseroidaan service guide... \n";
  parse_services($path_ss['guide']);

}


function repair_times() {
  global $path_rt;

  check_all_paths($path_rt);

  // luetaan korjauksien kuvaukset
  echo "Parseroidaan korjauksien kuvaukset... \n";
  parse_repair_texts();

  echo "Parseroidaan korjausajat... \n";
  parse_repair_times();

}


function remove_all_autodata() {

  $qu = "TRUNCATE autodata";
  pupe_query($qu);

  $qu = "TRUNCATE yhteensopivuus_autodata";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_link";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_rt_incjob";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_rt_text";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_rt_time";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_sg_guide";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_sg_interval";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_sg_note";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_sg_operation";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_sg_string";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_td";
  pupe_query($qu);

  $qu = "TRUNCATE autodata_td_string";
  pupe_query($qu);
}


// Parseroi linkki filet
// Link fileissä on linkit eri tyyppien datafileihin
function parse_links($path) {
  global $kukarow, $errors;

  $allowed_link_types = array('TD', 'RT' , 'SG', 'SGFIN');

  // avataan kansio
  $dir = dir($path);
  $rows = count(scandir($path));
  $row  = 0;

  while (($file = $dir->read()) !== false) {

    // progressbar
    $row++;
    progress_bar($row, $rows);

    if (in_array($file, array('..', '.')) or (strpos($file, '_Ranges.xml') !== false) or (strpos($file, 'Years.XML') !== false)) {
      // skipataan tämä file
      continue;
    }

    if (! is_readable($path . '/' . $file)) {
      $errors[] = "Could not read file $file\n";
      continue;
    }

    // ladataan file SimpleXml objectiksi
    if (! $obj = xml_load_file($path . '/' . $file)) {
      continue;
    }

    foreach ($obj as $mid) {

      $insert_data = array();

      $data = array(
        'yhtio' => $kukarow['yhtio'],
        'mid'  => (string) $mid->attributes()->ID, // autodata mid
        'type' => null,
        'link' => null,
        'id'   => null,
      );

      foreach ($mid->Category as $cat) {

        // type, esim. SG, TD, RT
        $data['type'] = (string) $cat->attributes()->ID;

        // skip not allowed link types
        if (!in_array($data['type'], $allowed_link_types)) {
          continue;
        }

        foreach ($cat->Link as $link) {
          // saadaan vielä itse linkki
          $data['link'] = (string) $link;
          $data['id']   = (int) $link->attributes()->ID;

          $insert_data[$data['type']][] = $data;
        }
      }

      // if SGFIN links exists, forget normal SG links and use SGFIN, but first change SGFIN->SG
      if (isset($insert_data['SGFIN']) && isset($insert_data['SG'])) {
        unset($insert_data['SG']);
        $insert_data['SG'] = $insert_data['SGFIN'];
        unset($insert_data['SGFIN']);
      }


      foreach ($insert_data as $type_block) {
        foreach ($type_block as $data_block) {
          $insert_data['query_row'][] = "('".implode("', '", $data_block)."')";
        }
      }

      if (isset($insert_data['query_row'])) {
        $insert_query = "INSERT INTO autodata_link (yhtio, mid, type, link, id)
                         VALUES ".implode(",", $insert_data['query_row']);

        mysql_query($insert_query) or pupe_error($insert_query);
      }

    }

  }

  echo "\n";

}


// Hakee kaikki SG linkit ja käy läpi ne
function parse_services() {

  $query = "SELECT distinct link FROM autodata_link where type = 'SG' and link != 'none' and link != ''";
  $res = mysql_query($query) or pupe_error($query);

  $rows = mysql_num_rows($res);
  $row = 0;

  // nopeutetaan inserttejä lockilla
  $query = "LOCK TABLES autodata_sg_interval WRITE, autodata_sg_guide WRITE";
  $lock = mysql_query($query) or pupe_error($query);

  while ($link = mysql_fetch_array($res)) {

    // parseroidaan file
    parse_service_intervals($link['link']);

    // parseroidaan operaatiot
    parse_service_guide($link['link']);

    // progressbar
    $row++;
    progress_bar($row, $rows);

  }

  $query = "UNLOCK TABLES";
  $lock = mysql_query($query) or pupe_error($query);

  echo "\n";
}


function parse_service_operations() {
  global $kukarow, $path_ss, $errors;

  // haetaan osien nimet
  echo "Parseroidaan osien nimet...\n";
  $parts = parse_service_parts();

  if (! $sg_data_xml = xml_load_file($path_ss['operations'])) {
    $errors[] = "Operations file not found {$path_ss['operations']}\n";
    die();
  }

  echo "Parseroidaan operaatioiden tiedot... \n";

  $rows = count($sg_data_xml);
  $row = 0;

  foreach ($sg_data_xml as $line) {

    if (isset($parts[(int) $line->attributes()->no])) {
      $part_text = mysql_real_escape_string($parts[(int) $line->attributes()->no]);
    }
    else {
      $part_text = '';
    }

    // operaation kuvaus
    $op_text = mysql_real_escape_string(utf8_decode((string) $line->op_text));

    // yhden huolto-operaation tiedot
    $data = array(
      'yhtio'     => $kukarow['yhtio'],
      'line'      => (int) $line->attributes()->no,
      'op_ref'    => (string) $line->op_ref,            // yhden operaation "tuotenumero"
      'op_text'   => $op_text,                 // yhden operaation kuvaus
      'part_text' => $part_text,
    );

    $query = "INSERT INTO autodata_sg_operation set
              yhtio     = '{$data['yhtio']}',
              line      = '{$data['line']}',
              op_ref    = '{$data['op_ref']}',
              op_text   = '{$data['op_text']}',
              part_text = '{$data['part_text']}'";

    mysql_query($query) or pupe_error($query);

    // progressbar
    $row++;
    progress_bar($row, $rows);

  }

  echo "\n";

}


function parse_service_parts() {
  global $kukarow, $path_ss, $errors;

  // tehdään operaatioiden tiedoista indexi jossa key
  // on huolto-operaaion numero. Näin saadaan seuraavassa
  // vaiheessa haettua tiedot.
  if (! $sg_data_xml = xml_load_file($path_ss['parts'])) {
    $errors[] = "Parts file not found {$path_ss['parts']}\n";
    die();
  }

  $parts_data = array();

  $rows = count($sg_data_xml);
  $row = 0;

  foreach ($sg_data_xml as $line) {
    settype($line, 'array');

    // yhden operaation kuvaus
    $parts_data[(int) $line['@attributes']['no']] = utf8_decode($line['text']);

    // progressbar
    $row++;
    progress_bar($row, $rows);

  }

  echo "\n";

  return $parts_data;
}


// Parseroi yhden määräaikaishuoltolistan automallille
function parse_service_intervals($link) {
  global $path_ss, $kukarow, $errors;

  if (! $file = find_xml_file($path_ss['guide'] . 'INTERVALS/' . $link)) {
    $errors[] = "Interval file not found ".$path_ss['guide'] . 'INTERVALS/' . $link."\n";
    return;
  }

  if (! $xml = xml_load_file($file)) {
    $errors[] = "Interval file not found $file\n";
    return;
  }

  $insert_data = array();

  $data = array(
    'yhtio'    => $kukarow['yhtio'],
    'link'    => $link,
    'seq'     => null,
    'kms'     => null,
    'months'  => null,
    'stdtime' => null,
    'atstdtime' => null,
    'addserv' => 0,
    'dispstr' => 0,
    'dispstrmths' => 0,
    'dispstrkms' => 0
  );

  // käydään kaikki huollot läpi tälle mallille

  foreach ($xml as $service) {

    $data['seq']     = (string) $service->Seq;
    $data['kms']     = (string) $service->Kms;
    $data['months']  = (string) $service->Months;
    $data['stdtime'] = (string) $service->StdTime;
    $data['atstdtime'] = (string) $service->AtStdTime;
    $data['addserv'] = (int) $service->attributes()->addServ;
    $data['dispstr'] = (int) $service->attributes()->dispStr;
    $data['dispstrmths'] = (int) $service->attributes()->dispStrMths;
    $data['dispstrkms'] = (int) $service->attributes()->dispStrKms;

    $insert_data[] = "('".implode("', '", $data)."')";
  }

  if (count($insert_data) > 0) {
    $insert_query = "INSERT INTO autodata_sg_interval (yhtio, link, seq, kms, months, stdtime,
                     atstdtime, addserv, dispstr, dispstrmths, dispstrkms)
                     VALUES ".implode(", ", $insert_data);

    mysql_query($insert_query) or pupe_error($insert_query);
  }

}


// Parseroi määräaikaishuoltojen vaiheet yhdelle mallit
function parse_service_guide($link) {
  global $path_ss, $kukarow, $errors;

  // kaikki operaatiot yhdelle linkille
  //$file = $path_service_guide . 'OPERATIONS/' . $link . '.xml';
  if (! $file = find_xml_file($path_ss['guide'] . 'OPERATIONS/' . $link)) {
    $errors[] = "Operations file not found ".$path_ss['guide'] . 'OPERATIONS/' . $link."\n";
    return;
  }

  if (! $xml = xml_load_file($file)) {
    $errors[] = "Operations XML load failed $file\n";
    return;
  }

  // käydään kaikki huollot läpi tälle mallille
  foreach ($xml as $operation) {

    $insert_data = array();

    $data = array(
      'yhtio'   => $kukarow['yhtio'],
      'link'    => $link,                                   // autodata linkki oikeeseen fileen
      'seq'     => (string) $operation->attributes()->Seq,  // huollon numero (tyyppi), kertoo mitä huoltoon kuuluu
      'line'    => null,                                    // huollon yhden operaation tunnus
      'noteref' => null,                                    // huollon lisätyön tunnus
    );

    // käydään kaikki operaation vaiheet läpi
    // ja otetaan operaation tiedot toisesta filestä
    foreach ($operation as $line) {

      $data['line']    = (string) $line->attributes()->ID;
      $data['noteref'] = (string) $line->NoteRef;

      $insert_data[] = "('".implode("', '", $data)."')";

    }

    if (count($insert_data) > 0) {
      $insert_query = "INSERT INTO autodata_sg_guide (yhtio, link, seq, line, noteref) VALUES ".implode(", ", $insert_data);
      mysql_query($insert_query) or pupe_error($insert_query);
    }

  }
}


// Parseroi kaikki korjauksien kuvaukset
function parse_repair_texts() {
  global $path_rt, $kukarow, $errors;

  if (! $xml = xml_load_file($path_rt['text'])) {
    $errors[] = "Repair text file not found {$path_rt['text']}\n";
    die();
  }

  $opcodes = parse_opcodes();

  $rows = count($xml);
  $row = 0;

  foreach ($xml as $line) {

    // jos ID on 1-7 niin ei tehdä mitään
    if ((int) $line->attributes()->ID <= 7) {
      continue;
    }

    if (array_key_exists((string) $line->TEXTRIGHT, $opcodes)) {
      $right = utf8_decode($opcodes[(string) $line->TEXTRIGHT]);
    }
    else {
      $right = '';
    }

    // jos on esim A1.12345 niin otetaan se
    $ryhma_tunnus = preg_match("/([A-Z][0-9]{1,2}.[0-9]{4})/", (string) $line->TEXTLEFT, $matches);

    $ref = '';
    if ($ryhma_tunnus) {
      // löytyi ryhmä *ja* ryhmän tunnus
      $ref = $matches[0];
    }
    else {
      // jos on esim A1 otsikko niin otetaan se
      $ryhma = preg_match("/([A-Z][0-9]{1,2})/", (string) $line->TEXTLEFT, $matches);

      if ($ryhma) {
        // saatiin pelkka ryhma
        $ref = $matches[0];
      }
      else {
        // jos pelkka kirjain ekana
        $ryhma = preg_match("/([A-Z]+)\s.*/", (string) $line->TEXTLEFT, $matches);

        if ($ryhma) {
          // saatiin pelkka ryhma
          $ref = $matches[1];
        }
      }
    }

    // poistetaan tekstistä juuri saatu ryhmän tunnus
    $text = ltrim((string) $line->TEXTLEFT, $ref);

    // tyhjät vielä pois
    $text = trim($text);

    // korjaus teksti
    $text = mysql_real_escape_string(utf8_decode($text));

    // yhden huolto-operaation tiedot
    $data = array(
      'yhtio'     => $kukarow['yhtio'],
      'item_id'   => (string) $line->attributes()->ID,
      'text'      => $text,            // korjaus kuvaus
    );

    $query = "INSERT INTO autodata_rt_text set
              yhtio      = '{$data['yhtio']}',
              item_id    = '{$data['item_id']}',
              group_id   = '$ref',
              text       = '{$data['text']}',
              text_right = '$right'";
    mysql_query($query) or pupe_error($query);

    // progressbar
    $row++;
    progress_bar($row, $rows);

  }

  echo "\n";

}


// Lukee opcodes filen ja palauttaa key => value
function parse_opcodes() {
  global $path_rt, $errors;

  if (! $xml = xml_load_file($path_rt['opcodes'])) {
    $errors[] = "Opcodes file not found {$path_rt['opcodes']}\n";
    die();
  }

  $data = array();

  foreach ($xml as $opcode) {
    $data[(string) $opcode->attributes()->ID] = (string) $opcode;
  }

  return $data;
}


// Hakee kaikki RT linkit ja käy läpi ne
function parse_repair_times() {
  global $kukarow, $errors;

  // tyyppi on siis RT
  $query = "SELECT distinct link FROM autodata_link where type = 'RT' and link != 'none' and link != ''";
  $res = mysql_query($query) or pupe_error($query);

  $rows = mysql_num_rows($res);
  $links = array();
  $row = 0;

  // nopeutetaan inserttejä lockilla
  $query = "LOCK TABLES autodata_rt_incjob WRITE, autodata_rt_time WRITE, autodata_link WRITE";
  $lock = mysql_query($query) or pupe_error($query);

  while ($link = mysql_fetch_array($res)) {

    // parseroidaan yksi RT file
    $varia = parse_rt_file($link['link']);

    $links[$link['link']] = $varia;

    // progressbar
    $row++;
    progress_bar($row, $rows);
  }

  echo "\n";

  $rows = count($links);
  $row = 0;

  echo "Parseroidaan variantit... \n";

  // päivitetään kaikki variantit
  foreach ($links as $link => $text) {

    $text = mysql_real_escape_string($text);
    $query = "UPDATE autodata_link
              SET varia = '$text'
              WHERE link = '$link' AND
              type       = 'RT' AND
              yhtio      = '$kukarow[yhtio]'";
    mysql_query($query) or pupe_error($query);

    // progressbar
    $row++;
    progress_bar($row, $rows);

  }

  $query = "UNLOCK TABLES";
  $lock = mysql_query($query) or pupe_error($query);

  echo "\n";

}


function parse_rt_file($link) {
  global $path_rt, $kukarow, $errors;

  $filename = $path_rt['times'] . sprintf("%05s", $link);

  if (!$file = find_xml_file($filename)) {
    $errors[] = "File not found for repair time link $filename\n";
    return;
  }

  if (!$xml = xml_load_file($file)) {
    $errors[] = "Error parsing $file\n";
    return;
  }

  $data = array(
    'yhtio'    => $kukarow['yhtio'],
    'link'    => $link,
    'item_id' => null,
    'time'    => null,
  );

  $insert_data = array();
  $varia = '';

  // kaytaan lapi jokainen repair item
  foreach ($xml->ADB->ITEM as $item) {

    $id = (int) $item->attributes()->ID;

    if ($id > 1 and $id <= 7) {
      $varia .= (string) $item . ' - ';
      continue;
    }
    elseif ($id == 1) {
      // tälle ei tehdä yhtään mitään
      continue;
    }

    $data['item_id'] = (string) $item->attributes()->ID;
    $data['time']    = (float) str_replace(',', '.', (string) $item); // tehdään float

    $insert_data[] = "('".implode("', '", $data)."')";
  }

  if (count($insert_data) > 0) {
    $insert_query = "INSERT INTO autodata_rt_time (yhtio, link, item_id, time)
                     VALUES ".implode(", ", $insert_data);
    mysql_query($insert_query) or pupe_error($insert_query);
  }

  // katsotaan löytyykö incjobs file
  if ($found = find_xml_file($path_rt['incjobs'] . $link)) {
    if (! $xml = xml_load_file($found)) {
      $errors[] = "Error parsing $file\n";
      return;
    }

    foreach ($xml->ADB->MainTask as $task) {
      $data = array(
        'link'     => $link,
        'group_id' => (string) $task->attributes()->ID,
        'incjob'   => null,
      );

      foreach ($task->IncJob as $job) {
        $data['incjob'] = (string) $job;

        $query = "INSERT INTO autodata_rt_incjob set
                  yhtio    = '{$kukarow['yhtio']}',
                  link     = '{$data['link']}',
                  group_id = '{$data['group_id']}',
                  incjob   = '{$data['incjob']}'";

        mysql_query($query) or pupe_error($query);
      }
    }
  }

  return rtrim($varia, ' - ');
}


function parse_variants() {
  global $path_ss, $kukarow, $errors;

  if (! $xml = xml_load_file($path_ss['variants'])) {
    $errors[] = "Variants file not found {$path_ss['text']}\n";
    die();
  }

  $rows = count($xml);
  $row = 0;

  foreach ($xml as $variant) {

    $id   = (int) $variant->attributes()->ID;
    $text = mysql_real_escape_string(utf8_decode((string) $variant));

    $query = "UPDATE autodata_link SET varia = '$text' where id = $id AND yhtio = '{$kukarow['yhtio']}'";
    mysql_query($query) or pupe_error($query);

    // progressbar
    $row++;
    progress_bar($row, $rows);

  }

  echo "\n";
}


function parse_strings() {
  global $path_ss, $kukarow, $errors;

  if (! $xml = xml_load_file($path_ss['string'])) {
    $errors[] = "Strings file not found {$path_ss['text']}\n";
    die();
  }

  $rows = count($xml);
  $row = 0;

  foreach ($xml as $string) {

    $id   = (int) $string->attributes()->ID;
    $text = mysql_real_escape_string(utf8_decode((string) $string));

    $query = "INSERT INTO autodata_sg_string SET
              text  = '$text',
              id    = '$id',
              yhtio = '$kukarow[yhtio]'";
    mysql_query($query) or pupe_error($query);

    // progressbar
    $row++;
    progress_bar($row, $rows);

  }

  echo "\n";
}


function parse_oil_diesel() {
  global $path_ss, $kukarow, $errors;

  // avataan kansio
  $dir = dir($path_ss['oildiesel']);
  $rows = count(scandir($path_ss['oildiesel']));
  $row  = 0;

  while (($file = $dir->read()) !== false) {

    // progressbar
    $row++;
    progress_bar($row, $rows);

    if (! is_readable($path_ss['oildiesel'] . '/' . $file)) {
      $errors[] = "Could not read file $file\n";
      continue;
    }

    if (in_array($file, array('..', '.'))) {
      // skipataan tämä file
      continue;
    }

    // ladataan file SimpleXml objectiksi
    if (! $xml = xml_load_file($path_ss['oildiesel'] . '/' . $file)) {
      continue;
    }

    $adb = (int) $xml->ADB->attributes()->ID;

    $insert_data = array();

    foreach ($xml->ADB->ITEM as $diesel) {

      $item_id = "";
      $item_name = "";
      $noteref = "";

      $item_id = (int) $diesel->attributes()->ID;

      $item_name = mysql_real_escape_string(utf8_decode((string) $diesel[0]));

      if ($diesel->NoteRef != '') {
        $noteref = mysql_real_escape_string(utf8_decode((string) $diesel->NoteRef));
      }

      $insert_data[] = "('artr','$adb','$item_id','$item_name','$noteref')";

    }

    if ($insert_data > 0) {
      $insert_query = "INSERT INTO autodata_td (yhtio, adb, item_id, item_name, noteref)
                       VALUES ".implode(", ", $insert_data);
      mysql_query($insert_query) or pupe_error($insert_query);
    }
  }
  echo "\n";
}


function parse_oil_petrol() {
  global $path_ss, $kukarow, $errors;

  // avataan kansio
  $dir = dir($path_ss['oilpetrol']);
  $rows = count(scandir($path_ss['oilpetrol']));
  $row  = 0;

  while (($file = $dir->read()) !== false) {

    // progressbar
    $row++;
    progress_bar($row, $rows);

    if (! is_readable($path_ss['oilpetrol'] . '/' . $file)) {
      $errors[] = "Could not read file $file\n";
      continue;
    }

    if (in_array($file, array('..', '.'))) {
      // skipataan tämä file
      continue;
    }

    // ladataan file SimpleXml objectiksi
    if (! $xml = xml_load_file($path_ss['oilpetrol'] . '/' . $file)) {
      continue;
    }

    $insert_data = array();

    $adb = (int) $xml->ADB->attributes()->ID;

    foreach ($xml->ADB->ITEM as $diesel) {

      $item_id = "";
      $item_name = "";
      $noteref = "";

      $item_id = (int) $diesel->attributes()->ID;

      $item_name = mysql_real_escape_string(utf8_decode((string) $diesel[0]));

      if ($diesel->NoteRef != '') {
        $noteref = mysql_real_escape_string(utf8_decode((string) $diesel->NoteRef));
      }

      $insert_data[] = "('{$kukarow['yhtio']}', '$adb', '$item_id', '$item_name', '$noteref')";

    }

    if ($insert_data > 0) {
      $insert_query = "INSERT INTO autodata_td (yhtio, adb, item_id, item_name, noteref)
                       VALUES ".implode(",", $insert_data);
      mysql_query($insert_query) or pupe_error($insert_query);
    }
  }
  echo "\n";
}


function parse_notes() {
  global $path_ss, $kukarow, $errors;

  if (! $xml = xml_load_file($path_ss['notes'])) {
    $errors[] = "Strings file not found {$path_ss['notes']}\n";
    die();
  }

  $rows = count($xml);
  $row = 0;

  foreach ($xml as $txtno) {

    $id   = (int) $txtno->attributes()->ID;
    $text = mysql_real_escape_string(utf8_decode((string) $txtno->txtx1));

    $query = "INSERT INTO autodata_sg_note SET
              text  = '$text',
              id    = '$id',
              yhtio = '$kukarow[yhtio]'";
    mysql_query($query) or pupe_error($query);

    // progressbar
    $row++;
    progress_bar($row, $rows);

  }

  echo "\n";
}


function middata($middata_path = NULL) {
  global $kukarow, $errors;


  $file = fopen($middata_path, "r");

  while ($rivi = fgets($file)) {

    $rivi = mysql_real_escape_string($rivi);

    $data = explode("@", $rivi);

    $qu = "INSERT INTO autodata
           SET
           yhtio          = '{$kukarow['yhtio']}',
           jarjestys      = '{$data[0]}',
           merkki         = '{$data[1]}',
           malli          = '{$data[2]}',
           vali           = '{$data[3]}',
           mallitark      = '{$data[4]}',
           ryhmittely     = '{$data[5]}',
           moottoritil    = '{$data[6]}',
           moottorityyppi = '{$data[7]}',
           teho           = '{$data[8]}',
           moottoritark   = '{$data[9]}',
           alkuvuosi      = '{$data[10]}',
           loppuvuosi     = '{$data[11]}',
           autodataid     = '{$data[12]}',
           kayttovoima    = '{$data[13]}',
           ajoneuvolaji   = '{$data[14]}',
           au             = '{$data[15]}',
           wd             = '{$data[16]}',
           laatija        = '{$kukarow['kuka']}',
           luontiaika     = now()
           ";

    pupe_query($qu);
  }

  return;
}


function midtecdoc($data_path = NULL) {
  global $kukarow, $errors;
  if ($data_path == NULL) {
    return FALSE;
  }

  $file = fopen($data_path, "r");

  while ($rivi = fgets($file)) {
    $data = explode("@", $rivi);

    $qu = "INSERT INTO yhteensopivuus_autodata (yhtio, autodataid, autoid)
           VALUES ('{$kukarow['yhtio']}','{$data[0]}','{$data[1]}')";

    $re = pupe_query($qu);
  }
  return;
}


function td_strings($file, $tyyppi) {
  global $kukarow, $errors;

  if ($file == NULL or $tyyppi == NULL) {
    return FALSE;
  }

  if (! $xml = xml_load_file($file)) {
    echo $file;
    continue;
  }

  foreach ($xml as $item) {
    $id = $item->attributes()->ID;
    $textleft = utf8_decode($item->TEXTLEFT);
    $textright = utf8_decode($item->TEXTRIGHT);

    $qu = "INSERT INTO autodata_td_string (yhtio, tyyppi, item_id, textleft, textright)
           VALUES ('artr','$tyyppi', $id, '$textleft', '$textright')";

    $re = pupe_query($qu);
  }
}


function load_xml_file($file) {
  global $errors;
  if (! $obj = xml_load_file($file)) {
    $errors[] = "Could not load file $file\n";
  }
}


function find_xml_file($file) {
  $lower = $file . '.xml';
  if (is_readable($lower)) {
    return $lower;
  }

  $upper = $file . '.XML';
  if (is_readable($upper)) {
    return $upper;
  }

  return false;
}


function xml_load_file($file) {
  global $errors;
  if (! $xml = @simplexml_load_file($file)) {
    $errors[] = "Error parsing $file\n";
  }

  return $xml;
}
