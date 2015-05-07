<?php
/**
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 */
class SecuredFilesContentController extends Extension {
    
    public function onAfterInit() {
        Requirements::javascript(SECURED_FILES_MODULE_DIR . "/javascript/ContentControllerContainingSecuredFiles.js");
    }
}
