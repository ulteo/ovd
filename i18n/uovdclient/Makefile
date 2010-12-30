# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

GETTEXT_PACKAGE=$(notdir $(shell pwd))
LOCALEDIR=/usr/share/locale

XGETTEXT=xgettext --force-po --add-comments --from-code=UTF-8 --default-domain=$(GETTEXT_PACKAGE) --copyright-holder="Ulteo SAS" --package-name=$(GETTEXT_PACKAGE) --foreign-user --keyword=_

TRANSFORM_RULES_1=This file is put in the public domain\./This file is distributed under the same license as the Ulteo OVD software (http:\/\/www.ulteo.com).
TRANSFORM_RULES_2=SOME DESCRIPTIVE TITLE/Copyright (C) `date +'%Y'` Ulteo SAS
TRANSFORM_RULES_3=charset=CHARSET/charset=UTF-8

LINGUAS=$(foreach po, $(wildcard *.po), $(basename $(po)))
GMOS=$(foreach lang, $(LINGUAS), $(lang).gmo)

all: $(GMOS)

%.gmo: %.po
	msgfmt -o $@ $?

install: all
	for lang in $(LINGUAS); do \
		dir=$(DESTDIR)$(LOCALEDIR)/$$lang/LC_MESSAGES; \
		mkdir -p $$dir; \
		install -c $$lang.gmo $$dir/$(GETTEXT_PACKAGE).mo; \
	done

update-pot:
	find ../../client/web/web -name "*.php" >POTFILES.in
	find ../../client/java/src/org/ulteo/ovd/client -name "*.java" >>POTFILES.in

	$(XGETTEXT) --files-from=POTFILES.in  --output=en.pot
	sed -i "s/$(TRANSFORM_RULES_1)/" en.pot
	sed -i "s/$(TRANSFORM_RULES_2)/" en.pot
	sed -i "s/$(TRANSFORM_RULES_3)/" en.pot

update-po:
	for lang in `find -name "*.po" -exec basename '{}' .po \;`; do \
		echo "$$lang:"; \
		intltool-update --dist $$lang --gettext-package en; \
	done

clean:
	$(RM) *.gmo
	$(RM) POTFILES.in
