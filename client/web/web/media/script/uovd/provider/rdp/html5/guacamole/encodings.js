/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David PHAM-VAN <d.pham-van@ulteo.com>, 2012
 *
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

function utf8_encode(string) {
	string = string.replace(/\r\n/g,"\n");
	var utftext = "";

	for (var n = 0; n < string.length; n++) {
		var c = string.charCodeAt(n);

		if (c < 128) {
			utftext += String.fromCharCode(c);
		}
		else if((c > 127) && (c < 2048)) {
			utftext += String.fromCharCode((c >> 6) | 192);
			utftext += String.fromCharCode((c & 63) | 128);
		}
		else {
			utftext += String.fromCharCode((c >> 12) | 224);
			utftext += String.fromCharCode(((c >> 6) & 63) | 128);
			utftext += String.fromCharCode((c & 63) | 128);
		}
	}
	return utftext;
}
 
function utf8_decode(utftext) {
	var string = "";
	var i = 0;
	var c = c1 = c2 = 0;

	while ( i < utftext.length ) {
		c = utftext.charCodeAt(i);
		if (c < 128) {
			string += String.fromCharCode(c);
			i++;
		}
		else if((c > 191) && (c < 224)) {
			c2 = utftext.charCodeAt(i+1);
			string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
			i += 2;
		}
		else {
			c2 = utftext.charCodeAt(i+1);
			c3 = utftext.charCodeAt(i+2);
			string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
			i += 3;
		}
	}
	return string;
}

function base64_decode(text) {
	if(window.atob) { return window.atob(text); }
	else            { return base64.decode(text); }
}


function base64_encode(text) {
	if(window.btoa) { return window.btoa(text); }
	else            { return base64.encode(text); }
}


DataStream = function(size) {
	size = size || 80;

	this.buffer = new Uint8Array(size);
	this.read_pos = 0;
	this.write_pos = 0;
};

DataStream.prototype.get_size = function() {
	return this.write_pos;
}

DataStream.prototype.hex_dump = function() {
	var buffer = "";

	for (var i = 0; i < this.write_pos ; i++) {
		var p = this.buffer[i].toString(16);
		p      = (p.length == 1) ? "0"+p : p;
		buffer = ((i+1)%8 == 0)  ? buffer+p+"\n" : buffer+p+" ";
	}

	console.log(buffer);
}

DataStream.prototype.read_Byte = function() {
	if (this.read_pos == this.write_pos) {
		throw "End of stream";
	}

	return this.buffer[this.read_pos++];
}


DataStream.prototype.read_UInt32LE = function() {
	try {
		var v1 = this.read_Byte();
		var v2 = this.read_Byte();
		var v3 = this.read_Byte();
		var v4 = this.read_Byte();

		return v1 + (v2 << 8) + (v3 << 16) + (v4 << 24);
	} catch(e) {
		throw "End of stream";
	}
};


DataStream.prototype.write_Byte = function(byte_) {
	if(this.write_pos == this.buffer.length) {
		/* Double Array length */
		var new_buffer = new Uint8Array(this.buffer.length*2);
		new_buffer.set(this.buffer);
		this.buffer = new_buffer;
	}

	this.buffer[this.write_pos++] = byte_;
}

DataStream.prototype.write_UInt32LE = function(value) {
	this.write_Byte(value);
	this.write_Byte(value >>> 8);
	this.write_Byte(value >>> 16);
	this.write_Byte(value >>> 24);
};


DataStream.prototype.write_UTF16LE = function(str) {
	/* Limited UTF-16LE implementation */
	/* Do not handle 4bytes notation since Javascript don't handle it */

	for (var i=0; i<str.length; i++) {
		var v = str[i].charCodeAt();
		var w1 = v & 0xFF;
		var w2 = (v & 0xFF00) >>> 8;

		this.write_Byte(w1);
		this.write_Byte(w2);
	}
};

/*\
|*|
|*|  Base64 / binary data / UTF-8 strings utilities
|*|
|*|  https://developer.mozilla.org/en-US/docs/Web/JavaScript/Base64_encoding_and_decoding
|*|
\*/

/* Array of bytes to base64 string decoding */

/* Base64 string to array encoding */

DataStream.prototype.uint6ToB64 = function(nUint6) {
	return nUint6 < 26 ?  nUint6 + 65 : nUint6 < 52 ?  nUint6 + 71 : nUint6 < 62 ?  nUint6 - 4 : nUint6 === 62 ?  43 : nUint6 === 63 ?  47 : 65;
}

DataStream.b64ToUint6 = function(nChr) {
	return nChr > 64 && nChr < 91 ?  nChr - 65 : nChr > 96 && nChr < 123 ?  nChr - 71 : nChr > 47 && nChr < 58 ?  nChr + 4 : nChr === 43 ?  62 : nChr === 47 ?  63 : 0;
}

DataStream.prototype.toBase64 = function() {
	aBytes = this.buffer.subarray(0, this.write_pos);

	var nMod3 = 2, sB64Enc = "";

	for (var nLen = aBytes.length, nUint24 = 0, nIdx = 0; nIdx < nLen; nIdx++) {
		nMod3 = nIdx % 3;
		if (nIdx > 0 && (nIdx * 4 / 3) % 76 === 0) { sB64Enc += "\r\n"; }
		nUint24 |= aBytes[nIdx] << (16 >>> nMod3 & 24);
		if (nMod3 === 2 || aBytes.length - nIdx === 1) {
			sB64Enc += String.fromCharCode(this.uint6ToB64(nUint24 >>> 18 & 63), this.uint6ToB64(nUint24 >>> 12 & 63), this.uint6ToB64(nUint24 >>> 6 & 63), this.uint6ToB64(nUint24 & 63));
			nUint24 = 0;
		}
	}

	return sB64Enc.substr(0, sB64Enc.length - 2 + nMod3) + (nMod3 === 2 ? '' : nMod3 === 1 ? '=' : '==');
};

DataStream.fromBase64 = function(sBase64, nBlocksSize) {
	var sB64Enc = sBase64.replace(/[^A-Za-z0-9\+\/]/g, "");
	var nInLen = sB64Enc.length;
	var nOutLen = nBlocksSize ? Math.ceil((nInLen * 3 + 1 >> 2) / nBlocksSize) * nBlocksSize : nInLen * 3 + 1 >> 2;
	var taBytes = new DataStream(nOutLen);

	for (var nMod3, nMod4, nUint24 = 0, nOutIdx = 0, nInIdx = 0; nInIdx < nInLen; nInIdx++) {
		nMod4 = nInIdx & 3;
		nUint24 |= DataStream.b64ToUint6(sB64Enc.charCodeAt(nInIdx)) << 18 - 6 * nMod4;
		if (nMod4 === 3 || nInLen - nInIdx === 1) {
			for (nMod3 = 0; nMod3 < 3 && nOutIdx < nOutLen; nMod3++, nOutIdx++) {
				taBytes.write_Byte(nUint24 >>> (16 >>> nMod3 & 24) & 255);
			}

			nUint24 = 0;
		}
	}

	return taBytes;
}
