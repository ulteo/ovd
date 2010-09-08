CCFLAGS ?= -Wall -DSYSCONFDIR=\"/etc\"
CXXFLAGS = $(CCFLAGS)

SRC = main.cpp Utils.cpp Action.cpp
OBJ = $(SRC:.cpp=.o)
EXE = HAshell

all: $(OBJ)
	$(CXX) -o $(EXE) $(OBJ) $(LDFLAGS)

$(EXE): all

install: $(EXE)
	mkdir -p $(DESTDIR)/usr/bin
	cp $(EXE) $(DESTDIR)/usr/bin
	chmod 4755 $(DESTDIR)/usr/bin/$(EXE)

clean:
	$(RM) *~
	$(RM) $(OBJ)
	$(RM) $(EXE)
