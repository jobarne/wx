#!/usr/bin/python

import requests
from bs4 import BeautifulSoup

DEG_2_RAD = math.pi/180
KN_2_MS = 1000.0/1944.0

# Work checks
page = requests.get('http://www.sailflow.com/spot/93452')
soup = BeautifulSoup(page.content, 'html.parser')
soup.find_all('script')[16] # EXACT, there are 18 - Maybe more rubust with steps
currentCond = soup.find_all('script')[16].get_text().encode('ascii') # EXACT, there are 18
station=json.loads(currentCond.split(";")[12].replace('\r\n            var stations = ','').replace('null','"dummy"'))
station[0]['data_values'][0][0].encode('ascii') # Timestamp
station[0]['data_values'][0][5] # TWD
station[0]['data_values'][0][7] # TWS kanske, kolla detta (eller skicka med noll)
data_values=json.loads(currentCond.split(";")[12].replace('\r\n            var stations = ','').replace('null','"dummy"'))[0]['data_values'][0]
data_values=json.loads(soup.find_all('script')[16].get_text().encode('ascii').split(";")[12].replace('\r\n            var stations = ','').replace('null','"dummy"'))[0]['data_values'][0]

# standalone:
data_values=json.loads(requests.get('http://www.sailflow.com/spot/93452').find_all('script')[16].get_text().encode('ascii').split(";")[12].replace('\r\n            var stations = ',''))[0]['data_values'][0]
timer = data_values[0].encode('ascii')
TWD = data_values[5] * DEG_2_RAD
TWS = data_values[7] * KN_2_MS # kanske...

# from lxml import html
# import requests
# page = requests.get('http://www.sailflow.com/spot/93452')
# tree = html.fromstring(page.content)
# tree.xpath('/html/body/table/tbody/tr[500]/td[2]')
# tree.xpath('//div[@class="clear bg-dark spot-info-hdr"]/text()')
# tree.xpath('/html/body/div[@class="body overflow"]/text()')