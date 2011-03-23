SUBDIRS=\
	admin-console \
	iso \
	native \
	windows

DATA=\
	aps-session-win-appps.png \
	aps_win_event.png \
	aps_win_install_1.png \
	aps_win_install_config.png \
	aps_win_services.png \
	aps_win_services_set_boot.png \
	cm_session_kde.png \
	cm_session_launching.png \
	cm_session.png \
	important.png \
	note.png \
	ovd.png \
	profile-ad.png \
	profile-internal.png \
	profile-ldap-home.png \
	profile-ldap.png \
	profile-ldap-ug.png \
	session.png \
	sm_admin_external_name.png \
	sm_admin_install_fqdn.png \
	sm_admin_main.png \
	sm_admin_populate.png \
	sm_admin_publication.png \
	sm_admin_server_unregistered.png \
	sm_admin_server_unregistered_win.png \
	sm_admin_server_win.png \
	sm_install_admin_config_init.png \
	sm_install_admin_confirm_password.png \
	sm_install_admin_login.png \
	sm_install_admin_password.png \
	sm_install_chroot_location.png \
	sm_install_config_sql.png \
	sm_www_admin_before_login.png \
	sm_www_admin_login.png \
	subsystem_install_sm_host.png \
	wc_advanced_login_window.png \
	wc_desktop_session.png \
	wc_install_plug_sm.png \
	wc_install_sm_host.png \
	wc_loading.png \
	wc_login.png \
	wc_login_window.png \
	wc_portal_session.png \
	wc_session_desktop_started.png \
	wc-session-desktop-windows.png \
	wc_session_end.png \
	wc_session_loading.png \
	wc_test_failed.png \
	wc_test_window.png

all:

install:
	install -d 		$(DIST)
	install $(DATA)		$(DIST)
	@for d in $(SUBDIRS); do \
		$(MAKE) -w -C $$d $(MAKECMDGOALS) DIST=$(abspath $(DIST))/$$d; \
	done

