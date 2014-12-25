<?php

$pgtitle = "Servisler:Hotspot";
require("guiconfig.inc");

if (!is_array($config['captiveportal']['passthrumac']))
	$config['captiveportal']['passthrumac'] = array();

passthrumacs_sort();
$a_passthrumacs = &$config['captiveportal']['passthrumac'] ;

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;

		$retval = captiveportal_passthrumac_configure();

		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_passthrumacsdirty_path)) {
				config_lock();
				unlink($d_passthrumacsdirty_path);
				config_unlock();
			}
		}
	}
}

if ($_GET['act'] == "del") {
	if ($a_passthrumacs[$_GET['id']]) {
		unset($a_passthrumacs[$_GET['id']]);
		write_config();
		touch($d_passthrumacsdirty_path);
		header("Location: services_captiveportal_mac.php");
		exit;
	}
}

include("head.inc");

?>
<?php include("fbegin.inc"); ?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<p class="pgtitle"><?=$pgtitle?></p>
<form action="services_captiveportal_mac.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_passthrumacsdirty_path)): ?><p>
<?php print_info_box_np("The captive portal MAC address configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
<?php
	$tab_array = array();
	$tab_array[] = array("Hotspot", false, "services_captiveportal.php");
	$tab_array[] = array("MAC üzerinden geçiş", true, "services_captiveportal_mac.php");
	$tab_array[] = array("İzinli IP Adresleri", false, "services_captiveportal_ip.php");
	$tab_array[] = array("Kullanıcılar", false, "services_captiveportal_users.php");
	$tab_array[] = array("Dosya yöneticisi", false, "services_captiveportal_filemanager.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
  <td class="tabcont">
  <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td width="30%" class="listhdrr">MAC adresi</td>
	  <td width="60%" class="listhdr">Açıklama</td>
	  <td width="10%" class="list"></td>
	</tr>
  <?php $i = 0; foreach ($a_passthrumacs as $mac): ?>
	<tr>
	  <td class="listlr">
		<?=strtolower($mac['mac']);?>
	  </td>
	  <td class="listbg">
		<?=htmlspecialchars($mac['descr']);?>&nbsp;
	  </td>
	  <td valign="middle" nowrap class="list"> <a href="services_captiveportal_mac_edit.php?id=<?=$i;?>"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_e.gif" title="edit host" width="17" height="17" border="0"></a>
		 &nbsp;<a href="services_captiveportal_mac.php?act=del&id=<?=$i;?>" onclick="return confirm('Bu host silmek istediğinizden emin misiniz?')"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_x.gif" title="delete host" width="17" height="17" border="0"></a></td>
	</tr>
  <?php $i++; endforeach; ?>
	<tr> 
	  <td class="list" colspan="2">&nbsp;</td>
	  <td class="list"> <a href="services_captiveportal_mac_edit.php"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_plus.gif" title="add host" width="17" height="17" border="0"></a></td>
	</tr>
	<tr>
	<td colspan="2" class="list"><span class="vexpl"><span class="red"><strong>
	Bilgi:<br>
	</strong></span>
	Eklenen MAC adresi herhangi karşılama sayfasına uğramadan doğrudan geçiş yapabilir.
	Hotspot timeout süresi sonunda bu MAC adresli kullanıcının da bağlantısı kesilecektir.	
	</span></td>
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
