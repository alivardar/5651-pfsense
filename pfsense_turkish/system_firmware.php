<?php
/* $Id$ */
/*
	system_firmware.php
	Copyright (C) 2008 Scott Ullrich <sullrich@gmail.com>
	All rights reserved.
	
	originally part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	
	
	originally part of m0n0wall (http://m0n0.ch/wall)

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

$d_isfwfile = 1;
require_once("globals.inc");
require_once("guiconfig.inc");

$curcfg = $config['system']['firmware'];

require_once("xmlrpc_client.inc");

/* Allow additional execution time 0 = no limit. */
ini_set('max_execution_time', '3600');
ini_set('max_input_time', '3600');

function file_is_for_platform($filename, $ul_name) {
	global $g;
	if($g['platform'] == "nanobsd") {
		if(stristr($ul_name, "nanobsd"))
			return true;
		else
			return false;		
	}
	exec("tar xzf $filename -C /tmp/ etc/platform");
	if(!file_exists("/tmp/etc/platform")) 
		return false;
	$upgrade_is_for_platform = trim(file_get_contents("/tmp/etc/platform"));
	if($g['platform'] == $upgrade_is_for_platform) {
		unlink("/tmp/etc/platform");
		return true;
	}
	return false;
}

/* if upgrade in progress, alert user */
if(file_exists($d_firmwarelock_path)) {
	$pgtitle = "System: Firmware: Manual Update";
	include("head.inc");
	echo "<body link=\"#0000CC\" vlink=\"#0000CC\" alink=\"#0000CC\">\n";
	include("fbegin.inc");
	echo "<div>\n";
	print_info_box("Güncelleme işlemi şu anda devam ediyor.<p>Yazılım işlem tamamlanınca yeniden başlayacaktır.<p><center><img src='/themes/{$g['theme']}/images/icons/icon_fw-update.gif'>");
	echo "</div>\n";
	include("fend.inc");
	echo "</body>";
	echo "</html>";
	exit;
}

if($_POST['kerneltype']) {
	system("echo {$_POST['kerneltype']} > /boot/kernel/pfsense_kernel.txt");
}

/* Handle manual upgrade */
if ($_POST && !file_exists($d_firmwarelock_path)) {

	unset($input_errors);
	unset($sig_warning);

	if (stristr($_POST['Submit'], "Enable"))
		$mode = "enable";
	else if (stristr($_POST['Submit'], "Disable"))
		$mode = "disable";
	else if (stristr($_POST['Submit'], "Upgrade") || $_POST['sig_override'])
		$mode = "upgrade";
	else if ($_POST['sig_no']) {
		if(file_exists("{$g['upload_path']}/firmware.tgz"))
				unlink("{$g['upload_path']}/firmware.tgz");
	}
	if ($mode) {
		if ($mode == "enable") {
			conf_mount_rw();
			touch($d_fwupenabled_path);
		} else if ($mode == "disable") {
			conf_mount_ro();
			if (file_exists($d_fwupenabled_path))
				unlink($d_fwupenabled_path);
		} else if ($mode == "upgrade") {
			if (is_uploaded_file($_FILES['ulfile']['tmp_name'])) {
				/* verify firmware image(s) */
				if (file_is_for_platform($_FILES['ulfile']['tmp_name'], $_FILES['ulfile']['name']) == false && !$_POST['sig_override'])
					$input_errors[] = "Yüklenen imaj dosyası bu platforma ait değil! ({$g['platform']}).";
				else if (!file_exists($_FILES['ulfile']['tmp_name'])) {
					/* probably out of memory for the MFS */
					$input_errors[] = "Imaj yükleme hatası (Hafıza kaynaklı?)";
					exec_rc_script("/etc/rc.firmware disable");
					if (file_exists($d_fwupenabled_path))
						unlink($d_fwupenabled_path);
				} else {
					/* move the image so PHP won't delete it */
					rename($_FILES['ulfile']['tmp_name'], "{$g['upload_path']}/firmware.tgz");

					/* check digital signature */
					$sigchk = verify_digital_signature("{$g['upload_path']}/firmware.tgz");

					if ($sigchk == 1)
						$sig_warning = "Imaj ait dijital imza hatalı!";
					else if ($sigchk == 2)
						$sig_warning = "Imaj dijital olarak imzalanmamış!";
					else if (($sigchk == 3) || ($sigchk == 4))
						$sig_warning = "Imaj imza belirlemede hata ile karşılaşıldı!";

					if (!verify_gzip_file("{$g['upload_path']}/firmware.tgz")) {
						$input_errors[] = "Imaj dosyası bozuk!";
						unlink("{$g['upload_path']}/firmware.tgz");
					}
				}
			}
			
			run_plugins("/usr/local/pkg/firmware_upgrade");
			
            /* Check for input errors, firmware locks, warnings, then check for firmware if sig_override is set */
            if (!$input_errors && !file_exists($d_firmwarelock_path) && (!$sig_warning || $_POST['sig_override'])) {
                    if (file_exists("{$g['upload_path']}/firmware.tgz")) {
                            /* fire up the update script in the background */
                            touch($d_firmwarelock_path);
                            $savemsg = "Güncelleme yüklendi. Birazdan otomatik olarak yeniden başlatılacaktır.";
							if(stristr($_FILES['ulfile']['name'],"nanobsd") or $_POST['isnano'] == "yes")
								mwexec_bg("/etc/rc.firmware pfSenseNanoBSDupgrade {$g['upload_path']}/firmware.tgz");
							else if(stristr($_FILES['ulfile']['name'],"bdiff"))
                            	mwexec_bg("/etc/rc.firmware delta_update {$g['upload_path']}/firmware.tgz");
							else 
								mwexec_bg("/etc/rc.firmware pfSenseupgrade {$g['upload_path']}/firmware.tgz");
                    } else {
                            $savemsg = "Güncelleme imajı kayıp veya başka bir hata ile karşılaşıldı, lütfen tekrar deneyiniz.";
                    }
            }
		}
	}
}

$pgtitle = "System: Firmware: Elle Güncelleme";
include("head.inc");

?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if ($fwinfo <> "") print_info_box($fwinfo); ?>
<?php if ($sig_warning && !$input_errors): ?>
<form action="system_firmware.php" method="post">
<?php
$sig_warning = "<strong>" . $sig_warning . "</strong><br>This means that the image you uploaded " .
	"is not an official/supported image and may lead to unexpected behavior or security " .
	"compromises. Only install images that come from sources that you trust, and make sure ".
	"that the image has not been tampered with.<br><br>".
	"Do you want to install this image anyway (on your own risk)?";
print_info_box($sig_warning);
if(stristr($_FILES['ulfile']['name'],"nanobsd"))
	echo "<input type='hidden' name='isnano' id='isnano' value='yes'>\n";
?>
<input name="sig_override" type="submit" class="formbtn" id="sig_override" value=" Evet ">
<input name="sig_no" type="submit" class="formbtn" id="sig_no" value=" Hayır ">
</form>
<?php else: ?>
            <?php if (!file_exists($d_firmwarelock_path)): ?>
<form action="system_firmware.php" method="post" enctype="multipart/form-data">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
<?php
	$tab_array = array();
	$tab_array[] = array("Manual Update", true, "system_firmware.php");
	if($g['platform'] <> "nanobsd") {
		$tab_array[] = array("Auto Update", false, "system_firmware_check.php");
		$tab_array[] = array("Updater Settings", false, "system_firmware_settings.php");
	}
	display_top_tabs($tab_array);
?>
		</td>
	</tr>
  <tr>
    <td>
	<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
		 <td colspan="2" class="listtopic"> Elle Yükseltme İşlemi</td>
		</tr>
		  <td width="22%" valign="baseline" class="vncell">&nbsp;</td>
                  <td width="78%" class="vtable">
            <p>Click &quot;Enable firmware
              upload&quot; below, then choose the image file image.tgz
			  to be uploaded.<br>Click &quot;Upgrade firmware&quot;
              to start the upgrade process.</p>
                    <?php if (!file_exists($d_sysrebootreqd_path)): ?>
                    <?php if (!file_exists($d_fwupenabled_path)): ?>
                    <input name="Submit" type="submit" class="formbtn" value="Yazılım güncellemeyi etkinleştir">
				  <?php else: ?>
				   <input name="Submit" type="submit" class="formbtn" value="Yazılım güncellemeyi pasifleştir">
                    <br><br>
					<strong>Firmware imaj dosyası: </strong>&nbsp;
					<input name="ulfile" type="file" class="formfld">
					<br><br>
					<?php if ($g['platform'] == "nanobsd"): ?>
					<b>NOT: .gz biçiminde bir dosya yüklemelisiniz lütfen açılmış bir imaj dosyası kullanmayınız!</b>
					<?php else: ?>
					<b>NOT: .tgz biçiminde bir dosya yüklemelisiniz lütfen açılmış bir imaj dosyası kullanmayınız!</b>
					<?php endif; ?>
                    <br><br>
					  <?php
				  		if(!file_exists("/boot/kernel/pfsense_kernel.txt")) {
				  			if($g['platform'] == "pfSense") { 
								echo "Please select kernel type: ";
								echo "<select name='kerneltype'>";
								echo "<option value='SMP'>Multiprocessor kernel</option>";
								echo "<option value='UP'>Uniprocessor kernel</option>";
								echo "<option value='wrap'>Embedded kernel</option>";
								echo "<option value='Developers'>Developers kernel</option>";
								echo "</select>";
								echo "<br><br>";
							}
						}
					  ?>
		    <input name="Submit" type="submit" class="formbtn" value="Yazılımı Güncelle">
				  <?php endif; else: ?>
				    <strong>Güncelleme işleminden önce sistemi yeniden başlatmalısınız.</strong>
				  <?php endif; ?>
                  </td>
		</td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"><span class="vexpl"><span class="red"><strong>Uyarı:<br>
                    </strong></span>DO NOT abort the firmware upgrade once it
                    has started. The firewall will reboot automatically after
                    storing the new firmware. The configuration will be maintained.</span></td>
              </table>
		</div>
		</tr>
		</td>
</table>

</form>
<?php endif; endif; ?>
<?php include("fend.inc"); ?>
</body>
</html>
