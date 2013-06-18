var uovd = uovd || {};
uovd.provider = uovd.provider || {};
uovd.provider.http = uovd.provider.http || {};
uovd.provider.rdp = uovd.provider.rdp || {};
uovd.provider.rdp.application = uovd.provider.rdp.application || {};
uovd.provider.rdp.html5 = uovd.provider.rdp.html5 || {};


uovd.SUCCESS = "success";
uovd.ERROR = "error";

uovd.SESSION_MODE_DESKTOP = "desktop";
uovd.SESSION_MODE_APPLICATIONS = "applications";


uovd.SESSION_STATUS_ERROR = "error";
uovd.SESSION_STATUS_UNKNOWN = "unknown";
uovd.SESSION_STATUS_CREATING = "creating";
uovd.SESSION_STATUS_CREATED = "created";
uovd.SESSION_STATUS_INITED = "init";
uovd.SESSION_STATUS_READY  = "ready";
uovd.SESSION_STATUS_LOGGED = "logged";
uovd.SESSION_STATUS_DISCONNECTED = "disconnected";
uovd.SESSION_STATUS_WAIT_DESTROY = "wait_destroy";
uovd.SESSION_STATUS_DESTROYING = "destroying";
uovd.SESSION_STATUS_DESTROYED = "destroyed";


uovd.SERVER_STATUS_UNKNOWN ="unknown";
uovd.SERVER_STATUS_CONNECTED = "connected";
uovd.SERVER_STATUS_READY = "ready";
uovd.SERVER_STATUS_DISCONNECTED = "disconnected";

uovd.APPLICATION_STARTED = "started";
uovd.APPLICATION_STOPPED = "stopped";

uovd.SESSION_PHASE_UNKNOWN = "unknown";
uovd.SESSION_PHASE_STARTING = "starting";
uovd.SESSION_PHASE_STARTED = "started";
uovd.SESSION_PHASE_DESTROYING = "destroying";
uovd.SESSION_PHASE_DESTROYED = "destroyed";
