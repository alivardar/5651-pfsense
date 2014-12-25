<?php
/* $Id$ */
/*
	diag_logs_settings.php
	Copyright (C) 2004-2009 Scott Ullrich
	All rights reserved.

	originially part of m0n0wall (http://m0n0.ch/wall)
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

/*	
	pfSense_MODULE:	system
*/

##|+PRIV
##|*IDENT=page-diagnostics-logs-settings
##|*NAME=Diagnostics: Logs: Settings page
##|*DESCR=Allow access to the 'Diagnostics: Logs: Settings' page.
##|*MATCH=diag_logs_settings.php*
##|-PRIV

require("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");

define("CRON_TIB_USB_CMD_FILE", "/sbin/dhcplistcronusb.sh");
define("CRON_TIB_SMB_CMD_FILE", "/sbin/dhcplistcronsmb.sh");
define("CRON_TIB_FTP_CMD_FILE", "/sbin/dhcplistcronftp.sh");

function getMPDCRONSettings() {
  global $config;

  if (is_array($config['cron']['item'])) {
    for ($i = 0; $i < count($config['cron']['item']); $i++) {
      $item =& $config['cron']['item'][$i];

      if (strpos($item['command'], CRON_TIB_USB_CMD_FILE) !== false) {
        return array("ID" => $i, "ITEM" => $item);
      }
    }
  }

  return NULL;
}

function getSMBCRONSettings() {
  global $config;

  if (is_array($config['cron']['item'])) {
    for ($i = 0; $i < count($config['cron']['item']); $i++) {
      $item =& $config['cron']['item'][$i];

      if (strpos($item['command'], CRON_TIB_SMB_CMD_FILE) !== false) {
        return array("ID" => $i, "ITEM" => $item);
      }
    }
  }

  return NULL;
}

function getFTPCRONSettings() {
  global $config;

  if (is_array($config['cron']['item'])) {
    for ($i = 0; $i < count($config['cron']['item']); $i++) {
      $item =& $config['cron']['item'][$i];

      if (strpos($item['command'], CRON_TIB_FTP_CMD_FILE) !== false) {
        return array("ID" => $i, "ITEM" => $item);
      }
    }
  }

  return NULL;
}


function getMPDResetTimeFromConfig() {
  $itemhash = getMPDCRONSettings();
  $cronitem = $itemhash['ITEM'];

  if (isset($cronitem)) {

    return "{$cronitem['minute']} {$cronitem['hour']} {$cronitem['mday']} {$cronitem['month']} {$cronitem['wday']}";
  } else {
    return NULL;
  }
}


$pconfig['reverse'] = isset($config['syslog']['reverse']);
$pconfig['nentries'] = $config['syslog']['nentries'];
$pconfig['remoteserver'] = $config['syslog']['remoteserver'];
$pconfig['remoteserver2'] = $config['syslog']['remoteserver2'];
$pconfig['remoteserver3'] = $config['syslog']['remoteserver3'];
$pconfig['filter'] = isset($config['syslog']['filter']);
$pconfig['dhcp'] = isset($config['syslog']['dhcp']);
$pconfig['portalauth'] = isset($config['syslog']['portalauth']);
$pconfig['vpn'] = isset($config['syslog']['vpn']);
$pconfig['apinger'] = isset($config['syslog']['apinger']);
$pconfig['relayd'] = isset($config['syslog']['relayd']);
$pconfig['hostapd'] = isset($config['syslog']['hostapd']);
$pconfig['logall'] = isset($config['syslog']['logall']);
$pconfig['system'] = isset($config['syslog']['system']);
$pconfig['enable'] = isset($config['syslog']['enable']);
$pconfig['logdefaultblock'] = !isset($config['syslog']['nologdefaultblock']);
$pconfig['rawfilter'] = isset($config['syslog']['rawfilter']);
$pconfig['disablelocallogging'] = isset($config['syslog']['disablelocallogging']);

$pconfig['usbtibyedek'] = isset($config['syslog']['usbtibyedek']);
$pconfig['usbtibyedeksaat'] = $config['syslog']['usbtibyedeksaat'];

$pconfig['smbtibyedek'] = isset($config['syslog']['smbtibyedek']);
$pconfig['smbtibyedeksaat'] = $config['syslog']['smbtibyedeksaat'];
$pconfig['smbtibyedekip'] = $config['syslog']['smbtibyedekip'];
$pconfig['smbtibyedekdomain'] = $config['syslog']['smbtibyedekdomain'];
$pconfig['smbtibyedekpaylas'] = $config['syslog']['smbtibyedekpaylas'];
$pconfig['smbtibyedekkullanici'] = $config['syslog']['smbtibyedekkullanici'];
$pconfig['smbtibyedeksifre'] = $config['syslog']['smbtibyedeksifre'];

$pconfig['ftptibyedek'] = isset($config['syslog']['ftptibyedek']);
$pconfig['ftptibyedeksaat'] = $config['syslog']['ftptibyedeksaat'];
$pconfig['ftptibyedekip'] = $config['syslog']['ftptibyedekip'];
$pconfig['ftptibyedekkullanici'] = $config['syslog']['ftptibyedekkullanici'];
$pconfig['ftptibyedeksifre'] = $config['syslog']['ftptibyedeksifre'];


if (!$pconfig['nentries'])
	$pconfig['nentries'] = 50;

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable'] && !is_ipaddr($_POST['remoteserver'])) {
		$input_errors[] = gettext("#1. Sunucu &#304;&ccedil;in Ge&ccedil;erli Bir IP Adresi Tan&#305;mlanmal&#305;d&#305;r.");
	}
	if ($_POST['enable'] && $_POST['remoteserver2'] && !is_ipaddr($_POST['remoteserver2'])) {
		$input_errors[] = gettext("#2. Sunucu &#304;&ccedil;in Ge&ccedil;erli Bir IP Adresi Tan&#305;mlanmal&#305;d&#305;r.");
	}
	if ($_POST['enable'] && $_POST['remoteserver3'] && !is_ipaddr($_POST['remoteserver3'])) {
		$input_errors[] = gettext("#3.  Sunucu &#304;&ccedil;in Ge&ccedil;erli Bir IP Adresi Tan&#305;mlanmal&#305;d&#305;r.");
	}
	if ($_POST['enable'] && !is_ipaddr($_POST['remoteserver'])) {
		$input_errors[] = gettext("Ge&ccedil;erli Bir IP Adresi Tan&#305;mlanmal&#305;d&#305;r.");
	}

	if (($_POST['nentries'] < 5) || ($_POST['nentries'] > 2000)) {
		$input_errors[] = gettext("G&ouml;sterilecek Log Girdileri Say&#305;s&#305; 5 ile 2000 Aras&#305;nda Olabilir.");
	}

	if (!$input_errors) {
		$config['syslog']['reverse'] = $_POST['reverse'] ? true : false;
		$config['syslog']['nentries'] = (int)$_POST['nentries'];
		$config['syslog']['remoteserver'] = $_POST['remoteserver'];
		$config['syslog']['remoteserver2'] = $_POST['remoteserver2'];
		$config['syslog']['remoteserver3'] = $_POST['remoteserver3'];
		$config['syslog']['filter'] = $_POST['filter'] ? true : false;
		$config['syslog']['dhcp'] = $_POST['dhcp'] ? true : false;
		$config['syslog']['portalauth'] = $_POST['portalauth'] ? true : false;
		$config['syslog']['vpn'] = $_POST['vpn'] ? true : false;
		$config['syslog']['apinger'] = $_POST['apinger'] ? true : false;
		$config['syslog']['relayd'] = $_POST['relayd'] ? true : false;
		$config['syslog']['hostapd'] = $_POST['hostapd'] ? true : false;
		$config['syslog']['logall'] = $_POST['logall'] ? true : false;
		$config['syslog']['system'] = $_POST['system'] ? true : false;
		$config['syslog']['disablelocallogging'] = $_POST['disablelocallogging'] ? true : false;
		$config['syslog']['enable'] = $_POST['enable'] ? true : false;
		$oldnologdefaultblock = isset($config['syslog']['nologdefaultblock']);
		$config['syslog']['nologdefaultblock'] = $_POST['logdefaultblock'] ? false : true;
		$config['syslog']['rawfilter'] = $_POST['rawfilter'] ? true : false;
		if($config['syslog']['enable'] == false)
			unset($config['syslog']['remoteserver']);
			unset($config['syslog']['remoteserver2']);
			unset($config['syslog']['remoteserver3']);
		
		$config['syslog']['usbtibyedek'] = $_POST['usbtibyedek'] ? true : false;
		$config['syslog']['usbtibyedeksaat'] = $_POST['usbtibyedeksaat'];
		
		$config['syslog']['smbtibyedek'] = $_POST['smbtibyedek'] ? true : false;
		$config['syslog']['smbtibyedeksaat'] = $_POST['smbtibyedeksaat'];
		$config['syslog']['smbtibyedekip'] = $_POST['smbtibyedekip'];
		$config['syslog']['smbtibyedekdomain'] = $_POST['smbtibyedekdomain'];
		$config['syslog']['smbtibyedekpaylas'] = $_POST['smbtibyedekpaylas'];
		$config['syslog']['smbtibyedekkullanici'] = $_POST['smbtibyedekkullanici'];
		$config['syslog']['smbtibyedeksifre'] = $_POST['smbtibyedeksifre'];

		$config['syslog']['ftptibyedek'] = $_POST['ftptibyedek'] ? true : false;
		$config['syslog']['ftptibyedeksaat'] = $_POST['ftptibyedeksaat'];
		$config['syslog']['ftptibyedekip'] = $_POST['ftptibyedekip'];
		$config['syslog']['ftptibyedekkullanici'] = $_POST['ftptibyedekkullanici'];
		$config['syslog']['ftptibyedeksifre'] = $_POST['ftptibyedeksifre'];
		
		
		

		
		if ($_POST['usbtibyedek']==true) {		 
			if (! is_array($config['cron']['item'])) { $config['cron']['item'] = array(); }
			$itemhash = getMPDCRONSettings();
			$item = $itemhash['ITEM'];
			if (empty($item)) $item = array();
		 
			$item['minute'] = "1";
			$item['hour'] = '*/'.$_POST['usbtibyedeksaat'];
			$item['mday'] = "*";
			$item['month'] = "*";
			$item['wday'] = "*";
			$item['who'] = "root";
			$item['command'] = CRON_TIB_USB_CMD_FILE;
		 		 
		 
			if (isset($itemhash['ID'])) { $config['cron']['item'][$itemhash['ID']] = $item;	}
				else { $config['cron']['item'][] = $item; }
        
		}//eger usbtibyedek false ise
		else{				    
			if (empty($_POST['usbtibyedek'])) {
			/* test whether a cron item exists and unset() it if necessary */
			$itemhash = getMPDCRONSettings();
			$item = $itemhash['ITEM'];
			if (isset($item)) { unset($config['cron']['item'][$itemhash['ID']]); }
			}
		}	


		if ($_POST['smbtibyedek']==true) {		 
			if (! is_array($config['cron']['item'])) { $config['cron']['item'] = array(); }
			$itemhash = getSMBCRONSettings();
			$item = $itemhash['ITEM'];
			if (empty($item)) $item = array();
		 
			$item['minute'] = "1";
			$item['hour'] = '*/'.$_POST['smbtibyedeksaat'];
			$item['mday'] = "*";
			$item['month'] = "*";
			$item['wday'] = "*";
			$item['who'] = "root";
			$item['command'] = CRON_TIB_SMB_CMD_FILE;
		 
			if (isset($itemhash['ID'])) { $config['cron']['item'][$itemhash['ID']] = $item;	}
				else { $config['cron']['item'][] = $item; }
			
			conf_mount_rw();
			$fstab = <<<EOF
#!/bin/sh
tarih=`date "+%Y%m%d-%H%M%S"`
mkdir /var/mountsamba
cd /var/mountsamba
awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > ./dhcplog-\$tarih.txt
/usr/local/bin/smbclient \\\\\\\\{$_POST['smbtibyedekip']}\\\\{$_POST['smbtibyedekpaylas']} -U {$_POST['smbtibyedekkullanici']}%"{$_POST['smbtibyedeksifre']}" -W {$_POST['smbtibyedekdomain']} -N -c "prompt; put dhcplog-\$tarih.txt"
logger "Windows paylas&#305;m&#305;na dosya kopyaland&#305;."
cd ..
rm -rf /var/mountsamba
EOF;
			file_put_contents("/sbin/dhcplistcronsmb.sh", $fstab);
			exec("chmod 755 /sbin/dhcplistcronsmb.sh");
			
		}//eger smbtibyedek false ise
		else{				    
			if (empty($_POST['smbtibyedek'])) {
			/* test whether a cron item exists and unset() it if necessary */
			$itemhash = getSMBCRONSettings();
			$item = $itemhash['ITEM'];
			if (isset($item)) { unset($config['cron']['item'][$itemhash['ID']]); }
			}
		}	

		
		
		
		if ($_POST['ftptibyedek']==true) {		 
			if (! is_array($config['cron']['item'])) { $config['cron']['item'] = array(); }
			$itemhash = getFTPCRONSettings();
			$item = $itemhash['ITEM'];
			if (empty($item)) $item = array();
		 
			$item['minute'] = "1";
			$item['hour'] = '*/'.$_POST['ftptibyedeksaat'];
			$item['mday'] = "*";
			$item['month'] = "*";
			$item['wday'] = "*";
			$item['who'] = "root";
			$item['command'] = CRON_TIB_FTP_CMD_FILE;
		 
			if (isset($itemhash['ID'])) { $config['cron']['item'][$itemhash['ID']] = $item;	}
				else { $config['cron']['item'][] = $item; }
			
			conf_mount_rw();
			$fstab = <<<EOF
#!/bin/sh

tarih=`date "+%Y%m%d-%H%M%S"`

HOST='{$config['system']['hostname']}.{$config['system']['domain']}'
USER='{$_POST['ftptibyedekkullanici']}'
PASSWD='{$_POST['ftptibyedeksifre']}'
SERVER='{$_POST['ftptibyedekip']}'

mkdir /var/mountftp
cd /var/mountftp

awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > ./dhcplog\$HOST-\$tarih.txt

logger `ftp -n -v \$SERVER << EOT
ascii
user \$USER \$PASSWD
prompt
put dhcplog\$HOST-\$tarih.txt
bye
EOT`

cd ..
rm -rf /var/mountftp

EOF;

			file_put_contents("/sbin/dhcplistcronftp.sh", $fstab);
			exec("chmod 755 /sbin/dhcplistcronftp.sh");
			
		}//eger ftptibyedek false ise
		else{				    
			if (empty($_POST['ftptibyedek'])) {
			/* test whether a cron item exists and unset() it if necessary */
			$itemhash = getFTPCRONSettings();
			$item = $itemhash['ITEM'];
			if (isset($item)) { unset($config['cron']['item'][$itemhash['ID']]); }
			}
		}	
		
		
		
		

		/* crontab yeniden baslat */
		configure_cron();
		sigkillbypid("{$g['varrun_path']}/cron.pid", "HUP");
	  
		write_config();

		$retval = 0;
		config_lock();
		$retval = system_syslogd_start();
		if ($oldnologdefaultblock !== isset($config['syslog']['nologdefaultblock']))
			$retval |= filter_configure();
		config_unlock();

		$savemsg = get_std_save_message($retval);
	}
}

$pgtitle = array(gettext("Status"), gettext("System logs"), gettext("Settings"));
include("head.inc");

?>


<script language="JavaScript">
<!--
function enable_change(enable_over) {
	if (document.iform.enable.checked || enable_over) {
		document.iform.remoteserver.disabled = 0;
		document.iform.remoteserver2.disabled = 0;
		document.iform.remoteserver3.disabled = 0;
		document.iform.filter.disabled = 0;
		document.iform.dhcp.disabled = 0;
		document.iform.portalauth.disabled = 0;
		document.iform.vpn.disabled = 0;
		document.iform.apinger.disabled = 0;
		document.iform.relayd.disabled = 0;
		document.iform.hostapd.disabled = 0;
		document.iform.system.disabled = 0;
		document.iform.logall.disabled = 0;
	} else {
		document.iform.remoteserver.disabled = 1;
		document.iform.remoteserver2.disabled = 1;
		document.iform.remoteserver3.disabled = 1;
		document.iform.filter.disabled = 1;
		document.iform.dhcp.disabled = 1;
		document.iform.portalauth.disabled = 1;
		document.iform.vpn.disabled = 1;
		document.iform.apinger.disabled = 1;
		document.iform.relayd.disabled = 1;
		document.iform.hostapd.disabled = 1;
		document.iform.system.disabled = 1;
		document.iform.logall.disabled = 1;
	}
}
function check_everything() {
	if (document.iform.logall.checked) {
		document.iform.filter.disabled = 1;
		document.iform.filter.checked = false
		document.iform.dhcp.disabled = 1;
		document.iform.dhcp.checked = false
		document.iform.portalauth.disabled = 1;
		document.iform.portalauth.checked = false
		document.iform.vpn.disabled = 1;
		document.iform.vpn.checked = false
		document.iform.apinger.disabled = 1;
		document.iform.apinger.checked = false
		document.iform.relayd.disabled = 1;
		document.iform.relayd.checked = false
		document.iform.hostapd.disabled = 1;
		document.iform.hostapd.checked = false
		document.iform.system.disabled = 1;
		document.iform.system.checked = false
	} else {
		document.iform.filter.disabled = 0;
		document.iform.dhcp.disabled = 0;
		document.iform.portalauth.disabled = 0;
		document.iform.vpn.disabled = 0;
		document.iform.apinger.disabled = 0;
		document.iform.relayd.disabled = 0;
		document.iform.hostapd.disabled = 0;
		document.iform.system.disabled = 0;
	}
}
// -->
</script>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<form action="diag_logs_settings.php" method="post" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td>
<?php
	$tab_array = array();
	$tab_array[] = array(gettext("Sistem"), false, "diag_logs.php");
	$tab_array[] = array(gettext("Firewall"), false, "diag_logs_filter.php");
	$tab_array[] = array(gettext("DHCP"), false, "diag_logs_dhcp.php");
	$tab_array[] = array(gettext("Hotspot"), false, "diag_logs_auth.php");
	$tab_array[] = array(gettext("IPsec"), false, "diag_logs_ipsec.php");
	$tab_array[] = array(gettext("PPP"), false, "diag_logs_ppp.php");
	$tab_array[] = array(gettext("VPN"), false, "diag_logs_vpn.php");
	$tab_array[] = array(gettext("Y&uumlk Dengeleyici"), false, "diag_logs_relayd.php");
	$tab_array[] = array(gettext("OpenVPN"), false, "diag_logs_openvpn.php");
	$tab_array[] = array(gettext("OpenNTPD"), false, "diag_logs_ntpd.php");
	$tab_array[] = array(gettext("Wireless"), false, "diag_logs_wireless.php");
	$tab_array[] = array(gettext("Ayarlar"), true, "diag_logs_settings.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
	  <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
                      <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> <input name="reverse" type="checkbox" id="reverse" value="yes" <?php if ($pconfig['reverse']) echo "checked"; ?>>
			<strong><?=gettext("Log girdilerini ters s&#305;ralamaya g&ouml;re g&ouml;ster(yeni olanlar en tepede)");?></strong></td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
			<td width="78%" class="vtable"><?=gettext("Ka&ccedil; adet log girdisi g&ouml;sterilecek :")?>
                          <input name="nentries" id="nentries" type="text" class="formfld unknown" size="4" value="<?=htmlspecialchars($pconfig['nentries']);?>"></td>
                      </tr>
                      <tr>
                        <td valign="top" class="vtable">&nbsp;</td>
                        <td class="vtable"> <input name="logdefaultblock" type="checkbox" id="logdefaultblock" value="yes" <?php if ($pconfig['logdefaultblock']) echo "checked"; ?>>
			<strong><?=gettext("Kurallarla bloklanm&#305;&#351; paketleri logla");?></strong><br>
			  <?=gettext("Bilgi: E&#287;er se&ccedil;imi kald&#305;r&#305;san&#305;z bloklanm&#305;&#351; paketlerin kay&#305;tlar&#305; tutulmayacakt&#305;r.                          
                          Kurallar&#305;n ayarlar&#305;na etki etmeyecektir.");?></td>
                      </tr>
                      <tr>
                        <td valign="top" class="vtable">&nbsp;</td>
                        <td class="vtable"> <input name="rawfilter" type="checkbox" id="rawfilter" value="yes" <?php if ($pconfig['rawfilter']) echo "checked"; ?>>
			<strong><?=gettext("&#304;&#351;lenmemi&#351; filtrelere ait loglar&#305; g&ouml;ster");?></strong><br>
			  <?=gettext("Bilgi: E&#287;er bu alan se&ccedil;ilirse, paket filtresi taraf&#305;ndan &uuml;retilmi&#351; olan t&uuml;m loglar de&#287;i&#351;iklik yap&#305;lmadan g&ouml;sterilecektir. Bu i&#351;lem &ccedil;ok miktarda detayl&#305; bilginin loglanmas&#305;n&#305; sa&#287;lar");?></td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> <input name="disablelocallogging" type="checkbox" id="disablelocallogging" value="yes" <?php if ($pconfig['disablelocallogging']) echo "checked"; ?> onClick="enable_change(false)">
			  <strong><?=gettext("Log dosyalar&#305;n&#305; yerel disk &uuml;zerine yaz&#305;lmas&#305;n&#305; kapat");?></strong></td>
                       </tr>
                      <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> <input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
			  <strong><?=gettext("Uzak Syslog sunucusunu kullan");?></strong></td>
                      </tr>
                      <tr>
			<td width="22%" valign="top" class="vncell"><?=gettext("Uzak Syslog sunucusu");?></td>
                        <td width="78%" class="vtable"> 
							<table>
								<tr>
									<td>
										<?=gettext("Server") . " 1";?>
									</td>
									<td>
										<input name="remoteserver" id="remoteserver" type="text" class="formfld host" size="20" value="<?=htmlspecialchars($pconfig['remoteserver']);?>">
									</td>
								</tr>
								<tr>
									<td>
										<?=gettext("Server") . " 2";?>
									</td>
									<td>
										<input name="remoteserver2" id="remoteserver2" type="text" class="formfld host" size="20" value="<?=htmlspecialchars($pconfig['remoteserver2']);?>">
									</td>
								</tr>
								<tr>
									<td>
										<?=gettext("Server") . " 3";?>
									</td>
									<td>
										<input name="remoteserver3" id="remoteserver3" type="text" class="formfld host" size="20" value="<?=htmlspecialchars($pconfig['remoteserver3']);?>">
									</td>
								</tr>
								<tr>
									<td>&nbsp;
										
									</td>
									<td>
										<?=gettext("Uzak syslog sunucunun IP adresi");?>
									</td>
							</table>
					 	  <input name="system" id="system" type="checkbox" value="yes" onClick="enable_change(false)" <?php if ($pconfig['system']) echo "checked"; ?>>
						  <?=gettext("Sistem olaylar&#305;");?><br>
						  <input name="filter" id="filter" type="checkbox" value="yes" <?php if ($pconfig['filter']) echo "checked"; ?>>
						  <?=gettext("Firewall olaylar&#305;");?><br>
						  <input name="dhcp" id="dhcp" type="checkbox" value="yes" <?php if ($pconfig['dhcp']) echo "checked"; ?>>
						  <?=gettext("DHCP servis olaylar&#305;");?><br>
						  <input name="portalauth" id="portalauth" type="checkbox" value="yes" <?php if ($pconfig['portalauth']) echo "checked"; ?>>
						  <?=gettext("Hotspot kimlik do&#287;rulamalar&#305;");?><br>
						  <input name="vpn" id="vpn" type="checkbox" value="yes" <?php if ($pconfig['vpn']) echo "checked"; ?>>
						  <?=gettext("VPN (PPTP, IPsec, OpenVPN) olaylar&#305;");?><br>
						  <input name="apinger" id="apinger" type="checkbox" value="yes" <?php if ($pconfig['apinger']) echo "checked"; ?>>
						  <?=gettext("A&#287; ge&ccedil;idi izleme olaylar&#305;");?><br>
						  <input name="relayd" id="relayd" type="checkbox" value="yes" <?php if ($pconfig['relayd']) echo "checked"; ?>>
						  <?=gettext("Y&uuml;k dengeleyici olaylar&#305;");?><br>
						  <input name="hostapd" id="hostapd" type="checkbox" value="yes" <?php if ($pconfig['hostapd']) echo "checked"; ?>>
						  <?=gettext("Wireless olaylar&#305;");?><br>
                          <br> <input name="logall" id="logall" type="checkbox" value="yes" <?php if ($pconfig['logall']) echo "checked"; ?> onClick="check_everything();">
						  <?=gettext("Her&#351;ey");?>
                        </td>
                      </tr>
                                            <tr>
                        <td width="22%" height="53" valign="top">&nbsp;</td>
						<td width="78%"><strong><span class="red">Bilgi:</span></strong><br>
						Yerel syslog log bilgilerini UDP 514 numaral&#305; portu kullanarak g&ouml;nderir.
						Uzak syslog sunucunun bu &#351;ekilde yap&#305;land&#305;r&#305;lm&#305;&#351; oldu&#287;undan emin olunuz ayn&#305; zamanda
						<?=$g['product_name']?> sunucusundan gelen log bilgilerini kabul etmek i&ccedil;in ayarl&#305; oldu&#287;undan emin olunuz.  
						  
						  </td>
                      </tr>

 <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> 
						<input name="usbtibyedek" type="checkbox" id="usbtibyedek" value="yes" <?php if ($pconfig['usbtibyedek']) echo "checked"; ?>>
                          <strong>T&#304;B formatl&#305; DHCP kay&#305;tlar&#305;n&#305;, USB disk &uuml;zerine yedekle (FAT32 formatl&#305; olmal&#305;d&#305;r)</strong>
						  <br>
						  Ka&ccedil; saatte bir yedek al&#305;ns&#305;n : 
						  <input name="usbtibyedeksaat" id="usbtibyedeksaat" type="text" 
						  class="formfld" size="2" value="<?=htmlspecialchars($pconfig['usbtibyedeksaat']);?>">
						  
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>
						  Yaz&#305;labilecek de&#287;erler 1, 2, 3, 4, 6, 8, 12 &#351;eklindedir. &Ouml;rnek olarak 12 yaz&#305;lmas&#305;
						  durumunda g&uuml;nde iki kere, 4 yaz&#305;lmas&#305; durumunda g&uuml;nde alt&#305; kere yedek alacakt&#305;r.
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  DHCP sunucusunun yerel a&#287; &uuml;zerinde da&#287;&#305;tt&#305;&#287;&#305; IP ve MAC adresi bilgileri T&#304;B yaz&#305;m kurallar&#305;na uygun
						  bir &#351;ekilde FAT32 formatl&#305; disk &uuml;zerine belirtilen saat aral&#305;klar&#305;nda kopyalan&#305;r.<br>
						  Bu alan se&ccedil;ili oldu&#287;u s&uuml;rece USB disk tak&#305;l&#305; olmal&#305;d&#305;r. Disk &uuml;zerinde tek FAT32 alan&#305; olmal&#305;d&#305;r.
						  </td>
                      </tr>
					  
					 <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> 
						  <p>
						    <input name="smbtibyedek" type="checkbox" id="smbtibyedek" value="yes" <?php if ($pconfig['smbtibyedek']) echo "checked"; ?>>
						    <strong>T&#304;B formatl&#305; DHCP kay&#305;tlar&#305;n&#305; Windows payla&#351;&#305;m&#305;na kopyala</strong>
						    <br>
						    
						    Ka&ccedil; saatte bir yedek al&#305;ns&#305;n : 
						    <input name="smbtibyedeksaat" id="smbtibyedeksaat" type="text" 
						  class="formfld" size="2" value="<?=htmlspecialchars($pconfig['smbtibyedeksaat']);?>">
						    
						    <br>
						    <strong><span class="red">Bilgi:</span></strong>
						    <br>
						    Yaz&#305;labilecek de&#287;erler 1, 2, 3, 4, 6, 8, 12 &#351;eklindedir. &Ouml;rnek olarak 12 yaz&#305;lmas&#305;
						    durumunda g&uuml;nde iki kere, 4 yaz&#305;lmas&#305; durumunda g&uuml;nde alt&#305; kere yedek alacakt&#305;r.
						    <br>
						    <br>
						    <strong>Windows bilgisayar&#305;n IP adresi: </strong><br>
						    <input name="smbtibyedekip" id="smbtibyedekip" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedekip']);?>">
						    <br>
						    <strong><span class="red">Bilgi:</span></strong>
						    <br>
						    Payla&#351;&#305;m&#305;n oldu&#287;u bilgisayar&#305;n IP numaras&#305; &ouml;rne&#287;e uygun olarak yaz&#305;lmal&#305;d&#305;r.<br>
						    &Ouml;rnek olarak \\10.0.0.10\Paylas olan bir &ouml;rnek payla&#351;&#305;mda IP numaras&#305; 10.0.0.10 de&#287;eridir.
						    <br>
						    <br>
						    <strong>Payla&#351;&#305;lan dizinin ad&#305; :</strong><br>
						    <input name="smbtibyedekpaylas" id="smbtibyedekpaylas" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedekpaylas']);?>">
						    <br>
						    <strong><span class="red">Bilgi:</span></strong>
						    <br>						  
						    Payla&#351;&#305;m yap&#305;lan dizinin ad&#305; yaz&#305;l&#305;r. <br>
						    &Ouml;rnek olarak \\10.0.0.10\Paylas olan bir &ouml;rnek payla&#351;&#305;mda, payla&#351;&#305;lan dizin ad&#305; "Paylas" de&#287;eridir.					      </p>
						  <p><strong>Domain (Varsa) :</strong><br>
                            <input name="smbtibyedekdomain" id="smbtibyedekdomain" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedekdomain']);?>">
                            <br>
                            <strong><span class="red">Bilgi:</span></strong> <br>
Dizin e&#287;er domain i&ccedil;erisinde ise smbclient hata verecektir. <br>
(Varsa) Domain bilginizi giriniz. <br>
<br>
						    <br>
						    <strong>Kullan&#305;c&#305; ad&#305; :</strong><br> 
						    <input name="smbtibyedekkullanici" id="smbtibyedekkullanici" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedekkullanici']);?>">
						    <br>
						    <strong><span class="red">Bilgi:</span></strong>
						    <br>						  
						    Windows &uuml;zerinde giri&#351; yapmak i&ccedil;in kullan&#305;lacak olan kullan&#305;c&#305;n&#305;n ad&#305; yaz&#305;l&#305;r. <br>
						    &Ouml;rnek sysuser, admin, administrator, ali ...
						    <br>
						    <br>
						    <strong>&#350;ifre : </strong><br>
						    <input name="smbtibyedeksifre" id="smbtibyedeksifre" type="password" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedeksifre']);?>">
						    <br>
						    <strong><span class="red">Bilgi:</span></strong>
						    <br>						  
						    Windows &uuml;zerinde giri&#351; yapmak i&ccedil;in kullan&#305;lacak olan kullan&#305;c&#305;n&#305;n &#351;ifresi yaz&#305;l&#305;r. <br>						  
						    <br>
					      </p></td>
                      </tr>
					  
					  
					 <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> 
						<input name="ftptibyedek" type="checkbox" id="ftptibyedek" value="yes" <?php if ($pconfig['ftptibyedek']) echo "checked"; ?>>
                          <strong>T&#304;B formatl&#305; DHCP kay&#305;tlar&#305;n&#305; FTP sunucuya kopyala</strong>
						  <br>
						  
						  Ka&ccedil; saatte bir yedek al&#305;ns&#305;n : 
						  <input name="ftptibyedeksaat" id="ftptibyedeksaat" type="text" 
						  class="formfld" size="2" value="<?=htmlspecialchars($pconfig['ftptibyedeksaat']);?>">
						  
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>
						  Yaz&#305;labilecek de&#287;erler 1, 2, 3, 4, 6, 8, 12 &#351;eklindedir. &Ouml;rnek olarak 12 yaz&#305;lmas&#305;
						  durumunda g&uuml;nde iki kere, 4 yaz&#305;lmas&#305; durumunda g&uuml;nde alt&#305; kere yedek alacakt&#305;r.
						  <br>
						  <br>
						  <strong>FTP sunucu ad&#305; veya IP adresi: </strong><br>
						  <input name="ftptibyedekip" id="ftptibyedekip" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['ftptibyedekip']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>
						  FTP sunucunun IP adresi veya ad&#305; yaz&#305;lmal&#305;d&#305;r.<br>
						  &Ouml;rnek : 10.0.0.10
						  <br>
						  <br>
						  <strong>Kullan&#305;c&#305; ad&#305; :</strong><br> 
						  <input name="ftptibyedekkullanici" id="ftptibyedekkullanici" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['ftptibyedekkullanici']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  FTP sunucuya giri&#351; yapmak i&ccedil;in kullan&#305;lacak olan kullan&#305;c&#305;n&#305;n ad&#305; yaz&#305;l&#305;r. <br>
						  &Ouml;rnek sysuser, admin, administrator, ali, ftp, ftpuser ...
						  <br>
						  <br>
						  <strong>&#350;ifre : </strong><br>
						  <input name="ftptibyedeksifre" id="ftptibyedeksifre" type="password" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['ftptibyedeksifre']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  FTP sunucuya giri&#351; yapmak i&ccedil;in kullan&#305;lacak olan kullan&#305;c&#305;n&#305;n &#351;ifresi yaz&#305;l&#305;r. <br>						  
						  <br>
						  
						  
						  </td>
                      </tr>

					  
					  
                      <tr>
                        <td width="22%" valign="top">&nbsp;</td>
                        <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="Kaydet" onClick="enable_change(true)">
                        </td>
                      </tr>

					  
					  
                    </table>
	</div>
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

