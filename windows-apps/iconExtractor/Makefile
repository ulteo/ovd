PREFIX?=i586-mingw32msvc-
CC=$(PREFIX)gcc

CFLAGS= -Wall
LDFLAGS=-lgdi32

RM=rm -f


all: extract_icon.exe

%.exe: %.c
	$(CC) $(CFLAGS) -o $@ $< $(LDFLAGS)

%.o: %.c
	$(CC) $(CFLAGS) -c $< -o $@


clean:
	$(RM) extract_icon.exe
	$(RM) *.o
	$(RM) *~