<?php
/**
 * Author: Normann
 * Date: 12/08/14
 * Time: 9:50 AM
 */

class NonSecuredAssetAdmin extends AssetAdmin{
    private static $menu_priority = 5;

    private static $allowed_actions = array(
        "doSync",
        "addfolder",
    );

    public function init(){
        parent::init();
        $this->initValidate();
    }

    public function initValidate() {
        $id = $this->urlParams['ID'];
        if($id && is_numeric($id) && $id !== 0){
            $folder = DataObject::get_by_id("Folder", $id);
            if($folder && $folder->exists()){
                if($folder->Secured){
                    die('not found');
                }
            }else{
                die('not found');
            }
        }
    }

    public function getList(){
        $list = parent::getList();
        $list = $list->exclude("Secured", "1");
        return $list;
    }

    public function SiteTreeAsUL() {
        return $this->getSiteTreeFor($this->stat('tree_class'), null, 'ChildFoldersExcludeSecured');
    }

    public function Breadcrumbs($unlinked = false) {
        $items = parent::Breadcrumbs($unlinked);
        if(isset($items[0]->Title)){
            $items[0]->Link = Controller::join_links(singleton('NonSecuredAssetAdmin')->Link('show'), 0);
        }
        return $items;
    }
    /**
     * Can be queried with an ajax request to trigger the filesystem sync. It returns a FormResponse status message
     * to display in the CMS
     */
    public function doSync() {
        $message = SecuredFilesystem::sync_secured();
        $this->response->addHeader('X-Status', rawurlencode($message));

        return;
    }

    public function addfolder($request){
        if(isset($_GET['ParentID'])&& is_numeric($_GET['ParentID']) && $_GET['ParentID'] ){
            $folder = DataObject::get_by_id("Folder", $_GET['ParentID']);
            if($folder && $folder->exists()){
                if($folder->Secured){
                    die('not found');
                }
            }else{
                die('not found');
            }
        }
        return parent::addfolder($request);
    }
}