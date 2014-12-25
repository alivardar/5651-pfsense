<?php
/* $Id$ */
/*
	services_dnsmasq.php
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2003-2004 Bob Zoller <bob@kludgebox.com> and Manuel Kasper <mk@neon1.net>.
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

$pconfig['enable']  = isset($config['dnsmasq']['enable']);
$pconfig['regdhcp'] = isset($config['dnsmasq']['regdhcp']);
$pconfig['regdhcpstatic'] = isset($config['dnsmasq']['regdhcpstatic']);

if (!is_array($config['dnsmasq']['hosts'])) 
	$config['dnsmasq']['hosts'] = array();

if (!is_array($config['dnsmasq']['domainoverrides'])) 
       $config['dnsmasq']['domainoverrides'] = array();

hosts_sort();

$a_hosts 	   = &$config['dnsmasq']['hosts'];
$a_domainOverrides = &$config['dnsmasq']['domainoverrides'];

if ($_POST) {

	$pconfig = $_POST;

	$config['dnsmasq']['enable'] = ($_POST['enable']) ? true : false;
	$config['dnsmasq']['regdhcp'] = ($_POST['regdhcp']) ? true : false;
	$config['dnsmasq']['regdhcpstatic'] = ($_POST['regdhcpstatic']) ? true : false;

	write_config();

	$retval = 0;

	config_lock();
	$retval = services_dnsmasq_configure();
	config_unlock();

	$savemsg = get_std_save_message($retval);

	// Relaod filter (we might need to sync to CARP hosts)
	filter_configure();

	if ($retval == 0) {
		if (file_exists($d_hostsdirty_path))
			unlink($d_hostsdirty_path);
	}
}

if ($_GET['act'] == "del") {
       if ($_GET['type'] == 'host') {
               if ($a_hosts[$_GET['id']]) {
                       unset($a_hosts[$_GET['id']]);
                       write_config();
                       touch($d_hostsdirty_path);
                       header("Location: services_dnsmasq.php");
                       exit;
               }
       }
       elseif ($_GET['type'] == 'doverride') {
               if ($a_domainOverrides[$_GET['id']]) {
                       unset($a_domainOverrides[$_GET['id']]);
                       write_config();
                       touch($d_hostsdirty_path);
                       header("Location: services_dnsmasq.php");
                       exit;
               }
       }
}

$pgtitle = "Services: DNS forwarder";
include("head.inc");

?>

<script language="JavaScript">
<!--
function enable_change(enable_over) {
	var endis;
	endis = !(document.iform.enable.checked || enable_over);
	document.iform.regdhcp.disabled = endis;
	document.iform.regdhcpstatic.disabled = endis;
}
//-->
</script>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="services_dnsmasq.php" method="post" name="iform" id="iform">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_hostsdirty_path)): ?><p>
<?php print_info_box_np("DNS yönlendirici ayarları değiştirildi.<br>Değişiklikler uygulandıktan sonra etkinleşecektir.");?><br>
<?php endif; ?>
	<table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td class="vtable"><p>
                      <input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable'] == "yes") echo "checked";?> onClick="enable_change(false)">
                      <strong>DNS yönlendirmeyi aktifleştir<br>
                      </strong></p></td>
                </tr>
                <tr>
                  <td class="vtable"><p>
                      <input name="regdhcp" type="checkbox" id="regdhcp" value="yes" <?php if ($pconfig['regdhcp'] == "yes") echo "checked";?>>
                      <strong>DHCP tahsislerini DNS ileticisinde kaydet<br>
                      </strong>
					  
					  Eğer bu seçenek etkinleştirilirse DHCP tahsisi talep ederken sunucu
					  adlarını belirten makineler DNS iletisinde kaydedilecek ve böylelikle
					  isimleri çözülebilecektir.
					  Ayrıca <a href="system.php">Sistem: Genel Ayarlar</a> bölümde etki alanı değerini ayarlamalısınız.
					  </p>

                    </td>
                </tr>
                <tr>
                  <td class="vtable"><p>
                      <input name="regdhcpstatic" type="checkbox" id="regdhcpstatic" value="yes" <?php if ($pconfig['regdhcpstatic'] == "yes") echo "checked";?>>
                      <strong>DHCP statik eşlemeleri DNS ileticisinde kaydet<br>
                      </strong>
					  
					  Eğer bu seçenek etkinleştirilirse statik DHCP eşlemeleri DNS
					  ileticisinde kaydedilecek ve böylelikle isimleri çözülebilecektir.
					  Ayrıca
					  <a href="system.php">Sistem: General Ayarlar</a>
					  bölümde etki alanı değerini ayarlamalısınız.
					  
					  </p>
                    </td>
                </tr>				
                <tr>
                  <td> <input name="submit" type="submit" class="formbtn" value="Kaydet" onclick="enable_change(true)">
                  </td>
                </tr>
                <tr>
                  <td><p><span class="vexpl"><span class="red"><strong>Bilgi:<br>
                      </strong></span>
					  
					  Eğer DNS ileticisi etkinleştirilmişse, DHCP servisi (Etkinleştirilmişse)
					  LAN IP adresini DHCP kullanıcılarına DNS sunucu IP adresi olarak
					  sunacak ve DHCP kullanıcıları da bu IP adresini DNS ileticisi olarak
					  kullanacaklardır. DNS ileticisi <a href="system.php">Sistem: Genel Ayarlar</a> da girilen
					  DNS sunucularını veya eğer &quot;WAN bağlantısında DHCP/PPP’nin DNS sunucu
					  listesin güncellemesine izin ver&quot; seçilmiş ise DHCP veya PPP ile 
					  edilen DNS sunucularını kullanacaktır.
					  <br>
					  <br>
 					  İleticinin edindiği sonuçlar yerine kullanılacak kayıtları aşağıya girebilirsiniz.
					  
					  </span></p></td>
                </tr>
        </table>
        &nbsp;<br>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="20%" class="listhdrr">Host</td>
                  <td width="25%" class="listhdrr">Domain</td>
                  <td width="20%" class="listhdrr">IP</td>
                  <td width="25%" class="listhdr">Açıklama</td>
                  <td width="10%" class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td width="17"></td>
                        <td valign="middle"><a href="services_dnsmasq_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
		  </td>
		</tr>
			  <?php $i = 0; foreach ($a_hosts as $hostent): ?>
                <tr>
                  <td class="listlr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
                    <?=strtolower($hostent['host']);?>&nbsp;
                  </td>
                  <td class="listr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
                    <?=strtolower($hostent['domain']);?>&nbsp;
                  </td>
                  <td class="listr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
                    <?=$hostent['ip'];?>&nbsp;
                  </td>
                  <td class="listbg" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
                    <font color="#FFFFFF"><?=htmlspecialchars($hostent['descr']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
                        <td valign="middle"><a href="services_dnsmasq_edit.php?id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0"></a></td>
                        <td><a href="services_dnsmasq.php?type=host&act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this host?')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
		      </tr>
                   </table>
                </tr>
		<?php $i++; endforeach; ?>
                <tr>
                  <td class="list" colspan="4"></td>
                  <td class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td width="17"></td>
                        <td valign="middle"><a href="services_dnsmasq_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
		   </td>
	</table>
<!-- update to enable domain overrides -->
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
	       <tr><td>&nbsp;</td></tr>
               <tr>
                 <td><p>
				 Aşağıda bir yetkili DNS sunucusu belirterek tüm bir etki alanı
				 geçersiz kılabilirsiniz bu etki alanı için sorgulanacak.
				</p></td>
               </tr>
        </table>
	&nbsp;<br>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
               <tr>
                 <td width="35%" class="listhdrr">Domain</td>
                 <td width="20%" class="listhdrr">IP</td>
                 <td width="35%" class="listhdr">Açıklama</td>
                 <td width="10%" class="list">
			<table border="0" cellspacing="0" cellpadding="1">
			    <tr>
				<td width="17" heigth="17"></td>			
				<td><a href="services_dnsmasq_domainoverride_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
			    </tr>
			</table>
		 </td>
                              </tr>
                        <?php $i = 0; foreach ($a_domainOverrides as $doment): ?>
               <tr>
                 <td class="listlr">
                   <?=strtolower($doment['domain']);?>&nbsp;
                 </td>
                 <td class="listr">
                   <?=$doment['ip'];?>&nbsp;
                 </td>
                 <td class="listbg"><font color="#FFFFFF">
                   <?=htmlspecialchars($doment['descr']);?>&nbsp;
                 </td>
                 <td valign="middle" nowrap class="list"> <a href="services_dnsmasq_domainoverride_edit.php?id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0"></a>
                    &nbsp;<a href="services_dnsmasq.php?act=del&type=doverride&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this domain override?')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
                              </tr>
                        <?php $i++; endforeach; ?>
               <tr>
                 <td class="list" colspan="3"></td>
                 <td class="list">
			<table border="0" cellspacing="0" cellpadding="1">
			    <tr>
				<td width="17" heigth="17"></td>			
				<td><a href="services_dnsmasq_domainoverride_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
			    </tr>
			</table>
		 </td>
	       </tr>
        </table>
        </form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc"); ?>
</body>
</html>
