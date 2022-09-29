<?php
ob_start();
require "inc/parametrit.inc";
if (!isset($palaute_dl)) $palaute_dl = false;
if($palaute_dl and $file = getcwd().'/datain/palaute_dl.csv' and file_exists($file)) {
  ob_end_clean();
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header("Cache-Control: no-cache, must-revalidate");
  header("Expires: 0");
  header('Content-Disposition: attachment; filename="'.date("d_m_Y-H_i_s", filemtime($file))."-".basename($file).'"');
  header('Content-Length: ' . filesize($file));
  header('Pragma: public');
  flush();
  readfile($file);
  exit;
}

if (!isset($palaute_mail)) $palaute_mail = false;
if($palaute_mail and $file = getcwd().'/datain/palaute_dl.csv' and file_exists($file)) {
  $viikko_obj = new DateTime();
  $viikko = $viikko_obj->format("W");
  ob_end_clean();
  $params = array(
    "to"       => "igor.olhovski@sprintit.fi",
    "subject"     => t('Tuotepalautteet, viikko ').$viikko,
    "ctype"       => "text",
    "body"       => t('Tuotepalautteet, viikko ').$viikko,
    "attachements"   => array(
      array(
        "filename"     => $file,
        "newfilename"   => date("d_m_Y-H_i_s", filemtime($file))."-".basename($file),
        "ctype"       => "csv"
      )
    )
  );
  $onko_sahkoposti_lahetetty = pupesoft_sahkoposti($params);
  exit;
}

if (!isset($palaute_lisaa)) $palaute_lisaa = false;
if (!isset($maara)) $maara = false;
if (!isset($hinta)) $hinta = false;
if (!isset($tuoteno)) $tuoteno = false;
if (!isset($palaute_kuka)) $palaute_kuka = false;

if($palaute_lisaa and $maara and $hinta and $tuoteno and $palaute_kuka) {
  ob_end_clean();
  $lisataan = array(
    $tuoteno, 
    $maara, 
    $hinta,
    $palaute_kuka,
    date("d.m.Y H:i:s")
  );
  $tiedosto = fopen(getcwd()."/datain/palaute_dl.csv", "a");
  fputcsv($tiedosto, $lisataan);
  fclose($handle); 
  echo '<div class="vastaus">';
  echo t('Palaute lähetetty &#10003;');
  echo '</div>';
  exit;
}

if ($oikeurow['paivitys'] === '') {
  echo "<table width='100%' height='70%'>";
  echo "<tr>";
  echo "<td style='text-align:center;font-size:9pt;font-family:Lucida,Verdana,Helvetica,Arial; color: #666;'>";
  echo "<img src='{$palvelin2}pics/facelift/pupe.gif'><br><br>";
  echo "Käyttäjä {$kukarow["kuka"]} pyysi toimintoa $phpnimi $toim, joka on kielletty!";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  exit;
}

echo "<font class='head'>";
echo t("Tuotepalautteet"), "</font><hr>";

?>
<p><?php echo t('Alla on tiedosto, joka sisältää kaikki palautteet.'); ?>
<div><a class="message warning" target="_blank" href="?palaute_dl=lataa"><?php echo t('Lataa CSV'); ?></a></div>
<?php 

?>