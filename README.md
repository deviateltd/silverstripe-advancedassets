# SilverStripe Advanced Assets

Provides an additional Files admin area in the CMS in parallel with the CMS' standard
Files admin area. It allows for some "advanced" file and folder features as follows:

## Features

 1. Individual permissions to files and folders using the CMS' standard security system
 2. Embargo and expiry on individual files and folders
 3. Individual access to the Advanced Assets CMS admin
 4. Integrates with the [Subsites module](http://addons.silverstripe.org/add-ons/silverstripe/subsites)

Note: 1. and 2. (above) need to be explicitly enabled. See the "Options" section below.

## Compatibility

If installed alongside the standard "Secured Files" module, the latter's features and access
will be disabled.

## Options

The module's features have been broadly broken into 2 components; Embargo / Expiry and Security.
Each of these components are able to be individually enabled or disabled via the standard SilverStripe
YML configuration system and are _disabled_ by default. To enable them:

    AdvancedAssetsFilesSiteConfig:
      component_security_enabled: true
      component_embargoexpiry_enabled: true

## Installation

  1) Git Clone


    #> git clone https://github.com/deviateltd/silverstripe-advancedassets.git

  2) Composer command


    composer require deviateltd/silverstripe-advancedassets dev-master

  3) Composer (Manual)

Edit your project's `composer.json` as follows:

Add a new line under the "require" block:


    deviateltd/silverstripe-advancedassets

Add a new block under the "repositories" block:


      {
       "type": "vcs",
       "url": "https://github.com/deviateltd/silverstripe-advancedassets.git"
      }

Now run `dev/build` via the browser or command line - and don't forget to flush.

## Iconography

Component icons courtesy [thenounproject.com](http://www.thenounproject.com) licensed
under the [Creative Commons 3.0](https://creativecommons.org/licenses/by/3.0/us/).