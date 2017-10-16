<?php

if (@include "inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

$go = $goso = '';

if (isset($_REQUEST['go'])) {

  $go = $_REQUEST['go'];

  if (isset($_REQUEST['laji'])) {
    $go .= "&laji={$_REQUEST["laji"]}";
  }

  if (strpos($go, '?')) {
    $go .= "&indexvas=1";
  }
  else {
    $go .= "?indexvas=1";
  }
}

if (isset($_REQUEST['goso'])) {
  $goso = $_REQUEST['goso'];
}

if ($go == '') {
  $go = 'tervetuloa.php';
  $goso = '';
}

if (!headers_sent()) {
  header("Content-Type: text/html; charset=iso-8859-1");
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
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">\n";

if (file_exists("pics/pupeicon.gif")) {
  echo "<link rel='shortcut icon' href='pics/pupeicon.gif'>\n";
}
else {
  echo "<link rel='shortcut icon' href='{$palvelin2}devlab-shortcut.png'>\n";
}

if ($kukarow["extranet"] != "") {
  echo $yhtiorow["web_seuranta"];
}

echo "</head>";

if (($yhtiorow["kayttoliittyma"] == "" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "C") {
  echo "<frameset cols='175,*' border='0'>
          <frameset rows='*,0' border='0'>
            <frame noresize src='indexvas.php?goso=$goso' name='menuframe' frameborder='0' marginwidth='0' marginheight='0'>
            <frame noresize src='' name='alamenu' id='alamenuFrame' frameborder='0' marginwidth='0' marginheight='0'>
          </frameset>
          <frame noresize src='$go' name='mainframe' frameborder='0' marginwidth='0' marginheight='0'>
          <noframes>
            <body>
                    <p>
                        This page uses frames, but your browser does not support them.
                    </p>
                </body>
          </noframes>
        </frameset>";
}
else {

  if (isset($_COOKIE["yla_frame_showhide"]) and $_COOKIE["yla_frame_showhide"] == "hidden") {
    $yla_cols = "20,*";
  }
  else {
    $yla_cols = "90,*";
  }

  echo "<frameset rows='$yla_cols' border='0'>
          <frame noresize src='ylaframe.php' name='ylaframe' id='ylaframe' frameborder='0' marginwidth='0' marginheight='0' scrolling='no'>";

  if (isset($_COOKIE["vas_frame_showhide"]) and $_COOKIE["vas_frame_showhide"] == "hidden") {
    $vas_cols = "15,*";
  }
  else {
    $vas_cols = "270,*";
  }

  echo "  <frameset cols='$vas_cols' border='0'>
            <frame noresize src='indexvas.php?goso=$goso&go=$go' name='menuframe' id='menuframe' frameborder='0' marginwidth='0' marginheight='0'>
            <frame noresize src='$go' name='mainframe' id='mainframe' frameborder='0' marginwidth='0' marginheight='0'>
          </frameset>

          <noframes>
            <body>
              <p>
              This page uses frames, but your browser does not support them.
              </p>
            </body>
          </noframes>
        </frameset>";
}

echo "</html>";
