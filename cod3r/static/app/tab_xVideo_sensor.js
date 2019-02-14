/*
 * cod3r is a simple scripting environment for the Lego Mindstrom EV3
 * Copyright (C) 2014-2015 Jean BENECH
 *
 * cod3r is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * cod3r is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with cod3r.  If not, see <http://www.gnu.org/licenses/>.
 */
// A computation engine that is able to track points.
// Current implements is largely inspired from the jsfeast "Lukas Kanade optical flow" sample
class PointTrackingComputationEngine {
    constructor(appContext) {
        // init
        this.context = appContext; // The application context
        this.MAX_POINTS = 20;
        this.currentImagePyramid = undefined;
        this.previousImagePyramid = undefined;
        this.points = {
            number: 0,
            idx: 0,
            status: new Uint8Array(this.MAX_POINTS),
            name: [],
            currentXY: new Float32Array(this.MAX_POINTS * 2),
            previousXY: new Float32Array(this.MAX_POINTS * 2)
        };
        this.points.name[this.MAX_POINTS - 1] = undefined;
    }
    reset() {
        // Initialize 2 pyramid with depth 3 => 640x480 -> 320x240 -> 160x120
        this.currentImagePyramid = new jsfeat.pyramid_t(3);
        this.currentImagePyramid.allocate(this.width, this.height, jsfeat.U8_t | jsfeat.C1_t); // DataType: single channel unsigned char
        this.previousImagePyramid = new jsfeat.pyramid_t(3);
        this.previousImagePyramid.allocate(this.width, this.height, jsfeat.U8_t | jsfeat.C1_t);
        // Clear the points already defined
        this.points.number = 0;
    }
    compute(imageData, width, height) {
        // Swap data (recycle old objects to avoid costly instantiation)
        var recyclingPoints = this.points.previousXY;
        this.points.previousXY = this.points.currentXY;
        this.points.currentXY = recyclingPoints;
        var recyclingPyramid = this.previousImagePyramid;
        this.previousImagePyramid = this.currentImagePyramid;
        this.currentImagePyramid = recyclingPyramid;
        // Perform image processing
        jsfeat.imgproc.grayscale(imageData.data, width, height, this.currentImagePyramid.data[0]);
        this.currentImagePyramid.build(this.currentImagePyramid.data[0], true); // Populate the pyramid
        // See full documentation: http://inspirit.github.io/jsfeat/#opticalflowlk
        jsfeat.optical_flow_lk.track(this.previousImagePyramid, this.currentImagePyramid, // previous/current frame 8-bit pyramid_t
            this.points.previousXY, // Array of 2D coordinates for which the flow needs to be found
            this.points.currentXY, // Array of 2D coordinates containing the calculated new positions
            this.points.number, // Number of input coordinates
            25, // Size of the search window at each pyramid level
            30, // Stop searching after the specified maximum number of iterations (default: 30)
            this.points.status, // Each element is set to 1 if the flow for the corresponding features has been found otherwise 0 (default: null)
            0.1, // Stop searching when the search window moves by less than eps (default: 0.01)
            0); // The algorithm calculates the minimum eigen value of a 2x2 normal matrix of optical flow equations, divided by number of
        // pixels in a window; if this value is less than min_eigen_threshold, then a corresponding feature is filtered out and its flow is not
        // processed, it allows to remove bad points and get a performance boost (default: 0.0001)
        this.__removeLostPoints();
    }
    __removeLostPoints() {
        var n = this.points.number;
        var name = this.points.name;
        var status = this.points.status;
        var curXY = this.points.currentXY;
        var j = 0; // New number of points
        for (var i = 0; i < n; i++) {
            if (status[i] == 1) { // Keep the point
                if (j < i) {
                    curXY[j << 1] = curXY[i << 1];
                    curXY[(j << 1) + 1] = curXY[(i << 1) + 1];
                    name[j] = name[i];
                }
                j++;
            }
            else {
                this.context.messageLogVM.addError(i18n.t("videoSensorTab.pointsNoMoreTracked", { "name": name[i] }));
            }
        }
        this.points.number = j;
    }
    // Draw also returns the points structure as JSON
    drawComputationResult(ctx) {
        var result = {};
        var curXY = this.points.currentXY;
        var name = this.points.name;
        for (var i = this.points.number - 1; i >= 0; i--) {
            var x = Math.round(curXY[i << 1]), y = Math.round(curXY[(i << 1) + 1]);
            var txt = name[i] + ": {x: " + x + ", y: " + y + "}";
            var txtWidthOn2 = ctx.measureText(txt).width / 2;
            ctx.beginPath();
            ctx.moveTo(x, y);
            ctx.lineTo(x + 5, y - 14);
            ctx.moveTo(x + 5 - txtWidthOn2, y - 15);
            ctx.lineTo(x + 5 + txtWidthOn2, y - 15);
            ctx.stroke();
            ctx.fillText(txt, x + 5 - txtWidthOn2, y - 17);
            result[name[i]] = { x: x, y: y };
        }
        return result;
    };
    onClick(x, y) {
        var n = this.points.number;
        // Check if need to rename a point ?
        var xMin = x - 20, xMax = x + 20;
        var yMin = y - 20, yMax = y + 20;
        for (var i = 0; i < n; i++) {
            var px = this.points.currentXY[i << 1], py = this.points.currentXY[(i << 1) + 1];
            if ((xMin < px) && (px < xMax) && (yMin < py) && (py < yMax)) {
                this.__renamePoint(i);
                return;
            }
        }
        // Create a new point if possible
        if (n < (this.MAX_POINTS - 1)) {
            this.points.currentXY[n << 1] = x;
            this.points.currentXY[(n << 1) + 1] = y;
            this.points.name[n] = i18n.t("videoSensorTab.newPoint") + (++this.points.idx);
            this.points.number++;
            this.__renamePoint(n);
        }
        else {
            bootbox.alert(i18n.t("videoSensorTab.errors.maximumTrackedPointsReached", { number: this.MAX_POINT }));
        }
    }
    __renamePoint(pointIdx) {
        // Get the point name
        bootbox.prompt({
            title: i18n.t('videoSensorTab.configureTrackedPointNameModal.title'),
            value: this.points.name[pointIdx],
            callback: function (result) {
                if (result) {
                    this.points.name[pointIdx] = result;
                } // Cancel clicked
            }
        });
    };
}

// Model to manage the Video x-Sensor.
class VideoSensorTabViewModel {
    constructor(appContext) {
        // Init
        this.context = appContext; // The application context
        this.sensorName = ko.observable("xVideo");
        this.isStarted = ko.observable(false);
        this.webcam = document.getElementById("xVideoSensorWebcam"); // Video webcam HTML widget
        this.canvas = document.getElementById("xVideoSensorCanvas"); // Video canvas HTML widget
        this.webcamMediaStream = undefined; // The camera
        this.WIDTH = 640;
        this.HEIGHT = 480;
        // The computation data
        this.perf = undefined;
        this.ptce = new PointTrackingComputationEngine(appContext);
        this.perfSummary = ko.observable("");
        this.perfSummary.extend({ rateLimit: 200 }); // Accept lower refresh rate
        this.canvasCtx = this.canvas.getContext('2d');
        this.canvasCtx.fillStyle = "rgb(0,255,127)";
        this.canvasCtx.strokeStyle = "rgb(0,255,127)";
        this.canvasCtx.textBaseline = "bottom";
        this.canvasCtx.font = "bold 14px sans-serif";
        this.canvasCtx.lineWidth = 2;
    }
    onStart() {
        this.isStarted(!this.isStarted());
        if (this.isStarted()) {
            if (this.context.compatibility.isUserMediaSupported()) {
                // Request to access to the Webcam
                this.context.compatibility.getUserMedia({ video: true }, this.handleVideo, this.videoAccessRefused);
            }
        }
        else {
            // Stop acquiring video
            this.webcam.pause();
            this.webcam.src = null;
            this.perfSummary("");
            this.__clearCanvas();
            if (this.webcamMediaStream) { // Defined
                if (this.webcamMediaStream.stop)
                    this.webcamMediaStream.stop();
                else
                    this.webcamMediaStream.getVideoTracks()[0].stop();
                this.webcamMediaStream = undefined;
            }
            // Send an not started value
            this.SendSensorValue({ isStarted: this.isStarted() });
        }
    }
    SendSensorValue(value) {
        this.context.ev3BrickServer.streamXSensorValue(this.sensorName(), "Vid1", value);
    }
    // Start acquisition: Ensure that all the stuff is correctly initialized
    handleVideo(webcamMediaStream) {
        // Init webcam
        this.webcam.src = this.context.compatibility.URL.createObjectURL(webcamMediaStream);
        this.webcamMediaStream = webcamMediaStream;
        // Init computation stuff
        this.ptce.reset();
        this.prof = new profiler();
        // Launch the show
        setTimeout(function () {
            this.webcam.play();
            this.context.compatibility.requestAnimationFrame(this.onAnimationFrame);
        }, 500);
    }
    videoAccessRefused(err) {
        console.log("Error: " + JSON.stringify(err));
        alert(i18n.t("videoSensorTab.errors.videoAccessRefused"));
    }
    onAnimationFrame() {
        //console.log("onAnimmationFrame");
        if (this.isStarted()) {
            this.prof.new_frame();
            if (this.webcam.readyState === this.webcam.HAVE_ENOUGH_DATA) { // See https://developer.mozilla.org/en-US/docs/Web/API/HTMLMediaElement
                // Get image and compute
                this.canvasCtx.drawImage(this.webcam, 0, 0, this.WIDTH, this.HEIGHT);
                var imageData = this.canvasCtx.getImageData(0, 0, this.WIDTH, this.HEIGHT);
                this.ptce.compute(imageData, this.WIDTH, this.HEIGHT);
                // Update display
                var ceJson = this.ptce.drawComputationResult(this.canvasCtx);
                this.perfSummary("FPS: " + Math.round(this.prof.fps));
                // Send JSON event
                this.SendSensorValue({ isStarted: this.isStarted(), objects: ceJson });
            }
            this.context.compatibility.requestAnimationFrame(this.onAnimationFrame); // Call for each frame - See note on: https://developer.mozilla.org/en-US/docs/Web/API/window.requestAnimationFrame
        }
        else {
            this.__clearCanvas();
        }
    }
    __clearCanvas() {
        this.canvasCtx.clearRect(0, 0, this.WIDTH, this.HEIGHT);
    }
    onCanvasClick(data, event) {
        if (this.isStarted()) {
            var rect = this.canvas.getBoundingClientRect();
            var x = event.clientX - rect.left;
            var y = event.clientY - rect.top;
            if ((x > 0) && (y > 0) && (x < this.WIDTH) && (y < this.HEIGHT)) { // Add a new point
                this.ptce.onClick(x, y);
            }
        }
    };
}