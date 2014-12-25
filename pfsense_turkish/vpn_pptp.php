<?php
/*
	vpn_pptp.php
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>.
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

if (!is_array($config['pptpd']['radius'])) {
	$config['pptpd']['radius'] = array();
}
$pptpcfg = &$config['pptpd'];

$pconfig['remoteip'] = $pptpcfg['remoteip'];
$pconfig['localip'] = $pptpcfg['localip'];
$pconfig['redir'] = $pptpcfg['redir'];
$pconfig['mode'] = $pptpcfg['mode'];
$pconfig['wins'] = $pptpcfg['wins'];
$pconfig['req128'] = isset($pptpcfg['req128']);
$pconfig['radiusenable'] = isset($pptpcfg['radius']['enable']);
$pconfig['radacct_enable'] = isset($pptpcfg['radius']['accounting']);
$pconfig['radiusserver'] = $pptpcfg['radius']['server'];
$pconfig['radiussecret'] = $pptpcfg['radius']['secret'];

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['mode'] == "server") {
		$reqdfields = explode(" ", "localip remoteip");
		$reqdfieldsn = explode(",", "Server address,Remote start address");
		
		if ($_POST['radiusenable']) {
			$reqdfields = array_merge($reqdfields, explode(" ", "radiusserver radiussecret"));
			$reqdfieldsn = array_merge($reqdfieldsn, 
				explode(",", "RADIUS sunucu adresi,RADIUS shared secret"));
		}
		
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
		
		if (($_POST['localip'] && !is_ipaddr($_POST['localip']))) {
			$input_errors[] = "Geçerli sunucu adı tanımlanmalıdır.";
		}
		if (($_POST['subnet'] && !is_ipaddr($_POST['remoteip']))) {
			$input_errors[] = "Geçerli uzak başlangıç adresi tanımlanmalıdır.";
		}
		if (($_POST['radiusserver'] && !is_ipaddr($_POST['radiusserver']))) {
			$input_errors[] = "Geçerli sunucu adresi tanımlanmalıdır.";
		}
		
		if (!$input_errors) {	
			$_POST['remoteip'] = $pconfig['remoteip'] = gen_subnet($_POST['remoteip'], $g['pptp_subnet']);
			$subnet_start = ip2long($_POST['remoteip']);
			$subnet_end = ip2long($_POST['remoteip']) + $g['n_pptp_units'] - 1;
						
			if ((ip2long($_POST['localip']) >= $subnet_start) && 
			    (ip2long($_POST['localip']) <= $subnet_end)) {
				$input_errors[] = "Tanımlanan sunucu adresi uzak altağ geçersiz kabul ediyor.";	
			}
			if ($_POST['localip'] == $config['interfaces']['lan']['ipaddr']) {
				$input_errors[] = "Tanımlanan sunucu adresi LAN arayüzündeki adres ile aynı.";	
			}
		}
	} else if ($_POST['mode'] == "redir") {
		$reqdfields = explode(" ", "redir");
		$reqdfieldsn = explode(",", "PPTP yönlendirme hedef adresi");
		
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
		
		if (($_POST['redir'] && !is_ipaddr($_POST['redir']))) {
			$input_errors[] = "Geçerli bir adres tanımlanmalıdır.";
		}
	} else {
		unset($config['pptpd']['mode']);
		write_config();
	}

	if (!$input_errors) {
		$pptpcfg['remoteip'] = $_POST['remoteip'];
		$pptpcfg['redir'] = $_POST['redir'];
		$pptpcfg['localip'] = $_POST['localip'];
		$pptpcfg['mode'] = $_POST['mode'];
		$pptpcfg['wins'] = $_POST['wins'];
		$pptpcfg['radius']['server'] = $_POST['radiusserver'];
		$pptpcfg['radius']['secret'] = $_POST['radiussecret'];

		if($_POST['req128'] == "yes") 
			$pptpcfg['req128'] = true;
		else
			unset($pptpcfg['req128']);

		if($_POST['radiusenable'] == "yes") 
			$pptpcfg['radius']['enable'] = true;
		else 
			unset($pptpcfg['radius']['enable']);
			
		if($_POST['radacct_enable'] == "yes") 
			$pptpcfg['radius']['accounting'] = true;
		else 
			unset($pptpcfg['radius']['accounting']);
		
		write_config();
		
		$retval = 0;
		
		config_lock();
		$retval = vpn_setup();
		config_unlock();
		
		$savemsg = get_std_save_message($retval);
		
		filter_configure();
	}
}

$pgtitle = "VPN PPTP";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<script language="JavaScript">
<!--
function get_radio_value(obj)
{
	for (i = 0; i < obj.length; i++) {
		if (obj[i].checked)
			return obj[i].value;
	}
	return null;
}

function enable_change(enable_over) {
	if ((get_radio_value(document.iform.mode) == "server") || enable_over) {
		document.iform.remoteip.disabled = 0;
		document.iform.localip.disabled = 0;
		document.iform.req128.disabled = 0;
		document.iform.radiusenable.disabled = 0;
		document.iform.wins.disabled = 0;
		
		if (document.iform.radiusenable.checked || enable_over) {
			document.iform.radacct_enable.disabled = 0;
			document.iform.radiusserver.disabled = 0;
			document.iform.radiussecret.disabled = 0;
		} else {
			document.iform.radacct_enable.disabled = 1;
			document.iform.radiusserver.disabled = 1;
			document.iform.radiussecret.disabled = 1;
		}
	} else {
		document.iform.remoteip.disabled = 1;
		document.iform.localip.disabled = 1;
		document.iform.req128.disabled = 1;
		document.iform.radiusenable.disabled = 1;
		document.iform.radacct_enable.disabled = 1;
		document.iform.radiusserver.disabled = 1;
		document.iform.radiussecret.disabled = 1;
		document.iform.wins.disabled = 1;
	}
	if ((get_radio_value(document.iform.mode) == "redir") || enable_over) {
		document.iform.redir.disabled = 0;
	} else {
		document.iform.redir.disabled = 1;
	}
}
//-->
</script>
<form action="vpn_pptp.php" method="post" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
<?php
	$tab_array = array();
	$tab_array[0] = array("Ayarlar", true, "vpn_pptp.php");
	$tab_array[1] = array("Kullanıcılar", false, "vpn_pptp_users.php");
	display_top_tabs($tab_array);
?>  
  </td></tr>
  <tr> 
    <td>
<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td width="22%" valign="top" class="vtable">&nbsp;</td>
                  <td width="78%" class="vtable"> 
                    <input name="mode" type="radio" onclick="enable_change(false)" value="off"
				  	<?php if (($pconfig['mode'] != "server") && ($pconfig['mode'] != "redir")) echo "checked";?>>
                    Kapalı</td>
                <tr> 
                  <td width="22%" valign="top" class="vtable">&nbsp;</td>
                  <td width="78%" class="vtable">
<input type="radio" name="mode" value="redir" onclick="enable_change(false)" <?php if ($pconfig['mode'] == "redir") echo "checked"; ?>>
                    Gelen PPTP bağlantılarını buraya yönlendir:</td>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">PPTP yönlendirme</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="redir" type="text" class="formfld" id="redir" size="20" value="<?=htmlspecialchars($pconfig['redir']);?>"> 
                    <br>
					Host kabul edeceği gelen PPTP bağlantılarına ait IP adresini giriniz.
                    </td>
                <tr> 
                  <td width="22%" valign="top" class="vtable">&nbsp;</td>
                  <td width="78%" class="vtable">
<input type="radio" name="mode" value="server" onclick="enable_change(false)" <?php if ($pconfig['mode'] == "server") echo "checked"; ?>>
                    PPTP sunucuyu aktifleştir</td>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">En fazla eş zamanlı bağlantı</td>
                  <td width="78%" class="vtable"> 
                    <?=$g['n_pptp_units'];?>
                  </td>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Sunucu adresi</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="localip" type="text" class="formfld" id="localip" size="20" value="<?=htmlspecialchars($pconfig['localip']);?>"> 
                    <br>
					PPTP sunucunun bu tarafta tüm kullanıcılar için kullanacağı IP adresini giriniz.
                    </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Uzak adres aralığı</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="remoteip" type="text" class="formfld" id="remoteip" size="20" value="<?=htmlspecialchars($pconfig['remoteip']);?>">
                    / 
                    <?=$g['pptp_subnet'];?>
                    <br>
					Bağlanacak kullanıcılara ait bir alt ağ tanımlayınız.
                    <br>
                    PPTP sunucu bu bilgiyi atayacaktır
					<?=$g['n_pptp_units'];?>
                    atanacak adresler yukarıda girilen adresten başlayacaktır.
					</td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">RADIUS</td>
                  <td width="78%" class="vtable"> 
                      <input name="radiusenable" type="checkbox" id="radiusenable" onclick="enable_change(false)" value="yes" <?php if ($pconfig['radiusenable']) echo "checked"; ?>>
                      <strong>Kimlik doğrulama için bir RADIUS sunucu kullan<br>
                      </strong>
					  Bütün kullanıcıların RADIUS üzerinden kimlik doğrulaması seçildiğinde yerel 
					  sunucu üzerinde tanımlı olan kullanıcılar kullanılmayacaktır.
                      <br>
                      <br>
                      <input name="radacct_enable" type="checkbox" id="radacct_enable" onclick="enable_change(false)" value="yes" <?php if ($pconfig['radacct_enable']) echo "checked"; ?>>
                      <strong>RADIUS hesaplamayı aktifleştir<br>
                      </strong>RADIUS sunucuya hesaplanan paket bilgilerini gönder.</td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">RADIUS sunucu </td>
                  <td width="78%" class="vtable">
                      <input name="radiusserver" type="text" class="formfld" id="radiusserver" size="20" value="<?=htmlspecialchars($pconfig['radiusserver']);?>">
                      <br>
					  RADIUS sunucuya ait IP adresini giriniz.
                      </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">RADIUS sunucu 'secret' cümlesi</td>
                  <td width="78%" valign="top" class="vtable">
                      <input name="radiussecret" type="password" class="formfld" id="radiussecret" size="20" value="<?=htmlspecialchars($pconfig['radiussecret']);?>">
                      <br>
					  RADIUS sunucu üzerinde tanımlı olan sorgulama için gerekli olan 'secret' anahtar cümlecigini yazınız.
                      </td>
                </tr>

                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">WINS Sunucu</td>
                  <td width="78%" valign="top" class="vtable">
                      <input name="wins" class="formfld" id="wins" size="20" value="<?=htmlspecialchars($pconfig['wins']);?>">
                  </td>
                </tr>

                <tr> 
                  <td height="16" colspan="2" valign="top"></td>
                </tr>
                <tr> 
                  <td width="22%" valign="middle">&nbsp;</td>
                  <td width="78%" class="vtable"> 
                    <input name="req128" type="checkbox" id="req128" value="yes" <?php if ($pconfig['req128']) echo "checked"; ?>> 
                    <strong>128-bit şifreleme</strong><br>
                    Eğer bu alan seçilirse sadece 128-bit şifreleme kabul edilecek. Seçilmezse
					40-bit ve 56-bit şifreleme de kabul edilecektir. Bağlantı sırasındaki şifrelemede daima 
					sunucu bu şifreleme seviyelerini zorlayacaktır.</td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet" onclick="enable_change(true)"> 
                  </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"><span class="vexpl"><span class="red"><strong>Not:<br>
                    </strong></span>Unutmayınız <a href="firewall_rules.php?if=pptp">bir firewall kuralı</a> eklerek PPTP 
					erişimine izin vermelisiniz.
                    </span></td>
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
