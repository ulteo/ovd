INSTALLDIR=..

COPYDOKANLIB:
        if not exist "$(INSTALLDIR)\build" (md "$(INSTALLDIR)\build")
        if exist "$(OBJ_PATH)\$(O)\$(TARGETNAME).dll" copy "$(OBJ_PATH)\$(O)\$(TARGETNAME).dll" "$(INSTALLDIR)\build"
		if exist "$(OBJ_PATH)\$(O)\$(TARGETNAME).lib" copy "$(OBJ_PATH)\$(O)\$(TARGETNAME).lib" "$(INSTALLDIR)\build"
