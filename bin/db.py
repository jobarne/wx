#!/usr/bin/python

import MySQLdb

# Open database connection
db = MySQLdb.connect('localhost','ssf_wxX','ssf_yyyy','ssf_wxX')

# prepare a cursor object using cursor() method
cursor = db.cursor()
