#!/bin/bash
#Title : NoTrack
#Description : This script will download latest Adblock Domain block files from quidsup.net, then parse them into Dnsmasq.
#              Script will also create quick.lists for use by stats.php web page
#Author : QuidsUp
#Date : 2015-01-14
#Usage : sudo bash notrack.sh

#System Variables----------------------------------------------------
Version="0.4"
NetDev=$( ip -o link show | awk '{print $2,$9}' | grep ": UP" | cut -d ":" -f 1 )
TrackerSource="http://quidsup.net/trackers.txt" 
TrackerListFile="/etc/dnsmasq.d/adsites.list" 
TrackerQuickList="/etc/notrack/tracker-quick.list"
TrackerBlackList="/etc/notrack/blacklist.txt"
TrackerWhiteList="/etc/notrack/whitelist.txt"
DomainSource="http://quidsup.net/malicious-domains.txt"
DomainListFile="/etc/dnsmasq.d/malicious-domains.list"
DomainBlackList="/etc/notrack/domain-blacklist.txt"
DomainWhiteList="/etc/notrack/domain-whitelist.txt"
DomainQuickList="/etc/notrack/domain-quick.list"
ConfigFile="/etc/notrack/notrack.conf"
IPVersion=""
OldLatestVersion=""


#Error_Exit----------------------------------------------------------
Error_Exit() {
  echo "$1"
  echo "Aborting"
  exit 2
}

#Check File Exists---------------------------------------------------
Check_File_Exists() {
  if [ ! -e "$1" ]; then
    echo "Error file $1 is missing.  Aborting."
    exit 2
  fi
}

#Read Config File----------------------------------------------------
Read_Config_File() {
  if [ ! -d "/etc/notrack" ]; then               #Check /etc/notrack folder exists
    echo "Creating notrack folder under /etc"
    echo
    mkdir "/etc/notrack"
    if [ ! -d "/etc/notrack" ]; then             #Check again
      Error_Exit "Error Unable to create folder /etc/notrack"      
    fi
  fi

  if [ ! -e $ConfigFile ]; then
    echo "Creating config file"
    touch $ConfigFile                            #Create Config file
    IPVersion="IPv4"                             #Set default values
    OldLatestVersion=$Version
    if [ ! -e $ConfigFile ]; then                #Check again
      echo "Warning Unable to create config file. Continuing with default settings"      
    else                                         #successful, lets write the values
      echo "IPVersion = IPv4" >> $ConfigFile
      echo "LatestVersion = $Version" >> $ConfigFile
    fi
  else 
    IPVersion=$(cat "$ConfigFile" | grep "IPVersion" | cut -d "=" -f 2 | tr -d [[:space:]])
    OldLatestVersion=$(cat $ConfigFile | grep "LatestVersion" | cut -d "=" -f 2 | tr -d [[:space:]])
    
    #Verify variables have been loaded successfully
    if [ "$IPVersion" == "" ]; then              #Check If Config is line missing
      IPVersion="IPv4"                           #default to IPv4
      echo "IPVersion = IPv4" >> $ConfigFile
    fi
    
    if [[ $OldLatestVersion == "" ]]; then       #Check If OldLatestVersion is line missing
      echo "LatestVersion = $Version" >> $ConfigFile
      OldLatestVersion=$Version                  #Default to script version for now
    fi
  fi
  
}

#Check Lists---------------------------------------------------------
Check_Lists() {  
  #Check if Blacklist exists-----------------------------------------
  if [ ! -e $TrackerBlackList ]; then
    echo "Creating blacklist"
    touch $TrackerBlackList
    echo "#Use this file to add additional websites to be blocked" >> $TrackerBlackList
    echo "#Run notrack script (sudo notrack) after you make any changes to this file" >> $TrackerBlackList
    echo "#doubleclick.net" >> $TrackerBlackList
    echo "#google-analytics.com" >> $TrackerBlackList
    echo "#googletagmanager.com" >> $TrackerBlackList
    echo "#googletagservices.com" >> $TrackerBlackList
  fi

  #Check if Whitelist exists-----------------------------------------
  if [ ! -e $TrackerWhiteList ]; then
    echo "Creating whitelist"
    touch $TrackerWhiteList
    echo "# Use this file to remove files from blocklist" >> $TrackerWhiteList
    echo "# Run notrack script (sudo notrack) after you make any changes to this file" >> $TrackerWhiteList
    echo "#doubleclick.net" >> $TrackerWhiteList
    echo "#google-analytics.com" >> $TrackerWhiteList
  fi


  #Check if DomainBlacklist exists-----------------------------------
  if [ ! -e $DomainBlackList ]; then
    echo "Creating domain blacklist"
    touch $DomainBlackList
    echo "#Use this file to add additional domains to the blocklist." >> $DomainBlackList
    echo "#Run notrack script (sudo notrack) after you make any changes to this file" >> $DomainBlackList
    echo "# I have divided the list info three different classifications:" >> $DomainBlackList
    echo "# 1: Very high risk - Cheap/Free domains which attract a high number of scammers. This list gets downloaded from: $DomainSource" >> $DomainBlackList
    echo "# 2: Risky - More of a mixture of legitimate to malicious domains. Consider enabling blocking of these domains, unless you live in one of the countries listed." >> $DomainBlackList
    echo "# 3: Low risk - Malicious sites do appear in these domains, but they are well in the minority." >> $DomainBlackList

    echo "# Risky domains----------------------------------------" >> $DomainBlackList
    echo "#.asia #Asia-Pacific" >> $DomainBlackList
    echo "#.biz #Business" >> $DomainBlackList
    echo "#.cc #Cocos Islands" >> $DomainBlackList
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
  fi

  #Check if Domain Whitelist exists
  if [ ! -e $DomainWhiteList ]; then
    echo "Creating Domain whitelist"
    echo
    touch $DomainWhiteList
    echo "#Use this file to remove files malicious domains from blocklist" >> $DomainWhiteList
    echo "#Run notrack script (sudo notrack) after you make any changes to this file" >> $DomainWhiteList    
    echo "#.cf #Central African Republic" >> $DomainWhiteList
    echo "#.cricket" >> $DomainWhiteList
    echo "#.country" >> $DomainWhiteList
    echo "#.gq #Equatorial Guinea" >> $DomainWhiteList
    echo "#.kim" >> $DomainWhiteList
    echo "#.link" >> $DomainWhiteList
    echo "#.party" >> $DomainWhiteList
    echo "#.pink" >> $DomainWhiteList
    echo "#.review" >> $DomainWhiteList
    echo "#.science" >> $DomainWhiteList
    echo "#.work" >> $DomainWhiteList
    echo "#.xyz" >> $DomainWhiteList
  fi
}

#Get IP Address of System--------------------------------------------
Get_IPAddress() {
  echo "IP Version: $IPVersion"
  
  if [ "$IPVersion" == "IPv4" ]; then
    echo "Reading IPv4 Address from $NetDev."
    IPAddr=$( ip addr list "$NetDev" |grep "inet " |cut -d' ' -f6|cut -d/ -f1 )
    echo "System IP Address $IPAddr"
  elif [ "$IPVersion" == "IPv6" ]; then
    echo "Reading IPv6 Address"
    IPAddr=$( ip addr list "$NetDev" |grep "inet6 " |cut -d' ' -f6|cut -d/ -f1 )
    echo "System IP Address $IPAddr"
  else
    Error_Exit "Unknown IP Version"    
  fi
  echo
}

#Download Lists from Website-----------------------------------------
Download_Lists() {
  echo "Downloading Tracker Site List from: $TrackerSource"
  echo
  wget -O /etc/notrack/trackers.txt $TrackerSource
  echo
  echo "Downloading Malcious Domain List from: $DomainSource"
  echo
  wget -O /etc/notrack/domains.txt $DomainSource

  if [ ! -e /etc/notrack/trackers.txt ]; then     #Check if lists have been downloaded successfully 
    Error_Exit "Error Ad Site List not downloaded"    
  fi

  if [ ! -e /etc/notrack/domains.txt ]; then
    Error_Exit "Error Domain List not downloaded"    
  fi
}

#Build Trackers and Domain Lists to Parse into Dnsmasq---------------
Build_Lists() {
  if [ ! -e $TrackerListFile ]; then             #Check List Files exist
    touch $TrackerListFile                       #Create them if necessary
  fi
  if [ ! -e $TrackerQuickList ]; then
    touch $TrackerQuickList
  fi
  if [ ! -e $DomainListFile ]; then
    touch $DomainListFile
  fi
  if [ ! -e $DomainQuickList ]; then
    touch $DomainQuickList
  fi 

  #Merge Blacklist with URL List
  cat /etc/notrack/trackers.txt $TrackerBlackList > /tmp/combined.txt

  #Merge Whitelist with above two lists to remove duplicates-----------
  i=0
  echo "Processing Tracker List"
  echo "#Tracker Blocklist last updated $(date)" > $TrackerListFile
  echo "#Don't make any changes to this file, use $TrackerBlackList and $TrackerWhiteList instead" >> $TrackerListFile
  cat /dev/null > $TrackerQuickList

  awk 'NR==FNR{A[$1]; next}!($1 in A)' $TrackerWhiteList /tmp/combined.txt | while read -r Line; do
    if [ $i == 100 ]; then                       #Display some progress ..
      echo -n .
      i=0
    fi
    if [[ ! $Line =~ ^\ *# && -n $Line ]]; then
      Line="${Line%%\#*}"                        #Delete comments
      Line="${Line%%*( )}"                       #Delete trailing spaces
      echo "address=/$Line/$IPAddr" >> $TrackerListFile
      echo "$Line" >> $TrackerQuickList    
    elif [[ "${Line:0:14}" == "#LatestVersion" ]]; then
      LatestVersion="${Line:15}"                 #Substr version number only
      if [[ $OldLatestVersion != "$LatestVersion" ]]; then
        echo "New version of NoTrack available v$LatestVersion"
        sed -i "s/^\(LatestVersion *= *\).*/\1$LatestVersion/" $ConfigFile      
      fi
    fi
    ((i++))
  done

  echo .                                         #Final dot and carriage return
  echo "Imported $(wc -l $TrackerQuickList | cut -d' ' -f1) Advert Domains into block list"


#Domain List---------------------------------------------------------
  #Merge Blacklist with Domain List
  cat /etc/notrack/domains.txt $DomainBlackList > /tmp/combined.txt

  #Merge Whitelist with above two lists to remove duplicates---------
  echo "#Domain Blocklist last updated $(date)" > $DomainListFile
  echo "#Don't make any changes to this file, use $DomainBlackList and $DomainWhiteList instead" >> $DomainListFile
  cat /dev/null > $DomainQuickList

  awk 'NR==FNR{A[$1]; next}!($1 in A)' $DomainWhiteList /tmp/combined.txt | while read -r Line; do
    if [[ ! $Line =~ ^\ *# && -n $Line ]]; then 
      Line="${Line%%\#*}"  # Del in line right comments
      Line="${Line%%*( )}" # Del trailing spaces 
      echo "address=/$Line/$IPAddr" >> $DomainListFile
      echo "$Line" >> $DomainQuickList
    fi
  done

  echo "Imported $(wc -l $DomainQuickList | cut -d' ' -f1) Malicious Domains into TLD block list"

  echo "Removing temporary files"
  rm /tmp/combined.txt                           #Clear up

  echo "Restarting Dnsnmasq"
  service dnsmasq restart                        #Restart dnsmasq
}
#Upgrade-------------------------------------------------------------
Web_Upgrade() {
  if [ "$(id -u)" == "0" ]; then                 #Check if running as root
     echo "Error do not run the upgrader as root"
     Error_Exit "Execute with: bash notrack -b / notrack -u"     
  fi
  
  Check_File_Exists "/var/www/html/admin"
  InstallLoc=$(readlink -f /var/www/html/admin/)
  InstallLoc=${InstallLoc/%\/admin/}             #Trim "/admin" from string
    
  if [ "$(command -v git)" ]; then               #Utilise Git if its installed
    echo "Pulling latest updates of NoTrack using Git"
    cd "$InstallLoc" || Error_Exit "Unable to cd to $InstallLoc"
    git pull
    if [ $? != "0" ]; then                       #Git repository not found
      if [ -d "$InstallLoc-old" ]; then          #Delete NoTrack-old folder if it exists
        echo "Removing old NoTrack folder"
        rm -rf "$InstallLoc-old"
      fi
      echo "Moving $InstallLoc folder to $InstallLoc-old"
      mv "$InstallLoc" "$InstallLoc-old"
      echo "Cloning NoTrack to $InstallLoc with Git"
      git clone --depth=1 https://github.com/quidsup/notrack.git "$InstallLoc"
    fi
  else                                           #Git not installed, fallback to wget
    if [ -d "$InstallLoc" ]; then                #Check if NoTrack folder exists  
      if [ -d "$InstallLoc-old" ]; then          #Delete NoTrack-old folder if it exists
        echo "Removing old NoTrack folder"
        rm -rf "$InstallLoc-old"
      fi
      echo "Moving $InstallLoc folder to $InstallLoc-old"
      mv "$InstallLoc" "$InstallLoc-old"
    fi

    echo "Downloading latest version of NoTrack from https://github.com/quidsup/notrack/archive/master.zip"
    wget https://github.com/quidsup/notrack/archive/master.zip -O /tmp/notrack-master.zip
    if [ ! -e /tmp/notrack-master.zip ]; then    #Check to see if download was successful
      #Abort we can't go any further without any code from git
      Error_Exit "Error Download from github has failed"      
    fi
  
    echo "Unzipping notrack-master.zip"
    unzip -oq /tmp/notrack-master.zip -d /tmp
    echo "Copying folder across to $InstallLoc"
    mv /tmp/notrack-master "$InstallLoc"
    echo "Removing temporary files"
    rm /tmp/notrack-master.zip                  #Cleanup
  fi
  echo "Upgrade complete"
}

#Full Upgrade--------------------------------------------------------
Full_Upgrade() {
  #This function is run after Web_Upgrade
  #All we need to do is copy notrack.sh script to /usr/local/sbin
  
  InstallLoc=$(readlink -f /var/www/html/admin/)
  InstallLoc=${InstallLoc/%\/admin/}             #Trim "/admin" from string
  
  Check_File_Exists "$InstallLoc/notrack.sh"
  sudo cp "$InstallLoc/notrack.sh" /usr/local/sbin/
  sudo mv /usr/local/sbin/notrack.sh /usr/local/sbin/notrack
  sudo chmod +x /usr/local/sbin/notrack
  
  echo "NoTrack Script updated"
}
#Help----------------------------------------------------------------
Show_Help() {
  echo "Usage: notrack"
  echo "Downloads and Installs updated tracker lists"
  echo
  echo "The following options can be specified:"
  echo -e "  -b\t\tbasic upgrade launched from web browser"
  echo -e "  -h, --help\tdisplay this help and exit"
  echo -e "  -v, --version\tdisplay version information and exit"
  echo -e "  -u, --upgrade\trun a full upgrade"
}

#Show Version--------------------------------------------------------
Show_Version() {
  echo "NoTrack Version v$Version"
  if [[ $Version != "$OldLatestVersion" ]]; then
    echo "New version available v$OldLatestVersion"
  fi
  echo
}

#Main----------------------------------------------------------------
Read_Config_File                                 #Load saved variables

if [ "$1" ]; then                                #Have any arguments been given
  if ! options=$(getopt -o bhvu -l help,version,upgrade -- "$@"); then
    # something went wrong, getopt will put out an error message for us
    exit 1
  fi

  set -- $options

  while [ $# -gt 0 ]
  do
    case $1 in
      -b) 
        Web_Upgrade
      ;;
      -h|--help) 
        Show_Help
      ;;
      -v|--version) 
        Show_Version
      ;;
      -u|--upgrade)
        Web_Upgrade
        Full_Upgrade
      ;;      
      (--) 
        shift
        break
      ;;
      (-*)         
        Error_Exit "$0: error - unrecognized option $1"
      ;;
      (*) 
        break
      ;;
    esac
    shift
  done
else                                             #No arguments means update trackers
  if [ "$(id -u)" != "0" ]; then                 #Check if running as root
    Error_Exit "Error this script must be run as root"    
  fi
  
  Check_Lists
  Get_IPAddress
  Download_Lists
  Build_Lists
fi 
