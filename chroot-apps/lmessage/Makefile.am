bin_PROGRAMS = lmessage

lmessage_SOURCES = \
		main.c

lmessage_CFLAGS = \
		-Wall \
		-DNAME=\"lmessage\" \
		@GTK_CFLAGS@

lmessage_LDADD = \
		@GTK_LIBS@

if HAVE_HELP2MAN
man_MANS = lmessage.1

lmessage.1:
	help2man --no-info --section=1 --manual="User Commands" --name "Display messages in a GTK dialog" $(srcdir)/lmessage >$@

clean-local:
	rm -f $(man_MANS)
endif # HAVE_HELP2MAN
