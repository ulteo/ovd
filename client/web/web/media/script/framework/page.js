/* Init */

var session_management = null;

jQuery(document).ready(function() {
	jQuery("#session_manager").val("localhost");
	jQuery("#username").val("dpaul");
	jQuery("#password").val("dpaul");

	jQuery("#width").val("830");
	jQuery("#height").val("580");

	jQuery("#fullscreen").val("false");
	jQuery("#fullscreen").click(function() {
		jQuery("#width").prop("disabled", jQuery("#fullscreen").prop("checked"));
		jQuery("#height").prop("disabled", jQuery("#fullscreen").prop("checked"));
	});

	jQuery("#session_type").append(jQuery(document.createElement("option")).prop("text", "desktop"));
	jQuery("#session_type").append(jQuery(document.createElement("option")).prop("text", "applications"));

	jQuery("#rdp_provider").append(jQuery(document.createElement("option")).prop("text", "Java"));
	jQuery("#rdp_provider").append(jQuery(document.createElement("option")).prop("text", "HTML5"));

	jQuery("#ajax_provider").append(jQuery(document.createElement("option")).prop("text", "Xhr"));
	jQuery("#ajax_provider").append(jQuery(document.createElement("option")).prop("text", "Direct"));
	jQuery("#ajax_provider").append(jQuery(document.createElement("option")).prop("text", "Proxy"));

	jQuery("#submit").click(function () {
		var parameters = new Array();
		parameters["username"] = jQuery("#username").val();
		parameters["password"] = jQuery("#password").val();
		parameters["session_type"] = jQuery("#session_type").val();
		parameters["session_manager"] = jQuery("#session_manager").val();
		parameters["width"] = jQuery("#width").val();
		parameters["height"] = jQuery("#height").val();
		parameters["fullscreen"] = jQuery("#fullscreen").prop("checked");

		if(parameters["fullscreen"] == true) {
			parameters["width"] = window.innerWidth;
			parameters["height"] = parseInt(window.innerHeight) - 45;
		}

		/* choose rdp_provider */
		/* choice based on : capabilities, rdp_provider parameter */
		var provider = jQuery("#rdp_provider").val();
		var rdp_provider = null;
		if(provider == "Java") {
			rdp_provider = new JavaRdpProvider();
		} else if(provider == "HTML5") {
			rdp_provider = new Html5RdpProvider();
		}

		/* choose ajax_provider */
		var provider = jQuery("#ajax_provider").val();
		var ajax_provider = null;
		if(provider == "Xhr") {
			ajax_provider = new XhrAjaxProvider();
		} else if(provider == "Direct") {
			var ajax_provider = new DirectAjaxProvider();
		} else if(provider == "Proxy") {
			var ajax_provider = new ProxyAjaxProvider("proxy.php");
		}

		/* build session_management instance */
		session_management = new SessionManagement(parameters, rdp_provider, ajax_provider);

		/* Add debug panel */
		var debug_panel = new DebugPanel(session_management, jQuery("#logs")[0]);

		/* Add seamless panel */
		var seamless_launcher = new SeamlessLauncher(session_management, jQuery("#client")[0]);

		/* Add desktop container */
		var desktop_container = new DesktopContainer(session_management, jQuery("#client")[0]);

		/* Add seamless window manager */
		var seamless_window_manager = new SeamlessWindowManager(session_management, jQuery("#client")[0], new SeamlessWindowFactory());

		/* Bind disconnect on sumbit button */
		jQuery("#submit").val("Close");
		jQuery("#submit").off("click");
		jQuery("#submit").click(function() {
			session_management.stop();
		});

		/* start session */
		session_management.start();
	});
});
