System - Language Domains plugin
================================
Homepage: http://www.yireo.com/software/joomla-extensions/language-domains

This Joomla! 2.5 / 3.0 plugin allows you to assign a domain per lanuage. You configure specific "bindings" in the parameters of this plugin. Each line
contains a binding between a language-tag and a domain. The plugin checks whether the current domain matches the specified language, and redirects to the
neccessary domain if needed. Also, it will try to fix all of the generated Joomla! SEF URLs.

Steps to get it working:
* Make sure Joomla! SEF is fully enabled
* Install this plugin in Joomla!
* Disable "System - Language Filter" core-plugin (!)
* Enable "System - Language Domains" plugin (this plugin)
* Configure this plugin for the proper bindings.

For example you could configure the following bindings:

    en-GB=example.co.uk
    fr-FR=example.fr
    de-DE=example.de
    nl-NL=example.nl
