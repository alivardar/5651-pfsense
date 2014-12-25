<?php

require("guiconfig.inc");

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
$pconfig['filter'] = isset($config['syslog']['filter']);
$pconfig['dhcp'] = isset($config['syslog']['dhcp']);
$pconfig['portalauth'] = isset($config['syslog']['portalauth']);
$pconfig['vpn'] = isset($config['syslog']['vpn']);
$pconfig['system'] = isset($config['syslog']['system']);
$pconfig['logall'] = isset($config['syslog']['logall']);
$pconfig['enable'] = isset($config['syslog']['enable']);
$pconfig['logdefaultblock'] = !isset($config['syslog']['nologdefaultblock']);
$pconfig['rawfilter'] = isset($config['syslog']['rawfilter']);
$pconfig['disablelocallogging'] = isset($config['syslog']['disablelocallogging']);

$pconfig['usbtibyedek'] = isset($config['syslog']['usbtibyedek']);
$pconfig['usbtibyedeksaat'] = $config['syslog']['usbtibyedeksaat'];

$pconfig['smbtibyedek'] = isset($config['syslog']['smbtibyedek']);
$pconfig['smbtibyedeksaat'] = $config['syslog']['smbtibyedeksaat'];
$pconfig['smbtibyedekip'] = $config['syslog']['smbtibyedekip'];
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
		$input_errors[] = "Geçerli bir IP adresi tanımlanmalıdır.";
	}
	if (($_POST['nentries'] < 5) || ($_POST['nentries'] > 2000)) {
		$input_errors[] = "Gösterilecek Log girdileri sayısı 5 ile 2000 arasında olabilir.";
	}

	if (!$input_errors) {
		$config['syslog']['reverse'] = $_POST['reverse'] ? true : false;
		$config['syslog']['nentries'] = (int)$_POST['nentries'];
		$config['syslog']['remoteserver'] = $_POST['remoteserver'];
		$config['syslog']['filter'] = $_POST['filter'] ? true : false;
		$config['syslog']['dhcp'] = $_POST['dhcp'] ? true : false;
		$config['syslog']['portalauth'] = $_POST['portalauth'] ? true : false;
		$config['syslog']['vpn'] = $_POST['vpn'] ? true : false;
		$config['syslog']['logall'] = $_POST['logall'] ? true : false;
		$config['syslog']['system'] = $_POST['system'] ? true : false;
		$config['syslog']['disablelocallogging'] = $_POST['disablelocallogging'] ? true : false;
		$config['syslog']['enable'] = $_POST['enable'] ? true : false;
		$oldnologdefaultblock = isset($config['syslog']['nologdefaultblock']);
		$config['syslog']['nologdefaultblock'] = $_POST['logdefaultblock'] ? false : true;
		$config['syslog']['rawfilter'] = $_POST['rawfilter'] ? true : false;
		
		$config['syslog']['usbtibyedek'] = $_POST['usbtibyedek'] ? true : false;
		$config['syslog']['usbtibyedeksaat'] = $_POST['usbtibyedeksaat'];
		
		$config['syslog']['smbtibyedek'] = $_POST['smbtibyedek'] ? true : false;
		$config['syslog']['smbtibyedeksaat'] = $_POST['smbtibyedeksaat'];
		$config['syslog']['smbtibyedekip'] = $_POST['smbtibyedekip'];
		$config['syslog']['smbtibyedekpaylas'] = $_POST['smbtibyedekpaylas'];
		$config['syslog']['smbtibyedekkullanici'] = $_POST['smbtibyedekkullanici'];
		$config['syslog']['smbtibyedeksifre'] = $_POST['smbtibyedeksifre'];

		$config['syslog']['ftptibyedek'] = $_POST['ftptibyedek'] ? true : false;
		$config['syslog']['ftptibyedeksaat'] = $_POST['ftptibyedeksaat'];
		$config['syslog']['ftptibyedekip'] = $_POST['ftptibyedekip'];
		$config['syslog']['ftptibyedekkullanici'] = $_POST['ftptibyedekkullanici'];
		$config['syslog']['ftptibyedeksifre'] = $_POST['ftptibyedeksifre'];
		
		
		if($config['syslog']['enable'] == false)
			unset($config['syslog']['remoteserver']);

		
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
		 
			$item['minute'] = "3";
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

mount -uw /
mkdir /var/zaman_damgala
cd /var/zaman_damgala

awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > /var/zaman_damgala/dhcpdagitimlogtibformat.txt
sleep 3
/sbin/logzamandamgasi.sh
sleep 5

cd /var/zaman_damgali_loglar

/usr/local/bin/smbclient \\\\\\\\{$_POST['smbtibyedekip']}\\\\{$_POST['smbtibyedekpaylas']} -U {$_POST['smbtibyedekkullanici']}%"{$_POST['smbtibyedeksifre']}" -N -c "prompt; mput *.tar.gz"
logger "Windows paylasimina dosya kopyalandi."

sleep 5

cd ..

rm -rf /var/zaman_damgala
rm -rf /var/zaman_damgali_loglar
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
		 
			$item['minute'] = "5";
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

#tarih=`date "+%Y%m%d-%H%M%S"`

HOST='{$config['system']['hostname']}.{$config['system']['domain']}'
USER='{$_POST['ftptibyedekkullanici']}'
PASSWD='{$_POST['ftptibyedeksifre']}'
SERVER='{$_POST['ftptibyedekip']}'

mount -uw /
mkdir /var/zaman_damgala
cd /var/zaman_damgala

awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > ./dhcpdagitimlogtibformat.txt
sleep 1
/sbin/logzamandamgasi.sh
sleep 5

cd /var/zaman_damgali_loglar

logger `ftp -n -v \$SERVER << EOT
ascii
user \$USER \$PASSWD
prompt
mput *.tar.gz
bye
EOT`

sleep 5

rm -rf /var/zaman_damgala
rm -rf /var/zaman_damgali_loglar

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

$pgtitle = "Tanımlama: Sistem Günlük Dosyaları: Ayarlar";
include("head.inc");

?>


<script language="JavaScript">
<!--
function enable_change(enable_over) {
	if (document.iform.enable.checked || enable_over) {
		document.iform.remoteserver.disabled = 0;
		document.iform.filter.disabled = 0;
		document.iform.dhcp.disabled = 0;
		document.iform.portalauth.disabled = 0;
		document.iform.vpn.disabled = 0;
		document.iform.system.disabled = 0;
	} else {
		document.iform.remoteserver.disabled = 1;
		document.iform.filter.disabled = 1;
		document.iform.dhcp.disabled = 1;
		document.iform.portalauth.disabled = 1;
		document.iform.vpn.disabled = 1;
		document.iform.system.disabled = 1;
	}
}
// -->

function openwindow(targeturl, windowname) {
newwin =
window.open(targeturl,windowname,"height=480,width=640,scrollbars,resizable")
}

</script>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="diag_logs_settings.php" method="post" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td>
<?php
	$tab_array = array();
	$tab_array[] = array("Sistem", false, "diag_logs.php");
	$tab_array[] = array("Firewall", false, "diag_logs_filter.php");
	$tab_array[] = array("DHCP", false, "diag_logs_dhcp.php");
	$tab_array[] = array("Hotspot", false, "diag_logs_auth.php");
	$tab_array[] = array("IPsec", false, "diag_logs_ipsec.php");
	$tab_array[] = array("PPTP", false, "diag_logs_vpn.php");
	$tab_array[] = array("Yük Dengeleyici", false, "diag_logs_slbd.php");
	$tab_array[] = array("OpenVPN", false, "diag_logs_openvpn.php");
	$tab_array[] = array("OpenNTPD", false, "diag_logs_ntpd.php");
	$tab_array[] = array("Ayarlar", true, "diag_logs_settings.php");
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
                          <strong>Log girdilerini ters sıralamaya göre göster(yeni olanlar en tepede)</strong></td>
                      </tr>
					  
                      <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable">Kaç adet log girdisi gösterilecek :
                          <input name="nentries" id="nentries" type="text" class="formfld" size="4" value="<?=htmlspecialchars($pconfig['nentries']);?>"></td>
                      </tr>
                      <tr>
                        <td valign="top" class="vtable">&nbsp;</td>
                        <td class="vtable"> <input name="logdefaultblock" type="checkbox" id="logdefaultblock" value="yes" <?php if ($pconfig['logdefaultblock']) echo "checked"; ?>>
                          <strong>Kurallarla bloklanmış paketleri logla</strong><br>
                          Bilgi: Eğer seçimi kaldırısanız bloklanmış paketlerin kayıtları tutulmayacaktır.                          
                          Kuralların ayarlarına etki etmeyecektir.
						  </td>
                      </tr>
                      <tr>
                        <td valign="top" class="vtable">&nbsp;</td>
                        <td class="vtable"> 
						<input name="rawfilter" type="checkbox" id="rawfilter" value="yes" <?php if ($pconfig['rawfilter']) echo "checked"; ?>>
                          <strong>İşlenmemiş filtrelere ait logları göster</strong><br>
                          Bilgi: Eğer bu alan seçilirse, paket filtresi tarafından üretilmiş olan tüm loglar değişiklik yapılmadan gösterilecektir. Bu işlem çok miktarda detaylı bilginin loglanmasını sağlar.</td>
                      </tr>


					  <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> <input name="disablelocallogging" type="checkbox" id="disablelocallogging" value="yes" <?php if ($pconfig['disablelocallogging']) echo "checked"; ?> onClick="enable_change(false)">
                          <strong>Log dosyalarını yerel disk üzerine yazılmasını kapat</strong></td>
                       </tr>

                      <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> <input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
                          <strong>Uzak Syslog sunucusunu kullan</strong></td>
                      </tr>
					  
                      <tr>
                        <td width="22%" valign="top" class="vncell">Uzak Syslog sunucusu</td>
                        <td width="78%" class="vtable"> 
						<input name="remoteserver" id="remoteserver" type="text" class="formfld" size="20" value="<?=htmlspecialchars($pconfig['remoteserver']);?>">
                          <br>
                          Uzak syslog sunucunun IP adresi<br> <br> <input name="system" id="system" type="checkbox" value="yes" onclick="enable_change(false)" <?php if ($pconfig['system']) echo "checked"; ?>>
                          Sistem olayları <br> <input name="filter" id="filter" type="checkbox" value="yes" <?php if ($pconfig['filter']) echo "checked"; ?>>
                          Firewall olayları<br> <input name="dhcp" id="dhcp" type="checkbox" value="yes" <?php if ($pconfig['dhcp']) echo "checked"; ?>>
                          DHCP servis olayları<br> <input name="portalauth" id="portalauth" type="checkbox" value="yes" <?php if ($pconfig['portalauth']) echo "checked"; ?>>
                          Hotspot kimlik doğrulamaları <br> <input name="vpn" id="vpn" type="checkbox" value="yes" <?php if ($pconfig['vpn']) echo "checked"; ?>>
                          VPN olayları
						<br> <input name="logall" id="logall" type="checkbox" value="yes" <?php if ($pconfig['logall']) echo "checked"; ?>>
                          Hepsi
						  <br>
						  <br>
						<strong><span class="red">Bilgi:</span></strong><br>
						Yerel syslog log bilgilerini UDP 514 numaralı portu kullanarak gönderir.
						Uzak syslog sunucunun bu şekilde yapılandırılmış olduğundan emin olunuz aynı zamanda
						<?=$g['product_name']?> sunucusundan gelen log bilgilerini kabul etmek için ayarlı olduğundan emin olunuz.  
						  
						  </td>
					  </tr>

					 <?php 
					 /*
					 <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> 
                          <strong><span class="red">Sertifaka Üretme</span></strong>
						  <br>
						  <a href="javascript:if(openwindow('zaman_damgasi_certs.php') == false) alert('Popup bloklayici algilandi.');" >
						  Sertifika Olusturmak için burayı tıklayınız.</a> 
						  <br>
						  Zaman damgalarını kullanmak için öncelikle bir kereye mahsus olarak size özel
						  zaman damgası oluşturulmaıdır.						  				 
						  </td>
                      </tr>
					  */
					?>
					  
					<tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> 
						<input name="usbtibyedek" type="checkbox" id="usbtibyedek" value="yes" <?php if ($pconfig['usbtibyedek']) echo "checked"; ?>>
                          <strong>Zaman Damgalı TİB uyumlu DHCP kayıtlarını, USB disk üzerine yedekle (FAT32 formatlı olmalıdır)</strong>
						  <br>
						  Kaç saatte bir yedek alınsın : 
						  <input name="usbtibyedeksaat" id="usbtibyedeksaat" type="text" 
						  class="formfld" size="2" value="<?=htmlspecialchars($pconfig['usbtibyedeksaat']);?>">
						  
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>
						  Yazılabilecek değerler 1, 2, 3, 4, 6, 8, 12 şeklindedir. Örnek olarak 12 yazılması
						  durumunda günde iki kere, 4 yazılması durumunda günde altı kere yedek alacaktır.
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  DHCP sunucusunun yerel ağ üzerinde dağıttığı IP ve MAC adresi bilgileri TİB yazım kurallarına uygun
						  bir şekilde FAT32 formatlı disk üzerine belirtilen saat aralıklarında kopyalanır.<br>
						  Bu alan seçili olduğu sürece USB disk takılı olmalıdır. Disk üzerinde tek FAT32 alanı olmalıdır.
						  </td>
                      </tr>
					  
					 <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> 
						<input name="smbtibyedek" type="checkbox" id="smbtibyedek" value="yes" <?php if ($pconfig['smbtibyedek']) echo "checked"; ?>>
                          <strong>Zaman Damgalı TİB uyumlu DHCP kayıtlarını Windows paylaşımına kopyala</strong>
						  <br>
						  
						  Kaç saatte bir yedek alınsın : 
						  <input name="smbtibyedeksaat" id="smbtibyedeksaat" type="text" 
						  class="formfld" size="2" value="<?=htmlspecialchars($pconfig['smbtibyedeksaat']);?>">
						  
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>
						  Yazılabilecek değerler 1, 2, 3, 4, 6, 8, 12 şeklindedir. Örnek olarak 12 yazılması
						  durumunda günde iki kere, 4 yazılması durumunda günde altı kere yedek alacaktır.
						  <br>
						  <br>
						  <strong>Windows bilgisayarın IP adresi: </strong><br>
						  <input name="smbtibyedekip" id="smbtibyedekip" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedekip']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>
						  Paylaşımın olduğu bilgisayarın IP numarası örneğe uygun olarak yazılmalıdır.<br>
						  Örnek olarak \\10.0.0.10\Paylas olan bir örnek paylaşımda IP numarası 10.0.0.10 değeridir.
						  <br>
						  <br>
						  <strong>Paylaşılan dizinin adı :</strong><br>
						  <input name="smbtibyedekpaylas" id="smbtibyedekpaylas" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedekpaylas']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  Paylaşım yapılan dizinin adı yazılır. <br>
						  Örnek olarak \\10.0.0.10\Paylas olan bir örnek paylaşımda, paylaşılan dizin adı "Paylas" değeridir.
						  <br>
						  <br>
						  <strong>Kullanıcı adı :</strong><br> 
						  <input name="smbtibyedekkullanici" id="smbtibyedekkullanici" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedekkullanici']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  Windows üzerinde giriş yapmak için kullanılacak olan kullanıcının adı yazılır. <br>
						  Örnek sysuser, admin, administrator, ali ...
						  <br>
						  <br>
						  <strong>Şifre : </strong><br>
						  <input name="smbtibyedeksifre" id="smbtibyedeksifre" type="password" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['smbtibyedeksifre']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  Windows üzerinde giriş yapmak için kullanılacak olan kullanıcının şifresi yazılır. <br>						  
						  <br>
						  
						  
						  </td>
                      </tr>
					  
					  
					 <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable"> 
						<input name="ftptibyedek" type="checkbox" id="ftptibyedek" value="yes" <?php if ($pconfig['ftptibyedek']) echo "checked"; ?>>
                          <strong>Zaman Damgalı TİB uyumlu DHCP kayıtlarını FTP sunucuya kopyala</strong>
						  <br>
						  
						  Kaç saatte bir yedek alınsın : 
						  <input name="ftptibyedeksaat" id="ftptibyedeksaat" type="text" 
						  class="formfld" size="2" value="<?=htmlspecialchars($pconfig['ftptibyedeksaat']);?>">
						  
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>
						  Yazılabilecek değerler 1, 2, 3, 4, 6, 8, 12 şeklindedir. Örnek olarak 12 yazılması
						  durumunda günde iki kere, 4 yazılması durumunda günde altı kere yedek alacaktır.
						  <br>
						  <br>
						  <strong>FTP sunucu adı veya IP adresi: </strong><br>
						  <input name="ftptibyedekip" id="ftptibyedekip" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['ftptibyedekip']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>
						  FTP sunucunun IP adresi veya adı yazılmalıdır.<br>
						  Örnek : 10.0.0.10
						  <br>
						  <br>
						  <strong>Kullanıcı adı :</strong><br> 
						  <input name="ftptibyedekkullanici" id="ftptibyedekkullanici" type="text" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['ftptibyedekkullanici']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  FTP sunucuya giriş yapmak için kullanılacak olan kullanıcının adı yazılır. <br>
						  Örnek sysuser, admin, administrator, ali, ftp, ftpuser ...
						  <br>
						  <br>
						  <strong>Şifre : </strong><br>
						  <input name="ftptibyedeksifre" id="ftptibyedeksifre" type="password" 
						  class="formfld" size="15" value="<?=htmlspecialchars($pconfig['ftptibyedeksifre']);?>">
						  <br>
						  <strong><span class="red">Bilgi:</span></strong>
						  <br>						  
						  FTP sunucuya giriş yapmak için kullanılacak olan kullanıcının şifresi yazılır. <br>						  
						  <br>
						  
						  
						  </td>
                      </tr>

					  
					  
                      <tr>
                        <td width="22%" valign="top">&nbsp;</td>
                        <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="Kaydet" onclick="enable_change(true)">
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
