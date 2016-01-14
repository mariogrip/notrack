#!/usr/bin/env bash
#Title : NoTrack Installer
#Description : This script will install NoTrack and then configure dnsmasq and lighttpd
#Author : QuidsUp
#Date : 2016-01-10
#Usage : bash install.sh

#Program Settings----------------------------------------------------
Version="v0.2"
NetDev=$( ip -o link show | awk '{print $2,$9}' | grep ": UP" | cut -d ":" -f 1 )
Height=$(tput lines)
Width=$(tput cols)
Height=$((Height / 2))
Width=$(((Width * 2) / 3))
IPVersion=""
DNSChoice1=""
DNSChoice2=""

#Welcome Dialog------------------------------------------------------
Show_Welcome() {
  whiptail --msgbox --title "Welcome to NoTrack $Version" "This installer will transform your Raspberry Pi into a network-wide Tracker Blocker!" $Height $Width

  whiptail --title "Initating Network Interface" --yesno "NoTrack is a SERVER, therefore it needs a STATIC IP ADDRESS to function properly." --yes-button "Ok" --no-button "Abort" $Height $Width
  if (( $? == 1)) ; then                           #Abort install if user selected no
    echo "Aborting Install"
    exit 1
  fi
}

#Root Warning (Incase user executes this script as root)-------------
Show_RootWarning() {
  whiptail --msgbox --title "Error" "Do not run this script as Root!\nExecute with: bash install.sh" 10 $Width
  exit 2
}

#Finish Dialog-------------------------------------------------------
Show_Finish() {
  whiptail --msgbox --title "Install Complete" "NoTrack has been installed" 10 $Width
}

#Ask user which IP Version they are using on their network-----------
Ask_IPVersion() {
  Fun=$(whiptail --title "IP Version" --radiolist "Select IP Version being used" $Height $Width 2 --ok-button Select \
   IPv4 "IP Version 4 (default)" on \
   IPv6 "IP Version 6" off \
   3>&1 1>&2 2>&3) 
  Ret=$?
    
  if [ $Ret -eq 1 ]; then
    echo "Aborting Install"
    exit 1
  elif [ $Ret -eq 0 ]; then
    case "$Fun" in
      "IPv4") IPVersion="IPv4" ;;
      "IPv6") IPVersion="IPv6" ;;
      *) whiptail --msgbox "Programmer error: unrecognized option" 10 $Width 1 ;;
    esac 
  fi
}

#Ask user for preffered DNS server-----------------------------------
Ask_DNSServer() {
  Fun=$(whiptail --title "DNS Server" --radiolist "The job of a DNS server is to translate human readable domain names (e.g. google.com) into an  IP address which your computer will understand (e.g. 109.144.113.88) \nBy default your router forwards DNS queries to your Internet Service Provider (ISP), however ISP DNS servers are not the best.\nChoose a better DNS server from the list below:" $Height $Width 7 --ok-button Select \
   OpenDNS "OpenDNS" on \
   Google "Google Public DNS" off \
   DNSWatch "DNS.Watch" off \
   Verisign "Verisign" off \
   Comodo "Comodo" off \
   FreeDNS "FreeDNS" off \
   Yandex "Yandex DNS" off \
   3>&1 1>&2 2>&3) 
  Ret=$?
    
  if [ $Ret -eq 1 ]; then
    echo "Aborting Install"
    exit 1
  elif [ $Ret -eq 0 ]; then
    case "$Fun" in
      "OpenDNS") 
        DNSChoice1="208.67.222.222" 
        DNSChoice2="208.67.220.220"
      ;;
      "Google") 
        DNSChoice1="8.8.8.8"
        DNSChoice2="8.8.4.4"
      ;;
      "DNSWatch") 
        if [[ $IPVersion == "IPv6" ]]; then
          DNSChoice1="2001:1608:10:25::1c04:b12f"
          DNSChoice2="2001:1608:10:25::9249:d69b"
        else
          DNSChoice1="84.200.69.80"
          DNSChoice2="84.200.70.40"
        fi
      ;;
      "Verisign")
        DNSChoice1="64.6.64.6"
        DNSChoice2="64.6.65.6"
      ;;
      "Comodo")
        DNSChoice1="8.26.56.26"
        DNSChoice2="8.20.247.20"
      ;;
      "FreeDNS")
        DNSChoice1="37.235.1.174"
        DNSChoice2="37.235.1.177"
      ;;
      "Yandex")
        if [[ $IPVersion == "IPv6" ]]; then
          DNSChoice1="2a02:6b8::feed:bad"
          DNSChoice2="2a02:6b8:0:1::feed:bad"
        else
          DNSChoice1="77.88.8.88"
          DNSChoice2="77.88.8.2"
        fi
      ;;
      *) whiptail --msgbox "Programmer error: unrecognized option" 10 $Width 1 ;;
    esac 
  fi
}

#Check File Exists---------------------------------------------------
Check_File_Exists() {
  if [ ! -e $1 ]; then
    echo "Error file $1 is missing.  Aborting."
    exit 2
  fi
}

#Install Applications------------------------------------------------
Install_Apps() {
  sudo apt-get update
  echo
  echo "Installing dependencies"
  sleep 2s
  sudo apt-get -y install unzip
  echo
  echo "Installing Dnsmasq"
  sleep 2s
  sudo apt-get -y install dnsmasq
  echo
  echo "Installing Lighttpd and PHP5"
  sleep 2s
  sudo apt-get -y install lighttpd php5-cgi
  echo
}

#Backup Configs------------------------------------------------------
Backup_Conf() {
  echo "Backing up old config files"
  echo "Copying /etc/dnsmasq.conf to /etc/dnsmasq.conf.old"
  sudo cp /etc/dnsmasq.conf /etc/dnsmasq.conf.old
  echo "Copying /etc/lighttpd/lighttpd.conf to /etc/lighttpd/lighttpd.conf.old"
  sudo cp /etc/lighttpd/lighttpd.conf /etc/lighttpd/lighttpd.conf.old
  echo
}

#Download------------------------------------------------------------
Download_NoTrack() {
  if [ -d ~/NoTrack ]; then                      #Check if NoTrack folder exists
    echo "NoTrack folder exists. Skipping download"
  else
    echo "Downloading latest version of NoTrack from github"
    wget https://github.com/quidsup/notrack/archive/master.zip -O /tmp/notrack-master.zip
    if [ ! -e /tmp/notrack-master.zip ]; then  #Check again to see if download was successful
      echo "Error Download from github has failed"
      exit 2                                   #Abort we can't go any further without any code from git
    fi  

    unzip -oq /tmp/notrack-master.zip -d /tmp
    mv /tmp/notrack-master ~/NoTrack
    rm /tmp/notrack-master.zip                  #Cleanup
  fi
  
  sudo chown "$(whoami)":"$(whoami)" -hR ~/NoTrack
}

#Setup Dnsmasq-------------------------------------------------------
Setup_Dnsmasq() {
  #Copy config files modified for NoTrack
  echo "Copying config files from ~/NoTrack to /etc/"
  Check_File_Exists "~/NoTrack/conf/dnsmasq.conf"
  sudo cp ~/NoTrack/conf/dnsmasq.conf /etc/dnsmasq.conf
  
  Check_File_Exists "~/NoTrack/conf/lighttpd.conf"
  sudo cp ~/NoTrack/conf/lighttpd.conf /etc/lighttpd/lighttpd.conf
  echo
  
  #Finish configuration of dnsmasq config
  sudo sed -i "s/server=changeme1/server=$DNSChoice1/" /etc/dnsmasq.conf
  sudo sed -i "s/server=changeme2/server=$DNSChoice2/" /etc/dnsmasq.conf
  sudo sed -i "s/interface=eth0/interface=$NetDev/" /etc/dnsmasq.conf 
  sudo touch /etc/localhosts.list               #File for user to add DNS entries for their network
  
  #Setup Log rotation for dnsmasq
  Check_File_Exists "~/NoTrack/conf/logrotate.txt"
  sudo cp ~/NoTrack/conf/logrotate.txt /etc/logrotate.d/logrotate.txt
  sudo mv /etc/logrotate.d/logrotate.txt /etc/logrotate.d/notrack
  sudo mkdir /var/log/notrack/
  sudo touch /var/log/notrack.log                #Create log file for Dnsmasq
  sudo chmod 664 /var/log/notrack.log            #Dnsmasq sometimes defaults to permissions 774
  echo
}

#Setup Lighttpd------------------------------------------------------
Setup_Lighttpd() {
  echo "Configuring Lighttpd"
  sudo usermod -a -G www-data "$(whoami)"        #Add www-data group rights to current user
  sudo lighty-enable-mod fastcgi fastcgi-php
  
  if [ ! -d /var/www/html ]; then                #www/html folder will get created by Lighttpd install
    echo "Creating Web folder /var/www/html"
    sudo mkdir -p /var/www/html                  #Create the folder for now incase installer failed
  fi
  
  sudo ln -sf ~/NoTrack/sink /var/www/html/sink  #Setup symlinks for Web folders
  sudo ln -sf ~/NoTrack/admin /var/www/html/admin
  sudo chmod 775 /var/www/html                   #Give read/write/execute privilages to Web folder
  echo
  echo "Restarting Lighttpd"
  sudo service lighttpd restart
  echo
}

#Setup Notrack-------------------------------------------------------
Setup_NoTrack() {
  #Setup Tracker list downloader
  echo "Setting up Tracker list downloader"
  
  Check_File_Exists "~/NoTrack/notrack.sh"
  sudo cp ~/NoTrack/notrack.sh /usr/local/sbin/
  sudo mv /usr/local/sbin/notrack.sh /usr/local/sbin/notrack #Cron jobs will only execute on files Without extensions
  sudo chmod +x /usr/local/sbin/notrack          #Make NoTrack Script executable
  sudo ln -s /usr/local/sbin/notrack /etc/cron.daily/notrack
  echo
  
  if [ ! -d "/etc/notrack" ]; then              #Check /etc/notrack folder exists
    echo "Creating notrack folder under /etc"
    echo
    sudo mkdir "/etc/notrack"
  fi
  
  sudo touch /etc/notrack/notrack.conf          #Create Config file
  echo "IPVersion = $IPVersion" | sudo tee /etc/notrack/notrack.conf
  
  echo "Setup of NoTrack complete"
  echo
}

#Main----------------------------------------------------------------
if [ "$(id -u)" == "0" ]; then                   #Check if running as root
   Show_RootWarning                              #Running as root screws up lighttpd webpages
fi

Show_Welcome

Ask_IPVersion
echo "IPVersion set to: $IPVersion"
echo

Ask_DNSServer
echo "Primary DNS Server set to: $DNSChoice1"
echo "Secondary DNS Server set to: $DNSChoice2"
echo 
echo "Preparing to Install..."
sleep 5s

Install_Apps                                     #Install Applications

Backup_Conf                                      #Backup old config files

Download_NoTrack

Setup_Dnsmasq
Setup_Lighttpd
Setup_NoTrack

echo "Downloading List of Trackers"
sudo /usr/local/sbin/notrack

Show_Finish
