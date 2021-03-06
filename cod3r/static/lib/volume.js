(function () {
    function createAudioMeter(audioContext, clipLevel, averaging, clipLag) {
        var processor = audioContext.createScriptProcessor(512);
        processor.onaudioprocess = volumeAudioProcess;
        processor.clipping = false;
        processor.lastClip = 0;
        processor.volume = 0;
        processor.clipLevel = clipLevel || 0.98;
        processor.averaging = averaging || 0.95;
        processor.clipLag = clipLag || 750;

        // this will have no effect, since we don't copy the input to the output,
        // but works around a current Chrome bug.
        processor.connect(audioContext.destination);

        processor.checkClipping =
            function () {
                if (!this.clipping)
                    return false;
                if ((this.lastClip + this.clipLag) < window.performance.now())
                    this.clipping = false;
                return this.clipping;
            };

        processor.shutdown =
            function () {
                this.disconnect();
                this.onaudioprocess = null;
            };

        return processor;
    }

    function volumeAudioProcess(event) {
        var buf = event.inputBuffer.getChannelData(0);
        var bufLength = buf.length;
        var sum = 0;
        var x;

        // Do a root-mean-square on the samples: sum up the squares...
        for (var i = 0; i < bufLength; i++) {
            x = buf[i];
            if (Math.abs(x) >= this.clipLevel) {
                this.clipping = true;
                this.lastClip = window.performance.now();
            }
            sum += x * x;
        }

        // ... then take the square root of the sum.
        var rms = Math.sqrt(sum / bufLength);

        // Now smooth this out with the averaging factor applied
        // to the previous sample - take the max here because we
        // want "fast attack, slow release."
        this.volume = Math.max(rms, this.volume * this.averaging);
    }
    function wrapperAudioMeter(OPTIONS) {
        var audioContext = null;
        var meter = null;
        var rafID = null;
        var mediaStreamSource = null;

        // Retrieve AudioContext with all the prefixes of the browsers
        window.AudioContext = window.AudioContext || window.webkitAudioContext;

        // Get an audio context
        audioContext = new AudioContext();

        function onMicrophoneDenied() {
            if (typeof (OPTIONS["onError"]) == "function") {
                OPTIONS.onError('Stream generation failed.');
            }
        }

        function onMicrophoneGranted(stream) {
            // Create an AudioNode from the stream.
            mediaStreamSource = audioContext.createMediaStreamSource(stream);
            // Create a new volume meter and connect it.
            meter = createAudioMeter(audioContext);
            mediaStreamSource.connect(meter);

            // Trigger callback that 
            onLevelChange();
        }

        function onLevelChange(time) {

            if (typeof (meter.checkClipping() && OPTIONS["onClap"]) == "function") {
                OPTIONS.onClap(meter, time);
            }
            if (typeof (OPTIONS["onResult"]) == "function") {
                OPTIONS.onResult(meter, time);
            }

            // set up the next callback
            rafID = window.requestAnimationFrame(onLevelChange);
        }

        // Try to get access to the microphone
        try {

            // Retrieve getUserMedia API with all the prefixes of the browsers
            navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;

            // Ask for an audio input
            navigator.getUserMedia(
                {
                    "audio": {
                        "mandatory": {
                            "googEchoCancellation": "false",
                            "googAutoGainControl": "false",
                            "googNoiseSuppression": "false",
                            "googHighpassFilter": "false"
                        },
                        "optional": []
                    },
                },
                onMicrophoneGranted,
                onMicrophoneDenied
            );
        } catch (e) {
            if (typeof (OPTIONS["onError"]) == "function") {
                OPTIONS.onError(e);
            }
        }
    }

    window["microphoneLevel"] = wrapperAudioMeter;
})();