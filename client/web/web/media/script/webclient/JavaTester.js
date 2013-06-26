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
		this.insertSecondApplet();
		this.do_second_test();
	},
	
	insertSecondApplet: function() {
		var applet_params = new Hash();
		applet_params.set('onSuccess', 'JavaTester_appletSuccess');
		applet_params.set('onFailure', 'JavaTester_appletFailure');
		
		var applet = buildAppletNode('CheckSignedJava', 'org.ulteo.ovd.applet.CheckJava', 'ulteo-applet.jar', applet_params);
		jQuery('#testJava').append(applet);
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
		
		new Effect.Center(jQuery('#systemTestWrap')[0]);
		var elementDimensions = Element.getDimensions(jQuery('#systemTestWrap')[0]);
		jQuery('#systemTestWrap').width(elementDimensions.width);
		
		Event.observe(window, 'resize', function() {
			if (jQuery('#systemTestWrap')[0].visible())
				new Effect.Center(jQuery('#systemTestWrap')[0]);
		});
		
		new Effect.Appear(jQuery('#systemTestWrap')[0]);
	},
	
	hideSystemTest: function () {
		jQuery('#systemTestWrap').hide();
		
		hideLock();
	},
	
	showSystemTestError: function(error_id_) {
		hideError();
		
		hideOk();
		hideInfo();
		
		this.hideSystemTest();
		
		showLock();
		
		jQuery("#"+error_id_).show();
		
		new Effect.Center(jQuery('#systemTestErrorWrap')[0]);
		var elementDimensions = Element.getDimensions(jQuery('#systemTestErrorWrap')[0]);
		jQuery('#systemTestErrorWrap').width(elementDimensions.width);
		
		Event.observe(window, 'resize', function() {
			if (jQuery('#systemTestErrorWrap')[0].visible())
				new Effect.Center(jQuery('#systemTestErrorWrap')[0]);
		});
		
		new Effect.Appear(jQuery('#systemTestErrorWrap')[0]);
	},
	
	insertApplet: function() {
		var applet = buildAppletNode('CheckJava', 'org.ulteo.ovd.applet.CheckJava', 'CheckJava.jar', new Hash());
		jQuery('#testJava').append(applet);
	},
	
	lookupNavigatorPlugins: function(search) {
		for (i = 0; i < navigator.plugins.length; i++) {
			var plugin = navigator.plugins[i];
			for (j = 0; j < plugin.length; j++) {
				var mimetype = plugin[j];
				if (mimetype && mimetype.enabledPlugin && (mimetype.enabledPlugin.name == plugin.name) && mimetype.type == search)
					return true
			}
		}
		return false;
	}
});

JavaTester_appletSuccess = function() {
	JavaTester.prototype.applet_inited = true;
};

JavaTester_appletFailure = function() {
	JavaTester.prototype.applet_inited = false;
};
