/* Example of using GS DLL as a ps2pdf converter.  */

#if defined(_WIN32) && !defined(_Windows)
# define _Windows
#endif
#ifdef _Windows
/* add this source to a project with gsdll32.dll, or compile it directly with:
 *   cl -D_Windows -Isrc -Febin\ps2pdf.exe ps2pdf.c bin\gsdll32.lib
 */
# include <windows.h>
# define GSDLLEXPORT __declspec(dllimport)
#endif

#include "ierrors.h"
#include "iapi.h"

void *minst;

int main(int argc, char *argv[])
{
    int code, code1;
    const char * gsargv[10];
    int gsargc;
    gsargv[0] = "ps2pdf";	/* actual value doesn't matter */
    gsargv[1] = "-dNOPAUSE";
    gsargv[2] = "-dBATCH";
    gsargv[3] = "-dSAFER";
    gsargv[4] = "-sDEVICE=pdfwrite";
    gsargv[5] = "-sOutputFile=C:\\spool\\job.pdf";
    gsargv[6] = "-c";
    gsargv[7] = ".setpdfwrite";
    gsargv[8] = "-f";
    gsargv[9] = "C:\\spool\\job.ps";
    gsargc=10;
    

    code = gsapi_new_instance(&minst, NULL);
    if (code < 0)
	return 1;
    code = gsapi_init_with_args(minst, gsargc, gsargv);
    code1 = gsapi_exit(minst);
    if ((code == 0) || (code == e_Quit))
	code = code1;

    gsapi_delete_instance(minst);

    if ((code == 0) || (code == e_Quit))
	return 0;
    return 1;
}

