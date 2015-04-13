<?php
/**
 * Author: Normann
 * Date: 3/11/2014
 * Time: 12:40 PM
 */

class SecuredFilesContentController extends Extension {
    function onAfterInit(){
        Requirements::javascript(SECURED_FILES_MODULE."/javascript/ContentControllerContainingSecuredFiles.js");
    }
}