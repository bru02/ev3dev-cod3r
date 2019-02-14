// Model that manage the "Import Images" dialog
class ImportImagesViewModel {
  constructor(appContext) {
    // Init
    this.context = appContext; // The application context
    this.codeToBuildTheImage = ko.observable("");
    this.useDithering = ko.observable(true);
    this.keepAspectRatio = ko.observable(true);
    this.threshold = ko.observable(128);
    this.canvas = document.getElementById("importImagesModalCanvas"); // Canvas for displaying the image
    this.canvasCtx = this.canvas.getContext('2d');
    this.currentImage = undefined;
    this.MAX_WIDTH = 178;
    this.MAX_HEIGHT = 128;
    this.useDithering.subscribe(function (newValue) {
      this.RecomputeImage();
    });
    this.keepAspectRatio.subscribe(function (newValue) {
      this.RecomputeImage();
    });
    this.threshold.subscribe(function (newValue) {
      this.RecomputeImage();
    });
  }
  display() {
    // Initialize the values
    $('#importImagesModal').modal('show');
    $('#importImages_selectFileForm')[0].reset();
    this.currentImage = undefined;
    this.codeToBuildTheImage("");
    this.threshold(128);
    this.canvasCtx.clearRect(0, 0, this.canvas.width, this.canvas.height);
  };
  uploadImage(file) {
    console.log("Filename: " + file.name);
    // Only process image files.
    if (file.type.match('image.*')) {
      // Initialize the image object
      var newImg = new Image();
      newImg.onload = function () {
        this.currentImage = { filename: file.name, rawData: newImg };
        this.ImageLoaded();
      };
      // Load the selected file
      var reader = new FileReader();
      reader.onload = function (event) {
        newImg.src = event.target.result;
      };
      reader.readAsDataURL(file);
    }
    else {
      bootbox.alert(i18n.t("importImagesModal.errors.fileIsNotAnImageSelectAnother", { filename: file.name }));
      this.currentImage = undefined;
      $('#importImages_selectFileForm')[0].reset();
    }
  };
  hide() {
    $('#importImagesModal').modal('hide');
  };
  RecomputeImage() {
    if (this.currentImage) {
      this.ImageLoaded();
    }
    // else: No image loaded => ignore
  };
  ImageLoaded() {
    var newImg = this.currentImage.rawData;
    var filename = this.currentImage.filename;
    var targetWidth = Math.min(this.MAX_WIDTH, newImg.width);
    var targetHeight = Math.min(this.MAX_HEIGHT, newImg.height);
    if (this.keepAspectRatio()) {
      // Default: keep aspect ratio and reduce the image if needed
      var ratio = Math.min(1, (this.MAX_WIDTH / newImg.width), (this.MAX_HEIGHT / newImg.height));
      targetWidth = Math.min(this.MAX_WIDTH, Math.ceil(newImg.width * ratio));
      targetHeight = Math.min(this.MAX_HEIGHT, Math.ceil(newImg.height * ratio));
    }
    console.log("Target width: " + targetWidth + ", target height: " + targetHeight);
    this.canvasCtx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    this.canvasCtx.drawImage(newImg, 0, 0, targetWidth, targetHeight);
    var imageData = this.canvasCtx.getImageData(0, 0, targetWidth, targetHeight);
    var pixels = imageData.data;
    var numPixels = imageData.width * imageData.height;
    var sPixels = new Uint8Array(numPixels);
    // Grayscale on luminosity - See http://en.wikipedia.org/wiki/Luma_%28video%29
    for (var i = 0; i < numPixels; i++) {
      var i4 = 4 * i;
      sPixels[i] = 0.21 * pixels[i4] + 0.72 * pixels[i4 + 1] + 0.07 * pixels[i4 + 2];
    }
    if (this.useDithering()) {
      // Dithering in white and black
      this.__convertToWhiteAndBlackDither(targetWidth, targetHeight, this.threshold(), sPixels);
    }
    else {
      // Basic conversion
      this.__convertToWhiteAndBlack(targetWidth, targetHeight, this.threshold(), sPixels);
    }
    // Copy back pixels to context imageData
    for (var i = 0; i < numPixels; i++) {
      var i4 = 4 * i;
      pixels[i4] = sPixels[i];
      pixels[i4 + 1] = sPixels[i];
      pixels[i4 + 2] = sPixels[i];
    }
    // Display the images
    this.canvasCtx.putImageData(imageData, 0, 0);
    // Encode to data URI
    var imageAsDataURI = this.__getCodeToBuildTheImage(this.__convertToRGFBinaryData(imageData.width, imageData.height, sPixels), filename);
    this.codeToBuildTheImage(imageAsDataURI);
  };
  // Floyd�Steinberg dithering algorithm - See http://en.wikipedia.org/wiki/Floyd%E2%80%93Steinberg_dithering
  // One deviation: Divide by 32 instead of 16 in order to don't report all the error (seems to give better results with only 
  // 2 colours in the target palette)
  __convertToWhiteAndBlackDither(width, height, threshold, pixels) {
    for (var i = 0; i < height; i++) {
      var lineOffset = i * width;
      for (var j = 0; j < width; j++) {
        var idx = lineOffset + j; // Current pixel index
        var currentPixel = pixels[idx];
        var newPixel = (currentPixel < threshold ? 0 : 255); // Black or white
        var error = currentPixel - newPixel;
        pixels[idx] = newPixel; // Set the pixel color                  
        // Report error on other pixels
        var notLastRow = (j + 1 < width);
        if (notLastRow) {
          pixels[idx + 1] += error * 7 / 32; // pixel at right
        }
        if (i + 1 < height) {
          if (j > 0) {
            pixels[idx + width - 1] += error * 3 / 32; // Pixel at bottom left
          }
          pixels[idx + width] += error * 5 / 32; // Pixel at bottom
          if (notLastRow) {
            pixels[idx + width + 1] += error / 32; // Pixel at bottom right
          }
        }
      }
    }
  };
  // Basic algorithm that make white and black on a given threshold
  __convertToWhiteAndBlack(width, height, threshold, pixels) {
    for (var i = 0; i < height; i++) {
      var lineOffset = i * width;
      for (var j = 0; j < width; j++) {
        var idx = lineOffset + j; // Current pixel index
        pixels[idx] = (pixels[idx] < threshold ? 0 : 255); // Black or white
      }
    }
  };
  // Convert the array of pixels (Uint8Array) to an RGB binary representation
  __convertToRGFBinaryData(width, height, pixels) {
    var ev3ImageData = new Uint8Array(2 + Math.floor((width + 7) / 8) * height); // + 7 in order to have a full number of bytes
    ev3ImageData[0] = width;
    ev3ImageData[1] = height;
    var index = 2; // The index within the raw image data
    var currentByte = 0; // The 8 pixels in progress (1 pixel is 1 bit)
    for (var i = 0; i < height; i++) {
      var lineOffset = i * width;
      var idxInLine = 0;
      for (var j = 0; j < width; j += 8) {
        currentByte = 0;
        for (var k = 7; k >= 0; k--) {
          currentByte <<= 1;
          idxInLine = j + k;
          if (idxInLine < width) { // End of line is blank
            currentByte |= (pixels[lineOffset + idxInLine] == 255 ? 0 : 1);
          }
        }
        ev3ImageData[index++] = currentByte;
      }
    }
    return ev3ImageData;
  };
  // Return a Data URI representing the given RGF binary data
  __getCodeToBuildTheImage(binaryData, filename) {
    var imgDataURI = "data:image/rgf;base64," + btoa(String.fromCharCode.apply(null, binaryData));
    // Build the variable name
    var varName;
    try {
      varName = filename;
      var dotIndex = filename.lastIndexOf('.');
      if (dotIndex != -1) {
        varName = filename.substring(0, dotIndex);
      }
      varName = varName.replace(/[^a-zA-Z0-9_]/g, ''); // Remove all that is not a letter or number
      if (varName.length > 0) {
        varName = varName.charAt(0).toUpperCase() + varName.slice(1);
      }
      varName = "img" + varName;
    }
    catch (e) {
      console.log(e);
      varName = "img";
      // Just ignore, not standard filename
    }
    // Build the code
    var jsCode = "var " + varName + " = ev3.getBrick().getScreen().decodeImage(";
    var index = 0, nextLineLength = Math.max(100 - jsCode.length, 10);
    while (index < imgDataURI.length) {
      jsCode += "\"" + imgDataURI.slice(index, index + nextLineLength) + "\"";
      index += nextLineLength;
      if (index < imgDataURI.length) {
        jsCode += " + \n";
      }
      else {
        jsCode += ");";
      }
      nextLineLength = 100;
    }
    return jsCode;
  };
}
