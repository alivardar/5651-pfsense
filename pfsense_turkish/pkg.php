<?php
/* $Id$ */
/*
    pkg.php
    Copyright (C) 2004, 2005 Scott Ullrich
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
require_once("pkg-utils.inc");

function gettext($text) {
	return $text;
}

function gentitle_pkg($pgname) {
	global $config;
	return $config['system']['hostname'] . "." . $config['system']['domain'] . " - " . $pgname;
}

$xml = htmlspecialchars($_GET['xml']);

if($xml == "") {
            print_info_box_np(gettext("ERROR: No package defined."));
            die;
} else {
            $pkg = parse_xml_config_pkg("/usr/local/pkg/" . $xml, "packagegui");
}

if($pkg['donotsave'] <> "") {
	header("Location:  pkg_edit.php?xml=" . $xml);
}

if ($pkg['include_file'] != "") {
	require_once($pkg['include_file']);
}

$package_name = $pkg['menu'][0]['name'];
$section      = $pkg['menu'][0]['section'];
$config_path  = $pkg['configpath'];
$title	      = $pkg['title'];

$evaledvar = $config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'];

if ($_GET['act'] == "del") {
	    // loop through our fieldnames and automatically setup the fieldnames
	    // in the environment.  ie: a fieldname of username with a value of
            // testuser would automatically eval $username = "testuser";
	    foreach ($evaledvar as $ip) {
			if($pkg['adddeleteeditpagefields']['columnitem'])
			  foreach ($pkg['adddeleteeditpagefields']['columnitem'] as $column) {
				  ${xml_safe_fieldname($column['fielddescr'])} = $ip[xml_safe_fieldname($column['fieldname'])];
			  }
	    }

	    $a_pkg = &$config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'];

	    if ($a_pkg[$_GET['id']]) {
			unset($a_pkg[$_GET['id']]);
			write_config();
			if($pkg['custom_delete_php_command'] <> "") {
			    if($pkg['custom_php_command_before_form'] <> "")
					eval($pkg['custom_php_command_before_form']);
		    		eval($pkg['custom_delete_php_command']);
			}
			header("Location:  pkg.php?xml=" . $xml);
			exit;
	    }
}

$evaledvar = $config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'];

if($pkg['custom_php_global_functions'] <> "")
        eval($pkg['custom_php_global_functions']);

if($pkg['custom_php_command_before_form'] <> "")
	eval($pkg['custom_php_command_before_form']);

$pgtitle = $title;
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php
include("fbegin.inc");
?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="pkg.php" method="post">
<? if($_GET['savemsg'] <> "") $savemsg = htmlspecialchars($_GET['savemsg']); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<?php
if ($pkg['tabs'] <> "") {
    echo '<tr><td>';
    $tab_array = array();
    foreach($pkg['tabs']['tab'] as $tab) {
        if(isset($tab['active'])) {
                $active = true;
        } else {
                $active = false;
        }
        $urltmp = "";
        if($tab['url'] <> "") $urltmp = $tab['url'];
        if($tab['xml'] <> "") $urltmp = "pkg_edit.php?xml=" . $tab['xml'];

        $myurl = getenv("HTTP_HOST");
        // eval url so that above $myurl item can be processed if need be.
        $url = str_replace('$myurl', $myurl, $urltmp);
        $tab_array[] = array(
                                $tab['text'],
                                $active,
                                $url
                        );
    }
    display_top_tabs($tab_array);
    echo '</td></tr>';
}
?>
<tr><td><div id="mainarea"><table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                <?php
                $cols = 0;
		if($pkg['adddeleteeditpagefields']['columnitem'] <> "") {
		    foreach ($pkg['adddeleteeditpagefields']['columnitem'] as $column) {
			echo "<td class=\"listhdrr\">" . $column['fielddescr'] . "</td>";
			$cols++;
		    }
		}
                echo "</tr>";
		    $i=0;
		    if($evaledvar)
		    foreach ($evaledvar as $ip) {
			echo "<tr valign=\"top\">\n";
			if($pkg['adddeleteeditpagefields']['columnitem'] <> "")
				foreach ($pkg['adddeleteeditpagefields']['columnitem'] as $column) {
				   ?>
					<td class="listlr" ondblclick="document.location='pkg_edit.php?xml=<?=$xml?>&act=edit&id=<?=$i;?>';">
						<?php
						    $fieldname = $ip[xml_safe_fieldname($column['fieldname'])];
						    if($column['type'] == "checkbox") {
							if($fieldname == "") {
							    echo gettext("No");
							} else {
							    echo gettext("Yes");
							}
						    } else {
							echo $column['prefix'] . $fieldname . $column['suffix'];
						    }
						?>
					</td>
				   <?php
				}
			?>
			<td valign="middle" class="list" nowrap>
                          <table border="0" cellspacing="0" cellpadding="1">
                            <tr>
                              <td valign="middle"><a href="pkg_edit.php?xml=<?=$xml?>&act=edit&id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0"></a></td>
			      <td valign="middle"><a href="pkg.php?xml=<?=$xml?>&act=del&id=<?=$i;?>" onclick="return confirm('<?=gettext("Do you really want to delete this item?");?>')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
                            </tr>
                          </table>
			</td>
			<?php
			echo "</tr>\n";
			$i++;
		    }
		?>
               <tr>
                 <td colspan="<?=$cols?>"></td>
                 <td>
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
                        <td valign="middle"><a href="pkg_edit.php?xml=<?=$xml?>&id=<?=$i?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                     </tr>
                   </table>
                 </td>
               </tr>
        </table>
    </td>
  </tr>
</table>
</div></tr></td></table>

</form>
<?php include("fend.inc"); ?>

<script type="text/javascript">
NiftyCheck();
Rounded("div#mainarea","bl br","#FFF","#eeeeee","smooth");
</script>

</body>
</html>
