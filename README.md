# SilverStripe Advanced Assets

Provides a secure Files admin area in the CMS in parallel with the CMS' standard
Files admin area.

## Features

 * Allows fine-grained access to files and folders using the CMS' standard security system
 * Allows individual access to the SecuredFiles CMS admin
 * Allows for embargo and expiry on individual files and folders
 * Integrates with the [Subsites module](http://addons.silverstripe.org/add-ons/silverstripe/subsites)

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

## Usage

TODO
