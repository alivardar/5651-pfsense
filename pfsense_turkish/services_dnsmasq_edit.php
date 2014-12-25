<?php 
/* $Id$ */
/*
	services_dnsmasq_edit.php
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

if (!is_array($config['dnsmasq']['hosts'])) 
	$config['dnsmasq']['hosts'] = array();

hosts_sort();
$a_hosts = &$config['dnsmasq']['hosts'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_hosts[$id]) {
	$pconfig['host'] = $a_hosts[$id]['host'];
	$pconfig['domain'] = $a_hosts[$id]['domain'];
	$pconfig['ip'] = $a_hosts[$id]['ip'];
	$pconfig['descr'] = $a_hosts[$id]['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "domain ip");
	$reqdfieldsn = explode(",", "Domain,IP address");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if (($_POST['host'] && !is_hostname($_POST['host']))) 
		$input_errors[] = "Geçerli bir host tanımlanmalıdır.";

	if (($_POST['domain'] && !is_domain($_POST['domain']))) 
		$input_errors[] = "Geçerli bir domain tanımlanmalıdır.";
		
	if (($_POST['ip'] && !is_ipaddr($_POST['ip']))) 
		$input_errors[] = "Geçerli bir IP adresi tanımlanmalıdır.";

	/* check for overlaps */
	foreach ($a_hosts as $hostent) {
		if (isset($id) && ($a_hosts[$id]) && ($a_hosts[$id] === $hostent))
			continue;

		if (($hostent['host'] == $_POST['host']) && ($hostent['domain'] == $_POST['domain'])) {
			$input_errors[] = "Bu host/domain halihazırda tanımlanmıştır.";
			break;
		}
	}

	if (!$input_errors) {
		$hostent = array();
		$hostent['host'] = $_POST['host'];
		$hostent['domain'] = $_POST['domain'];
		$hostent['ip'] = $_POST['ip'];
		$hostent['descr'] = $_POST['descr'];

		if (isset($id) && $a_hosts[$id])
			$a_hosts[$id] = $hostent;
		else
			$a_hosts[] = $hostent;
		
		touch($d_hostsdirty_path);
		
		write_config();
		
		header("Location: services_dnsmasq.php");
		exit;
	}
}

$pgtitle = "Servisler: DNS yönlendirici: Host düzenleme";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
        <form action="services_dnsmasq_edit.php" method="post" name="iform" id="iform">
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td width="22%" valign="top" class="vncell">Host</td>
                  <td width="78%" class="vtable"> 
                    <input name="host" type="text" class="formfld" id="host" size="40" value="<?=htmlspecialchars($pconfig['host']);?>">
                    <br> <span class="vexpl">Host adı, domain kısmı olmadan<br>
                    örnek: <em>myhost</em></span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncellreq">Domain</td>
                  <td width="78%" class="vtable"> 
                    <input name="domain" type="text" class="formfld" id="domain" size="40" value="<?=htmlspecialchars($pconfig['domain']);?>">
                    <br> <span class="vexpl">(Domain) Alan adı<br>
                    örnek: <em>ornek.com</em></span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncellreq">IP adresi</td>
                  <td width="78%" class="vtable"> 
                    <input name="ip" type="text" class="formfld" id="ip" size="40" value="<?=htmlspecialchars($pconfig['ip']);?>">
                    <br> <span class="vexpl">Host ait IP adresi<br>
                    örnek: <em>192.168.100.100</em></span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Açıklama</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">Bu alan açıklama girebilirsiniz (Ayrı olmadan).</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet"> <input class="formbtn" type="button" value="Vazgeç" onclick="history.back()">
                    <?php if (isset($id) && $a_hosts[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
        </table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
