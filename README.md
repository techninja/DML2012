## Sylvia's Super-Awesome DML Demo 2012!

_Super-Awesome Sylvia came up with the idea and TechNinja coded it: It's our crazy [Digital Media & Learning][1] conference Science Fair demo, **Squishy Space Race 2042!** A game for three competitive players written in CSS3 and HTML5, and controlled by squishing conductive dough. **[Original Google+ Post Here][5]**_

### [Check out a live demo _(sans Arduino and squishy circuits)_ here!][6]

### [Check out the official blog post with pictures, video and more about the event here!][8]

### [OR skip straight to the video of the game and hardware in action at the DML 2012 Science Fair here!][7]

#### The Setup:

Up to three participants line up in front of the booth table and watch the TV in front of them. Each has their own "control panel" with a button and a lump of home-made conductive salt dough on two copper pads connected to an Ethernet enabled Arduino.

#### The Gimmick:

Each player controls a spaceship on screen by squishing the dough in _just the right way_, and the first player across the finish line on the right side of the screen, wins! Exactly how to squish the dough isn't given, and you can't simply squish it flat; the ships only move if there's enough change in the dough's resistance over time. Also the space field players must cross is randomly strewn with hurtling 3D asteroids that they can deflect with their laser, if they can react in time!

### Software Requirements:

**Server:** The index file was meant to run on a standard stack for PHP, but it's not required. The only PHP code used is at the top to create a server side proxy to wherever the Arduino happens to live, because the browser cannot request the JSON string cross domain without JSONP (which I hadn't implemented in my Arduino firmware).

**Client:** This was written for and in the latest version of Mozilla Firefox, but happens to also work great in Google Chrome. Also somewhat software though also hardware related, WebGL is required to realtime render the asteroids. This is a silly requirement considering the rest of the game is regular old CSS3 and HTML5 object markup, but hey, it's a demo. It's all about the fun. Also, it's recommended you use full browser zooming/scaling (CTRL+ 'Plus') to adjust the exact size you want the ships, then refresh the page. Although this makes everything blurrier, this cuts down on the number of operations required per frame during collision detection adn rendering, not to mention helps the game "fit" a given screen better. At 6 feet away on a 42 inch screen, no one said _**a thing**_ about the pixels ;)

### Assembly:

Your mileage may vary, but here's our physical setup from Demo night:

  * [JeeLabs Ethercard][2] piggybacking on a [Protoshield][3], on top of a standard Arduino
  * All 6 analog inputs pulled up to 5v with a 1k ohm resistor
  * First three analog inputs wired to one of two copper pads per control surface
  * Last three analog inputs wired to one side of a dollar store touch light hacked to use a momentary toggle pushbutton (hot glue is your friend!)
  * Remaining copper pads and switch leads all wired up to ground.
  * Arduino firmware simply assigns itself a known static IP address, and on a standard http request returns a JSON array of analogRead values, EG {a:[1023,1023,1023,1023,1023,1023]}
  * 10/100 Switch to ensure Arduino and laptop are on same network
  * Arduino IP address set properly in your hosts file (not required, if skipping this, simply replace "arduino.local" at the top of index.php for the proxy)

### Squishless testing:

The Arduino and [squishy circuits][4] we used for the demo aren't actually required to test the game out! Currently "A, S, D" add thrust to the ships, and "Q, W, E" fire lasers for #1, #2 & #3 respectively.

Also added for the benefit of those without Arduino that still want a taste of the action, these key combos are also available: (Though it will be a _very_ short game)

  * _Player_: **Thrust** / **Fire**
  * 1: A / Q
  * 2: B / Space Bar
  * 3: L / O

   [1]: http://dml2012.dmlcentral.net/
   [2]: http://jeelabs.net/projects/9/wiki/Ether_Card
   [3]: https://www.adafruit.com/products/51
   [4]: http://sylviashow.com/squishy
   [5]: https://plus.google.com/102649665767942479588/posts/ctMQKYZyPi2
   [6]: http://sylviashow.com/dml2012
   [7]: http://youtu.be/brPJJ5upgfA
   [8]: http://sylviashow.com/blog/techninja/2012/03/15/dml-wrap-make-photoshoot-and-plans-2012

