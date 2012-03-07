/**
* @file asteroid_controller.js
* Asteroid related routines for rendering and controlling anything to do
* with the asteroids for the DML demo game
*/

// set the scene size
var canvas_width = $(window).width(),
  canvas_height = $(window).height();

var asteroid_hit_sound = new Audio(hit1_sound_path);

// set some camera attributes
var VIEW_ANGLE = 45,
  ASPECT = canvas_width / canvas_height,
  NEAR = 0.1,
  FAR = 10000;

// create a WebGL renderer, camera
// and a scene
var renderer = new THREE.WebGLRenderer();
var camera =
  new THREE.PerspectiveCamera(
    VIEW_ANGLE,
    ASPECT,
    NEAR,
    FAR);

var scene = new THREE.Scene();

// the camera starts at 0,0,0
// so pull it back
camera.position.z = 300;

// start the renderer
renderer.setSize(canvas_width, canvas_height);

// attach the render-supplied DOM element
$(renderer.domElement).attr('id', 'backcanvas').appendTo('body');

//=====================================================================
// TODO: As much as this offset is correct depending on aspect ratio
// It does nothing to vary the incoming angles of asteroids, destroying resting
// ships when too skinny, and barely showing up for wide screens
var asteroid_start_offset_x = Math.round(ASPECT*200);
var asteroids = [];

var loader = new THREE.JSONLoader();
loader.load( "models/asteroid_small.js", function(geometry) {
  add_asteroid(geometry, 20, .04, .02);
  add_asteroid(geometry, 40, -.04, .01);
  add_asteroid(geometry, 35, .05, -.03);
});

loader.load( "models/asteroid_model_big.js", function(geometry) {
  add_asteroid(geometry, 30, -.01, .02);
});

function add_asteroid(geometry, scale, rotate_speed_x, rotate_speed_y) {
  asteroids.push(new THREE.Mesh( geometry, new THREE.MeshFaceMaterial()));
  var i = asteroids.length - 1;
  asteroids[i].active = false;
  asteroids[i].position.set(asteroid_start_offset_x, 0, 0);
  asteroids[i].scale.set(scale, scale, scale);
  asteroids[i].overdraw = true;
  asteroids[i].rotation.speed = {x: rotate_speed_x, y: rotate_speed_y};
  asteroids[i].activate = function(option_id){
    if (this.active) return;

    var ypos_options = [
      {ypos: 230, yspd: -1.6, xspd: -1.7},
      {ypos: 115, yspd: -1.2, xspd: -1.7},
      {ypos: 0, yspd: -0.6, xspd: -1.7},
      {ypos: 0, yspd: 0.6, xspd: -1.7},
      {ypos: -115, yspd: 1.1, xspd: -1.7},
      {ypos: -230, yspd: 1.6, xspd: -1.7}
      ];
    // Set Y to random of 6 positions
    if (option_id == undefined){
      option_id = Math.floor(Math.random() * ypos_options.length);
    }
    this.position.setY(ypos_options[option_id].ypos);

    // Set movespeed to random matching speed
    this.movespeed = {y: ypos_options[option_id].yspd, x: ypos_options[option_id].xspd};
    if (this.boundRadius > 2) {
      this.movespeed.y = this.movespeed.y/2;
      this.movespeed.x = this.movespeed.x/2;
    }
    this.active = true;
  }

  // Add mesh to scene for rendering
  scene.add(asteroids[i]);

  // Activate the last one when loaded! (somewhat random, and random thereafter)
  if (i == 3){
    asteroids[i].activate();
  }
}

var pointLight =
  new THREE.PointLight(0xFFFFFF);

// set its position
pointLight.position.x = 10;
pointLight.position.y = 50;
pointLight.position.z = 130;

// add to the scene
scene.add(pointLight);

// 2D Canvas and contexts for collision detection===============================

var ship_image = new Image();
ship_image.src='libs/ninjaships/resources/ship_a.png';
var ship_canvas = $('<canvas>')[0];
var ship_context = ship_canvas.getContext('2d');
var ship_imagedata;
ship_image.onload = function(){
  ship_context.drawImage(ship_image, 0, 0);
  ship_context.rotate(1.5707);
  ship_imagedata = ship_context.getImageData(0, 0, 64, 64);
};

var projectile_canvas = $('<canvas>')[0];
var projectile_context = ship_canvas.getContext('2d');
projectile_context.beginPath();
projectile_context.arc(10, 10, 10, 0, 2 * Math.PI, false);
projectile_context.fillStyle = "#8ED6FF";
projectile_context.fill();
var projectile_imagedata = ship_context.getImageData(0, 0, 20, 20);

var xrot = 0, yrot = 0, xpos = 0, ypos = 0;

function run_asteroid_render(){
  $.each(asteroids, function(i, asteroid){
    if (asteroid.active){
      // Asteroid rotation
      xrot = asteroid.rotation.x + asteroid.rotation.speed.x;
      yrot = asteroid.rotation.y + asteroid.rotation.speed.y;
      if (xrot > 360) xrot = 0; if (xrot < 0) xrot = 360;
      if (yrot > 360) yrot = 0; if (yrot < 0) yrot = 360;

      asteroid.rotation.set(xrot, yrot, 0);

      // Asteroid position
      xpos = asteroid.position.x + asteroid.movespeed.x;
      ypos = asteroid.position.y + asteroid.movespeed.y;

      if (ypos > 240 || ypos < -240 || xpos > asteroid_start_offset_x){
        // RESET POS
        asteroid.active = false;
        xpos = asteroid_start_offset_x;
        ypos = 0;
        setTimeout(function(){
          asteroids[Math.floor(Math.random() * asteroids.length)].activate();
        }, (Math.floor(Math.random() * 5)+1) * 1000);
        // Random next asteroid between 1 and 5 seconds
      }

      asteroid.position.set(xpos, ypos, 0);

    }
  });
  renderer.render(scene, camera);
}

function run_collision_check(){
  if (ship_imagedata){

    var asteroid_imagedata_array = new Uint8Array(canvas_width * canvas_height * 4);
    renderer.context.readPixels(0, 0, canvas_width, canvas_height, renderer.context.RGBA, renderer.context.UNSIGNED_BYTE, asteroid_imagedata_array);

    var asteroid_imagedata = {width:canvas_width, height: canvas_height, data: asteroid_imagedata_array};

    // Check collision for every ship
    $.each(ships, function(i, ship){
      if (!ship.exploding){
        if (isPixelCollision(ship_imagedata, ship.pos.x, ship.pos.y, asteroid_imagedata, 0, 0, false)){
          ship.trigger_boom(true);
        }
      }
      // Check collision for projectile
      $.each(ship.projectiles, function(i, projectile){
        if (projectile.active){
          // TODO: Adjust position for correct width
          var xpos = projectile.pos.x-20;
          if (isPixelCollision(projectile_imagedata, xpos, projectile.pos.y, asteroid_imagedata, 0, 0, false)){
            projectile.active = false;
            // TODO: Add some kind of support for more than one asteroid
            $.each(asteroids, function(i, asteroid){
              if (asteroid.active){ // Will send away all active asteroids
                // Calculate moveback based on asteroid size
                var mass = asteroid.boundRadius * asteroid.boundRadiusScale;

                // Apparently the math is too hard for my brain at 3 in the AM
                // So here's a nasty hack to allow for the simulation of this :P
                var cheat = [2.5, 1.2, 0.4];
                var idx = 0;

                if (mass < 35) idx = 0;
                if (mass > 35) idx = 1;
                if (mass > 100) idx = 2;

                xspd = asteroid.movespeed.x + cheat[idx];

                asteroid_hit_sound.play();
                asteroid.movespeed.x = xspd;
              }
            });
          }
        }
      });

    });
  }
}


 /**
 * @author Joseph Lenton - PlayMyCode.com
 *
 * @param first An ImageData object from the first image we are colliding with.
 * @param x The x location of 'first'.
 * @param y The y location of 'first'.
 * @param other An ImageData object from the second image involved in the collision check.
 * @param x2 The x location of 'other'.
 * @param y2 The y location of 'other'.
 * @param isCentred True if the locations refer to the centre of 'first' and 'other', false to specify the top left corner.
 */
function isPixelCollision( first, x, y, other, x2, y2, isCentred )
{
    // we need to avoid using floats, as were doing array lookups
    x  = Math.round( x );
    y  = Math.round( y );
    x2 = Math.round( x2 );
    y2 = Math.round( y2 );

    var w  = first.width,
        h  = first.height,
        w2 = other.width,
        h2 = other.height ;

    // deal with the image being centred
    if ( isCentred ) {
        // fast rounding, but positive only
        x  -= ( w/2 + 0.5) << 0
        y  -= ( h/2 + 0.5) << 0
        x2 -= (w2/2 + 0.5) << 0
        y2 -= (h2/2 + 0.5) << 0
    }

    // find the top left and bottom right corners of overlapping area
    var xMin = Math.max( x, x2 ),
        yMin = Math.max( y, y2 ),
        xMax = Math.min( x+w, x2+w2 ),
        yMax = Math.min( y+h, y2+h2 );

    // Sanity collision check, we ensure that the top-left corner is both
    // above and to the left of the bottom-right corner.
    if ( xMin >= xMax || yMin >= yMax ) {
        return false;
    }

    var xDiff = xMax - xMin,
        yDiff = yMax - yMin;

    // get the pixels out from the images
    var pixels  = first.data,
        pixels2 = other.data;

    // if the area is really small,
    // then just perform a normal image collision check
    if ( xDiff < 65 && yDiff < 65 ) {
        for ( var pixelX = xMin; pixelX < xMax; pixelX++ ) {
            for ( var pixelY = yMin; pixelY < yMax; pixelY++ ) {
                if (
                        ( pixels [ ((pixelX-x ) + (pixelY-y )*w )*4 + 3 ] !== 0 ) &&
                        ( pixels2[ ((pixelX-x2) + (h2-(pixelY-y2))*w2)*4 + 3 ] !== 0 )
                ) {
                    return true;
                }
            }
        }
    } else {
        /* What is this doing?
         * It is iterating over the overlapping area,
         * across the x then y the,
         * checking if the pixels are on top of this.
         *
         * What is special is that it increments by incX or incY,
         * allowing it to quickly jump across the image in large increments
         * rather then slowly going pixel by pixel.
         *
         * This makes it more likely to find a colliding pixel early.
         */

        // Work out the increments,
        // it's a third, but ensure we don't get a tiny
        // slither of an area for the last iteration (using fast ceil).
        var incX = xDiff / 3.0,
            incY = yDiff / 3.0;
        incX = (~~incX === incX) ? incX : (incX+1 | 0);
        incY = (~~incY === incY) ? incY : (incY+1 | 0);

        for ( var offsetY = 0; offsetY < incY; offsetY++ ) {
            for ( var offsetX = 0; offsetX < incX; offsetX++ ) {
                for ( var pixelY = yMin+offsetY; pixelY < yMax; pixelY += incY ) {
                    for ( var pixelX = xMin+offsetX; pixelX < xMax; pixelX += incX ) {
                        if (
                                ( pixels [ ((pixelX-x ) + (pixelY-y )*w )*4 + 3 ] !== 0 ) &&
                                ( pixels2[ ((pixelX-x2) + (pixelY-y2)*w2)*4 + 3 ] !== 0 )
                        ) {
                            return true;
                        }
                    }
                }
            }
        }
    }

    return false;
}



