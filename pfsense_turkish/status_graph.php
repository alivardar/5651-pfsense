<?php
/* $Id$ */
/*
	status_graph.php
	Part of pfSense
	Copyright (C) 2004 Scott Ullrich
	All rights reserved.

	Originally part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that following conditions are met:

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

if ($_POST['width'])
	$width = $_POST['width'];
else
	$width = "550";

if ($_POST['height'])
	$height = $_POST['height'];
else
	$height = "275";

/* link the ipsec interface magically */
$config['interfaces']['ipsec']['if'] = "enc0";

if ($_GET['if']) {
	$curif = $_GET['if'];
	$ifnum = $config['interfaces'][$curif]['if'];
} else {
	$curif = "wan";
	$ifnum = get_real_wan_interface();
}

$pgtitle = "Status: Trafik Grafiği";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php
$ifdescrs = array('wan' => 'WAN', 'lan' => 'LAN');

for($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
	if(isset($config['interfaces']['opt' . $j]['enable']))
		$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
}
if((isset($config['ipsec']['enable'])) || (isset($config['ipsec']['mobileclients']['enable']))) {
	$ifdescrs['ipsec'] = "IPSEC";
}

?>
<form name="form1" action="status_graph.php" method="get" style="padding-bottom: 10px; margin-bottom: 14px; border-bottom: 1px solid #999999">
Interface:
<select name="if" class="formfld" style="z-index: -10;" onchange="document.form1.submit()">
<?php
foreach ($ifdescrs as $ifn => $ifd) {
	echo "<option value=\"$ifn\"";
	if ($ifn == $curif) echo " selected";
	echo ">" . htmlspecialchars($ifd) . "</option>\n";
}
?>
</select>
</form>
<p><span class="red"><strong>Bilgi:</strong></span>  <a href="http://www.adobe.com/svg/viewer/install/" target="_blank">Adobe SVG Viewer</a> kurulu olmaldır. Düzgün şekilde görüntüleyebilmek için Firefox 1.5 veya daha üst bir sürüm üzerinde SVG kurulu olmalıdır.
<p><form method="post" action="status_graph.php">
</form>
<p>
<div align="center">
	<object data="graph.php?ifnum=<?=$ifnum;?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" type="image/svg+xml" width="550" height="275">
		<param name="src" value="graph.php?ifnum=<?=$ifnum;?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" />
		İnternet gezgininizin SVG desteği mevcut değildir. Adobe SVG plugin kurulu olması gerekmektedir..
	</object>
</div>

<?php include("fend.inc"); ?>
</body>
</html>
