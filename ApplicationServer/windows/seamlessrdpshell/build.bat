cmd /C build32.bat
if not %errorlevel%==0 goto :error

cmd /C build64.bat
if not %errorlevel%==0 goto :error

exit 0
error:
exit 1
