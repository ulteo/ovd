SUBDIRS = web po config scripts extra examples

clean-local:
	rm -f $(noinst_DATA)

# maintainer rules
update-potfiles-in:
	find web -name "*.php" > po/POTFILES.in
	sed -i '/defaults\.inc\.php/d' po/POTFILES.in
	sed -i '/web\/admin\/init\/setup.php/d' po/POTFILES.in
