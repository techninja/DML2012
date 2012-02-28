<?php
// Arduino php proxy! (as Arduino won't do JSONP yet)
if (isset($_GET['proxy'])){
  header('Content-type: application/json');
  header('Pragma: no-cache');
  echo file_get_contents('http://192.168.0.242');
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
  <title>SSA DML DEMO 2012</title>
</head>
<body>
  <div id="asteroid"></div>
  <img class="preload" src="../spacetest/explosion_wide.png"/>
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
    <div class="change">Change:<span>0</span></div>
  </div>
  <script src="asteroid_controller.js"></script>
<script>
  // SHIP STUFF!! =============================================================
  var animdelay = 25;
  var ships = [];
  var winscreenrun = false;
  shipscreenwrap = false;
  oneshotperscreen = true;

  ships[0] = new shipObject("Player 1", 'ship1', 'a', {x:0, y:50, d:90});
  ships[1] = new shipObject("Player 2", 'ship2', 'b', {x:0, y:150, d:90});
  ships[2] = new shipObject("Player 3", 'ship3', 'c', {x:0, y:250, d:90});

  (function animloop(){
    if (!stopanim){
      requestAnimFrame(animloop);
    }
    run_asteroid_render();
    run_collision_check();
    run_ship_anim_frame();
    run_win_check();
  })();


  function run_win_check(){
    var winner = false;
    if (!winscreenrun){
      $.each(ships, function(i, ship){
        //console.log(i, ship);
        if (!ship.exploding && ship.pos.x + 64 >= $(window).width() - 30){
          winscreenrun = true;
          winner = i;
        }
      });

      // Run once per win scenario
      if (winscreenrun){
        $('#winscreen').fadeIn('slow');
        $('#winscreen span').html(ships[winner].name);
        setTimeout(function(){
          $('#winscreen').fadeOut('slow');
          ships[winner].element.hide();
          ships[winner].pos.x = ships[winner].home.x;
          ships[winner].kill_velocity();
          ships[winner].element.fadeIn('slow');
          winscreenrun = false; winner = false;
        }, 5000);
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


  var dostop = true;
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

var last_val = 0;
  function grabdata(){
    $.ajax({
      url: "?proxy",
      success: function(data){
        var avg = ((data.a[0] / 2) + last_val) / 2;
        $('#a0').css('height', ((avg / 512) * 100) + "%" );

        var change = parseInt(Math.abs(last_val-avg));

        if (change > 20){
          ships[0].thrust = 0;
          //ships[0].trigger_boom(true);
        }else{
          if (!ships[0].exploding){
            ships[0].thrust = change / 40
          }else{
            ships[0].thrust = 0;
          }
        }
        last_val = avg;
        $('.change span').html(change);

        /*$.each(data.a, function(i, val){
          $('#a'+i).css('height', ((val/2)+$('#a'+i).height())/2);
        })*/
        fpscount++;
        if (!dostop){
          //setTimeout(grabdata, 33);
          grabdata();
        }
      },
      dataType: 'json',
      error: function(jqXHR, textStatus, errorThrown){
        console.log(jqXHR, textStatus, errorThrown);
      }
    });

  }
</script>

</body>
</html>