<?php 
/*
	services_captiveportal_users.php
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	Copyright (C) 2005 Pascal Suter <d-monodev@psuter.ch>.
	All rights reserved. 
	(files was created by Pascal based on the source code of services_captiveportal.php from Manuel)
	
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
$pgtitle = "Servisler:Hotspot";
require("guiconfig.inc");

if (!is_array($config['captiveportal']['user'])) {
	$config['captiveportal']['user'] = array();
}
captiveportal_users_sort();
$a_user = &$config['captiveportal']['user'];

if ($_GET['act'] == "del") {
	if ($a_user[$_GET['id']]) {
		unset($a_user[$_GET['id']]);
		write_config();
		header("Location: services_captiveportal_users.php");
		exit;
	}
}

//erase expired accounts
$changed = false;
for ($i = 0; $i < count($a_user); $i++) {
	if ($a_user[$i]['expirationdate'] && (strtotime("-1 day") > strtotime($a_user[$i]['expirationdate']))) {
		unset($a_user[$i]);
		$changed = true;
	}
}
if ($changed) {
	write_config();
	header("Location: services_captiveportal_users.php");
	exit;
}

include("head.inc");

?>
<?php include("fbegin.inc"); ?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<p class="pgtitle"><?=$pgtitle?></p>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td>
<?php
	$tab_array = array();
	$tab_array[] = array("Hotspot", false, "services_captiveportal.php");
	$tab_array[] = array("MAC üzerinden geçiş", false, "services_captiveportal_mac.php");
	$tab_array[] = array("İzinli IP Adresleri", false, "services_captiveportal_ip.php");
	$tab_array[] = array("Kullanıcılar", true, "services_captiveportal_users.php");
	$tab_array[] = array("Dosya yöneticisi", false, "services_captiveportal_filemanager.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
  <td class="tabcont">
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="35%" class="listhdrr">Kullanıcı adı</td>
                  <td width="20%" class="listhdrr">Tam adı</td>
                  <td width="35%" class="listhdr">Zaman aşımı</td>
                  <td width="10%" class="list">
			<table border="0" cellspacing="0" cellpadding="1">
			     <tr>
				<td width="17" heigth="17"></td>
				<td><a href="services_captiveportal_users_edit.php"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_plus.gif" title="add user" width="17" height="17" border="0"></a></td>
			     </tr>
			</table>
		  </td>
		</tr>
	<?php $i = 0; foreach($a_user as $userent): ?>
		<tr>
                  <td class="listlr">
                    <?=htmlspecialchars($userent['name']); ?>&nbsp;
                  </td>
                  <td class="listr">
                    <?=htmlspecialchars($userent['fullname']);?>&nbsp;
                  </td>
                  <td class="listbg">
                    <?=$userent['expirationdate']; ?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="services_captiveportal_users_edit.php?id=<?=$i; ?>"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_e.gif" title="edit user" width="17" height="17" border="0"></a>
                     &nbsp;<a href="services_captiveportal_users.php?act=del&id=<?=$i; ?>" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_x.gif" title="delete user" width="17" height="17" border="0"></a></td>
		</tr>
	<?php $i++; endforeach; ?>
		<tr> 
			  <td class="list" colspan="3"></td>
			  <td class="list">
				<table border="0" cellspacing="0" cellpadding="1">
				     <tr>
					<td width="17" heigth="17"></td>
					<td><a href="services_captiveportal_users_edit.php"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_plus.gif" title="add user" width="17" height="17" border="0"></a></td>
				     </tr>
				</table>
			  </td>
		</tr>
 </table>     
</td>
</tr>
</table>
<?php include("fend.inc"); ?>
</body>
</html>
