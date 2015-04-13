<?php
/**
 * Author: Normann
 * Date: 11/08/14
 * Time: 12:38 PM
 */
define("SECURED_FILES_MODULE", "advancedsecuredfiles");
if (!file_exists(BASE_PATH . DIRECTORY_SEPARATOR . SECURED_FILES_MODULE)) {
    user_error("SECURED_FILES_MODULE directory named incorrectly. This modulre requred to be installed into a folder named ".SECURED_FILES_MODULE);
}

Object::useCustomClass('AssetAdmin', 'NonSecuredAssetAdmin', true);
CMSMenu::remove_menu_item('AssetAdmin');
CMSMenu::remove_menu_item('CMSSecuredFileAddController');
CMSMenu::remove_menu_item('CMSNonSecuredFileAddController');
if(class_exists('SecureFileExtension')){
    File::remove_extension("SecureFileExtension");
}

