#!/usr/bin/env bash
#Title : NoTrack Upgrader
#Description : This script will download the latest release from Github
#Author : QuidsUp
#Date : 2016-01-10
#Usage : bash upgrade.sh (Can also be run from /admin/upgrade web interface)

#Program Settings----------------------------------------------------
NewVer="0.3"                                    #Need something more automated

#Main----------------------------------------------------------------
if [ "$(id -u)" == "0" ]; then                   #Check if running as root
   echo "Error do not run this script as root"
   echo "Execute with: bash upgrade.sh"
   exit 2
fi

if [ -d ~/NoTrack ]; then                        #Check if NoTrack folder exists
  if [ -d ~/NoTrack-old ]; then                  #Delete NoTrack-old folder if it exists
    echo "Removing old NoTrack folder"
    echo
    rm -r ~/NoTrack-old
  fi
  echo "Moving ~/NoTrack folder to ~/NoTrack-old"
  echo
  mv ~/NoTrack ~/NoTrack-old
fi

wget "https://github.com/quidsup/notrack/archive/v$NewVer.zip" -O /tmp/notrack-master.zip
if [ ! -e /tmp/notrack-master.zip ]; then      #Check if download was successful
  echo "Unable to download https://github.com/quidsup/notrack/archive/v$Version.zip"
  echo "Falling back to master version instead"
  wget https://github.com/quidsup/notrack/archive/master.zip -O /tmp/notrack-master.zip
  if [ ! -e /tmp/notrack-master.zip ]; then    #Check again to see if download was successful
    echo "Error Download from github has failed"
    exit 2                                     #Abort we can't go any further without any code from git
  fi
fi

echo "Upgrade complete"
