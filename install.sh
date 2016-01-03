#!/bin/bash
#Title : NoTrack Installer
#Description : This script will install NoTrack and then configure dnsmasq and lightpd
#Author : QuidsUp
#Date : 2016-01-03
#Version : 0.1
#Usage : sudo bash install.sh


#Setup symlinks for Web Folders
ln -s ~/NoTrack/pihole /var/www/html/pihole
ln -s ~/NoTrack/admin/ /var/www/html/admin
