#!/usr/bin/python

from mqtt import mqtt
import json
import urllib2
import datetime
import math
import schedule
import time

DEG_2_RAD = math.pi/180

mqtt_client=mqtt()

def scrape():
	data = json.load(urllib2.urlopen('https://services.viva.sjofartsverket.se:8080/output/vivaoutputservice.svc/vivastation/69'))
	tws = float(data["GetSingleStationResult"]["Samples"][1]["Value"].split()[1])
	#print tws
	twd = float(data["GetSingleStationResult"]["Samples"][1]["Heading"]*DEG_2_RAD)
	#print twd

	values=[]
	value={"path": "environment.wind.directionMagnetic","value": twd}
	values.append(value)
	value={"path": "environment.wind.speedTrue","value": tws}
	values.append(value)
	update = {}
	update["values"] = values
	update["timestamp"] = datetime.datetime.strptime(data["GetSingleStationResult"]["Samples"][1]["Updated"], "%Y-%m-%d %H:%M:%S").strftime("%Y-%m-%dT%H:%M:%SZ")
	updates = []
	updates.append(update)	
	delta = {}
	delta["updates"] = updates
	delta['context'] = 'vessels.viva2'
	print delta

	mqtt_client.publish(json.dumps(delta), "wx_viva2")
	
schedule.every(30).seconds.do(scrape)

while True:
    schedule.run_pending()
    time.sleep(1)

#publish.single("paho/test/single", "boo", hostname="test.mosquitto.org")
#publish.single(self, self.MQTTsubTopic, payload = msg, qos = 0, retain = False)