<?php

require "inc/parametrit.inc";

echo "<font class='head'>";

if (!isset($id))                  $id = false;

echo t("Kyselyn lopettaminen"), "</font><hr>";
$result = pupe_query("SHOW FULL PROCESSLIST");
?>
<p><?php echo t('Vain yli 10 sekuntia sitten aloittaneet kyselyt näytetään.'); ?>
<form method="GET" action="">
<select name="id">
<option checked><?php echo t('Valitse kysely'); ?></option>
<?php 
$kaikki_prosessit = array();
while ($row = mysql_fetch_array($result)) {
  $kaikki_prosessit[] = $row;
  if ($row["Time"] >= 10 and $row["Info"] !== NULL) {
    if($id and $row["Id"] == $id) {
      $result = pupe_query("KILL $id");
      sleep(1);
      Header("Location: /killsql.php");
    }
    ?>
    <option value="<?php echo $row["Id"];?>">
      <?php echo substr($row["Info"], 0, 100); ?>
    </option>
    <?php
  }
}
?>
</select>

<div>
<div style="margin-top: 5px; margin-bottom: 5px; padding: 15px; background-color: red; color: #ffffff; display: inline-block;">
<?php echo t('OLE TOSI TARKKA ENNEN KUIN PAINAT "LOPETA" -NAPPIA!'); ?>
</div></div>
<input type="submit" value="<?php echo t('Lopeta'); ?>">
<h3><?php echo t("Kaikki prosessit"); ?></h3>
<?php
foreach ($kaikki_prosessit as $prosessi) {
  if ($prosessi["Time"] >= 0 and $prosessi["Info"] !== NULL) {
    echo substr($prosessi["Info"], 0, 100)."...<hr>";
  }
}
?>
</form>
<?php 

?>