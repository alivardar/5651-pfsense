<?php
/*
	diag_nanobsd.php
	Copyright (C) 2009 Scott Ullrich <sullrich@gmail.com>
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

##|+PRIV
##|*IDENT=page-diagnostics-nanobsd
##|*NAME=Diagnostics: NanoBSD
##|*DESCR=Allow access to the 'Diagnostics: NanoBSD' page.
##|*MATCH=diag_nanobsd.php*
##|-PRIV

ini_set('zlib.output_compression', 0);
ini_set('implicit_flush', 1);
ini_set('max_input_time', '9999');

require_once("guiconfig.inc");
require_once("config.inc");

$pgtitle = "Diagnostics: NanoBSD";
include("head.inc");

$BOOT_DEVICE=trim(`/sbin/mount | /usr/bin/grep pfsense | /usr/bin/cut -d'/' -f4 | /usr/bin/cut -d' ' -f1`);
$REAL_BOOT_DEVICE=trim(`/sbin/glabel list | /usr/bin/grep -B2 ufs/{$BOOT_DEVICE} | /usr/bin/head -n 1 | /usr/bin/cut -f3 -d' '`);
$BOOT_DRIVE=trim(`/sbin/glabel list | /usr/bin/grep -B2 ufs/pfsense | /usr/bin/head -n 1 | /usr/bin/cut -f3 -d' ' | /usr/bin/cut -d's' -f1`);

function detect_slice_info() {
	global $SLICE, $OLDSLICE, $TOFLASH, $COMPLETE_PATH, $COMPLETE_BOOT_PATH;
	global $GLABEL_SLIZE, $UFS_ID, $OLD_UFS_ID, $BOOTFLASH;
	global $BOOT_DEVICE, $REAL_BOOT_DEVICE, $BOOT_DRIVE;
	
	$BOOT_DEVICE=trim(`/sbin/mount | /usr/bin/grep pfsense | /usr/bin/cut -d'/' -f4 | /usr/bin/cut -d' ' -f1`);
	$REAL_BOOT_DEVICE=trim(`/sbin/glabel list | /usr/bin/grep -B2 ufs/{$BOOT_DEVICE} | /usr/bin/head -n 1 | /usr/bin/cut -f3 -d' '`);
	$BOOT_DRIVE=trim(`/sbin/glabel list | /usr/bin/grep -B2 ufs/pfsense | /usr/bin/head -n 1 | /usr/bin/cut -f3 -d' ' | /usr/bin/cut -d's' -f1`);

	// Detect which slice is active and set information.
	if(strstr($REAL_BOOT_DEVICE, "s1")) {
		$SLICE="2";
		$OLDSLICE="1";
		$TOFLASH="{$BOOT_DRIVE}s{$SLICE}";
		$COMPLETE_PATH="{$BOOT_DRIVE}s{$SLICE}a";
		$COMPLETE_BOOT_PATH="{$BOOT_DRIVE}s{$OLDSLICE}";	
		$GLABEL_SLICE="pfsense1";
		$UFS_ID="1";
		$OLD_UFS_ID="0";
		$BOOTFLASH="{$BOOT_DRIVE}s{$OLDSLICE}";
	} else {
		$SLICE="1";
		$OLDSLICE="2";
		$TOFLASH="{$BOOT_DRIVE}s{$SLICE}";
		$COMPLETE_PATH="{$BOOT_DRIVE}s{$SLICE}a";
		$COMPLETE_BOOT_PATH="{$BOOT_DRIVE}s{$OLDSLICE}";
		$GLABEL_SLICE="pfsense0";
		$UFS_ID="0";
		$OLD_UFS_ID="1";
		$BOOTFLASH="{$BOOT_DRIVE}s{$OLDSLICE}";
	}
}

// Survey slice info
detect_slice_info();

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC" onload="<?=$jsevents["body"]["onload"];?>">
<script src="/javascript/scriptaculous/prototype.js" type="text/javascript"></script>

<?php include("fbegin.inc"); ?>

<?php

if($_POST['bootslice']) {
	echo <<<EOF
	 	<div id="loading">			
			Setting slice information, please wait...
			<p/>&nbsp;
		</div>
EOF;
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	if(strstr($_POST['bootslice'], "s2")) {
		$ASLICE="2";
		$AOLDSLICE="1";
		$ATOFLASH="{$BOOT_DRIVE}s{$ASLICE}";
		$ACOMPLETE_PATH="{$BOOT_DRIVE}s{$ASLICE}a";
		$AGLABEL_SLICE="pfsense1";
		$AUFS_ID="1";
		$AOLD_UFS_ID="0";
		$ABOOTFLASH="{$BOOT_DRIVE}s{$AOLDSLICE}";
	} else {
		$ASLICE="1";
		$AOLDSLICE="2";
		$ATOFLASH="{$BOOT_DRIVE}s{$ASLICE}";
		$ACOMPLETE_PATH="{$BOOT_DRIVE}s{$ASLICE}a";
		$AGLABEL_SLICE="pfsense0";
		$AUFS_ID="0";
		$AOLD_UFS_ID="1";
		$ABOOTFLASH="{$BOOT_DRIVE}s{$AOLDSLICE}";
	}

	conf_mount_rw();
	exec("sysctl kern.geom.debugflags=16");
	exec("gpart set -a active -i {$ASLICE} {$BOOT_DRIVE}");
	exec("/usr/sbin/boot0cfg -s {$ASLICE} -v /dev/{$BOOT_DRIVE}");
	exec("/sbin/tunefs -L ${AGLABEL_SLICE} /dev/$ACOMPLETE_PATH");
	exec("/bin/mkdir /tmp/{$AGLABEL_SLICE}");
	exec("/sbin/fsck_ufs -y /dev/{$ACOMPLETE_PATH}");
	exec("/sbin/mount /dev/ufs/{$AGLABEL_SLICE} /tmp/{$AGLABEL_SLICE}");
	$fstab = <<<EOF
/dev/ufs/{$AGLABEL_SLICE} / ufs ro 1 1
/dev/ufs/cf /cf ufs ro 1 1	
EOF;
	file_put_contents("/tmp/{$AGLABEL_SLICE}/etc/fstab", $fstab);
	exec("/sbin/umount /tmp/{$AGLABEL_SLICE}");
	exec("sysctl kern.geom.debugflags=0");
	conf_mount_ro();
	$savemsg = "The boot slice has been set to {$ABOOT_DRIVE} {$AGLABEL_SLICE}";
	// Survey slice info
	detect_slice_info();
}

$NANOBSD_SIZE = strtoupper(file_get_contents("/etc/nanosize.txt"));

if($_POST['destslice']) {

echo <<<EOF
 	<div id="loading">
		<img src="/themes/metallic/images/misc/loader.gif">
		Duplicating slice.  Please wait, this will take a moment...
		<p/>&nbsp;
	</div>
EOF;
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	exec("sysctl kern.geom.debugflags=16");
	exec("dd if=/dev/zero of=/dev/{$TOFLASH} bs=1m count=1");
	exec("/bin/dd if=/dev/{$BOOTFLASH} of=/dev/{$TOFLASH} bs=64k");
	exec("/sbin/tunefs -L {$GLABEL_SLICE} /dev/{$COMPLETE_PATH}");
	exec("/bin/mkdir /tmp/{$GLABEL_SLICE}");
	exec("/sbin/fsck_ufs -y /dev/{$COMPLETE_PATH}");
	exec("/sbin/mount /dev/ufs/{$GLABEL_SLICE} /tmp/{$GLABEL_SLICE}");
	exec("/bin/cp /etc/fstab /tmp/{$GLABEL_SLICE}/etc/fstab");
	exec("sysctl kern.geom.debugflags=0");
	$status = exec("sed -i \"\" \"s/pfsense{$OLD_UFS_ID}/pfsense{$UFS_ID}/g\" /tmp/{$GLABEL_SLICE}/etc/fstab");
	if($status) {
		exec("/sbin/umount /tmp/{$GLABEL_SLICE}");
		$savemsg = "There was an error while duplicating the slice.  Operation aborted.";
	} else {
		$savemsg = "The slice has been duplicated.<p/>If you would like to boot from this newly duplicated slice please set it using the bootup information area.";
		exec("/sbin/umount /tmp/{$GLABEL_SLICE}");
	}
	// Survey slice info
	detect_slice_info();
}

if ($savemsg)
	print_info_box($savemsg)

?>
<p class="pgtitle"><?=$pgtitle?></font></p>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<!-- tabs here if you want them -->
		</td>
	</tr>
	<tr>
		<td id="mainarea">
			<div class="tabcont">
				<span class="vexpl">
					<span class="red">
						<strong>NOTE:&nbsp</strong>
					</span>
					The options on this page are intended for use by advanced users only.
					<br/>&nbsp;
				</span>
				<p/>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<tr>
						<td colspan="2" valign="top" class="listtopic">Bootup information</td>
					</tr>
					<tr>						
						<td width="22%" valign="top" class="vncell">NanoBSD Image size</td>
						<td width="78%" class="vtable">
							<?php echo "$NANOBSD_SIZE"; ?>
						</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncell">Bootup</td>
						<td width="78%" class="vtable">
							<form action="diag_nanobsd.php" method="post" name="iform">
								Bootup slice:
								<select name='bootslice'>
									<option value='<?php echo $BOOTFLASH; ?>'>
										<?php echo $BOOTFLASH; ?>
									</option>
									<option value='<?php echo $TOFLASH; ?>'>
										<?php echo "{$TOFLASH}"; ?>
									</option>
								</select>
								<br/>
								This will set the bootup slice.
						</td>
					</tr>
					<tr>
						<td valign="top" class="">&nbsp;</td><td><br/><input type='submit' value='Set bootup'></form></td>
					</tr>
					<tr>
						<td colspan="2" valign="top" class="">&nbsp;</td>
					</tr>					
					<tr>
						<td colspan="2" valign="top" class="listtopic">Duplicate bootup slice to alternate</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncell">Duplicate bootup slice</td>
						<td width="78%" class="vtable">
							<form action="diag_nanobsd.php" method="post" name="iform">						
								Destination slice:							
								<select name='destslice'>
									<option value='<?php echo $COMPLETE_PATH; ?>'>
										<?php echo "{$COMPLETE_BOOT_PATH} -> {$TOFLASH}"; ?>
									</option>
								</select>
								<br/>
								This will duplicate the bootup slice to the alternate slice.  Use this if you would like to duplicate the known good working boot partition to the alternate.
						</td>
					</tr>
					<tr>
						<td valign="top" class="">&nbsp;</td><td><br/><input type='submit' value='Duplicate slice'></form></td>
					</tr>
<?php if(file_exists("/conf/upgrade_log.txt")): ?>
					<tr>
						<td colspan="2" valign="top" class="">&nbsp;</td>
					</tr>					
					<tr>
						<td colspan="2" valign="top" class="listtopic">View upgrade log</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncell">View previous upgrade log</td>
						<td width="78%" class="vtable">
						<?php
							if($_POST['viewupgradelog']) {
								echo "<textarea name='log' cols='80' rows='40'>";
								echo file_get_contents("/conf/upgrade_log.txt");
								echo "\nFile list:\n";
								echo file_get_contents("/conf/file_upgrade_log.txt");
								echo "\nMisc log:\n";
								echo file_get_contents("/conf/firmware_update_misc.log");
								echo "\nfdisk/bsdlabel log:\n";
								echo file_get_contents("/conf/fdisk_upgrade_log.txt");
								echo "</textarea>";
							} else {
								echo "<form action='diag_nanobsd.php' method='post' name='iform'>";
								echo "<input type='submit' name='viewupgradelog' value='View upgrade log'>";
							}
						?>
						</td>
					</tr>
<?php endif; ?>
					<tr>
						<td colspan="2" valign="top" class="">&nbsp;</td>
					</tr>					
				</table>
			</div>
		</td>
	</tr>
</table>
<?php require("fend.inc"); ?>
</body>
</html>

<?php

// Clear the loading indicator
echo "<script type=\"text/javascript\">";
echo "$('loading').innerHTML = '';";
echo "</script>";	

?>
