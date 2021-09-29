<?php
if($_GET and isset($_GET['tietuedata']) and isset($_GET['tietuedataname'])) {
  header("Content-type: text/plain");
  header("Content-Disposition: attachment; filename=".htmlspecialchars($_GET['tietuedataname'], ENT_QUOTES));
  echo base64_decode($_GET['tietuedata']);
  exit;
}