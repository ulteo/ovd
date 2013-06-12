
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

uovd.provider.rdp.html5.HTTPTunnel = function(tunnelURL, index) {

    var self = this; /* closure */

    var tunnel_uuid;

    var TUNNEL_CONNECT = tunnelURL + "?connect";
    var TUNNEL_READ    = tunnelURL + "?read:";
    var TUNNEL_WRITE   = tunnelURL + "?write:";

    var STATE_IDLE          = 0;
    var STATE_CONNECTED     = 1;
    var STATE_DISCONNECTED  = 2;

    var currentState = STATE_IDLE;

    var POLLING_ENABLED     = 1;
    var POLLING_DISABLED    = 0;

    // Default to polling - will be turned off automatically if not needed
    var pollingMode = POLLING_ENABLED;

    var sendingMessages = false;
    var outputMessageBuffer = "";

    var instructionHandlers = {};

		this.setStatus = function(state) {
			var status = ""
			currentState = state;

			if(state == STATE_IDLE) {status = "unknown";}
			else if(state == STATE_CONNECTED) {status = "connected";}
			else if(state == STATE_DISCONNECTED) {status = "disconnected";}
			else {return;}
			
			window.serverStatus(index, status);
		}

    this.sendMessage = function() {

        // Do not attempt to send messages if not connected
        if (currentState != STATE_CONNECTED)
            return;

        // Do not attempt to send empty messages
        if (arguments.length == 0)
            return;

        /**
         * Converts the given value to a length/string pair for use as an
         * element in a Guacamole instruction.
         * 
         * @param value The value to convert.
         * @return {String} The converted value. 
         */
        function getElement(value) {
            var string = new String(value);
            return string.length + "." + string; 
        }

        // Initialized message with first element
        var message = getElement(arguments[0]);

        // Append remaining elements
        for (var i=1; i<arguments.length; i++)
            message += "," + getElement(arguments[i]);

        // Final terminator
        message += ";";

        // Add message to buffer
        outputMessageBuffer += message;

        // Send if not currently sending
        if (!sendingMessages)
            sendPendingMessages();

    };

    function sendPendingMessages() {

        if (outputMessageBuffer.length > 0) {

            sendingMessages = true;

            var message_xmlhttprequest = new XMLHttpRequest();
            message_xmlhttprequest.open("POST", TUNNEL_WRITE + tunnel_uuid);
            message_xmlhttprequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

            // Once response received, send next queued event.
            message_xmlhttprequest.onreadystatechange = function() {
                if (message_xmlhttprequest.readyState == 4) {

                    // If an error occurs during send, handle it
                    if (message_xmlhttprequest.status != 200)
                        handleHTTPTunnelError(message_xmlhttprequest);

                    // Otherwise, continue the send loop
                    else
                        sendPendingMessages();

                }
            }

            message_xmlhttprequest.send(outputMessageBuffer);
            outputMessageBuffer = ""; // Clear buffer

        }
        else
            sendingMessages = false;

    }

    function getHTTPTunnelErrorMessage(xmlhttprequest) {

        var status = xmlhttprequest.status;

        // Special cases
        if (status == 0)   return "Disconnected";
        if (status == 200) return "Success";
        if (status == 403) return "Unauthorized";
        if (status == 404) return "Connection closed"; /* While it may be more
                                                        * accurate to say the
                                                        * connection does not
                                                        * exist, it is confusing
                                                        * to the user.
                                                        * 
                                                        * In general, this error
                                                        * will only happen when
                                                        * the tunnel does not
                                                        * exist, which happens
                                                        * after the connection
                                                        * is closed and the
                                                        * tunnel is detached.
                                                        */
        // Internal server errors
        if (status >= 500 && status <= 599) return "Server error";

        // Otherwise, unknown
        return "Unknown error";

    }

    function handleHTTPTunnelError(xmlhttprequest) {

        // Get error message
        var message = getHTTPTunnelErrorMessage(xmlhttprequest);

        // Call error handler
        if (self.onerror) self.onerror(message);

        // Finish
        self.disconnect();

    }


    function handleResponse(xmlhttprequest) {

        var interval = null;
        var nextRequest = null;

        var dataUpdateEvents = 0;

        // The location of the last element's terminator
        var elementEnd = -1;

        // Where to start the next length search or the next element
        var startIndex = 0;

        // Parsed elements
        var elements = new Array();

        function parseResponse() {

            // Do not handle responses if not connected
            if (currentState != STATE_CONNECTED) {
                
                // Clean up interval if polling
                if (interval != null)
                    clearInterval(interval);
                
                return;
            }

            // Do not parse response yet if not ready
            if (xmlhttprequest.readyState < 2) return;

            // Attempt to read status
            var status;
            try { status = xmlhttprequest.status; }

            // If status could not be read, assume successful.
            catch (e) { status = 200; }

            // Start next request as soon as possible IF request was successful
            if (nextRequest == null && status == 200)
                nextRequest = makeRequest();

            // Parse stream when data is received and when complete.
            if (xmlhttprequest.readyState == 3 ||
                xmlhttprequest.readyState == 4) {

                // Also poll every 30ms (some browsers don't repeatedly call onreadystatechange for new data)
                if (pollingMode == POLLING_ENABLED) {
                    if (xmlhttprequest.readyState == 3 && interval == null)
                        interval = setInterval(parseResponse, 30);
                    else if (xmlhttprequest.readyState == 4 && interval != null)
                        clearInterval(interval);
                }

                // If canceled, stop transfer
                if (xmlhttprequest.status == 0) {
                    self.disconnect();
                    return;
                }

                // Halt on error during request
                else if (xmlhttprequest.status != 200) {
                    handleHTTPTunnelError(xmlhttprequest);
                    return;
                }

                // Attempt to read in-progress data
                var current;
                try { current = xmlhttprequest.responseText; }

                // Do not attempt to parse if data could not be read
                catch (e) { return; }

                // While search is within currently received data
                while (elementEnd < current.length) {

                    // If we are waiting for element data
                    if (elementEnd >= startIndex) {

                        // We now have enough data for the element. Parse.
                        var element = current.substring(startIndex, elementEnd);
                        var terminator = current.substring(elementEnd, elementEnd+1);

                        // Add element to array
                        elements.push(element);

                        // If last element, handle instruction
                        if (terminator == ";") {

                            // Get opcode
                            var opcode = elements.shift();

                            // Call custom instruction handlers.
                            if (instructionHandlers[opcode])
                                instructionHandlers[opcode](opcode, elements);

                            // Call default instruction handler.
                            if (self.oninstruction != null)
                                self.oninstruction(opcode, elements);

                            // Clear elements
                            elements.length = 0;

                        }

                        // Start searching for length at character after
                        // element terminator
                        startIndex = elementEnd + 1;

                    }

                    // Search for end of length
                    var lengthEnd = current.indexOf(".", startIndex);
                    if (lengthEnd != -1) {

                        // Parse length
                        var length = parseInt(current.substring(elementEnd+1, lengthEnd));

                        // If we're done parsing, handle the next response.
                        if (length == 0) {

                            // Clean up interval if polling
                            if (interval != null)
                                clearInterval(interval);
                           
                            // Clean up object
                            xmlhttprequest.onreadystatechange = null;
                            xmlhttprequest.abort();

                            // Start handling next request
                            if (nextRequest)
                                handleResponse(nextRequest);

                            // Done parsing
                            break;

                        }

                        // Calculate start of element
                        startIndex = lengthEnd + 1;

                        // Calculate location of element terminator
                        elementEnd = startIndex + length;

                    }
                    
                    // If no period yet, continue search when more data
                    // is received
                    else {
                        startIndex = current.length;
                        break;
                    }

                } // end parse loop

            }

        }

        // If response polling enabled, attempt to detect if still
        // necessary (via wrapping parseResponse())
        if (pollingMode == POLLING_ENABLED) {
            xmlhttprequest.onreadystatechange = function() {

                // If we receive two or more readyState==3 events,
                // there is no need to poll.
                if (xmlhttprequest.readyState == 3) {
                    dataUpdateEvents++;
                    if (dataUpdateEvents >= 2) {
                        pollingMode = POLLING_DISABLED;
                        xmlhttprequest.onreadystatechange = parseResponse;
                    }
                }

                parseResponse();
            }
        }

        // Otherwise, just parse
        else
            xmlhttprequest.onreadystatechange = parseResponse;

        parseResponse();

    }


    function makeRequest() {

        // Download self
        var xmlhttprequest = new XMLHttpRequest();
        xmlhttprequest.open("POST", TUNNEL_READ + tunnel_uuid);
        xmlhttprequest.send(null);

        return xmlhttprequest;

    }

    this.connect = function(data) {

        // Start tunnel and connect synchronously
        var connect_xmlhttprequest = new XMLHttpRequest();
        connect_xmlhttprequest.open("POST", TUNNEL_CONNECT, false);
        connect_xmlhttprequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        connect_xmlhttprequest.send(data);

        // If failure, throw error
        if (connect_xmlhttprequest.status != 200) {
            var message = getHTTPTunnelErrorMessage(connect_xmlhttprequest);
            throw new Error(message);
        }

        // Get UUID from response
        tunnel_uuid = connect_xmlhttprequest.responseText;

        // Start reading data
        this.setStatus(STATE_CONNECTED);
        handleResponse(makeRequest());

    };

    this.disconnect = function() {
        this.setStatus(STATE_DISCONNECTED);
    };

    this.addInstructionHandler = function(opcode, callback) {
        instructionHandlers[opcode] = callback;
    };

    this.removeInstructionHandler = function(opcode) {
        if(instructionHandlers[opcode]) {
            delete instructionHandlers[opcode];
        }
    };

};

try {
	uovd.provider.rdp.html5.HTTPTunnel.prototype = new Guacamole.Tunnel();
} catch(e) {}
