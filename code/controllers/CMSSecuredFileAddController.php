<?php
/**
 *
 * Specific controller used to add files to the advanced-assets CMS UI, that are secured.
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * @todo Modify addFolder() and initValidate() to show messages within the CMS.
 */
class CMSSecuredFileAddController extends CMSFileAddController {
    
    private static $url_segment = 'assets-secured/add';
    private static $url_priority = 65;
    private static $required_permission_codes = 'CMS_ACCESS_SecuredAssetAdmin';
    private static $menu_title = 'Advanced Assets';
    private static $tree_class = 'Folder';

    public function init(){
        parent::init();
        $this->initValidate();
    }

    /**
     * 
     * Intial validation of incoming CMS requests before we do anything useful.
     * 
     * @return SS_HTTPResponse
     * @todo Refactor into single static. There are v.close dupes of this in the other controllers.
     */
    public function initValidate() {
        $folderId = SecuredFilesystem::get_numeric_identifier($this, 'ID');
        if($folderId) {
            $folder = DataObject::get_by_id("Folder", $folderId);
            if($folder && $folder->exists()) {
                if(!$folder->Secured) {
                    $message = _t('SecuredFilesystem.messages.ERROR_ACCESS_ONLY_IN_FILES');
                    return SecuredFilesystem::show_access_message($this, $message);
                }
            } else {
                $message = _t('SecuredFilesystem.messages.ERROR_FOLDER_NOT_EXISTS');
                return SecuredFilesystem::show_access_message($this, $message);
            }
        }
    }

    /**
     * 
     * Custom currentPage() method to handle opening the 'root' folder.
     * 
     * @return Folder
     */
    public function currentPage() {
        $id = $this->currentPageID();
        if($id && is_numeric($id) && $id > 0) {
            $folder = DataObject::get_by_id('Folder', $id);
            if($folder && $folder->exists()) {
                return $folder;
            }
        } else {
            SecuredAssetAdmin::instantiate();
            $root = FileSecured::getSecuredRoot();
            if($root && $root->exists()) return $root;
            else return new Folder(array("Secured"=>true));
        }
    }

    /**
     * 
     * Return fake-ID "root" if no ID is found (needed to upload files into the root-folder)
     * 
     * @return mixed (string | number)
     */
    public function currentPageID() {
        if(is_numeric($this->request->requestVar('ID')))	{
            return $this->request->requestVar('ID');
        } elseif (is_numeric($this->urlParams['ID'])) {
            return $this->urlParams['ID'];
        } elseif(Session::get("{$this->class}.currentPage")) {
            return Session::get("{$this->class}.currentPage");
        } else {
            $securedRoot = FileSecured::getSecuredRoot();
            if($securedRoot && $securedRoot->exists()) return $securedRoot->ID;
            else {
                SecuredAssetAdmin::instantiate();
                $securedRoot = FileSecured::getSecuredRoot();
                return $securedRoot->ID;
            }
        }
    }

    /**
     * 
     * @param number $id Not used.
     * @param FieldList $fields Not used.
     * @return Form
     * @todo what template is used here? AssetAdmin_UploadContent.ss doesn't seem to be used anymore
     */
    public function getEditForm($id = null, $fields = null) {
        $form = parent::getEditForm($id, $fields);
        $folder = $this->currentPage();
        $backLink = LiteralField::create(
            'BackLink',
            sprintf(
                '<a href="%s" class="backlink ss-ui-button cms-panel-link" data-icon="back">%s</a>',
                Controller::join_links(singleton('SecuredAssetAdmin')->Link('show'),  $folder->ID),
                _t('AssetAdmin.BackToFolder', 'Back to folder')
            )
        );
        $fields = $form->Fields();
        $fields->removeByName("BackLink");
        $fields->push($backLink);
        
        return $form;
    }

    /**
     * 
     * @param boolean $unlinked
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false) {
        $itemsDefault = parent::Breadcrumbs($unlinked);
        $items = new ArrayList();
        $i = 0;
        $originalLink = singleton('AssetAdmin')->Link('show');
        $changedLink = singleton('SecuredAssetAdmin')->Link('show');
        foreach($itemsDefault as $item) {
            if($i!==0){
                $item->Link = str_replace($originalLink, $changedLink, $item->Link);
                $items->push($item);
            }
            $i++;
        }
        if(isset($items[0]->Title)){
            $items[0]->Title = _t("SECUREDASSETADMIN.SecuriedFiles", "Advanced Assets");
        }
        return $items;
    }
}
