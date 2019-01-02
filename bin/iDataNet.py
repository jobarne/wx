#!/usr/bin/python

import socket

class iDataNet():
	
	def __init__(self):
		self.UDP_IP = "255.255.255.255"
		self.UDP_PORT = 6001
		self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
		self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
	
	def send(self, nmea_obj):
		# The callback for when the client receives a CONNACK response from the server.
		self.sock.sendto(nmea_obj.iDataNetOut(), (self.UDP_IP, self.UDP_PORT))