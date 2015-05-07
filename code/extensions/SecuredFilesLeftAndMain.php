<?php
/**
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 */
class SecuredFilesLeftAndMain extends Extension {
    
    public function init(&$dummy) {
        $controller = Controller::curr();
        if(get_class($controller) == 'AssetAdmin' || get_class($controller) == 'CMSFileAddController') {
            die('This module disabled usage of AssetAdmin or CMSFileAddController');
        }
    }
}
