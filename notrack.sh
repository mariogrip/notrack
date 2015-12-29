#!/bin/bash
#Title : NoTrack
#Description : This script will download latest Adblock Domain block files from quidsup.net, then parse them into Dnsmasq.
#Author : QuidsUp
#Date : 2015-12-28
#Version : 0.1
#Usage : sudo bash notrack.sh
#Version History: 
#0.1    2015-12-28    Created

#IP Version number for Localhost (4 or 6)
IPVersion="4"
TrackerSource="http://quidsup.net/adsites.txt" 
TrackerListFile="/etc/dnsmasq.d/adsites.list" 
TrackerQuickList="/etc/notrack/tracker-quick.list"
TrackerBlackList="/etc/notrack/blacklist.txt"
TrackerWhiteList="/etc/notrack/whitelist.txt"
DomainSource="http://quidsup.net/malicious-domains.txt"
DomainListFile="/etc/dnsmasq.d/malicious-domains.list"
DomainBlackList="/etc/notrack/domain-blacklist.txt"
DomainWhiteList="/etc/notrack/domain-whitelist.txt"
DomainQuickList="/etc/notrack/domain-quick.list"

#Check /etc/notrack folder exists
if [ ! -d "/etc/notrack" ]; then
  echo "Creating notrack folder under /etc"
  mkdir "/etc/notrack"
fi

#Check if Blacklist exists
if [ ! -e $TrackerBlackList ]; then
  echo "Creating blacklist"
  touch $TrackerBlackList
  echo "# Use this file to add additional websites to be blocked, e.g." >> $TrackerBlackList
  echo "#doubleclick.net" >> $TrackerBlackList
  echo "#google-analytics.com" >> $TrackerBlackList
  echo "#googletagmanager.com" >> $TrackerBlackList
  echo "#googletagservices.com" >> $TrackerBlackList
fi

#Check if Whitelist exists
if [ ! -e $TrackerWhiteList ]; then
  echo "Creating whitelist"
  touch $TrackerWhiteList
  echo "# Use this file to remove files from blocklist, e.g." >> $TrackerWhiteList
  echo "#doubleclick.net" >> $TrackerWhiteList
  echo "#googleadservices.com" >> $TrackerWhiteList
fi

#Domain Black & White Lists------------------------------------------
#Check if DomainBlacklist exists
if [ ! -e $DomainBlackList ]; then
  echo "Creating domain blacklist"
  touch $DomainBlackList
  echo "# Use this file to add additional domains to the blocklist." >> $DomainBlackList
  echo "# I have divided the list info three different classifications:" >> $DomainBlackList
  echo "# 1: Very high risk - Cheap/Free domains that attract a high number of scammers. This list gets downloaded from: $DomainSource" >> $DomainBlackList
  echo "# 2: Risky - More of a mixture of legitimate to malicious domains. Consider enabling blocking of these domains, unless you live in one of the countries listed." >> $DomainBlackList
  echo "# 3: Low risk - Malicious sites do appear in these domains, but they are well in the minority." >> $DomainBlackList

  echo "# Risky domains----------------------------------------" >> $DomainBlackList
  echo "#.asia #Asia-Pacific" >> $DomainBlackList
  echo "#.biz #Business" >> $DomainBlackList
  echo "#.co #Columbia" >> $DomainBlackList
  echo "#.cn #China" >> $DomainBlackList
  echo "#.eu #European Union" >> $DomainBlackList
  echo "#.ga # Gabonese Republic" >> $DomainBlackList
  echo "#.in #India" >> $DomainBlackList
  echo "#.info #Information" >> $DomainBlackList
  echo "#.mobi #Mobile Devices" >> $DomainBlackList
  echo "#.org #Organisations" >> $DomainBlackList
  echo "#.pl #Poland" >> $DomainBlackList
  echo "#.ru #Russia" >> $DomainBlackList
  echo "#.us #USA" >> $DomainBlackList

  echo "# Low Risk domains--------------------------------------" >> $DomainBlackList
  echo "#.am #Armenia" >> $DomainBlackList
  echo "#.hr #Croatia" >> $DomainBlackList
  echo "#.hu #Hungary" >> $DomainBlackList
  echo "#.pe #Peru" >> $DomainBlackList
  echo "#.rs #Serbia" >> $DomainBlackList
  echo "#.st #São Tomé and Príncipe" >> $DomainBlackList
  echo "#.tc #Turks and Caicos Islands" >> $DomainBlackList
  echo "#.th #Thailand" >> $DomainBlackList
  echo "#.tk #Tokelau" >> $DomainBlackList
  echo "#.tl #East Timor" >> $DomainBlackList
  echo "#.tt #Trinidad and Tobago" >> $DomainBlackList
  echo "#.tv #Tuvalu" >> $DomainBlackList
  echo "#.vn #Vietnam" >> $DomainBlackList
  echo "#.ws #Western Samoa" >> $DomainBlackList
  #echo "#" >> $DomainBlackList
fi

#Check if Domain Whitelist exists
if [ ! -e $DomainWhiteList ]; then
  echo "Creating Domain whitelist"
  touch $DomainWhiteList
  echo "# Use this file to remove files malicious domains from blocklist" >> $DomainWhiteList
fi

#Get IP Address of System--------------------------------------------
IPVersion="4"
if [ "$IPVersion" = "4" ]; then
  echo "Reading IPv4 Address"
  IPAddr=$( ip addr list eth0 |grep "inet " |cut -d' ' -f6|cut -d/ -f1 )
  echo "System IP Address $IPAddr"
elif [ “$IPVersion” = "6" ]; then
  echo "Reading IPv6 Address"
  IPAddr=$( ip addr list eth0 |grep "inet6 " |cut -d' ' -f6|cut -d/ -f1 )
  echo "System IP Address $IPAddr"
else
  echo "Unknown IP Version" 1>&2
  exit 1
fi


#Download Lists from Website-----------------------------------------
echo "Downloading Tracker Site List from: $TrackerSource"
echo
wget -O /etc/notrack/adsites.txt $TrackerSource
echo
echo "Downloading Malcious Domain List from: $DomainSource"
echo
wget -O /etc/notrack/domains.txt $DomainSource

#Check if lists have been downloaded successfully 
if [ ! -e /etc/notrack/adsites.txt ]; then
  echo "Error Ad Site List not downloaded"
  exit 1
fi

if [ ! -e /etc/notrack/domains.txt ]; then
  echo "Error Domain List not downloaded"
  exit 1
fi


#Check if Tracker and Domain Lists exist in dnsmasq.d----------------
if [ ! -e $TrackerListFile ]; then
  touch $TrackerListFile 
fi
if [ ! -e $TrackerQuickList ]; then
  touch $TrackerQuickList
fi
if [ ! -e $DomainSourceFile ]; then
  touch $DomainSourceFile
fi
if [ ! -e $DomainQuickList ]; then
  touch $DomainQuickList
fi 


#Merge Blacklist with URL List
cat /etc/notrack/adsites.txt $TrackerBlackList > /tmp/combined.txt

#Merge Whitelist with above two lists to remove duplicates-----------
i=0
echo "Processing Tracker List"
echo "#Tracker Blocklist last updated $(date)" > $TrackerListFile
echo "#Don't make any changes to this file, use $TrackerBlackList and $TrackerWhiteList instead" >> $TrackerListFile
echo "" > $TrackerQuickList

awk 'NR==FNR{A[$1]; next}!($1 in A)' $TrackerWhiteList /tmp/combined.txt | while read Line; do
  if [ $i == 100 ]; then                         #Display some progress ..
    echo -n .
    i=0
  fi
  if [[ ! $Line =~ ^\ *# && -n $Line ]]; then
    Line="${Line%%\#*}"  # Del in line right comments
    Line="${Line%%*( )}" # Del trailing spaces
    echo "address=/$Line/$IPAddr" >> $TrackerListFile
    echo $Line >> $TrackerQuickList
  fi
  ((i++))
done

echo .
echo "Imported $(wc -l $TrackerQuickList | cut -d' ' -f1) Advert Domains into block list"


#Domain List---------------------------------------------------------
#Merge Blacklist with Domain List
cat /etc/notrack/domains.txt $DomainBlackList > /tmp/combined.txt

#Merge Whitelist with above two lists to remove duplicates-----------
echo "#Domain Blocklist last updated $(date)" > $DomainListFile
echo "#Don't make any changes to this file, use $DomainBlackList and $DomainWhiteList instead" >> $DomainListFile
echo "" > $DomainQuickList

awk 'NR==FNR{A[$1]; next}!($1 in A)' $DomainWhiteList /tmp/combined.txt | while read Line; do
  if [[ ! $Line =~ ^\ *# && -n $Line ]]; then 
    Line="${Line%%\#*}"  # Del in line right comments
    Line="${Line%%*( )}" # Del trailing spaces 
    echo "address=/$Line/$IPAddr" >> $DomainListFile
    echo $Line >> $DomainQuickList
  fi
done

echo "Imported $(wc -l $DomainQuickList | cut -d' ' -f1) Malicious Domains into block list"

#Clear up
echo "Removing temporary files"
rm /tmp/combined.txt

#Restart dnsmasq
echo "Restarting Dnsnmasq"
service dnsmasq restart

