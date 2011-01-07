
This is a demo code to understand how to integrate Ulteo OVD in an existing web portal/CMS.

Web Portal/CMS store files. To modify those files, users need applications. The purpose of this project is to open the files with applications provides by Ulteo instead of forcing the user to install applications on his workstation.


== How to test that code ==

 Prerequisites: 
   * have a running Ulteo farm with the the web client installed
   * have a running PHP server to push those files
 
 1. edit the config.inc.php file
 2. replace the value of ULTEO_OVD_SM_HOST and ULTEO_OVD_WEBCLIENT_URL according to your own architecture
 3. open index.php with your web browser


== How to integrate Ulteo OVD with your own portal ==

There are 3 points:

From Ulteo side, there are 3 points to look at:

  1. How to access to your CMS files from Ulteo servers (Linux/Windows)
  2. How to manage the authentication between the portal and Ulteo to avoid to ask the user to login twice
  3. Add Ulteo application icons as custom actions into the portal
  

== Ulteo Authentication ==

The easiest way to authenticate to Ulteo is to use the Token authentication module.

In the OVD administration console, go into the Authentication method settings and enable the Token method and set the URL according to the token.php on this code.
