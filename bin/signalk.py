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
import socket
import math
import json
from decimal import Decimal

class signalk:
	KN_2_MS = 1000.0/1944.0
	DEG_2_RAD = math.pi/180
	NM_2_MET = 100000/54
	DAY_2_SEC = 86400
	NM_2_BL = 15.85*100000/54
	DEG_2_K = 273.15
	ExpIDs = [1, 2, 3, 4, 5, 6, 10, 13, 14, 15, 16, 17, 20, 31, 32, 48, 49, 50, 51, 57, 58, 79, 92, 93, 96, 103, 105, 106, 144, 145, 204, 207, 237, 238, 275]
	ExpNames = ['Bsp', 'Awa', 'Aws', 'Twa', 'Tws', 'Twd', 'Lwy', 'Hdg', 'AirTemp', 'SeaTemp', 'Baro', 'Depth', 'Rudder', 'Vmg', 'ROT', 'Lat', 'Lon', 'Cog', 'Sog', 'PolarBsp', 'PolarBspPercent', 'OppTrack', 'Vmc', 'MagVar', 'LayDist', 'OptVmcTwa', 'MarkRng', 'MarkBrg', 'TargTwaS', 'TargBspS', 'StartTimeToGun', 'StartDistBelowLine', 'TargTwaP', 'TargBspP', 'TripLog']
	dbNames = ['Boatspeed', 'AW_angle', 'AW_speed', 'TW_angle', 'TW_speed', 'TW_Dirn', 'Leeway', 'Heading', 'AirTemp', 'SeaTemp', 'AirPressure', 'Depth', 'Rudder', 'Vmg', 'ROT', 'Latitude', 'Longitude', 'Ext_COG', 'Ext_SOG', 'PolarBsp', 'PolarBspPercent', 'OppTrack', 'Vmc', 'MagVar', 'LayDist', 'OptVmcTwa', 'MarkRng', 'MarkBrg', 'TargTwaS', 'TargBspS', 'StartTimeToGun', 'StartDistBelowLine', 'TargTwaP', 'TargBspP', 'TripLog']
	Paths = ['navigation.speedThroughWater', 'environment.wind.angleApparent', 'environment.wind.speedApparent', 'environment.wind.angleTrueWater', 'environment.wind.speedTrue', 'environment.wind.directionMagnetic', 'navigation.leewayAngle', 'navigation.headingMagnetic', 'environment.outside.temperature', 'environment.water.temperature', 'environment.outside.pressure', 'environment.depth.belowSurface', 'steering.rudderAngle', 'performance.velocityMadeGood', 'navigation.rateOfTurn', 'navigation.position.latitude', 'navigation.position.longitude', 'navigation.courseOverGroundMagnetic', 'navigation.speedOverGround', 'performance.polarSpeed', 'performance.polarSpeedRatio', 'performance.tackMagnetic', 'navigation.courseRhumbline.nextPoint.velocityMadeGood', 'navigation.magneticVariation', 'navigation.racing.distanceLayline', 'performance.beatAngle', 'navigation.courseRhumbline.nextPoint.distance', 'navigation.courseRhumbline.nextPoint.bearingMagnetic', 'performance.targetAngle', 'performance.targetSpeed', 'navigation.racing.timeToStart', 'navigation.racing.distanceStartline', 'performance.targetAngle', 'performance.targetSpeed', 'navigation.trip.log']
	Calcs = ['*self.KN_2_MS', '*self.DEG_2_RAD', '*self.KN_2_MS', '*self.DEG_2_RAD', '*self.KN_2_MS', '*self.DEG_2_RAD', '*self.DEG_2_RAD', '*self.DEG_2_RAD', '+self.DEG_2_K', '+self.DEG_2_K', '', '*1', '*self.DEG_2_RAD', '*self.KN_2_MS', '*self.DEG_2_RAD', '*1', '*1', '*self.DEG_2_RAD', '*self.KN_2_MS', '*self.KN_2_MS', '/100', '*self.DEG_2_RAD', '*self.KN_2_MS', '*self.DEG_2_RAD', '*1', '*self.DEG_2_RAD', '*self.NM_2_MET', '*self.DEG_2_RAD', '*self.DEG_2_RAD', '*self.KN_2_MS', '*self.DAY_2_SEC', '*self.NM_2_MET', '*self.DEG_2_RAD', '*self.KN_2_MS', '*self.NM_2_MET']
	InvCalcs = ['/self.KN_2_MS', '/self.DEG_2_RAD', '/self.KN_2_MS', '/self.DEG_2_RAD', '/self.KN_2_MS', '/self.DEG_2_RAD', '/self.DEG_2_RAD', '/self.DEG_2_RAD', '-self.DEG_2_K', '-self.DEG_2_K', '', '*1', '/self.DEG_2_RAD', '/self.KN_2_MS', '/self.DEG_2_RAD', '*1', '*1', '/self.DEG_2_RAD', '/self.KN_2_MS', '/self.KN_2_MS', '*100', '/self.DEG_2_RAD', '/self.KN_2_MS', '/self.DEG_2_RAD', '*1', '/self.DEG_2_RAD', '/self.NM_2_MET', '/self.DEG_2_RAD', '/self.DEG_2_RAD', '/self.KN_2_MS', '/self.DAY_2_SEC', '/self.NM_2_MET', '/self.DEG_2_RAD', '/self.KN_2_MS', '/self.NM_2_MET']
	channels = [{'ExpID':ExpIDs[k],'ExpName':ExpNames[k],'dbName':dbNames[k],'Path':Paths[k],'Calc':Calcs[k],'Value':''} for k in range(0,35)]
		
	def __str__ (self):
		"""
		Return a string representation of the grid for debugging.
		"""
		return str(vars(self))
				
	def get_json(self, nmea_obj, host="self"):
		dbItems = nmea_obj.__dict__.items()
		dbDict = {k: v for k, v in dict(dbItems).items() if (v and k!="timestamp" and k!="timer" and k!="host" and k!="Latitude" and k!="Longitude")}
		# print dbDict
		values=[]
		for param, value in dbDict.iteritems():
			id = next(index for (index, d) in enumerate(self.channels) if d["dbName"] == param)
			value={"path": self.channels[id]['Path'],"value": eval(str(value) + self.channels[id]['Calc'])}
			values.append(value)
		if (nmea_obj.Latitude!="" and nmea_obj.Longitude!=""):
			position = {"latitude": nmea_obj.Latitude, "longitude": nmea_obj.Longitude}
			value = {"path": "navigation.position","value": position}
			values.append(value)
		update = {}
		update["values"] = values
		update["timestamp"] = nmea_obj.timestamp.strftime("%Y-%m-%dT%H:%M:%SZ")
		updates = []
		updates.append(update)		
		delta = {}
		delta["updates"] = updates
		if host:
			delta['context'] = 'vessels.' + host
		else:
			delta['context'] = 'vessels.self'
		return json.dumps(delta)
		
	def json2sql(self, json_str, host="self"):
		obj = json.loads(json_str)
		params = [self.dbNames[self.Paths.index(d['path'].encode('ascii'))] for d in obj['updates'][0]['values'] if d['path']!="navigation.position"]
		#values = [str(d['value']) for d in obj['updates'][0]['values'] if d['path']!="navigation.position"]
		values = [str(eval(str(d['value']) + self.InvCalcs[self.Paths.index(d['path'].encode('ascii'))])) for d in obj['updates'][0]['values'] if d['path']!="navigation.position"]
		params.append("host")
		values.append("'" + host + "'")
		if ('timestamp' in obj['updates'][0]): # There is a timestamp
			params.append("timer")
			print "CONVERT_TZ('" + str(datetime.datetime.strptime(obj['updates'][0]['timestamp'].encode('ascii'),"%Y-%m-%dT%H:%M:%SZ")) + "','+00:00','SYSTEM')"
			# print type(datetime.datetime.strptime(obj['updates'][0]['timestamp'].encode('ascii'),"%Y-%m-%dT%H:%M:%SZ"))
			# values.append(datetime.datetime.strftime(datetime.datetime.strptime(obj['updates'][0]['timestamp'].encode('ascii'),"%Y-%m-%dT%H:%M:%SZ"), "%Y-%m-%d %H:%M:%S"))
			values.append("CONVERT_TZ('" + str(datetime.datetime.strptime(obj['updates'][0]['timestamp'].encode('ascii'),"%Y-%m-%dT%H:%M:%SZ")) + "','+00:00','SYSTEM')")
			# timer = datetime.datetime.strptime(obj['updates'][0]['timestamp'].encode('ascii'),"%Y-%m-%dT%H:%M:%SZ")
			# timestamp = datetime.datetime.strftime(timer, "%Y-%m-%d %H:%M:%S")
		if (any(d['path']=="navigation.position" for d in obj['updates'][0]['values'])): # There is a position
			params.append("Latitude")
			values.append(str(obj['updates'][0]['values'][[str(d['path']) for d in obj['updates'][0]['values']].index("navigation.position")]['value']['latitude']))
			params.append("Longitude")
			values.append(str(obj['updates'][0]['values'][[str(d['path']) for d in obj['updates'][0]['values']].index("navigation.position")]['value']['longitude']))
		sql = "INSERT INTO `t_session_val` (`" + '`, `'.join(params) + "`) VALUES (" + ", ".join(values) + ");"
		print sql
		return sql
		