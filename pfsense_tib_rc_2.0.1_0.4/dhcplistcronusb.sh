#!/bin/sh

tarih=`date "+%Y%m%d-%H%M%S"`
disks="da0s1 da0s2 da0s3 da0s4 da1s1 da1s2 da1s3 da1s4"

mkdir /var/mountusb
mkdir /var/tiblog

for db in $disks
do
  if mount -t msdosfs /dev/$db /var/mountusb; then
  logger "/dev/$db USB aygit baglama BASARILIDIR."  
  
  awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > /var/tiblog/dhcplog-$tarih.txt  
  mv /var/tiblog/* /var/mountusb  
  umount /var/mountusb
  logger "Kopyalama gerceklestirildi."
  else
  logger "/dev/$db USB aygit baglanamadi."
  fi
done

rmdir /var/mountusb
rmdir /var/tiblog

