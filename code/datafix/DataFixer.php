<?php
/**
 * Author: Normann
 * Date: 30/10/2014
 * Time: 6:22 PM
 */

class DataFixer extends Controller{
    private static $allowed_actions = array(
        'securedRootDefaultValueFix' => "ADMIN",
        'removeDefaultLockImagesFromSecuredArea' => 'ADMIN'
    );

    function securedRootDefaultValueFix(){
        $root = FileSecured::getSecuredRoot();
        if($root && $root->exists()){
            $root->CanViewType="Anyone";
            $root->CanEditType="LoggedInUsers";
            $root->write();
        }
    }

    function removeDefaultLockImagesFromSecuredArea(){
        $secured_root_folder = BASE_PATH . DIRECTORY_SEPARATOR .ASSETS_DIR . DIRECTORY_SEPARATOR . "_securedfiles";
        $folder_to_remove = $secured_root_folder . DIRECTORY_SEPARATOR . "_defaultlockimages";
        if(is_dir($folder_to_remove)){
            $dir = dir($folder_to_remove);
            while(false !== $entry = $dir->read()){
                // Skip pointers
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                unlink($folder_to_remove.DIRECTORY_SEPARATOR . $entry);
            }
            rmdir($folder_to_remove);
        }
    }
}