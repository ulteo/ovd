
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is guacamole-common-js.
 *
 * The Initial Developer of the Original Code is
 * Michael Jumper.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *  David PHAM-VAN <d.pham-van@ulteo.com> Ulteo SAS - http://www.ulteo.com
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

// Guacamole namespace
var Guacamole = Guacamole || {};

/**
 * Provides cross-browser mouse events for a given element. The events of
 * the given element are automatically populated with handlers that translate
 * mouse events into a non-browser-specific event provided by the
 * Guacamole.Mouse instance.
 * 
 * Touch events are translated into mouse events as if the touches occurred
 * on a touchpad (drag to push the mouse pointer, tap to click).
 * 
 * @constructor
 * @param {Element} element The Element to use to provide mouse events.
 */
Guacamole.Mouse = function(element) {

    /**
     * Reference to this Guacamole.Mouse.
     * @private
     */
    var guac_mouse = this;

    /**
     * The distance a two-finger touch must move per scrollwheel event, in
     * pixels.
     */
    this.scrollThreshold = 20 * (window.devicePixelRatio || 1);

    /**
     * The maximum number of milliseconds to wait for a touch to end for the
     * gesture to be considered a click.
     */
    this.clickTimingThreshold = 250;

    /**
     * The time a touch event is transformed to a right click
     */
    this.rightClickTime = 500;

    /**
     * The time between two clicks to generate a double click
     */
    this.doubleClickTime = 300;

    /**
     * The maximum number of pixels to allow a touch to move for the gesture to
     * be considered a click.
     */
    this.clickMoveThreshold = 10 * (window.devicePixelRatio || 1);

    /**
     * The current mouse state. The properties of this state are updated when
     * mouse events fire. This state object is also passed in as a parameter to
     * the handler of any mouse events.
     * 
     * @type Guacamole.Mouse.State
     */
    this.currentState = new Guacamole.Mouse.State(
        0, 0, 
        false, false, false, false, false
    );

    /**
     * Fired whenever the user presses a mouse button down over the element
     * associated with this Guacamole.Mouse.
     * 
     * @event
     * @param {Guacamole.Mouse.State} state The current mouse state.
     */
	this.onmousedown = null;

    /**
     * Fired whenever the user releases a mouse button down over the element
     * associated with this Guacamole.Mouse.
     * 
     * @event
     * @param {Guacamole.Mouse.State} state The current mouse state.
     */
	this.onmouseup = null;

    /**
     * Fired whenever the user moves the mouse over the element associated with
     * this Guacamole.Mouse.
     * 
     * @event
     * @param {Guacamole.Mouse.State} state The current mouse state.
     */
	this.onmousemove = null;

    /**
     * Fired whenever the user touches the element with two fingers
     * associated with this Guacamole.Mouse.
     * 
     * @event
     */
	this.ontwofingers = null;

    /**
     * Fired whenever the user touches the element with three fingers
     * associated with this Guacamole.Mouse.
     * 
     * @event
     */
	this.onthreefingers = null;

    function cancelEvent(e) {
        e.stopPropagation();
        if (e.preventDefault) e.preventDefault();
        e.returnValue = false;
    }

    function moveMouse(clientX, clientY) {

        guac_mouse.currentState.x = clientX - element.offsetLeft;
        guac_mouse.currentState.y = clientY - element.offsetTop;

        // This is all JUST so we can get the mouse position within the element
        var parent = element.offsetParent;
        while (parent && !(parent === document.body)) {
            guac_mouse.currentState.x -= parent.offsetLeft - parent.scrollLeft;
            guac_mouse.currentState.y -= parent.offsetTop  - parent.scrollTop;

            parent = parent.offsetParent;
        }

        // Offset by document scroll amount
        var documentScrollLeft = document.body.scrollLeft || document.documentElement.scrollLeft;
        var documentScrollTop = document.body.scrollTop || document.documentElement.scrollTop;

        guac_mouse.currentState.x -= parent.offsetLeft - documentScrollLeft;
        guac_mouse.currentState.y -= parent.offsetTop  - documentScrollTop;

        if (guac_mouse.onmousemove)
            guac_mouse.onmousemove(guac_mouse.currentState);

    }


    // Block context menu so right-click gets sent properly
    element.addEventListener("contextmenu", function(e) {
        cancelEvent(e);
    }, false);

    element.addEventListener("mousemove", function(e) {

        // Don't handle if we aren't supposed to
        if (gesture_in_progress) return;

        cancelEvent(e);

        moveMouse(e.clientX, e.clientY);

    }, false);

    var touch_count = 0;
    var last_touch_x = 0;
    var last_touch_y = 0;
    var first_touch_x = 0;
    var first_touch_y = 0;
    var last_touch_time = 0;
    var pixels_moved = 0;
    var doubleclick = false;

    var touch_move = false;
    var firstTapTime = 0;
    var touchInitialTime = 0;

    var gesture_in_progress = false;
    var gesture_release_timeout = null;

    var rightclick_timeout = null;
    
    simulate_click = function() {
      guac_mouse.currentState.left = true;
      if (guac_mouse.onmousedown)
        guac_mouse.onmousedown(guac_mouse.currentState);
      window.setTimeout(function() {
        guac_mouse.currentState.left = false;
        if (guac_mouse.onmouseup)
          guac_mouse.onmouseup(guac_mouse.currentState);
      }, 100);
    }
    
    simulate_click_right = function() {
      guac_mouse.currentState.right = true;
      if (guac_mouse.onmousedown)
        guac_mouse.onmousedown(guac_mouse.currentState);
      window.setTimeout(function() {
        guac_mouse.currentState.right = false;
        if (guac_mouse.onmouseup)
          guac_mouse.onmouseup(guac_mouse.currentState);
      }, 100);
    }

    element.addEventListener("touchend", function(e) {
        
        cancelEvent(e);
            
        if (rightclick_timeout) {
            window.clearTimeout(rightclick_timeout);
            rightclick_timeout = null;
        }

        var touchTimer = (new Date().getTime()) - touchInitialTime;

        touchInitialTime = 0;

        if (touch_move) {
            guac_mouse.currentState.left = false;
            if (guac_mouse.onmouseup)
                guac_mouse.onmouseup(guac_mouse.currentState);
        } else if (touch_count == 1) {
            guac_mouse.currentState.x = last_touch_x;
            guac_mouse.currentState.y = last_touch_y;
            if ((touchTimer < guac_mouse.rightClickTime || pixels_moved > guac_mouse.clickMoveThreshold) && !touch_move) {
                if (doubleclick) {
                    guac_mouse.currentState.x = first_touch_x;
                    guac_mouse.currentState.y = first_touch_y;
                }
                simulate_click();
            }
        } else if (touchTimer < guac_mouse.clickTimingThreshold && touch_count == 3 && pixels_moved < guac_mouse.clickMoveThreshold && !touch_move) {
            if (guac_mouse.onthreefingers)
                guac_mouse.onthreefingers();
        } else if (touchTimer < guac_mouse.clickTimingThreshold && touch_count == 2 && pixels_moved < guac_mouse.clickMoveThreshold && !touch_move) {
            if (guac_mouse.ontwofingers)
                guac_mouse.ontwofingers();
        }
        touch_move = false;
        touch_count = 0;
        if (gesture_release_timeout)
            window.clearTimeout(gesture_release_timeout);
        gesture_release_timeout = window.setTimeout(function () {
            gesture_in_progress = false;
        }, guac_mouse.rightClickTime*2);
    }, false);

    element.addEventListener("touchstart", function(e) {

        cancelEvent(e);

        // Track number of touches
        touch_count = e.touches.length;

        var touch = e.touches[0];
        last_touch_x = touch.clientX;
        last_touch_y = touch.clientY;
        touchInitialTime = new Date().getTime();
        if (touchInitialTime - last_touch_time > guac_mouse.doubleClickTime || pixels_moved > guac_mouse.clickMoveThreshold) {
            first_touch_x = touch.clientX;
            first_touch_y = touch.clientY;
            doubleclick = false;
        } else {
          doubleclick = true;
        }
        last_touch_time = touchInitialTime;
        pixels_moved = 0;
        gesture_in_progress = true;
        if (gesture_release_timeout) {
            window.clearTimeout(gesture_release_timeout);
            gesture_release_timeout = null;
        }
        if (rightclick_timeout) {
            window.clearTimeout(rightclick_timeout);
        }
        rightclick_timeout = setTimeout(function () {
            if (pixels_moved < guac_mouse.clickMoveThreshold) {
                simulate_click_right();
            }
            rightclick_timeout = null;
        }, guac_mouse.rightClickTime);
        
    }, false);

    element.addEventListener("touchmove", function(e) {

        cancelEvent(e);

        // Get change in touch location
        var touch = e.touches[0];
        var delta_x = touch.clientX - last_touch_x;
        var delta_y = touch.clientY - last_touch_y;

        // Track pixels moved
        pixels_moved += Math.abs(delta_x) + Math.abs(delta_y);

        if ((touch_count == 1 && pixels_moved > guac_mouse.clickMoveThreshold) || touch_move) {
            guac_mouse.currentState.x = touch.clientX;
            guac_mouse.currentState.y = touch.clientY;
            
            last_touch_x = touch.clientX;
            last_touch_y = touch.clientY;
            if (!touch_move) {
                guac_mouse.currentState.left = true;
                if (guac_mouse.onmousedown)
                    guac_mouse.onmousedown(guac_mouse.currentState);
                touch_move = true;
            }
            if (guac_mouse.onmousemove)
                guac_mouse.onmousemove(guac_mouse.currentState);
        }

        // Interpret two-finger swipe as scrollwheel
        else if (touch_count == 2) {

            // If change in location passes threshold for scroll
            if (Math.abs(delta_y) >= guac_mouse.scrollThreshold) {

                // Decide button based on Y movement direction
                var button;
                if (delta_y > 0) button = "up";
                else             button = "down";

                // Fire button down event
                guac_mouse.currentState[button] = true;
                if (guac_mouse.onmousedown)
                    guac_mouse.onmousedown(guac_mouse.currentState);

                // Fire button up event
                guac_mouse.currentState[button] = false;
                if (guac_mouse.onmouseup)
                    guac_mouse.onmouseup(guac_mouse.currentState);

                // Only update touch location after a scroll has been
                // detected
                last_touch_x = touch.clientX;
                last_touch_y = touch.clientY;

            }

        }

    }, false);


    element.addEventListener("mousedown", function(e) {

        // Don't handle if we aren't supposed to
        if (gesture_in_progress) return;

        cancelEvent(e);

        switch (e.button) {
            case 0:
                guac_mouse.currentState.left = true;
                break;
            case 1:
                guac_mouse.currentState.middle = true;
                break;
            case 2:
                guac_mouse.currentState.right = true;
                break;
        }

        if (guac_mouse.onmousedown)
            guac_mouse.onmousedown(guac_mouse.currentState);

    }, false);


    element.addEventListener("mouseup", function(e) {

        // Don't handle if we aren't supposed to
        if (gesture_in_progress) return;

        cancelEvent(e);

        switch (e.button) {
            case 0:
                guac_mouse.currentState.left = false;
                break;
            case 1:
                guac_mouse.currentState.middle = false;
                break;
            case 2:
                guac_mouse.currentState.right = false;
                break;
        }

        if (guac_mouse.onmouseup)
            guac_mouse.onmouseup(guac_mouse.currentState);

    }, false);

    element.addEventListener("mouseout", function(e) {

        // Don't handle if we aren't supposed to
        if (gesture_in_progress) return;

        // Get parent of the element the mouse pointer is leaving
       	if (!e) e = window.event;

        // Check that mouseout is due to actually LEAVING the element
        var target = e.relatedTarget || e.toElement;
        while (target != null) {
            if (target === element)
                return;
            target = target.parentNode;
        }

        cancelEvent(e);

        // Release all buttons
        if (guac_mouse.currentState.left
            || guac_mouse.currentState.middle
            || guac_mouse.currentState.right) {

            guac_mouse.currentState.left = false;
            guac_mouse.currentState.middle = false;
            guac_mouse.currentState.right = false;

            if (guac_mouse.onmouseup)
                guac_mouse.onmouseup(guac_mouse.currentState);
        }

    }, false);

    // Override selection on mouse event element.
    element.addEventListener("selectstart", function(e) {
        cancelEvent(e);
    }, false);

    // Scroll wheel support
    function mousewheel_handler(e) {

        // Don't handle if we aren't supposed to
        if (gesture_in_progress) return;

        var delta = 0;
        if (e.detail)
            delta = e.detail;
        else if (e.wheelDelta)
            delta = -event.wheelDelta;

        // Up
        if (delta < 0) {
            if (guac_mouse.onmousedown) {
                guac_mouse.currentState.up = true;
                guac_mouse.onmousedown(guac_mouse.currentState);
            }

            if (guac_mouse.onmouseup) {
                guac_mouse.currentState.up = false;
                guac_mouse.onmouseup(guac_mouse.currentState);
            }
        }

        // Down
        if (delta > 0) {
            if (guac_mouse.onmousedown) {
                guac_mouse.currentState.down = true;
                guac_mouse.onmousedown(guac_mouse.currentState);
            }

            if (guac_mouse.onmouseup) {
                guac_mouse.currentState.down = false;
                guac_mouse.onmouseup(guac_mouse.currentState);
            }
        }

        cancelEvent(e);

    }
    element.addEventListener('DOMMouseScroll', mousewheel_handler, false);
    element.addEventListener('mousewheel',     mousewheel_handler, false);

};

/**
 * Simple container for properties describing the state of a mouse.
 * 
 * @constructor
 * @param {Number} x The X position of the mouse pointer in pixels.
 * @param {Number} y The Y position of the mouse pointer in pixels.
 * @param {Boolean} left Whether the left mouse button is pressed. 
 * @param {Boolean} middle Whether the middle mouse button is pressed. 
 * @param {Boolean} right Whether the right mouse button is pressed. 
 * @param {Boolean} up Whether the up mouse button is pressed (the fourth
 *                     button, usually part of a scroll wheel). 
 * @param {Boolean} down Whether the down mouse button is pressed (the fifth
 *                       button, usually part of a scroll wheel). 
 */
Guacamole.Mouse.State = function(x, y, left, middle, right, up, down) {

    /**
     * The current X position of the mouse pointer.
     * @type Number
     */
    this.x = x;

    /**
     * The current Y position of the mouse pointer.
     * @type Number
     */
    this.y = y;

    /**
     * Whether the left mouse button is currently pressed.
     * @type Boolean
     */
    this.left = left;

    /**
     * Whether the middle mouse button is currently pressed.
     * @type Boolean
     */
    this.middle = middle

    /**
     * Whether the right mouse button is currently pressed.
     * @type Boolean
     */
    this.right = right;

    /**
     * Whether the up mouse button is currently pressed. This is the fourth
     * mouse button, associated with upward scrolling of the mouse scroll
     * wheel.
     * @type Boolean
     */
    this.up = up;

    /**
     * Whether the down mouse button is currently pressed. This is the fifth 
     * mouse button, associated with downward scrolling of the mouse scroll
     * wheel.
     * @type Boolean
     */
    this.down = down;
    
};

