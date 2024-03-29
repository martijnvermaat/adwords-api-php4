AdWords API PHP4 Client Library
===============================

A PHP4 library that provides access to the Google AdWords API v2009.


Motivation
----------

Now that the Google AdWords API has moved from [version v13][API v13]
to [version v2009][API v2009], there no longer is a client library for
PHP4.

[The APIlity Library][APIlity] is deprecated and only implements API
version v13, while the new [AdWords API PHP Client Library][AdWords PHP]
implements API version v2009 but is only compatible with PHP 5.2.

Any site still running PHP4 should seriously consider upgrading, but
this can take some time and effort. In the mean time, this library can
be used to access the AdWords API from PHP4.


Status
------

This library is not meant to be a complete and rigorous implementation
of the v2009 API, but a temporary workable abstraction for the most
basic API tasks. More involved operations are likely to stay
unimplemented.

For the moment, the library is focussed on managing ad group criteria
and is very incomplete otherwise.


Compatibility
-------------

Tested on

* Debian 4.0 (etch) with libapache2-mod-php4 version 4.4.4 and
* Ubuntu 9.10 (Karmic Koala) with libapache2-mod-php5 version 5.2.10,

both using [NuSOAP][NuSOAP] 0.7.3.


Download
--------

Version 0.2, 2010-02-03: [adwords-api-php4-0.2.tar.gz][Download]
([changelog][Changelog])

Or clone the latest development version from GitHub:

    git clone https://github.com/martijnvermaat/adwords-api-php4.git


Author
------

[Martijn Vermaat][Martijn] (<martijn@vermaat.name>)


License
-------

Licensed under the [Apache License, Version 2.0][License]


[API v13]:     http://code.google.com/intl/nl/apis/adwords/docs/
[API v2009]:   http://code.google.com/intl/nl/apis/adwords/v2009/docs/
[APIlity]:     http://google-apility.sourceforge.net/
[AdWords PHP]: http://code.google.com/p/google-api-adwords-php/
[NuSOAP]:      http://sourceforge.net/projects/nusoap/
[Download]:    http://martijn.vermaat.name/adwords-api-php4/files/adwords-api-php4-0.2.tar.gz
[Changelog]:   CHANGELOG
[Martijn]:     http://martijn.vermaat.name/
[License]:     http://www.apache.org/licenses/LICENSE-2.0
