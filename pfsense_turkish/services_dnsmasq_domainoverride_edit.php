<?php
/*
       services_dnsmasq_domainoverride_edit.php
       part of m0n0wall (http://m0n0.ch/wall)

       Copyright (C) 2003-2005 Bob Zoller <bob@kludgebox.com> and Manuel Kasper <mk@neon1.net>.
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

if (!is_array($config['dnsmasq']['domainoverrides'])) {
       $config['dnsmasq']['domainoverrides'] = array();
}
$a_domainOverrides = &$config['dnsmasq']['domainoverrides'];

$id = $_GET['id'];
if (isset($_POST['id']))
       $id = $_POST['id'];

if (isset($id) && $a_domainOverrides[$id]) {
       $pconfig['domain'] = $a_domainOverrides[$id]['domain'];
       $pconfig['ip'] = $a_domainOverrides[$id]['ip'];
       $pconfig['descr'] = $a_domainOverrides[$id]['descr'];
}

if ($_POST) {

       unset($input_errors);
       $pconfig = $_POST;

       /* input validation */
       $reqdfields = explode(" ", "domain ip");
       $reqdfieldsn = explode(",", "Domain,IP address");

       do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

       if (($_POST['domain'] && !is_domain($_POST['domain']))) {
               $input_errors[] = "Geçersiz bir alanadı tanımlandı.";
       }
       if (($_POST['ip'] && !is_ipaddr($_POST['ip']))) {
               $input_errors[] = "Geçersiz bir IP adresi tanımlandı.";
       }

       if (!$input_errors) {
               $doment = array();
               $doment['domain'] = $_POST['domain'];
               $doment['ip'] = $_POST['ip'];
               $doment['descr'] = $_POST['descr'];

               if (isset($id) && $a_domainOverrides[$id])
                       $a_domainOverrides[$id] = $doment;
               else
                       $a_domainOverrides[] = $doment;

               touch($d_dnsmasqdirty_path);

               write_config();

               header("Location: services_dnsmasq.php");
               exit;
       }
}

$pgtitle = "Servisler: DNS Yönlendirici: Geçersiz Alan Adını Düzenle";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="services_dnsmasq_domainoverride_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                               <tr>
                  <td width="22%" valign="top" class="vncellreq">Domain</td>
                  <td width="78%" class="vtable">
                    <?=$mandfldhtml;?><input name="domain" type="text" class="formfld" id="domain" size="40" value="<?=htmlspecialchars($pconfig['domain']);?>">
                    <br> <span class="vexpl">Etki alanını geçersiz kıl (Bilgi: Bu geçerli bir TLD olmak zorunda değildir!)<br>
                    örnek <em>test</em></span></td>
                </tr>
                               <tr>
                  <td width="22%" valign="top" class="vncellreq">IP adresi</td>
                  <td width="78%" class="vtable">
                    <?=$mandfldhtml;?><input name="ip" type="text" class="formfld" id="ip" size="40" value="<?=htmlspecialchars($pconfig['ip']);?>">
                    <br> <span class="vexpl">Bu domain için yetkili DNS sunucu IP adresi.<br>
                    örnek: <em>192.168.100.100</em></span></td>
                </tr>
                               <tr>
                  <td width="22%" valign="top" class="vncell">Açıklama</td>
                  <td width="78%" class="vtable">
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">Bu alana bir açıklama yazılabilir. (ayırmadan).</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet">
                    <?php if (isset($id) && $a_domainOverrides[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
