<?php
// IP/Host of the SessionManager to link the Web Client to
// define('SESSIONMANAGER_HOST', '127.0.0.1');

// Option session: force mode
// define('OPTION_FORCE_SESSION_MODE', 'desktop');
// define('OPTION_FORCE_SESSION_MODE', 'applications');
// default: do not force any behavior

// Option desktop session: force fullscreen mode
// define('OPTION_FORCE_FULLSCREEN', true);
// define('OPTION_FORCE_FULLSCREEN', false);
// default: do not force any behavior


// Option session language: default value
// define('OPTION_LANGUAGE_DEFAULT', 'fr'); // French for instance
// define('OPTION_LANGUAGE_DEFAULT', 'es'); // Spanish for instance
// default: 'en-us'

// Option session language: autodetect language frow browser settings
// define('OPTION_LANGUAGE_AUTO_DETECT', true);
// define('OPTION_LANGUAGE_AUTO_DETECT', false);
// default: true

// Option session language: force the option
// define('OPTION_FORCE_LANGUAGE', true);
// define('OPTION_FORCE_LANGUAGE', false);
// default: false (do not force any behavior)


// Option session language: default value
// define('OPTION_KEYMAP_DEFAULT', 'fr'); // French for instance
// define('OPTION_KEYMAP_DEFAULT', 'es'); // Spanish for instance
// default: 'en-us'

// Option session language: autodetect keyboard layout from client environment or language
// define('OPTION_KEYMAP_AUTO_DETECT', true);
// define('OPTION_KEYMAP_AUTO_DETECT', false);
// default: true

// Option session language: force the option
// define('OPTION_FORCE_KEYMAP', true);
// define('OPTION_FORCE_KEYMAP', false);
// default: false (do not force any behavior)


// Option local credentials: show the option
// define('OPTION_SHOW_USE_LOCAL_CREDENTIALS', true);
// define('OPTION_SHOW_USE_LOCAL_CREDENTIALS', false);
// default: do not force any behavior

// Option local credentials: force the mode
// define('OPTION_FORCE_USE_LOCAL_CREDENTIALS', true);
// define('OPTION_FORCE_USE_LOCAL_CREDENTIALS', false);
// default: do not force any behavior

// Option force SSO: do not let the user enter a login and password. The login is set to REMOTE_USER if possible
// define('OPTION_FORCE_SSO', true);
// define('OPTION_FORCE_SSO', false);
// default is false

// Option force SAML2: do not let the user enter a login and password the user is redirected th the Identity Provider.
// define('OPTION_FORCE_SAML2', true);
// define('OPTION_FORCE_SAML2', false);
// default is false

// Enable/disable debug mode
//  define('DEBUG_MODE', true);
//  define('DEBUG_MODE', false);
// default: false

// Select RDP input method
// define('RDP_INPUT_METHOD', 'scancode'); // alternative method
// define('RDP_INPUT_METHOD', 'unicode');  // default
// define('RDP_INPUT_METHOD', 'unicode_local_ime');  // alternative method with client integration

// RDP input method : show option
// define('OPTION_SHOW_INPUT_METHOD', false); // default
// define('OPTION_SHOW_INPUT_METHOD', true);

// RDP input method : force option
// Must be used in conjunction of RDP_INPUT_METHOD
// define('OPTION_FORCE_INPUT_METHOD', false); // default
// define('OPTION_FORCE_INPUT_METHOD', true);

// Perform desktop integration in portal sessions
//   publish destkop and start menu icons, mime type association, ...
// define('PORTAL_LOCAL_INTEGRATION', true); // default is false


// Gateway port
//   the port to use to contact the Gateway server in 'gateway_first' mode
//   usefull if the port used by the client to connect to the Gateway is different 
//    from the port binded by the Gateway (nat redirection)
//   default is to use the same port as the client connection
// define('GATEWAY_FORCE_PORT', 443);

// CONFIRM LOGOUT
// define('OPTION_CONFIRM_LOGOUT', 'always');
// define('OPTION_CONFIRM_LOGOUT', 'apps_only');
// define('OPTION_CONFIRM_LOGOUT', 'never');
// default = never

// Option direct SM communication (with proxy.php)
// define('OPTION_USE_PROXY', true);
// define('OPTION_USE_PROXY', false);
// default: false

// HTML5 Client installed
// define('RDP_PROVIDER_HTML5_INSTALLED', false); // default
// define('RDP_PROVIDER_HTML5_INSTALLED', true);

// Java web client installed
// define('RDP_PROVIDER_JAVA_INSTALLED', true); // default
// define('RDP_PROVIDER_JAVA_INSTALLED', false);
