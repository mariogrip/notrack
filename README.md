# NoTrack
Tracking is absolutely rife on the Internet, news websites are among the worst with some of them dropping over 100 cookies per visit. Sure you can block third party cookies, but that alone is not enough because many tracking sites utilise tracking pixels. The only way to stop them is to prevent your browser downloading these tiny 1 pixel images.
There are only three websites in the top 100 that don't track you – Wikipedia, Apple, and HSBC. 
  
NoTrack is a network-wide DNS server which blocks Tracking websites from creating cookies or sending tracking pixels. It does this by resolving the IP address of known tracking sites to a web server running on the device inside your network.
  
NoTrack currently works in Debian and Ubuntu.
You can use on a Raspberry Pi with a fresh install of Raspbian Jessie Lite
  
# To Install:  
wget https://raw.githubusercontent.com/quidsup/notrack/master/install.sh  
bash install.sh

Point the DNS IP of all your systems to your NoTrack device.
