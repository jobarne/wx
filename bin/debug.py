#!/usr/bin/python

import serial
import sys

ser = serial.Serial(
	port='/dev/ttyUSB0',
	# port='/dev/pts/6',
	baudrate = 4800,
	parity=serial.PARITY_NONE,
	stopbits=serial.STOPBITS_ONE,
	bytesize=serial.EIGHTBITS,
	timeout=5
)

while True:
	line = ser.readline()
	sys.stdout.write(line.strip('\x00'))