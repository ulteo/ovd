INSTALLDIR=..

COPYDOKANMOUNT:
        if not exist "$(INSTALLDIR)\build" (md "$(INSTALLDIR)\build")
        if exist "$(OBJ_PATH)\$(O)\$(TARGETNAME).exe" copy "$(OBJ_PATH)\$(O)\$(TARGETNAME).exe" "$(INSTALLDIR)\build"
