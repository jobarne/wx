#!/usr/bin/python

import paho.mqtt.client as MQTT
from signalk import signalk
from db import *

class mqtt(MQTT.Client):
	
	def __init__(self):
		super(mqtt, self).__init__()
		# MQTT.Client.__init__(self)
		print "MQTT client created"
		self.MQTTbroker = 'localhost'
		self.MQTTport = 1883
		self.MQTTtopic = 'vessels'
		self.MQTTuserName = ""
		self.MQTTpassword = ""
		# self.username_pw_set(self.MQTTuserName, self.MQTTpassword)
		self.connect(self.MQTTbroker, self.MQTTport, 60)
		self.signalk_obj = signalk()
	
	def on_connect(self, client_id, userdata, flags, rc):
		# The callback for when the client receives a CONNACK response from the server.
		print("Connected with result code "+str(rc))
		# Subscribing in on_connect() means that if we lose the connection and
		# reconnect then subscriptions will be renewed.
		# self.subscribe(self.MQTTsubTopic)
	
	def on_message(self, client_id, userdata, msg):
		# The callback for when a PUBLISH message is received from the server.
		# print(msg.topic+" "+str(msg.payload))
		try:
			# Execute the SQL command
			cursor.execute(self.signalk_obj.json2sql(str(msg.payload), msg.topic.split("/")[1]))
			# Commit your changes in the database
			db.commit()
			print "Database insert succeeded"
		except:
			# Rollback in case there is any error
			db.rollback()
			print "Database insert failed"
		
	def publish(self, msg, subtopic = "self"):
		try:
			super(mqtt, self).publish(self.MQTTtopic + "/" + subtopic, payload = msg, qos = 0, retain = False)
			# Debug: print "Msg '" + msg + "' published on topic " + self.MQTTtopic + "/" + subtopic
			print "MQTT published on " + self.MQTTtopic + "/" + subtopic
		except:
			print "No MQTT publish"
			
	def subscribe(self, subtopic="#", QoS=0):
		try:
			super(mqtt, self).subscribe(self.MQTTtopic + "/" + subtopic, QoS)
			# Debug: print "Msg '" + msg + "' published on topic " + self.MQTTtopic + "/" + subtopic
			print "MQTT subscribing " + self.MQTTtopic + "/" + subtopic
		except:
			print "No MQTT publish"