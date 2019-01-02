# wx

_**WX stations for SSF.**_

## Installation

### Install Raspbian Stretch
* enable ssh by creating a file named ssh or ssh.txt in boot partition

```
sudo apt-get update
sudo apt-get upgrade
```

### Fix raspbian install
```
sudo raspi-config
```
* Enable SSH
* Change name to wx
* Change to Swedish keyboard

### Install samba
Install samba according to http://raspberrypihq.com/how-to-share-a-folder-with-a-windows-computer-from-a-raspberry-pi
```
sudo apt-get install samba samba-common-bin
sudo vi /etc/samba/smb.conf
```
Set parameters workgroup, wins support and shared folder
Make [homes] browseable by:
```
browseable=yes
read only=no
create mask=0755
directory mask=0755
```
Add web root
```
[www]
 comment=WX web server
 path=/var/www/html
 browseable=Yes
 writeable=Yes
 only guest=no
 create mask=0777
 directory mask=0777
 public=no
```
Initialize samba
```
sudo /etc/init.d/samba restart
sudo smbpasswd -a pi
```
Set password to something short and simple

### Install mosquitto
Install mosquitto with websockets using https://mosquitto.org/2013/01/mosquitto-debian-repository/

Probably not necessary but if applicable, fix missing dependencies with:
```
cd ~
wget http://security.debian.org/debian-security/pool/updates/main/o/openssl/libssl1.0.0_1.0.1t-1+deb8u7_armhf.deb
sudo dpkg -i libssl1.0.0_1.0.1t-1+deb8u7_armhf.deb
wget http://ftp.nz.debian.org/debian/pool/main/libw/libwebsockets/libwebsockets3_1.2.2-1_armhf.deb
sudo dpkg -i libwebsockets3_1.2.2-1_armhf.deb
sudo apt-get install mosquitto mosquitto-clients
```
Set up mosquitto as service: https://www.dexterindustries.com/howto/run-a-program-on-your-raspberry-pi-at-startup/
(might be set up this way automatically...)

Add cloudmqtt.conf as:
```
connection cloudmqtt
address XXX
remote_username XXX
remote_password XXX
clientid hem.XXX.info
try_private false
start_type automatic
topic # out 0
topic # in 0
```

### Install LAMP
Install LAMP according to https://www.raspberrypi.org/learning/lamp-web-server-with-wordpress/worksheet/
```
cd ~
sudo apt-get install apache2 php5 mysql-server mysql-client phpmyadmin
sudo chmod 755 /var/www
sudo mousepad /etc/apache2/apache2.conf
```

### Install gettext for translations:
```
sudo apt-get install gettext
```

### Install ftp
(a bit like instructions on http://www.techrapid.co.uk/raspberry-pi/setup-ftp-server-raspberry-pi-vsftpd)
```
sudo apt-get install vsftpd
```

### Install python packages
```
sudo apt-get install python-pip
pip install pynmea2 arrow MySQLdb pprint pyserial mysqlclient timezonefinderL numpy paho-mqtt schedule bs4
sudo apt-get install python-mysqldb python-numpy
```

## Configuration

### Set up USB ftdi pigtails
Load kernel module by
```
sudo modprobe ftdi_sio
```
or rather permanently by adding the row "ftdi_sio" to /etc/modules
Add AIRMAR NMEA-USB converter to USB driver list by
```
sudo chmod 777 /sys/bus/usb-serial/drivers/ftdi_sio/new_id
sudo echo 0403 cc60 > /sys/bus/usb-serial/drivers/ftdi_sio/new_id
```
or rather permanently by adding these commands in a script file (/home/pi/addUsb.sh) and included in /etc/rc.local

### Set up WLAN
* Set up WLAN AP according to https://www.raspberrypi.org/documentation/configuration/wireless/access-point.md (but without routing)

(changed from the previous instructions: http://xmodulo.com/raspberry-pi-wifi-access-point.html)
```
sudo apt-get install hostapd udhcpd zd1211-firmware
```
* Change ip address so that wx1 has ip 192.168.0.1 (wx0 gets 192.168.0.1) etc and set SSID to ssf_wxX, ssf_wxY etc as well as password ssf_XXX, ssf_YYY
```
sudo vi /etc/dhcpcd.conf
sudo vi /etc/network/interfaces
sudo vi /etc/hostapd/hostapd.conf
```
* Configure 2nd external USB wifi (wlan1) as: https://www.raspberrypi.org/documentation/configuration/wireless/wireless-cli.md
* Install driver for TL-WN725N V2 according to: https://www.raspberrypi.org/forums/viewtopic.php?t=62371
* Set up sub web page for wifi config according to: https://github.com/billz/raspap-webgui ...but add rows for wlan1 as well as for the wlan0 in /etc/sudoers file

### Update credentials according to station name/number
* Change database credentials in db.py, db.php and config.php in www folder
* Change MQTT credentials in /etc/mosquitto/conf.d/cloudmqtt.conf
* Change username/password in web interface: localhost/wifi from raspap-webgui below

### Set up 3G (not necessary or used?)
* Set up 3g connectivity through USB-stick according to: http://raspberry.arctics.se/2013/03/30/ansluta-ett-3g-usb-modem-till-raspberry-pi/
* Move sakis3g to /usr/local/bin
* Add umtskeeper from: http://mintakaconciencia.net/squares/umtskeeper
* Create umtskeeper.conf file in /etc folder
* Add row in rc.local as: /home/pi/umtskeeper/umtskeeper --conf /etc/umtskeeper.conf --silent &>> /home/pi/umtskeeper/error.log &
* Toggle 3G service through sudo systemctl enable/disable 3G.service

### Set up python scripts
* Copy scripts addUsb.sh, startWX.sh and storeDB.sh to home directory
* Add execution of scripts to /etc/rc.local by:
```
sudo vi /etc/rc.local
```
* Set up to running python processes as services: https://www.dexterindustries.com/howto/run-a-program-on-your-raspberry-pi-at-startup/

### Configure sensor
Enable following sentences on weather station:
* GGA
* HDG
* MDA
* VTG
* MWV (for checking)
* RMC for date

## Change setup for another station number/name
* Change hostname
```
sudo raspi-config
```
-> change hostname

* Change setting  (4 places) for replication to cloudmqtt server:
```
sudo vi /etc/mosquitto/conf.d/cloudmqtt.conf
```

* Change ip (1 place) in dhcp server
```
sudo vi /etc/dhcpcd.conf
```
(sudo vi /etc/network/interfaces is not needed)

* Change ssid and wpa_passphrase in WLAN AP
```
sudo vi /etc/hostapd/hostapd.conf
```

* Change mysql connection string (username, password and database) to database in db.py and db.php
```
sudo vi $HOME/bin/db.py
sudo vi /var/www/html/db.php
```

* Change connection settings (username and password) for web pages in config.php
```
sudo vi /var/www/html/config.php
```

* Change credentials (Username, Old password, New password and Repeat new password) in web interface
http://wx2/wifi and select "Configure Auth"

* Add new database as well as user and remove previous ones

* Expand filesystem if needed

## Troubleshooting

> *I can not connect to station*

* Is station powered up?

* Does station LED lights flash? (one small red and small green on main board, one big green on USB connector)

> *I can connect but no data in web page*

* Open web page in Chrome browser and hit F12 for debug window. 

## License

TBD.
