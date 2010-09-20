CC=gcc
JAVAHOME=/usr/lib/jvm/java-6-sun
CFLAGS=-Wall
LDFLAGS=-I${JAVAHOME}/include -I${JAVAHOME}/include/linux
EXEC=libXClientArea.so

all: $(EXEC)
$(EXEC): src/org_ulteo_ovd_client_env_WorkArea.o
	$(CC) src/org_ulteo_ovd_client_env_WorkArea.o $(LDFLAGS) -shared -o ${EXEC}
src/org_ulteo_ovd_client_env_WorkArea.o: src/org_ulteo_ovd_client_env_WorkArea.c
	$(CC) -c -o src/org_ulteo_ovd_client_env_WorkArea.o $(CFLAGS) $(LDFLAGS) src/org_ulteo_ovd_client_env_WorkArea.c
clean:
	rm -rf src/*.o
mrproper: clean
	rm -rf $(EXEC)