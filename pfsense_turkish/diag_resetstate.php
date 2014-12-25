<?php
/* $Id$ */
/*
	diag_resetstate.php
	Copyright (C) 2004 Scott Ullrich
	All rights reserved.

	originially part of m0n0wall (http://m0n0.ch/wall)
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

if ($_POST) {
	$savemsg = "";
	if ($_POST['statetable']) {
		filter_flush_state_table();
		if ($savemsg)
			$savemsg .= " ";
		$savemsg .= "Durum tablosu başarıyla boşaltıldı.";
	}
}

$pgtitle = "Tanımlama: Durumu Yenileme";
include("head.inc");

?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
        <form action="diag_resetstate.php" method="post" name="iform" id="iform">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td>
	<?php
		$tab_array = array();
		$tab_array[0] = array("States", false, "diag_dump_states.php");
		$tab_array[1] = array("Reset States", true, "diag_resetstate.php");
		display_top_tabs($tab_array);
	?>
	</td></tr>
	<tr><td class="tabcont">	    
	    
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td width="22%" valign="top" class="vtable">&nbsp;</td>
                  <td width="78%" class="vtable"> <p>
                      <input name="statetable" type="checkbox" id="statetable" value="yes" checked>
                      <strong>Firewall durum tablosu</strong><br>
                      <span class="vexpl"><br>
                      Resetting the state tables will remove all entries from
                      the corresponding tables. This means that all open connections
                      will be broken and will have to be re-established. This
                      may be necessary after making substantial changes to the
                      firewall and/or NAT rules, especially if there are IP protocol
                      mappings (e.g. for PPTP or IPv6) with open connections.<br>
                      <br>
                      </span><span class="vexpl">The firewall will normally leave
                      the state tables intact when changing rules.<br>
                      <br>
                      NOTE: If you reset the firewall state table, the browser
                      session may appear to be hung after clicking &quot;Reset&quot;.
                      Simply refresh the page to continue.</span></p>
                    </td>
				</tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Sıfırla">
                  </td>
                </tr>
              </table>
	 </td></tr>
	</table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
