"""nmea.py:  Module containing instrument data from nmea feed

	Contents:
		cube(x):  Returns the cube of x
		cubeVersion:  Current version number of this module
		# test nobj.__dict__ = oobj.__dict__.copy() , destination.__dict__.update(source.__dict__). or inspect.getmembers() from http://stackoverflow.com/questions/243836/how-to-copy-all-properties-of-an-object-to-another-object-in-python
"""

import pynmea2
import datetime
import time
import sys
from decimal import Decimal

class nmea:
	def __init__ (self):
		self.host = "self"
		self.Latitude = ""
		self.Longitude = ""
		self.Heading = ""
		self.AirPressure = ""
		self.AirTemp = ""
		self.TW_Dirn = ""
		self.TW_speed = ""
		self.Ext_COG = ""
		self.Ext_SOG = ""
		self.AW_speed = ""
		self.AW_angle = ""
		
	def __str__ (self):
		"""
		Return a string representation of the grid for debugging.
		"""
		return str(vars(self))
		
	def parse (self, nmea_sentence):
		try:
			pynmea2_object = pynmea2.parse(nmea_sentence)
			if isinstance(pynmea2_object, pynmea2.types.talker.RMC):
				self.Latitude = pynmea2_object.latitude
				self.Longitude = pynmea2_object.longitude
				self.timestamp = datetime.datetime.combine(pynmea2_object.datestamp,pynmea2_object.timestamp)
				self.timer = datetime.datetime.strftime(self.timestamp, "%Y-%m-%d %H:%M:%S")
			if isinstance(pynmea2_object, pynmea2.types.talker.HDG):
				self.Heading = pynmea2_object.heading
			# if isinstance(pynmea2_object, pynmea2.types.talker.MDA):
				# self.AirPressure = pynmea2_object.b_pressure_bar*1000	
				# self.AirTemp = pynmea2_object.air_temp
				# self.TW_Dirn = str(int(pynmea2_object.direction_magnetic))
				# self.TW_speed = pynmea2_object.wind_speed_knots
			if isinstance(pynmea2_object, pynmea2.types.talker.MWD):
				# self.AirPressure = pynmea2_object.b_pressure_bar*1000	
				# self.AirTemp = pynmea2_object.air_temp
				self.TW_Dirn = str(int(pynmea2_object.direction_magnetic))
				self.TW_speed = pynmea2_object.wind_speed_knots
			# if isinstance(pynmea2_object, pynmea2.types.talker.VTG):
				# self.Ext_COG = pynmea2_object.mag_track
				# self.Ext_SOG = pynmea2_object.spd_over_grnd_kts
			# if isinstance(pynmea2_object, pynmea2.types.talker.VWR):
				# if (int(pynmea2_object.deg_r) > 180):
					# self.AW_angle = pynmea2_object.deg_r - 360
				# else:
					# self.AW_angle = pynmea2_object.deg_r
				# self.AW_speed = pynmea2_object.wind_speed_kn
		except:
			e = sys.exc_info()[0]
			print e
			
	def iDataNetOut (self, *params):
		iDataNetMsg = "iDataNet"
		iDatanetItems = self.__dict__.items()
		if params:
			# print params
			iDatanetItems = [iDatanetItems[x] for x in params[0]]
		#for key, value in iDatanetItems:
		try:
			for key, value in {k: str(v) for k, v in iDatanetItems if (k!="host" and k!="timestamp" and k!="timer")}.iteritems():
				iDataNetMsg = iDataNetMsg + "@" + key + ";" + str(value)
		except ValueError as e:
			print e
		# Debug: print iDataNetMsg
		print "iDataNet sent"
		return iDataNetMsg + "\n"
		
		
	def sql (self, *params):
		dbItems = self.__dict__.items()
		if params:
			dbItems = [dbItems[x] for x in params[0]]
		dbDict = {k: v for k, v in dict(dbItems).items() if (v and k!="timestamp")}
		# To insert local times
		dbDict['timer'] = "' + DATE_ADD('" + dbDict['timer']  + "', INTERVAL " + str(-time.timezone) + " SECOND) + '"
		sql = "INSERT INTO t_session_val (`" + "`, `".join(dbDict.keys()) + "`) VALUES ('" + "', '".join(str(v) for v in dbDict.values())  + "');"
		# Debug: print sql
		print "Dababase insert"
		return sql
