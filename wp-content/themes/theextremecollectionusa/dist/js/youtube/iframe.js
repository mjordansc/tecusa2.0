/* Setup Youtube API and create Iframe */

var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

/* Setup Video Iframe */
var player;
function onYouTubeIframeAPIReady() {
  player = new YT.Player('player', {
    height: '390',
    width: '100%',
    videoId: 'DvLmUanjXSQ',
    widget_referrer: window.location.origin,
    origin: window.location.origin,
    host: 'https://www.youtube.com',
    enablejsapi:1,
    playerVars: {
      'playsinline': 1
    },
    events: {
      'onReady': onPlayerReady,
      'onStateChange': onPlayerStateChange
    }
  });
}

//var timestamp = 5;
var timestamp_array = [10,15,21,52];
//var timestamp_2 = 6;
/* Function when video is ready */
function onPlayerReady(event) {
}

/* Function when video state changes */
function onPlayerStateChange(event) {
  /*if (event.data == -1 ) {
    console.log('video is unstarted');
  }*/



  if (event.data == YT.PlayerState.PLAYING) {

      timestamp_array.forEach(timestamp_display);
      //timestamp_callback(timestamp_array);

  }
}

/* Timestamp logic */



//var timestamp_2 = 15;
var timer = [];

function timestamp_callback(timestamps) {
  var timestamps_length = timestamps.length;
  for (var i = 0; i < timestamps_length; i++) {
    clearTimeout(timer[i]);
    current_time = player.getCurrentTime();
    remaining_time = timestamps[i] - current_time;
    remaining_time = Math.ceil(remaining_time);
    if (remaining_time > 0) {
      console.log(remaining_time);
      timestamp = timestamps[i];
      timer = setTimeout(
        function() { timestamp_reached(timestamp); },
        remaining_time * 1000);
    }
  }

}
function timestamp_reached(timestamp) {
  console.log('here ' + timestamp);
  var messages = document.querySelectorAll('.chat-message')
  //var message = document.getElementById('chat-1');
  messages.forEach(process_messages);

  function process_messages(message) {
    if ( message.dataset.timestamp == timestamp )
    message.style.display = 'block';
  }
  //messages[0].style.display = 'block';
  /*if (timestamp == 6) {
    console.log('made it here');
    var output_alert = timestamp + ' seconds -- Looks like we made it!';
    alert(output_alert);
  }*/

}

function timestamp_display(timestamp) {

    var timeout = setTimeout(function(){
      var interval = setInterval(function(){
        current_time = player.getCurrentTime();
        current_time = Math.ceil(current_time);
        if(current_time === timestamp){
            clearInterval(interval);
            timestamp_reached(timestamp);
        }
        console.log(current_time);
      },1000);
    },(timestamp - 2)*1000);


}


var iframe = document.getElementById('vimeo-iframe');
var player = new Vimeo.Player(iframe);


player.on('play', function() {
  console.log('Played the video');

});
var flag = false;
player.on('timeupdate', function(data){
  //console.log(data);

  current_time = Math.round(data.seconds);
  console.log(current_time);
  if(current_time === 4 && !flag){
      flag = true;
      alert('we made it');
  }
});
