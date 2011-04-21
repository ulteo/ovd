INSTALLDIR=..

COPYDOKANWEBDAV:
    if not exist "$(INSTALLDIR)\$(_BUILDARCH)" (md "$(INSTALLDIR)\$(_BUILDARCH)")
    if exist "$(OBJ_PATH)\$(O)\$(TARGETNAME).exe" copy "$(OBJ_PATH)\$(O)\$(TARGETNAME).exe" "$(INSTALLDIR)\$(_BUILDARCH)"
