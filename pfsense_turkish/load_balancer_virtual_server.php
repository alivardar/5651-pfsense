<?php
/* $Id$ */
/*
	load_balancer_virtual_server.php
	part of pfSense (http://www.pfsense.com/)

	Copyright (C) 2005 Bill Marquette <bill.marquette@gmail.com>.
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

require_once("guiconfig.inc");
require_once("vslb.inc");

if (!is_array($config['load_balancer']['virtual_server'])) {
	$config['load_balancer']['virtual_server'] = array();
}
$a_vs = &$config['load_balancer']['virtual_server'];

if ($_POST) {
	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		config_lock();
		$retval |= filter_configure();
		$retval |= slbd_configure();
		config_unlock();
		$savemsg = get_std_save_message($retval);
		unlink_if_exists($d_vsconfdirty_path);
	}
}

if ($_GET['act'] == "del") {
	if ($a_vs[$_GET['id']]) {

		if (!$input_errors) {
			unset($a_vs[$_GET['id']]);
			write_config();
			touch($d_vsconfdirty_path);
			header("Location: load_balancer_virtual_server.php");
			exit;
		}
	}
}

$pgtitle = "Servisler: Yük Dengeleyici: Sanal Sunucular";
include("head.inc");

?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="load_balancer_virtual_server.php" method="post">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_vsconfdirty_path)): ?><p>
<?php print_info_box_np("Sanal sunucu ayarları değiştirildi. <br>Değişikliklerin geçerli olabilmesi için uygulanması gerekmektedir.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <?php
        /* active tabs */
        $tab_array = array();
        $tab_array[] = array("Pools", false, "load_balancer_pool.php");
        $tab_array[] = array("Virtual Servers", true, "load_balancer_virtual_server.php");
        display_top_tabs($tab_array);
  ?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="10%" class="listhdrr">İsim</td>
                  <td width="20%" class="listhdrr">Sunucu adresi</td>
                  <td width="10%" class="listhdrr">Port</td>
                  <td width="20%" class="listhdrr">Pool</td>
                  <td width="30%" class="listhdr">Açıklama</td>
                  <td width="10%" class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td width="17"></td>
                        <td valign="middle"><a href="load_balancer_virtual_server_edit.php"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
		  </td>
		</tr>
			  <?php $i = 0; foreach ($a_vs as $vsent): ?>
                <tr>
                  <td class="listlr" ondblclick="document.location='load_balancer_virtual_server_edit.php?id=<?=$i;?>';">
			<?=$vsent['name'];?>
                  </td>
                  <td class="listlr" ondblclick="document.location='load_balancer_virtual_server_edit.php?id=<?=$i;?>';">
			<?=$vsent['ipaddr'];?>
                  </td>
                  <td class="listlr" ondblclick="document.location='load_balancer_virtual_server_edit.php?id=<?=$i;?>';">
			<?=$vsent['port'];?>
                  <td class="listlr" align="center" ondblclick="document.location='load_balancer_virtual_server_edit.php?id=<?=$i;?>';">
			<?=$vsent['pool'];?>
                  </td>
                  <td class="listbg" ondblclick="document.location='load_balancer_virtual_server_edit.php?id=<?=$i;?>';">
                    <font color="#FFFFFF"><?=htmlspecialchars($vsent['desc']);?>&nbsp;
                  </td>
                  <td class="list" nowrap>
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
                        <td valign="middle"><a href="load_balancer_virtual_server_edit.php?id=<?=$i;?>"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0"></a></td>
                        <td valign="middle"><a href="load_balancer_virtual_server.php?act=del&id=<?=$i;?>" onclick="return confirm('Bu girdiyi silmek istediğinizden eminmisiniz?')"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <?php $i++; endforeach; ?>
                <tr>
                  <td class="list" colspan="5"></td>
                  <td class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td width="17"></td>
                        <td valign="middle"><a href="load_balancer_virtual_server_edit.php"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
	   </div>
	</table>
            </form>
<?php include("fend.inc"); ?>
</body>
</html>
