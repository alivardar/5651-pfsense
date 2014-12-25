<?php
/*
	services_captiveportal_ip.php
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2004 Dinesh Nair <dinesh@alphaque.com>
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

$pgtitle = "Services:Hotspot";
require("guiconfig.inc");

if (!is_array($config['captiveportal']['allowedip']))
	$config['captiveportal']['allowedip'] = array();

allowedips_sort();
$a_allowedips = &$config['captiveportal']['allowedip'] ;

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;

		$retval = captiveportal_allowedip_configure();

		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_allowedipsdirty_path)) {
				config_lock();
				unlink($d_allowedipsdirty_path);
				config_unlock();
			}
		}
	}
}

if ($_GET['act'] == "del") {
	if ($a_allowedips[$_GET['id']]) {
		unset($a_allowedips[$_GET['id']]);
		write_config();
		touch($d_allowedipsdirty_path);
		header("Location: services_captiveportal_ip.php");
		exit;
	}
}


include("head.inc");
?>
<?php include("fbegin.inc"); ?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<p class="pgtitle"><?=$pgtitle?></p>
<form action="services_captiveportal_ip.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_allowedipsdirty_path)): ?>
<?php print_info_box_np("Hotspot IP adres ayarları değiştirildi.<br>Değişikliklerin geçerli olması için uygulanmalıdır.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
<?php
	$tab_array = array();
	$tab_array[] = array("Hotspot", false, "services_captiveportal.php");
	$tab_array[] = array("MAC üzerinden geçiş", false, "services_captiveportal_mac.php");
	$tab_array[] = array("İzinli IP Adresleri", true, "services_captiveportal_ip.php");
	$tab_array[] = array("Kullanıcılar", false, "services_captiveportal_users.php");
	$tab_array[] = array("Dosya yöneticisi", false, "services_captiveportal_filemanager.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
  <td class="tabcont">
  <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td width="30%" class="listhdrr">IP adresi</td>
	  <td width="60%" class="listhdr">Açıklama</td>
	  <td width="10%" class="list">
		<table border="0" cellspacing="0" cellpadding="1">
		   <tr>
			<td width="17" heigth="17"></td>
			<td><a href="services_captiveportal_ip_edit.php"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_plus.gif" title="add address" width="17" height="17" border="0"></a></td>
		   </tr>
		</table>
	  </td>
	</tr>
  <?php $i = 0; foreach ($a_allowedips as $ip): ?>
	<tr>
	  <td class="listlr">
		<?php if($ip['dir'] == "to")
			echo "any <img src=\"/themes/{$g['theme']}/images/icons/icon_in.gif\" width=\"11\" height=\"11\" align=\"absmiddle\">";
		?>
		<?=strtolower($ip['ip']);?>
		<?php if($ip['dir'] == "from")
			echo "<img src=\"/themes/{$g['theme']}/images/icons/icon_in.gif\" width=\"11\" height=\"11\" align=\"absmiddle\"> any";
		?>
	  </td>
	  <td class="listbg">
		<?=htmlspecialchars($ip['descr']);?>&nbsp;
	  </td>
	  <td valign="middle" nowrap class="list"> <a href="services_captiveportal_ip_edit.php?id=<?=$i;?>"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_e.gif" title="edit address" width="17" height="17" border="0"></a>
		 &nbsp;<a href="services_captiveportal_ip.php?act=del&id=<?=$i;?>" onclick="return confirm('Bu adresi gerçekten silmek istiyor musunuz?')"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_x.gif" title="delete address" width="17" height="17" border="0"></a></td>
	</tr>
  <?php $i++; endforeach; ?>
	<tr>
	  <td class="list" colspan="2">&nbsp;</td>
	  <td class="list">
		<table border="0" cellspacing="0" cellpadding="1">
		   <tr>
			<td width="17" heigth="17"></td>
			<td><a href="services_captiveportal_ip_edit.php"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_plus.gif" title="add address" width="17" height="17" border="0"></a></td>
		   </tr>
		</table>
	  </td>
	</tr>
	<tr>
	<td colspan="2" class="list"><p class="vexpl"><span class="red"><strong>
	  Bilgi:<br>
	  </strong></span>
	  Eklenen IP adresleri Hospot üzerinde herhangi bir karşılama sayfası görmeden interenete erişebilirler.
	  Bu özellik özellikle iç alanda bulunan sunucuların dış ağdan bir dosyaya erişmeleri için kullanılabilir.
	  
	  </p>
	  <table border="0" cellspacing="0" cellpadding="0">
		<tr>
		  <td><span class="vexpl">any <img src="/themes/<?=$g['theme'];?>/images/icons/icon_in.gif" width="11" height="11" align="absmiddle"> x.x.x.x </span></td>
		  <td><span class="vexpl">Bütün bağlantılar <strong>to</strong> the IP address are allowed</span></td>
		</tr>
		<tr>
		  <td colspan="5" height="4"></td>
		</tr>
		<tr>
		  <td>x.x.x.x <span class="vexpl"><img src="/themes/<?=$g['theme'];?>/images/icons/icon_in.gif" width="11" height="11" align="absmiddle"></span> any&nbsp;&nbsp;&nbsp; </td>
		  <td><span class="vexpl">Bütün bağlantılar <strong>from</strong> the IP address are allowed </span></td>
		</tr>
	  </table></td>
	<td class="list">&nbsp;</td>
	</tr>
  </table>
  </td>
  </tr>
  </table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
