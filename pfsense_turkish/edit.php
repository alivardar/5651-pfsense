<?php
/* $Id$ */
/*
    edit.php
    Copyright (C) 2004, 2005 Scott Ullrich
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

require("guiconfig.inc");

if (($_GET['submit'] == "Load") && file_exists($_GET['savetopath'])) {
	$fd = fopen($_GET['savetopath'], "r");
	if ((filesize($_GET['savetopath']) != 0)) {  $content = fread($fd, filesize($_GET['savetopath'])); } else { $content = ""; }
	fclose($fd);
	$edit_area="";
	$loadmsg = gettext("Loaded text from")." " . $_GET['savetopath'];
	if(stristr($_GET['savetopath'], ".php") == true)
		$language = "php";
	else if(stristr($_GET['savetopath'], ".inc") == true)
		$language = "php";
	else if(stristr($_GET['savetopath'], ".sh") == true)
		$language = "core";
	else if(stristr($_GET['savetopath'], ".xml") == true)
		$language = "xml";

	$savetopath = $_GET['savetopath'];
}

if (($_POST['submit'] == "Load") && file_exists($_POST['savetopath'])) {
	$fd = fopen($_POST['savetopath'], "r");
	if ((filesize($_POST['savetopath']) != "0")) {  $content = fread($fd, filesize($_POST['savetopath'])); } else { $content = ""; }
	fclose($fd);
	$edit_area="";
	$loadmsg = "Loaded text from " . $_POST['savetopath'];
	if(stristr($_POST['savetopath'], ".php") == true)
		$language = "php";
	else if(stristr($_POST['savetopath'], ".inc") == true)
		$language = "php";
	else if(stristr($_POST['savetopath'], ".sh") == true)
		$language = "core";
	else if(stristr($_POST['savetopath'], ".xml") == true)
		$language = "xml";
	$savetopath = $_POST['savetopath'];
} else if (($_POST['submit'] == "Save")) {
	conf_mount_rw();
	$content = ereg_replace("\r","",$_POST['code']) ;
	$fd = fopen($_POST['savetopath'], "w");
	fwrite($fd, $content);
	fclose($fd);
	$edit_area="";
	$savemsg = "Saved text to " . $_POST['savetopath'];
	if($_POST['savetopath'] == "/cf/conf/config.xml")
		unlink_if_exists("/tmp/config.cache");
	conf_mount_ro();
	$savetopath = $_POST['savetopath'];
} else if (($_POST['submit'] == "Load") && !file_exists($_POST['savetopath'])) {
	$savemsg = "File not found " . $_POST['savetopath'];
	$content = "";
	$_POST['savetopath'] = "";
}

if($_POST['highlight'] <> "") {
	if($_POST['highlight'] == "yes" or
	  $_POST['highlight'] == "enabled") {
		$highlight = "yes";
	} else {
		$highlight = "no";
	}
} else {
	$highlight = "no";
}

if($_POST['rows'] <> "")
	$rows = $_POST['rows'];
else
	$rows = 30;

if($_POST['cols'] <> "")
	$cols = $_POST['cols'];
else
	$cols = 66;
?>
<?php

/*
	Exec+ v1.02-000 - Copyright 2001-2003, All rights reserved
	Created by technologEase (http://www.technologEase.com).
	(modified for m0n0wall by Manuel Kasper <mk@neon1.net>)
        (modified for pfSense Edit/Save file by Scott Ullrich, Copyright 2004, 2005)
*/

// Function: is Blank
// Returns true or false depending on blankness of argument.

function isBlank( $arg ) { return ereg( "^\s*$", $arg ); }

// Function: Puts
// Put string, Ruby-style.

function puts( $arg ) { echo "$arg\n"; }

// "Constants".

$Version    = '';
$ScriptName = $HTTP_SERVER_VARS['SCRIPT_NAME'];

// Get year.

$arrDT   = localtime();
$intYear = $arrDT[5] + 1900;

$pgtitle = "Tanımlama: Dosya Düzenle";

include("head.inc");

?>

<?php include("fbegin.inc"); ?>

<script language="Javascript">
function sf() { document.forms[0].savetopath.focus(); }
</script>
<body onLoad="sf();">
<p><span class="pgtitle"><?=$pgtitle?></span>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if ($loadmsg) echo "<p><b><div style=\"background:#eeeeee\" id=\"shapeme\">&nbsp;&nbsp;&nbsp;{$loadmsg}</div><br>"; ?>
<form action="edit.php" method="POST">

<div id="shapeme">
<table width="100%" cellpadding='9' cellspacing='9' bgcolor='#eeeeee'>
 <tr>
  <td>
	<center>
	Dizinden dosyayı kaydet/yükle: <input size="42" id="savetopath" name="savetopath" value="<?php echo $savetopath; ?>">
	<input name="submit" type="submit"  class="button" id="Load" value="Yükle"> <input name="submit" type="submit"  class="button" id="Save" value="Kaydet">	<hr noshade>
	<?php if($_POST['highlight'] == "no"): ?>
	   Rows: <input size="3" name="rows" value="<? echo $rows; ?>">
	   Cols: <input size="3" name="cols" value="<? echo $cols; ?>">
	<?php endif; ?>
  </td>
 </tr>
</table>
</div>

<br>

  <table width='100%'>
    <tr>
      <td valign="top" class="label">
	<div style="background:#eeeeee" id="textareaitem">
	&nbsp;<br>&nbsp;
	<center>
	<textarea style="width:98%" name="code" language="<?php echo $language; ?>" rows="<?php echo $rows; ?>" cols="<?php echo $cols; ?>" name="content"><?php echo htmlentities($content); ?></textarea><br>
	&nbsp;
	</div>
        <p>
    </td>
    </tr>
  </table>
<?php include("fend.inc"); ?>
</form>
</body>
</html>

<script language="Javascript">
sf();
</script>

</div>
<script language="javascript" src="/code-syntax-highlighter/shCore.js"></script>
<script language="javascript" src="/code-syntax-highlighter/shBrushCSharp.js"></script>
<script language="javascript" src="/code-syntax-highlighter/shBrushPhp.js"></script>
<script language="javascript" src="/code-syntax-highlighter/shBrushJScript.js"></script>
<script language="javascript" src="/code-syntax-highlighter/shBrushVb.js"></script>
<script language="javascript" src="/code-syntax-highlighter/shBrushSql.js"></script>
<script language="javascript" src="/code-syntax-highlighter/shBrushXml.js"></script>
<script language="javascript" src="/code-syntax-highlighter/shBrushDelphi.js"></script>
<script language="javascript" src="/code-syntax-highlighter/shBrushPython.js"></script>

<?php if($_POST['highlight'] == "yes") {
	echo "<script language=\"javascript\">\n";
	echo "dp.SyntaxHighlighter.HighlightAll('code', true, true);\n";
	echo "</script>\n";
}
?>

<script type="text/javascript">
NiftyCheck();
Rounded("div#shapeme","all","#FFF","#eeeeee","smooth");
Rounded("div#textareaitem","all","#FFF","#eeeeee","smooth");
</script>
