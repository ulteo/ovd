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

PATH_EDITAREA = EditArea/edit_area

editarea_DATA = $(PATH_EDITAREA)/edit_area_full.js
editareadir = $(datadir)/ulteo/administration_console/web/media/script/lib/edit_area

regsyntax_DATA = $(PATH_EDITAREA)/reg_syntax/basic.js \
               $(PATH_EDITAREA)/reg_syntax/c.js \
               $(PATH_EDITAREA)/reg_syntax/cpp.js \
               $(PATH_EDITAREA)/reg_syntax/html.js \
               $(PATH_EDITAREA)/reg_syntax/js.js \
               $(PATH_EDITAREA)/reg_syntax/perl.js \
               $(PATH_EDITAREA)/reg_syntax/python.js \
               $(PATH_EDITAREA)/reg_syntax/ruby.js \
               $(PATH_EDITAREA)/reg_syntax/tsql.js \
               $(PATH_EDITAREA)/reg_syntax/xml.js \
               $(PATH_EDITAREA)/reg_syntax/brainfuck.js \
               $(PATH_EDITAREA)/reg_syntax/coldfusion.js \
               $(PATH_EDITAREA)/reg_syntax/css.js \
               $(PATH_EDITAREA)/reg_syntax/java.js \
               $(PATH_EDITAREA)/reg_syntax/pas.js \
               $(PATH_EDITAREA)/reg_syntax/php.js \
               $(PATH_EDITAREA)/reg_syntax/robotstxt.js \
               $(PATH_EDITAREA)/reg_syntax/sql.js \
               $(PATH_EDITAREA)/reg_syntax/vb.js \
               $(PATH_EDITAREA)/reg_syntax/vbs.js \
               $(PATH_EDITAREA)/reg_syntax/reg.js \
               $(PATH_EDITAREA)/reg_syntax/bash.js \
               $(PATH_EDITAREA)/reg_syntax/powershell.js \
               $(PATH_EDITAREA)/reg_syntax/batch.js

regsyntaxdir = $(datadir)/ulteo/administration_console/web/media/script/lib/edit_area/reg_syntax

langs_DATA = $(PATH_EDITAREA)/langs/bg.js \
               $(PATH_EDITAREA)/langs/de.js \
               $(PATH_EDITAREA)/langs/en.js \
               $(PATH_EDITAREA)/langs/es.js \
               $(PATH_EDITAREA)/langs/fr.js \
               $(PATH_EDITAREA)/langs/it.js \
               $(PATH_EDITAREA)/langs/mk.js \
               $(PATH_EDITAREA)/langs/pl.js \
               $(PATH_EDITAREA)/langs/ru.js \
               $(PATH_EDITAREA)/langs/zh.js \
               $(PATH_EDITAREA)/langs/cs.js \
               $(PATH_EDITAREA)/langs/dk.js \
               $(PATH_EDITAREA)/langs/eo.js \
               $(PATH_EDITAREA)/langs/fi.js \
               $(PATH_EDITAREA)/langs/hr.js \
               $(PATH_EDITAREA)/langs/ja.js \
               $(PATH_EDITAREA)/langs/nl.js \
               $(PATH_EDITAREA)/langs/pt.js \
               $(PATH_EDITAREA)/langs/sk.js

langsdir = $(datadir)/ulteo/administration_console/web/media/script/lib/edit_area/langs

images_DATA = $(PATH_EDITAREA)/images/autocompletion.gif \
               $(PATH_EDITAREA)/images/help.gif \
               $(PATH_EDITAREA)/images/newdocument.gif \
               $(PATH_EDITAREA)/images/reset_highlight.gif \
               $(PATH_EDITAREA)/images/spacer.gif \
               $(PATH_EDITAREA)/images/close.gif \
               $(PATH_EDITAREA)/images/highlight.gif \
               $(PATH_EDITAREA)/images/opacity.png \
               $(PATH_EDITAREA)/images/save.gif \
               $(PATH_EDITAREA)/images/statusbar_resize.gif \
               $(PATH_EDITAREA)/images/fullscreen.gif \
               $(PATH_EDITAREA)/images/load.gif \
               $(PATH_EDITAREA)/images/processing.gif \
               $(PATH_EDITAREA)/images/search.gif \
               $(PATH_EDITAREA)/images/undo.gif \
               $(PATH_EDITAREA)/images/go_to_line.gif \
               $(PATH_EDITAREA)/images/move.gif \
               $(PATH_EDITAREA)/images/redo.gif \
               $(PATH_EDITAREA)/images/smooth_selection.gif \
               $(PATH_EDITAREA)/images/word_wrap.gif

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
