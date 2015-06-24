#!/usr/bin/env python

import hashlib
import os
from glob import glob

firstdir = 'dist'
dirs = [firstdir]
to_uninst = []

with open('dist.nsh', 'w') as fd:
	for d in dirs:
		files = glob(d + '/*')
		f_of_d = filter(os.path.isfile, files)
		dirs.extend(filter(os.path.isdir, files))
		h = hashlib.md5(d).hexdigest()
		fd.writelines(['Section "Sec%(h)s" Sec%(h)s\n' % {'h': h},
		               '  SetOutPath "$INSTDIR\\%s"\n' % d[5:].replace('/', "\\"),
		               '  SetOverwrite on\n',
		               '\n'])
		for f in f_of_d:
			fd.write('  File "%s"\n' % f.replace('/', "\\"))
		fd.writelines(['SectionEnd\n', '\n']);
		to_uninst.append((d[5:].replace('/', "\\"), h, f_of_d))
	while to_uninst:
		d, h, f_of_d = to_uninst.pop()
		fd.write('Section "un.Sec%(h)s" SecUn%(h)s\n' % {'h': h})
		for f in f_of_d:
			fd.write('  Delete "$INSTDIR\\%s"\n' % f.replace('/', "\\"))
		fd.writelines(['  RMDir "$INSTDIR\\%s"\n' % d,
		               'SectionEnd\n', '\n']);
