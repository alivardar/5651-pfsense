<?php
/* $Id$ */
/*
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
require_once("system.inc");
$pgtitle = "Web Sunucuyu Yeniden Başlatma";
include("head.inc");
?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<form>
<?php include("fbegin.inc"); ?>

<p class="pgtitle"><?php echo $pgtitle; ?></p>

Yerel dosya sistemi okuma/yazma modunda bağlanıyor...

<?php flush(); sleep(1); conf_mount_rw(); ?>
Tamam.<br>
Bütün yerel yetkiler 0755 yapılıyor...
<?php flush(); sleep(1); system('chmod -R 0755 /usr/local/www/*.php'); ?>
Tamam.<br>
Dosya sistemleri sadece okuma moduna geçiriliyor...
<?php flush(); sleep(1); conf_mount_ro(); ?>
Tamam.<br>
Web sunucu yeniden başlatılıyor...
<?php flush(); sleep(1); system_webgui_start(); ?>
Tamam.<br>

<?php
include("fend.inc");
?>
