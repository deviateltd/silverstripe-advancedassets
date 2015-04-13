<?php
/**
 * Author: Normann
 * Date: 12/08/14
 * Time: 3:57 PM
 */

class SecuredFilesLeftAndMain extends Extension{
    function init(&$dummy) {
        $controller = Controller::curr();
        if(get_class($controller) == 'AssetAdmin' || get_class($controller) == 'CMSFileAddController'){
            die('This module disabled usage of AssetAdmin or CMSFileAddController');
        }
    }
}