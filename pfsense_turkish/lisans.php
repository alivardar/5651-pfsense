<?php
/* $Id$ */
/*
    lisans.php
*/

require("guiconfig.inc");

$savetopath="/etc/license.key";

if (($_POST['submit'] == "Kaydet")) {
	conf_mount_rw();
	$content = ereg_replace("\r","",$_POST['code']) ;
	$fd = fopen($savetopath, "w");	
	fwrite($fd, $content);
	fclose($fd);
	$edit_area="";
	$savemsg = "Lisans kaydedildi. Sistemi yeniden başlatınız. " ;
	conf_mount_ro();
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

$pgtitle = "Tanımlama: Lisans Düzenleme";

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
<form action="lisans.php" method="POST">

<div id="shapeme">
<table width="100%" cellpadding='9' cellspacing='9' bgcolor='#eeeeee'>
 <tr>
  <td>
	<center>
	<br>
	Anahtar bilgisi aşağıdaki şekildedir. Aşağıdaki anahtarı mail yoluyla gönderiniz.<br>
	Size gönderilen lisansı	aşağıdaki alana <br> 
	kopyalayınız ve kaydet butonuna basınız <br>
	etkinleşmesi için yeniden başlatınız  : 
	
	<input name="submit" type="submit"  class="button" id="Save" value="Kaydet">	
	<hr noshade>
	
	<?php 
	//key yazma	
	if (file_exists("/etc/keyresult")) 
	{readfile("/etc/keyresult"); }
	else 
	{print("Anahtar dosyası bulunanmadı!!!!");}
	?>
	
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
	<textarea style="width:98%" name="code" language="<?php echo $language; ?>" rows="15" 
	cols="10" name="content"><?php echo htmlentities($content); ?></textarea><br>
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

