<?php

require ("inc/parametrit.inc");

$go = $goso = '';

if (isset($_REQUEST['go'])) {
  $go = $_REQUEST['go'];
}

if (isset($_REQUEST['goso'])) {
  $goso = $_REQUEST['goso'];
}

if ($go == '') {
  $go = 'tervetuloa.php';
  $goso = '';
}

$colwidth = '175';

if (isset($kukarow['resoluutio']) and $kukarow['resoluutio'] == 'P') {
  $colwidth = '45';
}

if (!headers_sent()) {
  header("Content-Type: text/html; charset=utf-8");
  header("Pragma: public");
  header("Expires: 0");
  header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
}

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"\n\"http://www.w3.org/TR/html4/frameset.dtd\">
<html>
  <head>
      <title>$yhtiorow[nimi]</title>\n
    <meta http-equiv=\"Cache-Control\" Content=\"no-cache\">\n
    <meta http-equiv=\"Pragma\" Content=\"no-cache\">\n
    <meta http-equiv=\"Expires\" Content=\"-1\">\n
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";

if (file_exists("pics/pupeicon.gif")) {
  echo "  <link rel='shortcut icon' href='pics/pupeicon.gif'>\n";
}
else {
  echo "  <link rel='shortcut icon' href='".$palvelin2."devlab-shortcut.png'>\n";
}


echo "</head>
  <frameset cols='$colwidth,*' border='0'>
    <frameset rows='*,0' border='0'>
      <frame noresize src='indexvas.php?goso=$goso' name='menu' frameborder='0' marginwidth='0' marginheight='0'>
      <frame noresize src='' name='alamenu' id='alamenuFrame' frameborder='0' marginwidth='0' marginheight='0'>
    </frameset>
    <frame noresize src='$go' name='main' frameborder='0' marginwidth='0' marginheight='0'>
    <noframes>
      <body>
              <p>
                  This page uses frames, but your browser does not support them.
              </p>
          </body>
    </noframes>
  </frameset>
</html>";
