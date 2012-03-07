## Arduino AnalogJSONServer for JeeLabs Ethercard

by TechNinja

#### Wiring:

**Arduino -> Ethercard**
PIN 08 ->  B0
PIN 11 -> MOSI
PIN 12 -> MISO
PIN 13 -> SCK

Make sure the IP matches your subnet mask, give it some power, and you should be able to start polling! On an immediate iterative polling loop in jQuery this server can do up to 45 requests per second in Firefox, and over 95 per second in Chrome.
