#ifndef _Hook_H
#define _Hook_H

#include <string>
#include <vector>
#include <Winternl.h>	//Nt 

void setupHooks();
void releaseHooks();

#define restoreObjectAttributes_ObjectName(ObjectAttributes, value)			ObjectAttributes->ObjectName->Buffer = value.Buffer; \
																			ObjectAttributes->ObjectName->Length = value.Length; \
																			ObjectAttributes->ObjectName->MaximumLength = value.MaximumLength; 

#endif //#ifndef _Hook_H