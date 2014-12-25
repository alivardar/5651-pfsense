<?php
/* $Id$ */
/*
	services_dyndns.php
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

if (!is_array($config['dnsupdate'])) {
	$config['dnsupdate'] = array();
}

$pconfig['username'] = $config['dyndns']['username'];
$pconfig['password'] = $config['dyndns']['password'];
$pconfig['host'] = $config['dyndns']['host'];
$pconfig['mx'] = $config['dyndns']['mx'];
$pconfig['type'] = $config['dyndns']['type'];
$pconfig['enable'] = isset($config['dyndns']['enable']);
$pconfig['wildcard'] = isset($config['dyndns']['wildcard']);

$pconfig['dnsupdate_enable'] = isset($config['dnsupdate']['enable']);
$pconfig['dnsupdate_host'] = $config['dnsupdate']['host'];
$pconfig['dnsupdate_server'] = $config['dnsupdate']['server'];
$pconfig['dnsupdate_ttl'] = $config['dnsupdate']['ttl'];
if (!$pconfig['dnsupdate_ttl'])
	$pconfig['dnsupdate_ttl'] = 60;
$pconfig['dnsupdate_keydata'] = $config['dnsupdate']['keydata'];
$pconfig['dnsupdate_keyname'] = $config['dnsupdate']['keyname'];
$pconfig['dnsupdate_keytype'] = $config['dnsupdate']['keytype'];
if (!$pconfig['dnsupdate_keytype'])
	$pconfig['dnsupdate_keytype'] = "zone";
$pconfig['dnsupdate_usetcp'] = isset($config['dnsupdate']['usetcp']);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = array();
	$reqdfieldsn = array();
	if ($_POST['enable']) {
		$reqdfields = array_merge($reqdfields, explode(" ", "host username password type"));
		$reqdfieldsn = array_merge($reqdfieldsn, explode(",", "Hostname,Username,Password,Service type"));
	}
	if ($_POST['dnsupdate_enable']) {
		$reqdfields = array_merge($reqdfields, explode(" ", "dnsupdate_host dnsupdate_ttl dnsupdate_keyname dnsupdate_keydata"));
		$reqdfieldsn = array_merge($reqdfieldsn, explode(",", "Hostname,TTL,Key name,Key"));
	}
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if($pconfig['type'] <> "zoneedit") {
		if (($_POST['host'] && !is_domain($_POST['host']))) {
			$input_errors[] = "Hostname geçersiz karakterler içeriyor.";
		}
	}
	if (($_POST['mx'] && !is_domain($_POST['mx']))) {
		$input_errors[] = "MX geçersiz karakterler içeriyor.";
	}
	if (($_POST['username'] && !is_dyndns_username($_POST['username']))) {
		$input_errors[] = "Kullanıcı adı geçersiz karakterler içeriyor.";
	}

	if (($_POST['dnsupdate_host'] && !is_domain($_POST['dnsupdate_host']))) {
		$input_errors[] = "DNS güncelleme hostname geçersiz karakterler içeriyor.";
	}
	if (($_POST['dnsupdate_ttl'] && !is_numericint($_POST['dnsupdate_ttl']))) {
		$input_errors[] = "DNS güncelleme TTL değeri tam sayı olmak zorundadır.";
	}
	if (($_POST['dnsupdate_keyname'] && !is_domain($_POST['dnsupdate_keyname']))) {
		$input_errors[] = "DNS güncelleme anahtar adı geçersiz karakterler içeriyor.";
	}

	if (!$input_errors) {
		$config['dyndns']['type'] = $_POST['type'];
		$config['dyndns']['username'] = $_POST['username'];
		$config['dyndns']['password'] = $_POST['password'];
		$config['dyndns']['host'] = $_POST['host'];
		$config['dyndns']['mx'] = $_POST['mx'];
		$config['dyndns']['wildcard'] = $_POST['wildcard'] ? true : false;
		$config['dyndns']['enable'] = $_POST['enable'] ? true : false;

		$config['dnsupdate']['enable'] = $_POST['dnsupdate_enable'] ? true : false;
		$config['dnsupdate']['host'] = $_POST['dnsupdate_host'];
		$config['dnsupdate']['server'] = $_POST['dnsupdate_server'];
		$config['dnsupdate']['ttl'] = $_POST['dnsupdate_ttl'];
		$config['dnsupdate']['keyname'] = $_POST['dnsupdate_keyname'];
		$config['dnsupdate']['keytype'] = $_POST['dnsupdate_keytype'];
		$config['dnsupdate']['keydata'] = $_POST['dnsupdate_keydata'];
		$config['dnsupdate']['usetcp'] = $_POST['dnsupdate_usetcp'] ? true : false;

		write_config();

		$retval = 0;

		/* nuke the cache file */
		config_lock();
		services_dyndns_reset();
		$retval = services_dyndns_configure();
		$retval |= services_dnsupdate_process();
		config_unlock();
		$savemsg = get_std_save_message($retval);
	}
}

$pgtitle = "Servisler: Dinamik DNS";
include("head.inc");

?>

<script language="JavaScript">
<!--
function enable_change(enable_change) {
	var endis;

	endis = !(document.iform.enable.checked || enable_change);
	document.iform.host.disabled = endis;
	document.iform.mx.disabled = endis;
	document.iform.type.disabled = endis;
	document.iform.wildcard.disabled = endis;
	document.iform.username.disabled = endis;
	document.iform.password.disabled = endis;

	endis = !(document.iform.dnsupdate_enable.checked || enable_change);
	document.iform.dnsupdate_host.disabled = endis;
	document.iform.dnsupdate_server.disabled = endis;
	document.iform.dnsupdate_ttl.disabled = endis;
	document.iform.dnsupdate_keyname.disabled = endis;
	document.iform.dnsupdate_keytype[0].disabled = endis;
	document.iform.dnsupdate_keytype[1].disabled = endis;
	document.iform.dnsupdate_keytype[2].disabled = endis;
	document.iform.dnsupdate_keydata.disabled = endis;
	document.iform.dnsupdate_usetcp.disabled = endis;
}
//-->
</script>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
            <form action="services_dyndns.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td colspan="2" valign="top" class="optsect_t">
				  <table border="0" cellspacing="0" cellpadding="0" width="100%">
				  <tr><td class="optsect_s"><strong>Dinamik DNS istemcisi</strong></td>
				  <td align="right" class="optsect_s"><input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)"> 
				  <strong>Etkinleştir</strong></td></tr>
				  </table></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Servis tipi</td>
                  <td width="78%" class="vtable">
<select name="type" class="formfld" id="type">
                      <?php
				$types = explode(",", "DynDNS (dynamic),DynDNS (static),DynDNS (custom),DHS,DyNS,easyDNS,No-IP,ODS.org,ZoneEdit,Loopia,freeDNS");
				$vals = explode(" ", "dyndns dyndns-static dyndns-custom dhs dyns easydns noip ods zoneedit loopia freedns");
				$j = 0; for ($j = 0; $j < count($vals); $j++): ?>
                      <option value="<?=$vals[$j];?>" <?php if ($vals[$j] == $pconfig['type']) echo "selected";?>>
                      <?=htmlspecialchars($types[$j]);?>
                      </option>
                      <?php endfor; ?>
                    </select></td>
				</tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Hostname</td>
                  <td width="78%" class="vtable">
                    <input name="host" type="text" class="formfld" id="host" size="30" value="<?=htmlspecialchars($pconfig['host']);?>">
		    <br>
		    <span class="vexpl">
		    <span class="red"><strong>Bilgi:<br></strong>
		    </span>
			Tam olarak host/domain adını yazınız. örnek: adim.fomain.org  gibi 
			
		    </span>
                  </td>
		</tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">MX</td>
                  <td width="78%" class="vtable">
                    <input name="mx" type="text" class="formfld" id="mx" size="30" value="<?=htmlspecialchars($pconfig['mx']);?>">
                    <br>
					Bilgi: DynDNS servisi sadece isim karşığı kullanılır, bir IP adresi değil.
					<br>
					Eğer özel bir MX kaydına ihtiyaç duyuluyorsa bu alan seçilebilir ancak tüm servisler bunu desteklemez.
                    
                    </td>
				</tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Wildcards</td>
                  <td width="78%" class="vtable">
                    <input name="wildcard" type="checkbox" id="wildcard" value="yes" <?php if ($pconfig['wildcard']) echo "checked"; ?>>
                    Wildcard Etkinleştir</td>
				</tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Kullanıcı adı</td>
                  <td width="78%" class="vtable">
                    <input name="username" type="text" class="formfld" id="username" size="20" value="<?=htmlspecialchars($pconfig['username']);?>">
                  </td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Şifre</td>
                  <td width="78%" class="vtable">
                    <input name="password" type="password" class="formfld" id="password" size="20" value="<?=htmlspecialchars($pconfig['password']);?>">
                  </td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet" onClick="enable_change(true)">
                  </td>
                <tr>
                  <td colspan="2" class="list" height="12">&nbsp;</td>
                </tr>
                </tr>
                <tr>
                  <td colspan="2" valign="top" class="optsect_t">
				  <table border="0" cellspacing="0" cellpadding="0" width="100%">
				  <tr><td class="optsect_s"><strong>RFC 2136 Dinamik DNS güncellemesi</strong></td>
				  <td align="right" class="optsect_s"><input name="dnsupdate_enable" type="checkbox" value="yes" <?php if ($pconfig['dnsupdate_enable']) echo "checked"; ?> onClick="enable_change(false)"> 
				  <strong>Etkinleştir</strong></td></tr>
				  </table></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Hostname</td>
                  <td width="78%" class="vtable">
                    <input name="dnsupdate_host" type="text" class="formfld" id="dnsupdate_host" size="30" value="<?=htmlspecialchars($pconfig['dnsupdate_host']);?>">
                  </td>
				</tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Sunucu</td>
                  <td width="78%" class="vtable">
                    <input name="dnsupdate_server" type="text" class="formfld" id="dnsupdate_server" size="30" value="<?=htmlspecialchars($pconfig['dnsupdate_server']);?>">
                  </td>
				</tr>
                <tr>
                  <td valign="top" class="vncellreq">TTL</td>
                  <td class="vtable">
                    <input name="dnsupdate_ttl" type="text" class="formfld" id="dnsupdate_ttl" size="6" value="<?=htmlspecialchars($pconfig['dnsupdate_ttl']);?>">
                  saniye</td>
                </tr>
                <tr>
                  <td valign="top" class="vncellreq">Anahtar adı</td>
                  <td class="vtable">
                    <input name="dnsupdate_keyname" type="text" class="formfld" id="dnsupdate_keyname" size="30" value="<?=htmlspecialchars($pconfig['dnsupdate_keyname']);?>">
                    <br>
                   DNS sunucu üzerinde bu ayar aynı olmalıdır.</td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Anahtar tipi </td>
                  <td class="vtable">
				  <input name="dnsupdate_keytype" type="radio" value="zone" <?php if ($pconfig['dnsupdate_keytype'] == "zone") echo "checked"; ?>> Zone &nbsp;
                  <input name="dnsupdate_keytype" type="radio" value="host" <?php if ($pconfig['dnsupdate_keytype'] == "host") echo "checked"; ?>> Host &nbsp;
                  <input name="dnsupdate_keytype" type="radio" value="user" <?php if ($pconfig['dnsupdate_keytype'] == "user") echo "checked"; ?>> User
				</tr>
                <tr>
                  <td valign="top" class="vncellreq">Anahtar</td>
                  <td class="vtable">
                    <input name="dnsupdate_keydata" type="text" class="formfld" id="dnsupdate_keydata" size="70" value="<?=htmlspecialchars($pconfig['dnsupdate_keydata']);?>">
                    <br>
                    HMAC-MD5 anahtarı buraya yapıştırın.</td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Protokol</td>
                  <td width="78%" class="vtable">
                    <input name="dnsupdate_usetcp" type="checkbox" id="dnsupdate_usetcp" value="yes" <?php if ($pconfig['dnsupdate_usetcp']) echo "checked"; ?>>
                    <strong>UDP yerine TCP kullan</strong></td>
				</tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet" onClick="enable_change(true)">
                  </td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"><span class="vexpl"><span class="red"><strong>Bilgi:<br>
                    </strong></span>Bir DNS sunucu <a href="system.php">Sistem:
                    Genel ayarlar</a> bölümünde tanımlanmalıdır.  Güncellemenin çalışması için 
					WAN aygıtı üzerinde DHCP/PPP tarafından DNS sunucu listesinin yazılmasına izin veriniz.
					
					</span></td>
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
