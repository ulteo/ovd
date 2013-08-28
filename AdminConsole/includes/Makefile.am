scripts_DATA = core.inc.php \
               core-minimal.inc.php \
               defaults.inc.php \
               functions.inc.php \
               misc.inc.php \
               ovd-admin.wsdl \
               page_template.php \
               page_template_static.php \
               template_server.inc.php \
               webapp.inc.php

scriptsdir = $(datadir)/ulteo/administration_console/includes


tcpdf_DATA =   tcpdf/tcpdf.php \
               tcpdf/tcpdf_autoconfig.php

tcpdfdir = $(datadir)/ulteo/administration_console/includes/tcpdf


config_DATA = tcpdf/config/tcpdf_config.php

configdir = $(datadir)/ulteo/administration_console/includes/tcpdf/config


includes_DATA = tcpdf/include/sRGB.icc \
                tcpdf/include/tcpdf_colors.php \
                tcpdf/include/tcpdf_filters.php \
                tcpdf/include/tcpdf_font_data.php \
                tcpdf/include/tcpdf_fonts.php \
                tcpdf/include/tcpdf_images.php \
                tcpdf/include/tcpdf_static.php

includesdir = $(datadir)/ulteo/administration_console/includes/tcpdf/include


fonts_DATA =   tcpdf/fonts/courier.php \
               tcpdf/fonts/courierb.php \
               tcpdf/fonts/courierbi.php \
               tcpdf/fonts/courieri.php \
               tcpdf/fonts/helvetica.php \
               tcpdf/fonts/helveticab.php \
               tcpdf/fonts/helveticabi.php \
               tcpdf/fonts/helveticai.php

fontsdir = $(datadir)/ulteo/administration_console/includes/tcpdf/fonts


EXTRA_DIST = $(tcpdf_DATA) $(config_DATA) $(includes_DATA) $(fonts_DATA) \
               core.inc.php \
               core-minimal.inc.php \
               defaults.inc.php.in \
               functions.inc.php \
               misc.inc.php \
               ovd-admin.wsdl \
               page_template.php \
               page_template_static.php \
               template_server.inc.php \
               webapp.inc.php

defaults.inc.php:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALE_DIR@,$(datadir)/locale," \
	    -e "s,@OVD_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/defaults.inc.php.in > $@

clean-local:
	rm -f defaults.inc.php

.PHONY: defaults.inc.php
