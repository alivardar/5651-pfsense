<?php
/* $Id$ */
/*
    index.php
    Copyright (C) 2004, 2005 Scott Ullrich
    All rights reserved.

    Originally part of m0n0wall (http://m0n0.ch/wall)
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
    oR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

	## Load Essential Includes
	require_once('guiconfig.inc');
	require_once('notices.inc');


	## Load Functions Files
	require_once('includes/functions.inc.php');


	## Load AJAX, Initiate Class ###############################################
	require_once('includes/sajax.class.php');

	## Initiate Class and Set location of ajax file containing 
	## the information that we need for this page. Also set functions
	## that SAJAX will be using.
	$oSajax = new sajax();
	$oSajax->sajax_remote_uri = 'sajax/index.sajax.php';
	$oSajax->sajax_request_type = 'POST';
	$oSajax->sajax_export("get_stats");
	$oSajax->sajax_handle_client_request();
	############################################################################


	## Check to see if we have a swap space,
	## if true, display, if false, hide it ...
	if(file_exists("/usr/sbin/swapinfo")) {
		$swapinfo = `/usr/sbin/swapinfo`;
		if(stristr($swapinfo,'%') == true) $showswap=true;
	}


	## User recently restored his config.
	## If packages are installed lets resync
	if(file_exists('/conf/needs_package_sync')) {
		if($config['installedpackages'] <> '') {
			conf_mount_rw();
			unlink('/conf/needs_package_sync');
			header('Location: pkg_mgr_install.php?mode=reinstallall');
			exit;
		}
	}


	## If it is the first time webGUI has been
	## accessed since initial install show this stuff.
	if(file_exists('/conf/trigger_initial_wizard')) {

		echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>{$g['product_name']}.local - {$g['product_name']} ilk yapılandırma</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="/niftycssprintCode.css" media="print" />
	<script type="text/javascript">var theme = "nervecenter"</script>
	<script type="text/javascript" src="/themes/nervecenter/loader.js"></script>
		
EOF;
		echo "<body link=\"#0000CC\" vlink=\"#0000CC\" alink=\"#0000CC\">\n";
		
		if(file_exists("/usr/local/www/themes/{$g['theme']}/wizard.css")) 
			echo "<link rel=\"stylesheet\" href=\"/themes/{$g['theme']}/wizard.css\" media=\"all\" />\n";
		else 
			echo "<link rel=\"stylesheet\" href=\"/themes/{$g['theme']}/all.css\" media=\"all\" />";
		
		echo "<form>\n";
		echo "<center>\n";
		echo "<img src=\"/themes/{$g['theme']}/images/logo.gif\" border=\"0\"><p>\n";
		echo "<div \" style=\"width:700px;background-color:#ffffff\" id=\"nifty\">\n";
		echo "Hosgeldiniz {$g['product_name']}!<p>\n";
		echo "Ayarlama sihirbazı yapılandırılırken lütfen bekleyiniz.<p>\n";
		echo "Lütfen bekleyiniz, yapılandırma işlemleri biraz zaman alacaktır.<p>\n";
		echo "Eğer bu işlemi atlamak isterseniz {$g['product_name']} logoyu tıklayınız.\n";
		echo "</div>\n";
		echo "<meta http-equiv=\"refresh\" content=\"1;url=wizard.php?xml=setup_wizard.xml\">\n";
		echo "<script type=\"text/javascript\">\n";
		echo "NiftyCheck();\n";
		echo "Rounded(\"div#nifty\",\"all\",\"#AAA\",\"#FFFFFF\",\"smooth\");\n";
		echo "</script>\n";
		exit;
	}


	## Find out whether there's hardware encryption or not
	unset($hwcrypto);
	$fd = @fopen("{$g['varlog_path']}/dmesg.boot", "r");
	if ($fd) {
		while (!feof($fd)) {
			$dmesgl = fgets($fd);
			if (preg_match("/^hifn.: (.*?),/", $dmesgl, $matches)) {
				$hwcrypto = $matches[1];
				break;
			}
		}
		fclose($fd);
	}


	## Set Page Title and Include Header
	$pgtitle = "Web Kontrol Arayüzü";
	include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<script language="javascript">
var ajaxStarted = false;
</script>
<?php
include("fbegin.inc");
	if(!file_exists("/usr/local/www/themes/{$g['theme']}/no_big_logo"))
		echo "<center><img src=\"./themes/".$g['theme']."/images/logobig.jpg\"></center><br>";
?>
<p class="pgtitle">Sistem Hakkında</p>

<div id="niftyOutter">
<form action="index.php" method="post">
<table bgcolor="#990000" width="100%" border="0" cellspacing="0" cellpadding="0">
	<tbody>
		<tr>
			<td colspan="2" class="listtopic">Sistem Bilgileri</td>
		</tr>
		<tr>
			<td width="25%" class="vncellt">İsim</td>
			<td width="75%" class="listr"><?php echo $config['system']['hostname'] . "." . $config['system']['domain']; ?></td>
		</tr>
		<tr>
			<td width="25%" valign="top" class="vncellt">Sürüm numarası</td>
			<td width="75%" class="listr">
				<strong><?php readfile("/etc/version"); ?></strong>								
			</td>
		</tr>

		<?php if ($hwcrypto): ?>
		<tr>
			<td width="25%" class="vncellt">Donanım şifre desteği</td>
			<td width="75%" class="listr"><?=htmlspecialchars($hwcrypto);?></td>
		</tr>
		<?php endif; ?>
		<tr>
			<td width="25%" class="vncellt">Açık kalma süresi</td>
			<td width="75%" class="listr"><input style="border: 0px solid white;" size="30" name="uptime" id="uptime" value="<?= htmlspecialchars(get_uptime()); ?>" /></td>
		</tr>
			
		<?php if ($config['lastchange']): ?>
		<tr>
			<td width="25%" class="vncellt">Son ayar değişikliği</td>
			<td width="75%" class="listr"><?= htmlspecialchars(date("D M j G:i:s T Y", $config['revision']['time']));?></td>
		</tr>
		<?php endif; ?>
		<tr>
			<td width="25%" class="vncellt">Oturum durumu</td>
			<td width="75%" class="listr">
				<input style="border: 0px solid white;" size="30" name="pfstate" id="pfstate" value="<?= htmlspecialchars(get_pfstate()); ?>" />
		    	<br />
		    	<a href="diag_dump_states.php">Oturumları göster</a>
			</td>
		</tr>
		<tr>
			<td width="25%" class="vncellt">MBUF kullanımı</td>
			<td width="75%" class="listr">
				<?php
					$mbufs_inuse=`netstat -mb | grep "mbufs in use" | awk '{ print $1 }' | cut -d"/" -f1`;
					$mbufs_total=`netstat -mb | grep "mbufs in use" | awk '{ print $1 }' | cut -d"/" -f3`;
				?>
				<?=$mbufs_inuse?>/<?=$mbufs_total?>
			</td>
		</tr>
		<tr>
			<td width="25%" class="vncellt">CPU kullanımı</td>
			<td width="75%" class="listr">
				<?php $cpuUsage = "0"; ?>
				<img src="./themes/<?= $g['theme']; ?>/images/misc/bar_left.gif" height="15" width="4" border="0" align="middle" alt="left bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_blue.gif" height="15" name="cpuwidtha" id="cpuwidtha" width="<?= $cpuUsage; ?>" border="0" align="middle" alt="red bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_gray.gif" height="15" name="cpuwidthb" id="cpuwidthb" width="<?= (100 - $cpuUsage); ?>" border="0" align="middle" alt="gray bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_right.gif" height="15" width="5" border="0" align="middle" alt="right bar" />
				&nbsp;
				<input style="border: 0px solid white;" size="30" name="cpumeter" id="cpumeter" value="(5 Saniyede bir yenilenir)" />
			</td>
		</tr>
		<tr>
			<td width="25%" class="vncellt">Bellek kullanımı</td>
			<td width="75%" class="listr">
				<?php $memUsage = mem_usage(); ?>
				<img src="./themes/<?= $g['theme']; ?>/images/misc/bar_left.gif" height="15" width="4" border="0" align="middle" alt="left bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_blue.gif" height="15" name="memwidtha" id="memwidtha" width="<?= $memUsage; ?>" border="0" align="middle" alt="red bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_gray.gif" height="15" name="memwidthb" id="memwidthb" width="<?= (100 - $memUsage); ?>" border="0" align="middle" alt="gray bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_right.gif" height="15" width="5" border="0" align="middle" alt="right bar" />
				&nbsp;
				<input style="border: 0px solid white;" size="30" name="memusagemeter" id="memusagemeter" value="<?= $memUsage.'%'; ?>" />
			</td>
		</tr>
		<?php if($showswap == true): ?>
		<tr>
			<td width="25%" class="vncellt">SWAP kullanımı</td>
			<td width="75%" class="listr">
				<?php $swapUsage = swap_usage(); ?>
				<img src="./themes/<?= $g['theme']; ?>/images/misc/bar_left.gif" height="15" width="4" border="0" align="middle" alt="left bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_blue.gif" height="15" width="<?= $swapUsage; ?>" border="0" align="middle" alt="red bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_gray.gif" height="15" width="<?= (100 - $swapUsage); ?>" border="0" align="middle" alt="gray bar" /><img src="./themes/<?= $g['theme']; ?>/images/misc/bar_right.gif" height="15" width="5" border="0" align="middle" alt="right bar" />
				&nbsp;
				<input style="border: 0px solid white;" size="30" name="swapusagemeter" id="swapusagemeter" value="<?= $swapUsage.'%'; ?>" />
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<td width="25%" class="vncellt">Disk kullanımı</td>
			<td width="75%" class="listr">
				<?php $diskusage = disk_usage(); ?>
				<img src="./themes/<?= $g["theme"]; ?>/images/misc/bar_left.gif" height="15" width="4" border="0" align="middle" alt="left bar" /><img src="./themes/<?= $g["theme"]; ?>/images/misc/bar_blue.gif" height="15" width="<?= $diskusage; ?>" border="0" align="middle" alt="red bar" /><img src="./themes/<?= $g["theme"]; ?>/images/misc/bar_gray.gif" height="15" width="<?= (100 - $diskusage); ?>" border="0" align="middle" alt="gray bar" /><img src="./themes/<?= $g["theme"]; ?>/images/misc/bar_right.gif" height="15" width="5" border="0" align="middle" alt="right bar" />
				&nbsp;
				<input style="border: 0px solid white;" size="30" name="diskusagemeter" id="diskusagemeter" value="<?= $diskusage.'%'; ?>" />
			</td>
		</tr>
	</tbody>
</table>
</form>
</div>

<?php include("fend.inc"); ?>
	    
<script type="text/javascript">
	NiftyCheck();
	Rounded("div#nifty","top","#FFF","#EEEEEE","smooth");
</script>

</body>
</html>
