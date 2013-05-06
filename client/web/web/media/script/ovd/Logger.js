/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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
 **/


var Logger = Class.create({
	instance: null,
	
	push: function(level_, data_) {
		var flag = (($('debugContainer').scrollTop+$('debugContainer').offsetHeight) == $('debugContainer').scrollHeight);
		
		$('debugContainer').innerHTML += '<div class="'+level_+'">['+this._get_formated_date()+'] - '+data_+'</div>'+"\n";
		
		if (flag)
			$('debugContainer').scrollTop = $('debugContainer').scrollHeight;
	},
	
	_get_formated_date: function() {
		buf = new Date();
		
		hour = buf.getHours();
		if (hour < 10)
			hour = '0'+hour;
		
		minutes = buf.getMinutes();
		if (minutes < 10)
			minutes = '0'+minutes;
		
		seconds = buf.getSeconds();
		if (seconds < 10)
			seconds = '0'+seconds;
		
		return hour+':'+minutes+':'+seconds;
	},
	
	_toggle_level: function(level_) {
		var flag = (($('debugContainer').scrollTop+$('debugContainer').offsetHeight) == $('debugContainer').scrollHeight);
		
		var buf = $('debugContainer').className;
		
		if (buf.match('no_'+level_))
			buf = buf.replace('no_'+level_, level_);
		else
			buf = buf.replace(level_, 'no_'+level_);
		
		$('debugContainer').className = buf;
	
		if (flag)
			$('debugContainer').scrollTop = $('debugContainer').scrollHeight;
	},
	
	show: function() {
		$('debugContainer').innerHTML = '';
		$('debugContainer').show();
		$('debugContainer').style.display = 'inline';
		$('debugLevels').show();
		$('debugLevels').style.display = 'inline';
	},
	
	hide: function() {
		$('debugContainer').hide();
		$('debugLevels').hide();
	},
	
	_clear: function() {
		$('debugContainer').innerHTML = ''; 
	}
});

Logger.has_instance = function() {
	return (Logger.instance != null);
};

Logger.init_instance = function() {
	Logger.instance = new Logger();
	Logger.instance.show();
};

Logger.del_instance = function() {
	if (! Logger.has_instance())
		return;
	
	Logger.instance.hide();
	Logger.instance = null;
};

Logger.clear = function() {
	if (! Logger.has_instance())
		return;
	
	Logger.instance._clear();
};

Logger.toggle_level = function(level_) {
	if (! Logger.has_instance())
		return;
	
	Logger.instance._toggle_level(level_);
};

Logger.info = function(data_) {
	if (typeof console != "undefined" && console.info)
		console.info(data_);
	
	if (! Logger.has_instance())
		return;
	
	Logger.instance.push('info', data_);
};

Logger.warn = function(data_) {
	if (typeof console != "undefined"  && console.warn)
		console.warn(data_);
	
	if (! Logger.has_instance())
		return;
	
	Logger.instance.push('warn', data_);
};

Logger.error = function(data_) {
	if (typeof console != "undefined"  && console.error)
		console.error(data_);
	
	if (! Logger.has_instance())
		return;
	
	Logger.instance.push('error', data_);
};

Logger.debug = function(data_) {
	if (typeof console != "undefined"  && console.debug)
		console.debug(data_);
	
	if (! Logger.has_instance())
		return;
	
	Logger.instance.push('debug', data_);
};
