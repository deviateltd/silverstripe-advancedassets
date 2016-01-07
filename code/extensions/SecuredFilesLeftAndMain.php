<?php
/**
 *
 * Sample controller used for double checking once the module is installed and used. When visting /admin/assets/ or an
 * operation leads to /admin/assets/add, the current controller is neither AssetsAdmin nor CMSFileAddController.
 *
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 */
class SecuredFilesLeftAndMain extends Extension
{
    
    /**
     * 
     * @param type $dummy
     * @return void
     */
    public function init(&$dummy)
    {
        $controller = Controller::curr();
        if (get_class($controller) == 'AssetAdmin' || get_class($controller) == 'CMSFileAddController') {
            die('This module disabled usage of AssetAdmin or CMSFileAddController');
        }
    }
}
