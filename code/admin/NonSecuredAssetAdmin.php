<?php
/**
 *
 * Creates a new folder on the F/S for uploading assets to, but not in a secure manner. See {@Link SecuredAssetAdmin}.
 *
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * @todo Modify addFolder() and initValidate() to show messages within the CMS.
 */
class NonSecuredAssetAdmin extends AssetAdmin implements PermissionProvider
{
    
    /**
     *
     * @var int
     */
    private static $menu_priority = 5;
    
    /**
     *
     * @var string
     */
    private static $menu_icon = "silverstripe-advancedassets/images/icons/controller-non-secured-asset-admin-16.png";

    /**
     * This is called "Files" too becuase it overrides the CMS's standard {@link AssetAdmin}.
     *
     * @var string
     */
    private static $menu_title = "Files";

    /**
     *
     * @var array
     */
    private static $allowed_actions = array(
        "doSync",
        "addfolder"
    );

    /**
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        if($this->request->requestVar('ID') && is_numeric($this->request->requestVar('ID'))) {
            $this->setCurrentPageID($this->request->requestVar('ID'));
        }
        $this->initValidate();
    }

    /**
     * 
     * Intial validation of incoming CMS requests before we do anything useful.
     * 
     * @return SS_HTTPResponse
     * @todo Refactor into single static. There are v.close dupes of this in the other controllers.
     */
    public function initValidate()
    {
        $id = SecuredFilesystem::get_numeric_identifier($this, 'ID');
        if ($id) {
            $folder = DataObject::get_by_id("Folder", $id);
            if ($folder && $folder->exists()) {
                if ($folder->Secured) {
                    $message = _t('SecuredFilesystem.messages.ERROR_ACCESS_ONLY_IN_SECURED_FILES');
                    return SecuredFilesystem::show_access_message($this, $message);
                }
            } else {
                $message = _t('SecuredFilesystem.messages.ERROR_FOLDER_NOT_EXISTS');
                return SecuredFilesystem::show_access_message($this, $message);
            }
        }
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        $title = _t('NonSecuredAssetAdmin.labels.CMS_PERMISSIONS_LABEL_FILES_ADVANCED', 'Files (Advanced)');
        return array(
            "CMS_ACCESS_NonSecuredAssetAdmin" => array(
                'name' => _t('CMSMain.ACCESS', "Access to '{title}' section", array('title' => $title)),
                'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
            )
        );
    }

    /**
     * 
     * @return SS_List
     */
    public function getList()
    {
        $list = parent::getList();
        $list = $list->exclude("Secured", "1");
        $this->extend('updateList', $list);
        return $list;
    }

    /**
     * 
     * @return SS_List
     */
    public function SiteTreeAsUL()
    {
        $childrenMethod = 'ChildFoldersExcludeSecured';
        $this->extend('updateChildrenMethod', $childrenMethod);
        $tree = $this->getSiteTreeFor($this->stat('tree_class'), null, $childrenMethod);
        return $tree;
    }

    /**
     * @param boolean $unlinked
     * @return array
     */
    public function Breadcrumbs($unlinked = false)
    {
        $items = parent::Breadcrumbs($unlinked);
        if (isset($items[0]->Title)) {
            $items[0]->Link = Controller::join_links(singleton('NonSecuredAssetAdmin')->Link('show'), 0);
        }
        return $items;
    }
    
    /**
     * Can be queried with an ajax request to trigger the filesystem sync. It returns a FormResponse status message
     * to display in the CMS
     * 
     * @return null
     */
    public function doSync()
    {
        $message = SecuredFilesystem::sync_secured();
        $this->response->addHeader('X-Status', rawurlencode($message));

        return;
    }

    /**
     * 
     * {@inheritdoc}
     * 
     * @param SS_HTTPRequest $request
     * @return HTMLText
     */
    public function addfolder($request)
    {
        $parentId = SecuredFilesystem::get_numeric_identifier($this, 'ParentID');
        $folder = DataObject::get_by_id("Folder", $parentId);
        if ($folder && $folder->exists()) {
            if ($folder->Secured) {
                $message = _t('SecuredFilesystem.messages.ERROR_ACCESS_ONLY_IN_SECURED_FILES');
                return SecuredFilesystem::show_access_message($this, $message);
            }
            
            return parent::addfolder($request);
        } else {
            $message = _t('SecuredFilesystem.messages.ERROR_FOLDER_NOT_EXISTS');
            return SecuredFilesystem::show_access_message($this, $message);
        }
    }
}
