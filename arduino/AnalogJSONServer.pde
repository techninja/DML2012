// This was a demo of the RBBB running as webserver with the Ether Card
// The body of the response was modified to simply output a JSON formatted
// string of the analog inputs. -- TechNinja - tn42.com

#include <EtherCard.h>

// ethernet interface mac address, must be unique on the LAN
static byte mymac[] = { 0x74,0x69,0x69,0x2D,0x30,0x31 };
static byte myip[] = { 192,168,0,242 };

byte Ethernet::buffer[500];
BufferFiller bfill;

void setup () {
  if (ether.begin(sizeof Ethernet::buffer, mymac) == 0)
    Serial.println( "Failed to access Ethernet controller");
  ether.staticSetup(myip);
}

static word homePage() {
  long t = millis() / 1000;
  word h = t / 3600;
  byte m = (t / 60) % 60;
  byte s = t % 60;
  bfill = ether.tcpOffset();
  bfill.emit_p(PSTR(
    "HTTP/1.1 200 OK\r\n"
    "Content-Type: application/json\r\n"
    "Pragma: no-cache\r\n"
    "\r\n"
    "{\"a\":[$D,$D,$D,$D,$D,$D]}"
    ),
      analogRead(0), analogRead(1), analogRead(2), analogRead(3), analogRead(4), analogRead(5));
  return bfill.position();
}

void loop () {
  word len = ether.packetReceive();
  word pos = ether.packetLoop(len);

  if (pos)  // check if valid tcp data is received
    ether.httpServerReply(homePage()); // send web page data
}