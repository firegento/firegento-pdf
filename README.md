FireGento PDF
=============
[![Build Status](https://travis-ci.org/firegento/firegento-pdf.svg?branch=development)](https://travis-ci.org/firegento/firegento-pdf/)

FireGento PDF overwrites standard PDF layouts for invoices, shipments and creditmemos.

Facts
-----
- version: 1.3.0
- extension key: FireGento_Pdf
- [extension on Magento Connect](http://www.magentocommerce.com/magento-connect/firegento-pdf.html)
- Magento Connect 2.0 extension key: http://connect20.magentocommerce.com/community/FireGento_Pdf
- [extension on GitHub](https://github.com/firegento/firegento-pdf)
- [direct download link](https://github.com/firegento/firegento-pdf/archive/master.zip)

Description
-----------
FireGento PDF overwrites standard PDF layouts for invoices, shipments and creditmemos. Anyway, you can still use the standard Magento layout, because the extension is highly configurable.

Requirements
------------
- PHP >= 5.2.0
- Mage_Core
- Mage_Pdf
- Mage_Sales

Compatibility
-------------
- Magento >= 1.6

Installation Instructions
-------------------------
1. Install the extension via Magento Connect with the key shown above or copy all the files into your document root.
2. Clear the cache, logout from the admin panel and then login again.
3. Configure the extension under System - Configuration - Sales - PDF Print-outs.

### Recommendation
If you use this extension for an austrian shop or Austrian locale (de_AT), please make sure to install [Hackathon_LocaleFallback](https://github.com/magento-hackathon/Hackathon_LocaleFallback) as well, because we only maintain the strings which differ between German locales, so you need this plugin (or have to copy all the strings over). 

Uninstallation
--------------
1. Remove all extension files from your Magento installation.

Support
-------
If you have any issues with this extension, open an issue on [GitHub](https://github.com/firegento/firegento-pdf/issues).

Contribution
------------
Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests). In order to contribute to the latest code, please checkout the `development` branch after cloning your fork.

Developer
---------
FireGento team and all other [contributors](https://github.com/firegento/firegento-pdf/contributors)

License
-------
[GNU General Public License, version 3 (GPLv3)](http://opensource.org/licenses/gpl-3.0)

Copyright
---------
(c) 2013-2014 FireGento Team
