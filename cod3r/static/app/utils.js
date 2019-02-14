// Manage compatibility for accessing to the webcam (getUserMedia) and video rendering (requestAnimationFrame)
var compatibility = (function () {
  var lastTime = 0,
    URL = window.URL || window.webkitURL,

    requestAnimationFrame = function (callback, element) {
      var requestAnimationFrame =
        window.requestAnimationFrame ||
        window.webkitRequestAnimationFrame ||
        window.mozRequestAnimationFrame ||
        window.oRequestAnimationFrame ||
        window.msRequestAnimationFrame ||
        function (callback, element) {
          var currTime = new Date().getTime();
          var timeToCall = Math.max(0, 16 - (currTime - lastTime));
          var id = window.setTimeout(function () {
            callback(currTime + timeToCall);
          }, timeToCall);
          lastTime = currTime + timeToCall;
          return id;
        };

      return requestAnimationFrame.call(window, callback, element);
    },

    cancelAnimationFrame = function (id) {
      var cancelAnimationFrame = window.cancelAnimationFrame ||
        function (id) {
          clearTimeout(id);
        };
      return cancelAnimationFrame.call(window, id);
    },

    getUserMedia = function (options, success, error) {
      var getUserMedia =
        window.navigator.getUserMedia ||
        window.navigator.mozGetUserMedia ||
        window.navigator.webkitGetUserMedia ||
        window.navigator.msGetUserMedia ||
        function (options, success, error) {
          error();
        };

      return getUserMedia.call(window.navigator, options, success, error);
    },

    isUserMediaSupported = function () {
      return (window.navigator.getUserMedia ||
        window.navigator.mozGetUserMedia ||
        window.navigator.webkitGetUserMedia ||
        window.navigator.msGetUserMedia) != undefined;
    },

    // Adapted from: https://developer.mozilla.org/en-US/docs/Web/Guide/API/DOM/Using_full_screen_mode
    // Note: Currently MUST be triggered by the user and can't be done automatically (eg. based on screen size)
    toggleFullScreen = function () {
      var elem = document.body;
      var isFullScreen = (document.fullscreenElement ||
        document.mozFullScreenElement ||
        document.webkitFullscreenElement ||
        document.msFullscreenElement);

      if (isFullScreen) {
        if (document.exitFullscreen) {
          document.exitFullscreen();
        } else if (document.msExitFullscreen) {
          document.msExitFullscreen();
        } else if (document.mozCancelFullScreen) {
          document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
          document.webkitExitFullscreen(); // Element.ALLOW_KEYBOARD_INPUT
        }
      } else {
        if (elem.requestFullscreen) {
          elem.requestFullscreen();
        } else if (elem.msRequestFullscreen) {
          elem.msRequestFullscreen();
        } else if (elem.mozRequestFullScreen) {
          elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) {
          elem.webkitRequestFullscreen();
        }
      }
    };

  return {
    requestAnimationFrame: requestAnimationFrame,
    cancelAnimationFrame: cancelAnimationFrame,
    getUserMedia: getUserMedia,
    isUserMediaSupported: isUserMediaSupported,
    toggleFullScreen: toggleFullScreen,
    URL: URL
  };
})();

var CanvasUtils = {
  roundedRect: function (ctx, x, y, width, height, radius) {
    ctx.beginPath();
    ctx.moveTo(x, y + radius);
    ctx.lineTo(x, y + height - radius);
    ctx.quadraticCurveTo(x, y + height, x + radius, y + height);
    ctx.lineTo(x + width - radius, y + height);
    ctx.quadraticCurveTo(x + width, y + height, x + width, y + height - radius);
    ctx.lineTo(x + width, y + radius);
    ctx.quadraticCurveTo(x + width, y, x + width - radius, y);
    ctx.lineTo(x + radius, y);
    ctx.quadraticCurveTo(x, y, x, y + radius);
    ctx.closePath();
    return ctx;
    // ctx.stroke();
  }
}

var Utils = (function () {
  // Round by keeping only 2 decimal
  var round2dec = function (n) {
    // return Number(n.toFixed(2));
    return Math.round(n * 100) / 100;
  };

  // Generate a UUID
  var generateUUID = function () {
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = (d + Math.random() * 16) % 16 | 0;
      d = Math.floor(d / 16);
      return (c == 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
    return uuid;
  };

  // From: http://www.jquerybyexample.net/2012/06/get-url-parameters-using-jquery.html
  var getUrlParameter = function (sParam) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
      var sParameterName = sURLVariables[i].split('=');
      if (sParameterName[0] == sParam) {
        return sParameterName[1];
      }
    }
  };
  return {
    round2dec,
    generateUUID,
    getUrlParameter,
  };
})();


/////////////////////////////
// Knockout specific bindings

// Binding used for enabling/disabling the bootstrap buttons
ko.bindingHandlers['disabled'] = {
  update: function (element, valueAccessor) {
    var valueUnwrapped = ko.unwrap(valueAccessor());
    if (valueUnwrapped) {
      $(element).attr("disabled", "true").addClass("disabled");
    } else {
      $(element).removeAttr("disabled").removeClass("disabled");
    }
  }
};

// Binding used for setting data-i18n tag and i18n class
ko.bindingHandlers['i18n'] = {
  update: function (element, valueAccessor) {
    var valueUnwrapped = ko.unwrap(valueAccessor());
    if (valueUnwrapped) {
      $(element)
        .attr("data-i18n", valueUnwrapped)
        .removeClass("i18n") // Remove in order to avoid duplicate i18n (maybe useless ?)
        .addClass("i18n")
        .i18n(); // Finally translate the item
    } else {
      // Useless case
      $(element)
        .removeAttr("data-i18n")
        .removeClass("i18n");
    }
  }
};

function PopupCenter(url, title, w, h) {
  // Fixes dual-screen position                         Most browsers      Firefox
  var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
  var dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;

  var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
  var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

  var systemZoom = width / window.screen.availWidth;
  var left = (width - w) / 2 / systemZoom + dualScreenLeft
  var top = (height - h) / 2 / systemZoom + dualScreenTop
  var newWindow = window.open(url, title, 'scrollbars=yes, width=' + w / systemZoom + ', height=' + h / systemZoom + ', top=' + top + ', left=' + left);

  // Puts focus on the newWindow
  if (window.focus) newWindow.focus();
}