SUBDIRS = nifty calendarpopup

HOOKS =

if !PROTOTYPE
SUBDIRS += prototype
else
HOOKS += prototype-hook
endif

if !SCRIPTACULOUS
SUBDIRS += scriptaculous
else
HOOKS += scriptaculous-hook
endif

editarea_DATA = edit_area/edit_area_full.js
editareadir = $(datadir)/ulteo/administration_console/web/media/script/lib/edit_area

regsyntax_DATA = edit_area/reg_syntax/basic.js \
               edit_area/reg_syntax/c.js \
               edit_area/reg_syntax/cpp.js \
               edit_area/reg_syntax/html.js \
               edit_area/reg_syntax/js.js \
               edit_area/reg_syntax/perl.js \
               edit_area/reg_syntax/python.js \
               edit_area/reg_syntax/ruby.js \
               edit_area/reg_syntax/tsql.js \
               edit_area/reg_syntax/xml.js \
               edit_area/reg_syntax/brainfuck.js \
               edit_area/reg_syntax/coldfusion.js \
               edit_area/reg_syntax/css.js \
               edit_area/reg_syntax/java.js \
               edit_area/reg_syntax/pas.js \
               edit_area/reg_syntax/php.js \
               edit_area/reg_syntax/robotstxt.js \
               edit_area/reg_syntax/sql.js \
               edit_area/reg_syntax/vb.js \
               edit_area/reg_syntax/vbs.js \
               edit_area/reg_syntax/reg.js \
               edit_area/reg_syntax/bash.js \
               edit_area/reg_syntax/powershell.js \
               edit_area/reg_syntax/batch.js

regsyntaxdir = $(datadir)/ulteo/administration_console/web/media/script/lib/edit_area/reg_syntax

langs_DATA = edit_area/langs/bg.js \
               edit_area/langs/de.js \
               edit_area/langs/en.js \
               edit_area/langs/es.js \
               edit_area/langs/fr.js \
               edit_area/langs/it.js \
               edit_area/langs/mk.js \
               edit_area/langs/pl.js \
               edit_area/langs/ru.js \
               edit_area/langs/zh.js \
               edit_area/langs/cs.js \
               edit_area/langs/dk.js \
               edit_area/langs/eo.js \
               edit_area/langs/fi.js \
               edit_area/langs/hr.js \
               edit_area/langs/ja.js \
               edit_area/langs/nl.js \
               edit_area/langs/pt.js \
               edit_area/langs/sk.js

langsdir = $(datadir)/ulteo/administration_console/web/media/script/lib/edit_area/langs

images_DATA = edit_area/images/autocompletion.gif \
               edit_area/images/help.gif \
               edit_area/images/newdocument.gif \
               edit_area/images/reset_highlight.gif \
               edit_area/images/spacer.gif \
               edit_area/images/close.gif \
               edit_area/images/highlight.gif \
               edit_area/images/opacity.png \
               edit_area/images/save.gif \
               edit_area/images/statusbar_resize.gif \
               edit_area/images/fullscreen.gif \
               edit_area/images/load.gif \
               edit_area/images/processing.gif \
               edit_area/images/search.gif \
               edit_area/images/undo.gif \
               edit_area/images/go_to_line.gif \
               edit_area/images/move.gif \
               edit_area/images/redo.gif \
               edit_area/images/smooth_selection.gif \
               edit_area/images/word_wrap.gif

imagesdir = $(datadir)/ulteo/administration_console/web/media/script/lib/edit_area/images


EXTRA_DIST = $(editarea_DATA) $(regsyntax_DATA) $(langs_DATA) $(images_DATA)


install-exec-hook: $(HOOKS)

prototype-hook:
	$(INSTALL) -d $(DESTDIR)$(datadir)/ulteo/administration_console/web/media/script/lib
	ln -sf $(PROTOTYPEDIR) \
		$(DESTDIR)$(datadir)/ulteo/administration_console/web/media/script/lib/prototype

scriptaculous-hook:
	$(INSTALL) -d $(DESTDIR)$(datadir)/ulteo/administration_console/web/media/script/lib
	ln -sf $(SCRIPTACULOUSDIR) \
		$(DESTDIR)$(datadir)/ulteo/administration_console/web/media/script/lib/scriptaculous
