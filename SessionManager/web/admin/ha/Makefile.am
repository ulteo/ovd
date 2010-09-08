hadir = $(datadir)/ulteo/sessionmanager/admin/ha
ha_DATA = configuration.php \
          logs.php \
          status.php \
          registration.php

imagedir = $(hadir)/media/image
image_DATA = media/image/ha_down.png \
          media/image/resource.gif \
          media/image/menu_main.png \
          media/image/ha_online.png \
          media/image/loader.gif \
          media/image/ha_standby.png \
          media/image/cross_error_cib.png

styledir = $(hadir)/media/style
style_DATA = media/style/media-ha.css

classesdir = $(hadir)/classes
classes_DATA = classes/GRNode.class.php \
          classes/Abstract_node.class.php \
          classes/PRNode.class.php \
          classes/CLNode.class.php \
          classes/CibNode.class.php \
          classes/Cib.class.php \
          classes/MSNode.class.php \
          classes/ShellExec.class.php

EXTRA_DIST = $(ha_DATA) $(image_DATA) $(style_DATA) $(classes_DATA)
