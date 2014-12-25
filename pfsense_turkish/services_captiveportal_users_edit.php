<?php 
/*
	services_captiveportal_users_edit.php
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	Copyright (C) 2005 Pascal Suter <d-monodev@psuter.ch>.
	All rights reserved. 
	(files was created by Pascal based on the source code of services_captiveportal.php from Manuel)
	
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
$pgtitle = "Servisler: Hotspot: Kullanıcı Düzenle";
require("guiconfig.inc");

if (!is_array($config['captiveportal']['user'])) {
	$config['captiveportal']['user'] = array();
}
captiveportal_users_sort();
$a_user = &$config['captiveportal']['user'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_user[$id]) {
	$pconfig['username'] = $a_user[$id]['name'];
	$pconfig['fullname'] = $a_user[$id]['fullname'];
	$pconfig['expirationdate'] = $a_user[$id]['expirationdate'];
}

if ($_POST) {
	
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if (isset($id) && ($a_user[$id])) {
		$reqdfields = explode(" ", "username");
		$reqdfieldsn = explode(",", "Username");
	} else {
		$reqdfields = explode(" ", "username password");
		$reqdfieldsn = explode(",", "Username,Password");
	}
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if (preg_match("/[^a-zA-Z0-9\.\-_]/", $_POST['username']))
		$input_errors[] = "Kullanıcı adı geçersiz karakterler içermektedir.";
		
	if (($_POST['password']) && ($_POST['password'] != $_POST['password2']))
		$input_errors[] = "Girilen şifreler aynı değil.";

	//check for a valid expirationdate if one is set at all (valid means, strtotime() puts out a time stamp
	//so any strtotime compatible time format may be used. to keep it simple for the enduser, we only claim 
	//to accept MM/DD/YYYY as inputs. advanced users may use inputs like "+1 day", which will be converted to 
	//MM/DD/YYYY based on "now" since otherwhise such an entry would lead to a never expiring expirationdate
	if ($_POST['expirationdate']){
		if(strtotime($_POST['expirationdate']) > 0){
			if (strtotime("-1 day") > strtotime(date("m/d/Y",strtotime($_POST['expirationdate'])))){
				$input_errors[] = "The expiration date lies in the past.";			
			} else {
				//convert from any strtotime compatible date to MM/DD/YYYY
				$expdate = strtotime($_POST['expirationdate']);
				$_POST['expirationdate'] = date("m/d/Y",$expdate);
			}
		} else {
			$input_errors[] = "Geçersiz tarih formatı MM/DD/YYYY (ay/gun/yıl) biçiminde yazınız.";
		}
	}
	
	if (!$input_errors && !(isset($id) && $a_user[$id])) {
		/* make sure there are no dupes */
		foreach ($a_user as $userent) {
			if ($userent['name'] == $_POST['username']) {
				$input_errors[] = "Aynı kullanıcı adıyla bir kayıt zaten mevcuttur.";
				break;
			}
		}
	}
	
	if (!$input_errors) {
	
		if (isset($id) && $a_user[$id])
			$userent = $a_user[$id];
		
		$userent['name'] = $_POST['username'];
		$userent['fullname'] = $_POST['fullname'];
		$userent['expirationdate'] = $_POST['expirationdate'];
		
		if ($_POST['password'])
			$userent['password'] = md5($_POST['password']);
		
		if (isset($id) && $a_user[$id])
			$a_user[$id] = $userent;
		else
			$a_user[] = $userent;
		
		write_config();
		
		header("Location: services_captiveportal_users.php");
		exit;
	}
}

include("head.inc");

?>
<?php include("fbegin.inc"); ?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<p class="pgtitle"><?=$pgtitle?></p>
<script language="javascript" type="text/javascript" src="datetimepicker.js">
<!--
//Date Time Picker script- by TengYong Ng of http://www.rainforestnet.com
//Script featured on JavaScript Kit (http://www.javascriptkit.com)
//For this script, visit http://www.javascriptkit.com
// -->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<form action="services_captiveportal_users_edit.php" method="post" name="iform" id="iform">
  <table width="100%" border="0" cellpadding="6" cellspacing="0">
	<tr> 
	  <td width="22%" valign="top" class="vncellreq">Kullanıcı adı</td>
	  <td width="78%" class="vtable"> 
		<?=$mandfldhtml;?><input name="username" type="text" class="formfld" id="username" size="20" value="<?=htmlspecialchars($pconfig['username']);?>"> 
		</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncellreq">Şifre</td>
	  <td width="78%" class="vtable"> 
		<?=$mandfldhtml;?><input name="password" type="password" class="formfld" id="password" size="20"><br>
		<?=$mandfldhtml;?><input name="password2" type="password" class="formfld" id="password2" size="20">
		&nbsp;(confirmation)<?php if (isset($id) && $a_user[$id]): ?><br>
        <span class="vexpl">Eğer kullanıcı şifresini değiştirmek isterseniz iki defa girmelisiniz
        </span><?php endif; ?>
		</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">Tam adı</td>
	  <td width="78%" class="vtable"> 
		<input name="fullname" type="text" class="formfld" id="fullname" size="20" value="<?=htmlspecialchars($pconfig['fullname']);?>">
		<br>
		<span class="vexpl">Kullanıcının tam adı, sadece sizin bilginiz olması için</span></td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">Son kullanım tarihi</td>
	  <td width="78%" class="vtable"> 
		<input name="expirationdate" type="text" class="formfld" id="expirationdate" size="10" value="<?=$pconfig['expirationdate'];?>">
		<a href="javascript:NewCal('expirationdate','mmddyyyy')"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_cal.gif" width="16" height="16" border="0" alt="Pick a date"></a> 
		<br> 
		<span class="vexpl">Eğer zaman aşımı işlemine tabi olmaması için boş bırakınız, diğer türlü mm/dd/yyyy (ay/gun/yil) biçiminde bir tarih yazınız. </span></td>
	</tr>
	<tr> 
	  <td width="22%" valign="top">&nbsp;</td>
	  <td width="78%"> 
		<input name="Submit" type="submit" class="formbtn" value="Kaydet"> 
		<?php if (isset($id) && $a_user[$id]): ?>
		<input name="id" type="hidden" value="<?=$id;?>">
		<?php endif; ?>
	  </td>
	</tr>
  </table>
 </form>
<?php include("fend.inc"); ?>
</body>
</html>
