#!/bin/sh
tarih=`date "+%Y%m%d-%H%M%S"`
mkdir /var/mountsamba
cd /var/mountsamba
awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > ./dhcplog-$tarih.txt
/usr/local/bin/smbclient \\\\10.0.0.10\\filesother -U avardar%"vardar" -N -c "prompt; put dhcplog-$tarih.txt"
logger "Windows paylasımına dosya kopyalandı."
cd ..
rm -rf /var/mountsamba