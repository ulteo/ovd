OVD_VERSION?=0.0
PATH:=$(PATH):$(PWD)/tools

all: setup

dist:
	install -d dist

setup: dist
	dir2nsh -p1 dist
	makensis -NOCD -DSETUP_NAME=Ulteo-ovd-agent-`date +%Y%m%d` -DPRODUCT_VERSION=$(OVD_VERSION) nsis/main.nsi

clean:
	rm -rf dist dist.nsh
	rm -f *~
	rm -f *.exe
