/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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


var JavaTester = Class.create({
	applet_inited: null,
	finished_callback: null,
	
	initialize: function() {
		this.t0 = 0;
		this.ti = 0;
		
		this.finished_callback = new Array();
	},
	
	add_finished_callback: function(callback_) {
		this.finished_callback.push(callback_);
	},
	      
	perform: function() {
		this.showSystemTest();
		setTimeout(this.perform_.bind(this), 2000);
	},
	
	perform_: function() {
		this.t0 = (new Date()).getTime();
		this.do_first_test();
	},
	
	do_first_test: function() {
		try {
			var checkjava_isactive = $('CheckJava').isActive();
			if (! checkjava_isactive)
				throw "applet is not ready";
		}
		catch(e) {
			var t1 = (new Date()).getTime();
			if (t1 - this.t0 > 10000)
				this.showSystemTestError('systemTestError1');
			else
				setTimeout(this.do_first_test.bind(this), 500);
			return;
		}
		
		var applet_params = new Hash();
		applet_params.set('onSuccess', 'JavaTester_appletSuccess');
		applet_params.set('onFailure', 'JavaTester_appletFailure');
		
		var applet = buildAppletNode('CheckSignedJava', 'org.ulteo.ovd.applet.CheckJava', 'ulteo-applet.jar', applet_params);
		$('testJava').appendChild(applet);
		this.do_second_test();
	},
	
	do_second_test: function () {
		if (this.applet_inited != true) {
			if (this.applet_inited == false) {
				this.showSystemTestError('systemTestError2');
				return;
			}
			
			this.ti+= 1;
			if (this.ti > 60)
				this.showSystemTestError('systemTestError2');
			else
				setTimeout(this.do_second_test.bind(this), 1000);
			return;
		}
		
		this.finish();
	},
	
	finish: function() {
		for (var i=0; i<this.finished_callback.length; i++)
			this.finished_callback[i](this);
		
		this.hideSystemTest();
	},
	
	showSystemTest: function() {
		showLock();
		
		new Effect.Center($('systemTestWrap'));
		var elementDimensions = Element.getDimensions($('systemTestWrap'));
		$('systemTestWrap').style.width = elementDimensions.width+'px';
		
		Event.observe(window, 'resize', function() {
			if ($('systemTestWrap').visible())
				new Effect.Center($('systemTestWrap'));
		});
		
		new Effect.Appear($('systemTestWrap'));
	},
	
	hideSystemTest: function () {
		$('systemTestWrap').hide();
		
		hideLock();
	},
	
	showSystemTestError: function(error_id_) {
		hideError();
		
		hideOk();
		hideInfo();
		
		this.hideSystemTest();
		
		showLock();
		
		$(error_id_).show();
		
		new Effect.Center($('systemTestErrorWrap'));
		var elementDimensions = Element.getDimensions($('systemTestErrorWrap'));
		$('systemTestErrorWrap').style.width = elementDimensions.width+'px';
		
		Event.observe(window, 'resize', function() {
			if ($('systemTestErrorWrap').visible())
				new Effect.Center($('systemTestErrorWrap'));
		});
		
		new Effect.Appear($('systemTestErrorWrap'));
	}
});

JavaTester_appletSuccess = function() {
	JavaTester.prototype.applet_inited = true;
};

JavaTester_appletFailure = function() {
	JavaTester.prototype.applet_inited = false;
};
