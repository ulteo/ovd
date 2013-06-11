uovd.Html5SeamlessIcon = function() {
	this.pixelOffset = 0;
	this.canvas = document.createElement("canvas");
	this.context = this.canvas.getContext("2d");
	this.image = null;
}

uovd.Html5SeamlessIcon.prototype.parse = function(chunk, format, width, height, data) {
	if(chunk == 0) {
		this.image = this.context.createImageData(width, height);
		this.canvas.width=width;
		this.canvas.height=height;
	}

	var charData = new Array();
	for(var i = 0 ; i <data.length; i+=2) {
		charData.push(parseInt("0x"+data[i]+""+data[i+1]+""));
	}

	for(var i = 0 ; i < charData.length ; ++i )
	{
		this.image.data[parseInt(i)+parseInt(this.pixelOffset)] = parseInt(charData[i]);
	}
	this.pixelOffset += charData.length;
	this.context.putImageData(this.image,0,0);
}

uovd.Html5SeamlessIcon.prototype.getCanvas = function() {
	return this.canvas;
}
