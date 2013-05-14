/* Html5 Application provider */

function Html5ApplicationProvider(rdp_provider) {
	this.initialize(rdp_provider);
	this.tunnel = this.rdp_provider.guac_tunnel;
}
Html5ApplicationProvider.prototype = new ApplicationProvider();

Html5ApplicationProvider.prototype.applicationStart_implementation = function (application_id, token) { 
	var opcode    = "01";
	var appToken  = this.formatNumber(token, 4);
	var appId     = this.formatNumber(application_id, 4);

	this.tunnel.sendMessage("ovdapp", opcode+""+appToken+""+appId+";\n");
}

Html5ApplicationProvider.prototype.applicationStartWithArgs_implementation = function(application_id, args, token) { 
}

Html5ApplicationProvider.prototype.applicationStop_implementation = function(application_id, token) { 
}

Html5ApplicationProvider.prototype.formatNumber = function(num, bytes) {
	var buffer = new Array();
	var output = "";
	var input = ""

	/* convert to hex */
	input = ""+parseInt(num).toString(16);

	/* Prepend '0' if input.length is not even */
	if((input.length % 2) != 0) {
		input = "0" + input;
	}

	/* Push hex digits by pair */
	for(var i=0 ; i<input.length; i+=2) {
		buffer.push(input.charAt(i)+""+input.charAt(i+1));
	}

	/* Pad the aray with "00" up to 'bytes' arg */
	var remains = parseInt(bytes) - buffer.length;
	for(var i=0 ; i<remains ; ++i) {
		buffer.push("00");
	}

	/* output as a string */
	for(var i=0 ; i<buffer.length ; ++i) {
		output = output+buffer[i];
	}

	return output;
}
