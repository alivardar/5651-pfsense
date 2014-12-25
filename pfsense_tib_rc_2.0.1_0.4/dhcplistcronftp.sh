#!/bin/sh

tarih=`date "+%Y%m%d-%H%M%S"`

HOST='purenet.domain'
USER='muzik'
PASSWD='vardar'
SERVER='10.0.0.10'

mkdir /var/mountftp
cd /var/mountftp

awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > ./dhcplog$HOST-$tarih.txt

logger `ftp -n -v $SERVER << EOT
ascii
user $USER $PASSWD
prompt
put dhcplog$HOST-$tarih.txt
bye
EOT`

cd ..
rm -rf /var/mountftp