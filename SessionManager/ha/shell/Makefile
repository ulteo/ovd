CCFLAGS ?= -Wall -DSYSCONFDIR=\"/etc\"
CXXFLAGS = $(CCFLAGS)


SRC = main.cpp Utils.cpp Action.cpp
OBJ = $(SRC:.cpp=.o)
EXE = HAshell


all: $(OBJ)
	$(CXX) -o $(EXE) $(OBJ) $(LDFLAGS)
	



clean:
	$(RM) *~
	$(RM) $(OBJ)
	$(RM) $(EXE)

install:
	cp $(EXE) /usr/bin
	chmod 4755 /usr/bin/$(EXE)
