INSTALLDIR=..

COPYDOKANSYS:
        if not exist "$(INSTALLDIR)\$(_BUILDARCH)" (md "$(INSTALLDIR)\$(_BUILDARCH)")
        if exist "$(OBJ_PATH)\$(O)\$(TARGETNAME).sys" copy "$(OBJ_PATH)\$(O)\$(TARGETNAME).sys" "$(INSTALLDIR)\$(_BUILDARCH)"
