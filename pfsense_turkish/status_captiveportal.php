<?php 
/* $Id$ */
/*
	status_captiveportal.php
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
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

if ($_GET['act'] == "del") {
	captiveportal_disconnect_client($_GET['id']);
	Header("Location: status_captiveportal.php");
	exit;
}

// Lock and read in the captive portal DB
$captiveportallck = lock('captiveportal');
$cpcontents = file("/var/db/captiveportal.db", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
unlock($captiveportallck);

// Set how many sessions are currently under flight
$concurrent = count($cpcontents);

$pgtitle = "Status: Captive portal ({$concurrent})";

include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<script src="/javascript/sorttable.js"></script>
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php

flush();

function clientcmp($a, $b) {
	global $order;
	return strcmp($a[$order], $b[$order]);
}

$cpdb = array();
foreach ($cpcontents as $cpcontent) {
        $cpent = explode(",", $cpcontent);
        if ($_GET['showact'])
                $cpent[5] = captiveportal_get_last_activity($cpent[1]);
                $cpdb[] = $cpent;
}
if ($_GET['order']) {
        if ($_GET['order'] == "ip")
                $order = 2;
        else if ($_GET['order'] == "mac")
                $order = 3;
        else if ($_GET['order'] == "user")
                $order = 4;
        else if ($_GET['order'] == "lastact")
                $order = 5;
        else
                $order = 0;
        usort($cpdb, "clientcmp");
}
?>
<table class="sortable" width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="listhdrr"><a href="?order=ip&amp;showact=<?=$_GET['showact'];?>"><?=gettext("IP address");?></a></td>
    <td class="listhdrr"><a href="?order=mac&amp;showact=<?=$_GET['showact'];?>"><?=gettext("MAC address");?></a></td>
    <td class="listhdrr"><a href="?order=user&amp;showact=<?=$_GET['showact'];?>"><?=gettext("Username");?></a></td>
	<?php if ($_GET['showact']): ?>
    <td class="listhdrr"><a href="?order=start&amp;showact=<?=$_GET['showact'];?>"><?=gettext("Session start");?></a></td>
    <td class="listhdr"><a href="?order=lastact&amp;showact=<?=$_GET['showact'];?>"><?=gettext("Last activity");?></a></td>
	<?php else: ?>
    <td class="listhdr"><a href="?order=start&amp;showact=<?=$_GET['showact'];?>"><?=gettext("Session start");?></a></td>
	<?php endif; ?>
    <td class="list sort_ignore"></td>
  </tr>
<?php foreach ($cpdb as $cpent): ?>
  <tr>
    <td class="listlr"><?=$cpent[2];?></td>
    <td class="listr"><?=$cpent[3];?>&nbsp;</td>
    <td class="listr"><?=$cpent[4];?>&nbsp;</td>
    <td class="listr"><?=htmlspecialchars(date("m/d/Y H:i:s", $cpent[0]));?></td>
	<?php if ($_GET['showact']): ?>
    <td class="listr"><?php if ($cpent[5]) echo htmlspecialchars(date("m/d/Y H:i:s", $cpent[5]));?></td>
	<?php endif; ?>
	<td valign="middle" class="list" nowrap>
	<a href="?order=<?=$_GET['order'];?>&showact=<?=$_GET['showact'];?>&act=del&id=<?=$cpent[1];?>" onclick="return confirm('Bu baslantiyi sonlandirmak istediginizden emin misiniz?')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
  </tr>
<?php endforeach; ?>
</table>
<form action="status_captiveportal.php" method="get" style="margin: 14px;">
<input type="hidden" name="order" value="<?=$_GET['order'];?>" />
<?php if ($_GET['showact']): ?>
<input type="hidden" name="showact" value="0" />
<input type="submit" class="formbtn" value="<?=gettext("Son aktiviteleri g�sterme");?>" />
<?php else: ?>
<input type="hidden" name="showact" value="1" />
<input type="submit" class="formbtn" value="<?=gettext("Son aktiviteleri g�ster");?>" />
<?php endif; ?>
</form>
<?php include("fend.inc"); ?>

</body>
</html>
