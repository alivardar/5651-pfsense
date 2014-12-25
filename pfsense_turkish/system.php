<?php
/* $Id$ */
/*
	system.php
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

$pconfig['hostname'] = $config['system']['hostname'];
$pconfig['domain'] = $config['system']['domain'];
list($pconfig['dns1'],$pconfig['dns2']) = $config['system']['dnsserver'];

$pconfig['dnsallowoverride'] = isset($config['system']['dnsallowoverride']);
$pconfig['username'] = $config['system']['username'];
if (!$pconfig['username'])
	$pconfig['username'] = "admin";
$pconfig['webguiproto'] = $config['system']['webgui']['protocol'];
if (!$pconfig['webguiproto'])
	$pconfig['webguiproto'] = "http";
$pconfig['webguiport'] = $config['system']['webgui']['port'];
$pconfig['timezone'] = $config['system']['timezone'];
$pconfig['timeupdateinterval'] = $config['system']['time-update-interval'];
$pconfig['timeservers'] = $config['system']['timeservers'];
$pconfig['theme'] = $config['system']['theme'];

if (!isset($pconfig['timeupdateinterval']))
	$pconfig['timeupdateinterval'] = 300;
if (!$pconfig['timezone'])
	$pconfig['timezone'] = "Etc/UTC";
if (!$pconfig['timeservers'])
	$pconfig['timeservers'] = "pool.ntp.org";

$changedesc = "System: ";
$changecount = 0;

function is_timezone($elt) {
	return !preg_match("/\/$/", $elt);
}

if($pconfig['timezone'] <> $_POST['timezone']) {
	/* restart firewall log dumper helper */
	require_once("functions.inc");
	$pid = `ps awwwux | grep -v "grep" | grep "tcpdump -v -l -n -e -ttt -i pflog0"  | awk '{ print $2 }'`;
	if($pid) {
		mwexec("kill $pid");
		usleep(1000);
	}		
	filter_pflog_start();
}

exec('/usr/bin/tar -tzf /usr/share/zoneinfo.tgz', $timezonelist);
$timezonelist = array_filter($timezonelist, 'is_timezone');
sort($timezonelist);

if ($_POST) {

	$changecount++;

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = split(" ", "hostname domain username");
	$reqdfieldsn = split(",", "Hostname,Domain,Username");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if ($_POST['hostname'] && !is_hostname($_POST['hostname'])) {
		$input_errors[] = "Hostname a-z arasındaki karakterler, 0-9 arasındaki rakamlar ve '-' karakterini içerebilir.";
	}
	if ($_POST['domain'] && !is_domain($_POST['domain'])) {
		$input_errors[] = "Alan adı (Domain) a-z arasındaki karakterler, 0-9 arasındaki rakamlar ve '-' karakterini içerebilir.";
	}
	if (($_POST['dns1'] && !is_ipaddr($_POST['dns1'])) || ($_POST['dns2'] && !is_ipaddr($_POST['dns2']))) {
		$input_errors[] = "Birincil ve ikincil DNS sunucu için geçerli bir IP adresi tanımlanmalıdır.";
	}
	if ($_POST['username'] && !preg_match("/^[a-zA-Z0-9]*$/", $_POST['username'])) {
		$input_errors[] = "Kullanıcı adı a-z, A-Z ve 0-9 arasındaki değerlerden oluşmalıdır.";
	}
	if ($_POST['webguiport'] && (!is_numericint($_POST['webguiport']) ||
			($_POST['webguiport'] < 1) || ($_POST['webguiport'] > 65535))) {
		$input_errors[] = "Web Kontrol Arayüzü için geçerli bir port tanımlanmalıdır.";
	}
	if (($_POST['password']) && ($_POST['password'] != $_POST['password2'])) {
		$input_errors[] = "Yazılan şifreler aynı değil.";
	}

	$t = (int)$_POST['timeupdateinterval'];
	if (($t < 0) || (($t > 0) && ($t < 6)) || ($t > 1440)) {
		$input_errors[] = "The time update interval must be either 0 (disabled) or between 6 and 1440.";
	}
	foreach (explode(' ', $_POST['timeservers']) as $ts) {
		if (!is_domain($ts)) {
			$input_errors[] = "NTP Zaman Sunucusu a-z, 0-9, '-' ve '.' karakter ve rakamlardan oluşabilir.";
		}
	}

	if (!$input_errors) {
		update_if_changed("hostname", $config['system']['hostname'], strtolower($_POST['hostname']));
		update_if_changed("domain", $config['system']['domain'], strtolower($_POST['domain']));
		update_if_changed("username", $config['system']['username'], $_POST['username']);

		if (update_if_changed("webgui protocol", $config['system']['webgui']['protocol'], $_POST['webguiproto']))
			$restart_webgui = true;
		if (update_if_changed("webgui port", $config['system']['webgui']['port'], $_POST['webguiport']))
			$restart_webgui = true;

		update_if_changed("timezone", $config['system']['timezone'], $_POST['timezone']);
		update_if_changed("NTP servers", $config['system']['timeservers'], strtolower($_POST['timeservers']));
		update_if_changed("NTP update interval", $config['system']['time-update-interval'], $_POST['timeupdateinterval']);

		/* pfSense themes */
		update_if_changed("System Theme", $config['theme'], $_POST['theme']);

		/* XXX - billm: these still need updating after figuring out how to check if they actually changed */
		unset($config['system']['dnsserver']);
		if ($_POST['dns1'])
			$config['system']['dnsserver'][] = $_POST['dns1'];
		if ($_POST['dns2'])
			$config['system']['dnsserver'][] = $_POST['dns2'];

		$olddnsallowoverride = $config['system']['dnsallowoverride'];

		unset($config['system']['dnsallowoverride']);
		$config['system']['dnsallowoverride'] = $_POST['dnsallowoverride'] ? true : false;
                if ($_POST['password']) {
                        $config['system']['password'] = crypt($_POST['password']);
			update_changedesc("Web Kontrol Arayüzünden şifre değiştirildi.");
			sync_webgui_passwords();
                }

		if ($changecount > 0)
			write_config($changedesc);

		if ($restart_webgui) {
			global $_SERVER;
			list($host) = explode(":", $_SERVER['HTTP_HOST']);
			if ($config['system']['webgui']['port']) {
				$url="{$config['system']['webgui']['protocol']}://{$host}:{$config['system']['webgui']['port']}/system.php";
			} else {
				$url = "{$config['system']['webgui']['protocol']}://{$host}/system.php";
			}
		}

		$retval = 0;
		config_lock();
		$retval = system_hostname_configure();
		$retval |= system_hosts_generate();
		$retval |= system_resolvconf_generate();
		$retval |= system_password_configure();
		$retval |= services_dnsmasq_configure();
		$retval |= system_timezone_configure();
		$retval |= system_ntp_configure();

		if ($olddnsallowoverride != $config['system']['dnsallowoverride'])
			$retval |= interfaces_wan_configure();

		config_unlock();

		// Reload filter -- plugins might need to run
		filter_configure();

		$savemsg = get_std_save_message($retval);
		if ($restart_webgui)
			$savemsg .= "<br />Lütfen bekleyiniz... {$url} adresine 10 saniye içinde yönlendirileceksiniz.";
	}
}

$pgtitle = "Sistem: Genel Ayarlar";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="system.php" method="post">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Hostname</td>
                  <td width="78%" class="vtable"> <input name="hostname" type="text" class="formfld" id="hostname" size="40" value="<?=htmlspecialchars($pconfig['hostname']);?>">
                    <br> <span class="vexpl"> Alan adı (Domain) olmadan adını yazınız <br>
                    örnek <em>firewall01</em></span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Alan adı (Domain)</td>
                  <td width="78%" class="vtable"> <input name="domain" type="text" class="formfld" id="domain" size="40" value="<?=htmlspecialchars($pconfig['domain']);?>">
                    <br> <span class="vexpl"> örnek <em> sirket.com.tr</em> </span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">DNS sunucuları</td>
                  <td width="78%" class="vtable"> <p>
                      <input name="dns1" type="text" class="formfld" id="dns1" size="20" value="<?=htmlspecialchars($pconfig['dns1']);?>">
                      <br>
                      <input name="dns2" type="text" class="formfld" id="dns22" size="20" value="<?=htmlspecialchars($pconfig['dns2']);?>">
                      <br>
                      <span class="vexpl">IP adresleri; 
					  Bu adresler aynı zamanda DHCP sunucu, DNS yönlendirici ve PPTP VPN istemcileri tarafındanda kullanılacaktır.
					  <br>
                      <br>
                      <input name="dnsallowoverride" type="checkbox" id="dnsallowoverride" value="yes" <?php if ($pconfig['dnsallowoverride']) echo "checked"; ?>>
                      <strong>
					  WAN üzerinde DHCP/PPP sunucular tarafından bu değerlerin üzerine yazmasına izin ver
					  </strong><br>
                      Bu alan seçili olması durumunda <?=$g['product_name']?> DHCP/PPP sunucu tarafından seçilmiş olan 
					  DNS sunucuları kullanacaktır.</span></p></td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Kullanıcı adı</td>
                  <td class="vtable"> <input name="username" type="text" class="formfld" id="username" size="20" value="<?=$pconfig['username'];?>">
                    <br>
                     <span class="vexpl">Web Kontrol Arayüzüne girerken kullanılan kullanıcı adını değiştirmek isterseniz bu alana yazabilirsiniz.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Şifre</td>
                  <td width="78%" class="vtable"> <input name="password" type="password" class="formfld" id="password" size="20">
                    <br> <input name="password2" type="password" class="formfld" id="password2" size="20">
                    &nbsp;(doğrulama) <br> <span class="vexpl">Web Kontrol Arayüzüne girmek için kullanılan şifreyi değiştirmek için bulana iki kere yazınız</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Web Kontrol Arayüzü WEB protokolu</td>
                  <td width="78%" class="vtable"> <input name="webguiproto" type="radio" value="http" <?php if ($pconfig['webguiproto'] == "https") echo "checked"; ?>>
                    HTTP &nbsp;&nbsp;&nbsp; <input type="radio" name="webguiproto" value="https" <?php if ($pconfig['webguiproto'] == "https") echo "checked"; ?>>
                    HTTPS
					<br>
                    <span class="vexpl">
					Uyarı: Sistemde varsayılan olarak PRoxy sunucu üzerinde transparan proxy aktif haldedir.
					HTTP seçilmesi durumunda Web Kontrol Arayüzüne erişimi kaybedebilirsiniz.
					Lütfen HTTPS kullanınız.
					</span>
					
					</td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Web Kontrol Arayüzü port numarası</td>
                  <td class="vtable"> <input name="webguiport" type="text" class="formfld" id="webguiport" "size="5" value="<?=htmlspecialchars($config['system']['webgui']['port']);?>">
                    <br>
                    <span class="vexpl">
					Web Kontrol Arayüzü için istediğiniz bir port numarası tanımlayabilirsiniz. (varsayılan değerler HTTP için 80, HTTPS 443)
					Kayıt olduktan sonra değişikliler uygulanacaktır.
					</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Zaman bölgesi</td>
                  <td width="78%" class="vtable"> <select name="timezone" id="timezone">
                      <?php foreach ($timezonelist as $value): ?>
                      <option value="<?=htmlspecialchars($value);?>" <?php if ($value == $pconfig['timezone']) echo "selected"; ?>>
                      <?=htmlspecialchars($value);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br> <span class="vexpl">Size en yakın olan yeri seçiniz.</span></td>
                </tr>
                <!--
                <tr>
                  <td width="22%" valign="top" class="vncell">Time update interval</td>
                  <td width="78%" class="vtable"> <input name="timeupdateinterval" type="text" class="formfld" id="timeupdateinterval" size="4" value="<?=htmlspecialchars($pconfig['timeupdateinterval']);?>">
                    <br> <span class="vexpl">Minutes between network time sync.;
                    300 recommended, or 0 to disable </span></td>
                </tr>
                -->
                <tr>
                  <td width="22%" valign="top" class="vncell">NTP zaman sunucusu</td>
                  <td width="78%" class="vtable"> <input name="timeservers" type="text" class="formfld" id="timeservers" size="40" value="<?=htmlspecialchars($pconfig['timeservers']);?>">
                    <br> <span class="vexpl">
					Eğer birden çok NTP sunucusu tamımlamak isterseniz aralarına boşluk bırakarak bunu yapabilirsiniz.
					Eğer bir DNS sunucu tanımlaması yaptıysanız IP yerine isimde kullanabilirsiniz.
					</span></td>
                </tr>
				
				<tr>
					<td colspan="2" class="list" height="12">&nbsp;</td>
				</tr>
				
				<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="Kaydet">
                  </td>
                </tr>
				
              </table>
			  
</form>
<?php include("fend.inc"); ?>
<?php
	// restart webgui if proto or port changed
	if ($restart_webgui) {
		echo "<meta http-equiv=\"refresh\" content=\"10;url={$url}\">";
	}
?>
</body>
</html>
<?php
if ($restart_webgui) {
	touch("/tmp/restart_webgui");
}
?>