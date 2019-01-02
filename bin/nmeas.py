"""nmeas.py:  Module containing instrument data from nmea feed

	Contents:
		cube(x):  Returns the cube of x
		cubeVersion:  Current version number of this module
		# test nobj.__dict__ = oobj.__dict__.copy() , destination.__dict__.update(source.__dict__). or inspect.getmembers() from http://stackoverflow.com/questions/243836/how-to-copy-all-properties-of-an-object-to-another-object-in-python
"""

from nmea import nmea

class nmeas:
	def __init__ (self):
		self.members = []
		
	def __str__ (self):
		"""
		Return a string representation of the grid for debugging.
		"""
		return str(vars(self))
		
	def add (self, nmea_obj):
		self.members.append(nmea_obj)
		
	def average (self):
		nmea_ave = nmea()
		nmea_obj.__dict__.items()
		# for item in [items for items in self.members if x != 50]:
		for nmea_items in self.members:
			nmea_ave
			
			
