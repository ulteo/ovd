#! /usr/bin/python

import os
import pysvn
import shutil
import sys

# Detect the version
if os.environ.has_key("OVD_VERSION"):
    version = os.environ["OVD_VERSION"]

else:
    c = pysvn.Client()
    revision = c.info(".")["revision"].number

    version = "99.99~trunk+svn%05d"%(revision)


# OS detection
if sys.platform == "linux2":
    platform_dir = "Linux"

elif sys.platform == "win32":
    platform_dir = "Windows"

else:
   raise Exception("No supported platform")


# Set the Platform directory
path_virtual = os.path.join("ovd_shells", "Platform")
path_real    = os.path.join("ovd_shells", "Platform."+platform_dir)

if os.path.exists(path_virtual):
    shutil.rmtree(path_virtual)
shutil.copytree(path_real, path_virtual)


f = file("setup.py.in", "r")
content = f.read()
f.close()

content = content.replace("@VERSION@", str(version))

f = file("setup.py", "w")
f.write(content)
f.close()
