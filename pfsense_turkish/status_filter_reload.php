<?php
/* $Id$ */
/*
	status_filter_reload.php
	Copyright (C) 2006 Scott Ullrich
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
require_once("functions.inc");

$pgtitle = "Tanımlama: Filtreleri Yeniden Yükleme Durumu";

include("head.inc");

if(file_exists("{$g['varrun_path']}/filter_reload_status"))
	$status = file_get_contents("{$g['varrun_path']}/filter_reload_status");

if($_GET['getstatus']) {
	echo "|{$status}|";
	exit;
}

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<script src="/javascript/scriptaculous/prototype.js" type="text/javascript"></script>
<script src="/javascript/scriptaculous/scriptaculous.js" type="text/javascript"></script>

<?php include("fbegin.inc"); ?>

<p><span class="pgtitle"><?=$pgtitle;?></span></p>

<div id="status" name="status" style="padding:5px; border:1px dashed #990000; background-color: #ffffff; color: #000000;">
	<?php echo $status; ?>
</div>

<div id="doneurl" name="doneurl">
</div>

<p>

<div id="reloadinfo" name="reloadinfo">Bu sayfa her 3 saniyede bir otomatik olarak yenilenmektedir.</div>



<script language="javascript">
/* init update "thread */
function update_status_thread() {
	getURL('status_filter_reload.php?getstatus=true', update_data);
}
function update_data(obj) {
	var result_text = obj.content;
	var result_text_split = result_text.split("|");
	result_text = result_text_split[1];
	result_text = result_text.replace("\n","");
	result_text = result_text.replace("\r","");
	if (result_text) {
		$('status').innerHTML = '<img src="/themes/{$g['theme']}/images/misc/loader.gif"> ' + result_text + '...';
	} else {
		$('status').innerHTML = '<img src="/themes/{$g['theme']}/images/misc/loader.gif"> Obtaining filter status...';
	}
	if(result_text == "Initializing") {
		$('status').innerHTML = '<img src="/themes/{$g['theme']}/images/misc/loader.gif"> Initializing...';
	} else if(result_text == "Done") {
		new Effect.Highlight($('status'));
		$('status').innerHTML = 'Tamamlandı.  Filtre kuralları yeniden yüklendi.';
		$('reloadinfo').style.visibility="hidden";
		$('doneurl').style.visibility="visible";
		$('doneurl').innerHTML = "<p/><a href='status_queues.php'>Queue Status</a>";
	}
	window.setTimeout('update_status_thread()', 2500);
}
</script>

<script language="javascript">
/*
 * getURL is a proprietary Adobe function, but it's simplicity has made it very
 * popular. If getURL is undefined we spin our own by wrapping XMLHttpRequest.
 */
if (typeof getURL == 'undefined') {
  getURL = function(url, callback) {
    if (!url)
      throw 'No URL for getURL';

    try {
      if (typeof callback.operationComplete == 'function')
        callback = callback.operationComplete;
    } catch (e) {}
    if (typeof callback != 'function')
      throw 'No callback function for getURL';

    var http_request = null;
    if (typeof XMLHttpRequest != 'undefined') {
      http_request = new XMLHttpRequest();
    }
    else if (typeof ActiveXObject != 'undefined') {
      try {
        http_request = new ActiveXObject('Msxml2.XMLHTTP');
      } catch (e) {
        try {
          http_request = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (e) {}
      }
    }
    if (!http_request)
      throw 'Both getURL and XMLHttpRequest are undefined';

    http_request.onreadystatechange = function() {
      if (http_request.readyState == 4) {
        callback( { success : true,
                    content : http_request.responseText,
                    contentType : http_request.getResponseHeader("Content-Type") } );
      }
    }
    http_request.open('GET', url, true);
    http_request.send(null);
  }
}
window.setTimeout('update_status_thread()', 2500);
</script>

<?php include("fend.inc"); ?>

</body>
</html>
