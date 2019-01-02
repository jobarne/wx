#!/usr/bin/python

import os
from timezonefinder import TimezoneFinder
import datetime, time
import serial
import math
from decimal import Decimal
import sys
import socket
from nmea import nmea
from signalk import signalk
from mqtt import mqtt
from iDataNet import iDataNet
from db import *

tf = TimezoneFinder()

host = socket.gethostname()

old_time = 1500000000

virgin = 1

ser = serial.Serial(
	port='/dev/ttyUSB0',
	# port='/dev/pts/6',
	baudrate = 4800,
	parity=serial.PARITY_NONE,
	stopbits=serial.STOPBITS_ONE,
	bytesize=serial.EIGHTBITS,
	timeout=5
)

nmea_obj = nmea()
signalk_obj = signalk()
mqtt_client=mqtt()
iDataNet_obj=iDataNet()

while True:
	line = ser.readline()
	sys.stdout.write('-')
	try:
		# Debug: print line,
		nmea_obj.parse(line.strip('\x00'))
		if hasattr(nmea_obj, 'timestamp'):
			print "->nmea" # heartbeat
			try:
				timer = int(nmea_obj.timestamp.strftime("%s"))
				if (timer<1450000000 or timer>1550000000):
					new_time = math.floor(time.time())
				else:
					new_time = math.floor(timer)
			except:
				new_time = math.floor(time.time())
			#print nmea_obj
			if (new_time>old_time):
				if old_time!=1500000000:
					if virgin:
						if (new_time-old_time<10):
							try:
								# Set timezone
							#	os.system('sudo cp /usr/share/zoneinfo/' + tf.timezone_at(lng=nmea_obj.Longitude, lat=nmea_obj.Latitude).encode('ascii') + ' /etc/localtime')
								# Set time
								os.system("sudo date -u '+%Y-%m-%d %H:%M:%S' -s '" + nmea_obj.timestamp.strftime("%Y-%m-%d %H:%M:%S") + "'")
								# Done doing initialization
								virgin = 0
							except:
								e = sys.exc_info()[0]
								print e
#					else:
#						try:
#							# Execute the SQL command
#							cursor.execute(nmea_obj.sql())
#							# Commit your changes in the database
#							db.commit()
#						except:
#							# Rollback in case there is any error
#							db.rollback()
					# sock.sendto(nmea_obj.iDataNetOut(), (UDP_IP, UDP_PORT))
				#	iDataNet_obj.send(nmea_obj)
					mqtt_client.publish(signalk_obj.get_json(nmea_obj))
					if (float(new_time - 2)/10).is_integer():
						mqtt_client.publish(signalk_obj.get_json(nmea_obj,host),host)
				# Create a fresh nmea object
				nmea_obj = nmea()
				signalk_obj = signalk()
				old_time = new_time
	except:
		e = sys.exc_info()[0]
		print e
