bin_PROGRAMS = lmessage

lmessage_SOURCES = \
		main.c

lmessage_CFLAGS = \
		-Wall \
		-DNAME=\"lmessage\" \
		@GTK_CFLAGS@

lmessage_LDADD = \
		@GTK_LIBS@

man_MANS = lmessage.1
EXTRA_DIST = $(man_MANS)

gen-manpage:
	help2man --no-info --section=1 --manual="User Commands" --name "Display messages in a GTK dialog" ./lmessage >$@

