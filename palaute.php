<?php
if(isset($_GET['palaute_dl'])) {
  $lataa_tiedosto = 1;
}
if(isset($_POST['palaute_lisaa'])) {
  $no_head = 'yes';
}

require "inc/parametrit.inc";

if(!isset($palaute_dl_tiedosto) or !$palaute_dl_tiedosto) {
  $palaute_dl_tiedosto = getcwd()."/datain/palaute_dl.csv";
}

if (!isset($palaute_dl)) $palaute_dl = false;
if($palaute_dl and file_exists($palaute_dl_tiedosto)) {
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header("Cache-Control: no-cache, must-revalidate");
  header("Expires: 0");
  header('Content-Disposition: attachment; filename="'.date("d_m_Y-H_i_s", filemtime($palaute_dl_tiedosto))."-".basename($palaute_dl_tiedosto).'"');
  header('Content-Length: ' . filesize($palaute_dl_tiedosto));
  header('Pragma: public');
  flush();
  readfile($palaute_dl_tiedosto);
  exit;
}

if (!isset($palaute_mail)) $palaute_mail = false;

if($palaute_mail and file_exists($palaute_dl_tiedosto)) {
  $viikko_obj = new DateTime();
  $viikko = $viikko_obj->format("W");
  $params = array(
    "to"       => $yhtiorow["talhal_email"],
    "subject"     => t('Tuotepalautteet, viikko ').$viikko,
    "ctype"       => "text",
    "body"       => t('Tuotepalautteet, viikko ').$viikko,
    "attachements"   => array(
      array(
        "filename"     => $palaute_dl_tiedosto,
        "newfilename"   => date("d_m_Y-H_i_s", filemtime($palaute_dl_tiedosto))."-".basename($palaute_dl_tiedosto),
        "ctype"       => "csv"
      )
    )
  );
  pupesoft_sahkoposti($params);
  exit;
}

if (!isset($palaute_lisaa)) $palaute_lisaa = false;
if (!isset($maara)) $maara = false;
if (!isset($hinta)) $hinta = false;
if (!isset($tuoteno)) $tuoteno = false;
if (!isset($status)) $status = false;
if (!isset($ostoehdotus)) $ostoehdotus = false;
if (!isset($palaute_kuka)) $palaute_kuka = false;

if($palaute_lisaa and $maara and $hinta and $tuoteno and $palaute_kuka) {
  
  $lisataan = array(
    $tuoteno, 
    $maara, 
    $hinta,
    $status, 
    $ostoehdotus, 
    $palaute_kuka,
    date("d.m.Y H:i:s")
  );
  if($tiedosto = fopen($palaute_dl_tiedosto, "a")) {

    $first_row = false;
    while (($data = fgetcsv(fopen($palaute_dl_tiedosto, "r"), 1000, ";")) !== FALSE) {
      $first_row = $data;
      break;
    }
 
    if(!$first_row) {
      $header = "Tuotenumero;Maara;Hinta;Status;Ostoehdotus;Kayttaja;Milloin \r\n";
      $tiedosto_data = file_get_contents($palaute_dl_tiedosto);
      file_put_contents($palaute_dl_tiedosto, $header.$tiedosto_data);
    }

    fputcsv($tiedosto, $lisataan, ";");

    fclose($tiedosto); 
    echo t('Palaute l�hetetty &#10003;');
  } else {
    echo t('VIRHE!');
  }



  exit;
}

if ($oikeurow['paivitys'] === '') {
  echo "<table width='100%' height='70%'>";
  echo "<tr>";
  echo "<td style='text-align:center;font-size:9pt;font-family:Lucida,Verdana,Helvetica,Arial; color: #666;'>";
  echo "<img src='{$palvelin2}pics/facelift/pupe.gif'><br><br>";
  echo "K�ytt�j� {$kukarow["kuka"]} pyysi toimintoa $phpnimi $toim, joka on kielletty!";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  exit;
}

echo "<font class='head'>";
echo t("Tuotepalautteet"), "</font><hr>";
if(file_exists($palaute_dl_tiedosto)) {
?>
<p><?php echo t('Alla on tiedosto, joka sis�lt�� kaikki palautteet.'); ?>
<div><a class="message warning" target="_blank" href="?palaute_dl=lataa"><?php echo t('Lataa CSV'); ?></a></div>
<?php 
} else {
  echo '<div class="message warning">'.t("Tiedostoa ei viel� ole!").'</div>';
}
?>