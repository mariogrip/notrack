#!/usr/bin/env bash
#Title : NoTrack SSL Certificate Creator
#Description : This script will assist with creating and installing an SSL certificate on Lighttpd web server
#Author : QuidsUp
#Date : 2016-01-10
#Usage : bash create-ssl-cert.sh

#Program Settings----------------------------------------------------
HostName=$(cat /etc/hostname)
Height=$(tput lines)
Width=$(tput cols)
Height=$((Height / 2))
Width=$(((Width * 2) / 3))

#Welcome-------------------------------------------------------------
Show_Welcome() {
  whiptail --title "Welcome" --yesno "This installer will create an SSL Certificate on your NoTrack Webserver - Lighttpd" --yes-button "Ok" --no-button "Abort" 12 $Width
  if (( $? == 1)) ; then                           #Abort install if user selected no
    echo "Aborting Install"
    exit 1
  fi
}

#Install Complete----------------------------------------------------
Show_Finish() {
  whiptail --msgbox --title "Complete" "Your SSL Certificate has been installed and Lighttpd has sucessfully restarted" 10 $Width
  exit 2
}

#Root Warning (Incase user executes this script as root)-------------
Show_RootWarning() {
  whiptail --msgbox --title "Error" "Do not run this script as Root!\nExecute with: bash create-ssl-cert.sh" 10 $Width
  exit 2
}

#Check if required applications are installed------------------------
Check_AppsInstalled() {
  command -v lighttpd >/dev/null 2>&1 || { echo "Lighttpd is not installed.  Aborting." >&2; exit 1; }
  command -v openssl >/dev/null 2>&1 || { echo "OpenSSL is not installed.  Aborting." >&2; exit 1; }
}

#Main----------------------------------------------------------------
if [ "$(id -u)" == "0" ]; then                   #Check if running as root
   Show_RootWarning                              
fi

Show_Welcome

Check_AppsInstalled                              #Check if required apps are installed

echo "Enabling SSL Module on Lighttpd"
sudo lighty-enable-mod ssl
echo

echo "Creating SSL Certificate"
echo "Example details:"
echo "Country Name (2 letter code) [AU]: GB"
echo "State or Province Name (full name) [Some-State]: ."
echo "Locality Name (eg, city) []: Cardiff"
echo "Organization Name (eg, company) [Internet Widgits Pty Ltd]: Quidsup"
echo "Organizational Unit Name (eg, section) []: IT"
echo "Common Name (e.g. server FQDN or YOUR name) []: $HostName"
echo "Email Address []: certs@quidsup.net"
echo
echo "Two letter Country Codes: https://www.digicert.com/ssl-certificate-country-codes.htm"
echo
read -n1 -r -p "Press any key to continue..."

openssl req -new -newkey rsa:2048 -nodes -sha256 -x509 -days 365 -keyout ~/server.key -out ~/server.crt
echo

if [ ! -e ~/server.key ] || [ ! -e ~/server.crt ]; then
  echo "Error creation of SSL certificate has failed.  Aborting"
  exit 2
fi

echo "Merging Crt file and Key file to form Pem"
cat ~/server.key ~/server.crt > ~/server.pem
echo

echo "Copying Pem file to /etc/lighttpd/"
sudo cp ~/server.pem /etc/lighttpd/server.pem
echo

echo "Restarting Lighttpd"
sudo service lighttpd force-restart
echo

if [ -z "$(pgrep lighttpd)" ]; then                #Check if lighttpd restart has been successful
  whiptail --title "Error" --yesno "Lighttpd restart has failed. Something is wrong in the Lighttpdconfig\n\nDo you want to revert back to old configuration?" --yes-button "Yes" --no-button "No" 14 $Width
  if (( $? == 1)); then                            #Abort if user selected no
    echo "Something may have gone wrong with the certificate settings."
    echo "Try and re-run this script"
    echo "If you want to try and manually fix the Lighttpd config you'll find it in /etc/lighttpd"
    echo "Restart the service with: sudo service lighttpd force-restart"
    exit 1
  fi
  
  echo "Disabling Lighttpd SSL Module"
  sudo lighty-disable-mod ssl                      #Disable SSL Module
  echo "Restarting Lighttpd"
  sudo service lighttpd force-restart
  echo
  
  if [ -z "$(pgrep lighttpd)" ]; then              #Check if lighttpd restart has now been successful
    echo "Lighttpd restart failed"
    echo "I don't know how to fix this"
  else
    echo "Lighttpd restart successful"
  fi
else
  Show_Finish
fi

