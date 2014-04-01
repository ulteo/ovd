include Makefile.am

SVG=$(wildcard *.svgz) $(wildcard icons/*.svgz) $(wildcard flags/*.svgz)
LOGO=logo.svgz
ICON=icon.svgz
FILTER=uovd.png icon.png logo.png rotate.gif
IMAGES=$(filter-out $(FILTER), $(patsubst %.svgz, %.png, $(SVG)) $(scripts_DATA))

all: $(IMAGES)

%.png: %.svgz
	rsvg-convert -f png $< -o $@

favicon.png: $(ICON)
	rsvg-convert -w 16 -h 16 -f png $< -o $@

favicon.ico: favicon.png
	convert $< $@

ulteo-small.png: $(LOGO)
	rsvg-convert -w 141 -h 80 -f png $< -o $@

ulteo.png: $(LOGO)
	rsvg-convert -w 320 -h 182 -f png $< -o $@

clean:
	rm -f $(IMAGES)
