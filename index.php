<?php
// Arduino php proxy! (as Arduino won't do JSONP yet)
if (isset($_GET['proxy'])){
  header('Content-type: application/json');
  header('Pragma: no-cache');
  echo file_get_contents('http://arduino.local');
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <script src="libs/jquery.js"></script>
  <script src="libs/jquery.spritely-0.6.js"></script>
  <script src="libs/ninjaships/ninjaships.js"></script>
  <script src="libs/Three.js"></script>
  <link rel="stylesheet" href="demo_styles.css">
  <link rel="stylesheet" href="libs/ninjaships/resources/styles.css">
  <title>Sylvia's Super-Awesome DML Demo, 2012</title>
</head>
<body class="space">
  <h2 id="finishline"><span>F
I
N
I
S
H
!</span>
  </h2>
  <div id="winscreen"><img src="trophy.png"/><h2><span>PLAYER</span> Wins!!</h2></div>

  <div class="wrapper">
    <div id="a0" class="bar"></div>
    <div id="a1" class="bar"></div>
    <div id="a2" class="bar"></div>
    <div id="a3" class="bar"></div>
    <div id="a4" class="bar"></div>
    <div id="a5" class="bar"></div>
  </div>
  <div id="controls">
    <button>Stop/Start</button>
    <div class="fps">FPS:<span>0</span></div>
    <div id="preload_boom"> </div>
  </div>

  <a href="http://sylviashow.com"><img class="logo" src="logo.png"/></a>
  <img class="logo" src="logo.png"/>
  <script src="asteroid_controller.js"></script>
<script>
  // SHIP STUFF!! =============================================================
  var do_debug = location.hash == "#debug";
  var animdelay = 25;
  var ships = [];
  var dostop = true;
  var winscreenrun = false;
  shipscreenwrap = false;
  oneshotperscreen = true;
  var win_sound = new Audio("audio/fanfare.wav");
  explosion_sound_path = "audio/explosion.wav";
  thrust_sound_path = "audio/thrust.wav";
  fire1_sound_path = "audio/fire1.wav";
  fire2_sound_path = "audio/fire2.wav";
  fire3_sound_path = "audio/fire3.wav";
  hit1_sound_path = "audio/hit2.wav";
  hit2_sound_path = "audio/hit1.wav";

  if (!do_debug){
    $('#controls, .wrapper').hide();
    var dostop = false;
    grabdata();
    $('body').pan({fps: 20, speed: 1, dir: 'left'})
  }

  // Initialize ship objects! (in a handy global array for usage everywhere)
  ships[0] = new shipObject("Player 1", 'ship1', 'a', {x:0, y:50, d:90});
  ships[1] = new shipObject("Player 2", 'ship2', 'b', {x:0, y:150, d:90});
  ships[2] = new shipObject("Player 3", 'ship3', 'c', {x:0, y:250, d:90});

  // Main animation loop, using browser frame requests (doesn't run if tab isn't visible)
  (function animloop(){
    if (!stopanim){
      requestAnimFrame(animloop);
    }
    run_asteroid_render();
    if (!winscreenrun) run_collision_check();
    run_ship_anim_frame();
    run_win_check();
  })();

  /**
   * Check if a ship has crossed the finish line, if so, execute the win scenario
   */
  function run_win_check(){
    var winner = false;
    if (!winscreenrun){
      $.each(ships, function(i, ship){
        if (!ship.exploding && ship.pos.x + 64 >= $(window).width() - 30){
          winscreenrun = true;
          winner = i;
        }
      });

      // Run once per win scenario
      if (winscreenrun){
        ships[winner].element.fadeOut(function(){
          // Set position to appear inside cup
          ships[winner].pos.d = 0;
          ships[winner].pos.x = $('#winscreen img').offset().left+20;
          ships[winner].pos.y = 70;
          ships[winner].kill_velocity();
          // Cheap hack to keep ship from exploding while in trophy cup
          ships[winner].exploding = true;
          ships[winner].element.css('z-index', '11');
          ships[winner].element.fadeIn();
        });
        $('#winscreen').fadeIn('slow', function(){win_sound.play();});
        $('#winscreen span').html(ships[winner].name);

        setTimeout(function(){
          $('#winscreen').fadeOut('slow');
          ships[winner].element.fadeOut('slow', function(){
            // Bring winner ship back (and undo the weird stuff)
            ships[winner].pos.x = ships[winner].home.x;
            ships[winner].pos.y = ships[winner].home.y;
            ships[winner].exploding = false;
            ships[winner].pos.d = 90;
            ships[winner].element.css('z-index', '');
            ships[winner].kill_velocity();
            ships[winner].element.fadeIn('slow');
            winscreenrun = false; winner = false;
          });
        }, 4000);
        // Blow up the loser ships!
        $.each(ships, function(i, ship){
          if (ship.pos.x + 64 < $(window).width() - 30){
            ship.trigger_boom(true);
          }
        });
      }
    }
  }

  // ^^ SHIP STUFF!! ========================================================^^


  // DEBUG CONTROL STUFF!! ====================================================
  var keyCodes = {
    16: 'shift', 32: 'space', 37: 'left', 38: 'up', 39: 'right', 40: 'down', 9: 'tab',
    87: 'w', 83: 's', 65: 'a', 68: 'd', 81: 'q', 69: 'e', 88: 'x'
  };

  $(document).keydown(function(e){ // KEYDOWN INIT ----------------
    //console.log(e.keyCode);
    if (winscreenrun) return; // TODO: Be sure and apply this to any input!
    switch (keyCodes[e.keyCode]){
      case 'a':
        ships[0].thrust = 0.25; break;
      case 's':
        ships[1].thrust = 0.25; break;
      case 'd':
        ships[2].thrust = 0.25; break;
      case 'q':
        ships[0].fire(); break;
      case 'w':
        ships[1].fire(); break;
      case 'e':
        ships[2].fire(); break;
    }

  }).keyup(function(e){ // KEYUP CANCEL ----------------
    switch (keyCodes[e.keyCode]){
      case 'a': case 's': case 'd':
        ships[0].thrust = 0;
        ships[1].thrust = 0;
        ships[2].thrust = 0;
        break;
    }
  });
  // ^^ DEBUG CONTROL STUFF!! ================================================^^

  // ETHERNET ARDUINO READ LOOP!! ==============================================
  var fpscount = 0;
  $('button').click(function(){
    dostop = !dostop;
    if (!dostop){
      grabdata();
    }
  })

  setInterval(function(){
    $('.fps span').html(fpscount*2);
    fpscount = 0;
  }, 500);

  var last_val = [0,0,0,0,0,0];
  var intro_counter = 0;
  function grabdata(){
    $.ajax({
      url: "?proxy",
      success: function(data){
        if (intro_counter< 10) intro_counter++;
        $.each(data.a, function(i, analog_val){
          // Debug value output bars
          var avg = ((analog_val / 2) + last_val[i]) / 2;
          if (do_debug) $('#a' + i).css('height', ((avg / 512) * 100) + "%" );

          if (i <= 2){ // Conductive dough sensors
            var change = parseInt(Math.abs(last_val[i]-avg));

            // Mostly ignore the first few values
            if (intro_counter > 8 && !winscreenrun) {
              if (change > 30){
                ships[i].thrust = 0;
                ships[i].trigger_boom(true);
              }else{
                if (change > 5) change = 5;
                if (!ships[i].exploding){
                  ships[i].thrust = change / 20
                }else{
                  ships[i].thrust = 0;
                }
              }
            }
          }

          if (i > 2 && !winscreenrun) { // Buttons!
            // Analog inputs are pulled up to 5v with 1k ohm
            // When analog drops down, the button was pressed!
            if (analog_val < 800){
              ships[i-3].fire();
            }
          }
          last_val[i] = avg;
        });

        fpscount++;
        if (!dostop){
          grabdata();
        }
      },
      dataType: 'json',
      error: function(jqXHR, textStatus, errorThrown){
        console.log(jqXHR, textStatus, errorThrown);
      }
    });
  }
  // ETHERNET ARDUINO READ LOOP STUFF ^^^ !! =================================^^
</script>

</body>
</html>
