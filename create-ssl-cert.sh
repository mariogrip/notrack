#!/usr/bin/env bash
#Title : NoTrack SSL Certificate Creator
#Description : This script will assist with creating and installing an SSL certificate on Lighttpd web server
#Author : QuidsUp
#Date : 2016-01-08
#Usage : bash create-ssl-cert.sh

country="GB"
state="."
locality="Wales"
organization="quidsup.net"
organizationalunit="IT"
email="anything@something.com"

#Program Settings----------------------------------------------------
HostName=$(cat /etc/hostname)
Height=$(tput lines)
Width=$(tput cols)
Height=$(($Height / 2))
Width=$((($Width * 2) / 3))

#Welcome-------------------------------------------------------------
Show_Welcome() {
  whiptail --title "Welcome" --yesno "This installer will create an SSL Certificate on your NoTrack Webserver Lighttpd" --yes-button "Ok" --no-button "Abort" 12 $Width
  if (( $? == 1)) ; then                           #Abort install if user selected no
    echo "Aborting Install"
    exit 1
  fi
}

#Root Warning (Incase user executes this script as root)-------------
Show_RootWarning() {
  whiptail --msgbox --title "Error" "Do not run this script as Root!\nExecute with: bash create-ssl-cert.sh" 10 $Width
  exit 2
}


#Main----------------------------------------------------------------

if [ "$(id -u)" == "0" ]; then                   #Check if running as root
   Show_RootWarning                              
fi

Show_Welcome

echo "Enabling SSL Module on Lighttpd"
#sudo lighty-enable-mod ssl
echo

echo "Creating SSL Certificate"
#Country Name (2 letter code) [AU]:GB
#State or Province Name (full name) [Some-State]:.
#Locality Name (eg, city) []:Cardiff
#Organization Name (eg, company) [Internet Widgits Pty Ltd]:quidsup
#Organizational Unit Name (eg, section) []:IT
#Common Name (e.g. server FQDN or YOUR name) []:server.local
#Email Address []:root@quidsup.net

openssl req -new -newkey rsa:2048 -nodes -sha256 -x509 -days 365 -keyout ~/server.key -out ~/server.crt
echo

echo "Merging Crt file and Key file to form Pem"
cat ~/server.key ~/server.crt > ~/server.pem
echo

echo "Copying Pem file to /etc/lighttpd/certs/"
sudo cp ~/server.pem /etc/lighttpd/server.pem
echo

echo "Restarting Lighttpd"
sudo service lighttpd restart


