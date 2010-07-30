INSTALLDIR=.

COPYDLL:
        if not exist $(INSTALLDIR)\$(_BUILDARCH) (md $(INSTALLDIR)\$(_BUILDARCH) )
        if exist $(OBJ_PATH)\$(O)\$(TARGETNAME).dll copy $(OBJ_PATH)\$(O)\$(TARGETNAME).dll $(INSTALLDIR)\$(_BUILDARCH)
        if exist $(INSTALLDIR)\$(_BUILDARCH) copy $(INSTALLDIR)\ulteodll.inf $(INSTALLDIR)\$(_BUILDARCH)
        if exist $(INSTALLDIR)\$(_BUILDARCH) copy $(INSTALLDIR)\ulteops.ini $(INSTALLDIR)\$(_BUILDARCH)
        if exist $(INSTALLDIR)\$(_BUILDARCH) copy $(INSTALLDIR)\ulteo.ppd $(INSTALLDIR)\$(_BUILDARCH)

