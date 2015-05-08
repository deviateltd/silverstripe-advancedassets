<?php
/**
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 */

define("SECURED_FILES_MODULE_NAME", "Advanced Assets");
define("SECURED_FILES_MODULE_DIR", "silverstripe-advancedassets");

if(!file_exists(BASE_PATH . DIRECTORY_SEPARATOR . SECURED_FILES_MODULE_DIR)) {
    $msg = "Module directory seems to be named incorrectly."
            . " The " . SECURED_FILES_MODULE_NAME . " module"
            . " should be installed into a folder named " . SECURED_FILES_MODULE_DIR;
    user_error($msg);
}

// Overload use of default {@link AssetAdmin} with the module's own {@link NonSecuredAssetAdmin}.
Object::useCustomClass('AssetAdmin', 'NonSecuredAssetAdmin', true);

CMSMenu::remove_menu_item('AssetAdmin');
CMSMenu::remove_menu_item('CMSSecuredFileAddController');
CMSMenu::remove_menu_item('CMSNonSecuredFileAddController');

// "Obliterate" the securedfiles' SecureFileExtension class.
if(class_exists('SecureFileExtension')){
    File::remove_extension("SecureFileExtension");
}
