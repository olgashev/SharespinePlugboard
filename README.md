# Sharespine Plugboard API Additions

This is a small module that adds some additional methods, parameters and output to Magento's standard Soap API.

This module has to be installed in order to connect your Magento to Sharespine Plugboard.

## Installation

There is two (recommended) methods of installation, the best is using the popular tool ```modman```, the second is to use Magento Connect Manager.

**Note:** We recommend that you install this module in a development or staging environment before installing in production. Also take a new backup of your entire store before you deploy this module.

### Modman
For this method you will need access to a command line on your server. If you do not have that you'll need to contact your hosting provider, or choose another method of installation.

For this method to work, you will need the modman executable on your server. Most Magento hosting providers have this since it is a very common tool nowadays. If you do not have it, go to [modman's github page](https://github.com/colinmollenhour/modman) for installation instructions.

Once you have modman installed, if you have not used modman before on this Magento installation you have to run this command in the sites root directory.
```
modman init
```
Then you can run the below command to install and deploy the module:
```
modman clone https://github.com/sharespine/magento-api-extension.git
```
Unless there's an error with any of the above commands, the modules files is now installed. All you have to do is clear the cache in Magento.

### Magento Connect Manager
First download the [latest version of our Magento Connect Package file](https://github.com/sharespine/magento-api-extension/raw/gh-pages/Builds/Sharespine_Plugboard-0.0.17.tgz) to your computer.

Then, while logged in to your admin, go to ```System -> Magento Connect -> Magento Connect Manager```, here you'll have to log in again.

Under the heading *Direct package file upload*, press the ```Browse``` button, select the file you downloaded above, then press ```Upload```. Unless there was any errors the module is now installed and enabled in your store.

## Compatibility
Tested on Magento CE 1.6.2.0 - 1.9.1.0

### Note for 1.6.2.0

There is a known bug with webservice roles that prevents the role resources to be saved. Follow instructions in [this link](https://support.remarkety.com/hc/en-us/articles/208148766-Magento-1-6-Can-not-save-Role-Resources-for-WebService) to fix the issue.
**Note:** this bug is not directly related to Sharespine, but it will prevent an API role
from beeing created.

## Test the module after installation
There is a simple php-script in the ```/doc``` folder that can be used to send soap requests that test the module.

Place the file ```/doc/test.php``` in a place where you can run it in cli.

You might have to add execution rights to it:
```
chmod +x test.php
```
Then set up an API account in your store, IF you do it in test/staging environment you can set username and password to "plugboard". If you do it in production you should choose some other username and passwod, then change the data in test.php accordingly (```$username``` & ```$password``` variables)

To run a test you run the script with two parameters, first the domain to your site, then the test to run.
```
./test.php localhost info_v1
```
The available tests is as follows:
* info_v1
* info_v2
* info_wsi
* productlist_v2
* productlist_wsi
* productinfo_v2
* getconfig_v2
* getconfig_wsi
* productoptions_v2
* productoptions_wsi
* cart_add_v2
* control

