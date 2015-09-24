<?php
/**
 * 
 * Creates a new folder on the F/S for uploading assets to, and in a secure manner by:
 * 
 * - Adding dynamically populated .htaccess and web.config files.
 * - Use of canXX() methods on both child folders and files.
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * @see {@link FileSecured} and {@link FolderSecured}.
 * @todo Modify addFolder() and initValidate() to show messages within the CMS.
 */
class SecuredAssetAdmin extends AssetAdmin implements PermissionProvider {

    /**
     *
     * @var string
     */
    private static $url_segment = 'advanced-assets';

    /**
     *
     * @var string
     */
    private static $url_rule = '/$Action/$ID';

    /**
     *
     * @var string
     */
    private static $menu_title = 'Advanced Assets';

    /**
     *
     * @var string
     */
    private static $menu_icon = "silverstripe-advancedassets/images/icons/controller-secured-asset-admin-16.png";

    /**
     *
     * @var string
     */
    private static $tree_class = 'Folder';

    /**
     *
     * @var int
     */
    private static $menu_priority = 5;

    /**
     *
     * @var array
     */
    private static $allowed_actions = array(
        "addfolder"
    );

    /**
     * 
     * @return void
     */
    public function init() {
        self::instantiate();
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
     * @return void
     */
    public static function instantiate() {
        $secured_root_folder = BASE_PATH . DIRECTORY_SEPARATOR . ASSETS_DIR . DIRECTORY_SEPARATOR . SECURED_FILES_ASSET_SUBDIR;
        if(!is_dir($secured_root_folder)) {
            FileSecured::find_or_make_secured(SECURED_FILES_ASSET_SUBDIR . DIRECTORY_SEPARATOR . "Uploads");
        }

        $resource_folder = BASE_PATH . DIRECTORY_SEPARATOR . SECURED_FILES_MODULE_DIR . DIRECTORY_SEPARATOR . 'resource';
        $default_lock_images_folder = BASE_PATH . DIRECTORY_SEPARATOR . ASSETS_DIR . DIRECTORY_SEPARATOR . '_defaultlockimages';
        if(!is_dir($default_lock_images_folder)) {
            mkdir($default_lock_images_folder, Config::inst()->get('Filesystem', 'folder_create_mask'));
            $resource_images_folder = $resource_folder . DIRECTORY_SEPARATOR . 'images';
            $dir = dir($resource_images_folder);
            while(false !== $entry = $dir->read()) {
                if($entry == '.' || $entry == '..') {
                    continue;
                }
                copy($resource_images_folder . DIRECTORY_SEPARATOR . $entry, $default_lock_images_folder . DIRECTORY_SEPARATOR . $entry);
            }
        }

        self::write_config_files($secured_root_folder, $resource_folder);
    }

    /**
     * 
     * @return SS_List
     */
    public function getList() {
        $list = parent::getList();
        $list = $list->filter("Secured", 1);
        $securedRoot = FileSecured::getSecuredRoot();
        $list = $list->exclude("ID", $securedRoot->ID);
        return $list;
    }

    /**
     * 
     * Return fake-ID "root" if no ID is found (needed to upload files into the root-folder)
     * 
     * @return number
     */
    public function currentPageID() {
        if(is_numeric($this->request->requestVar('ID'))) {
            return $this->request->requestVar('ID');
        } elseif(is_numeric($this->urlParams['ID'])) {
            return $this->urlParams['ID'];
        } elseif(Session::get("{$this->class}.currentPage")) {
            return Session::get("{$this->class}.currentPage");
        } else {
            $securedRoot = FileSecured::getSecuredRoot();
            if($securedRoot && $securedRoot->exists()) {
                return $securedRoot->ID;
            } else {
                SecuredAssetAdmin::instantiate();
                $securedRoot = FileSecured::getSecuredRoot();
                return $securedRoot->ID;
            }
        }
    }

    /**
     * 
     * @param int $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null) {
        if(!$id)
            $id = $this->currentPageID();
        $form = parent::getEditForm($id, $fields);
        $folder = ($id && is_numeric($id) && $id > 0) ? DataObject::get_by_id('Folder', $id, false) : $this->currentPage();
        $gridField = $form->Fields()->dataFieldByName('File');
        $gridField->setTitle(_t("SECUREDASSETADMIN.SecuriedFiles", "Advanced Assets"));
        $config = $gridField->getConfig();
        $columns = $config->getComponentByType('GridFieldDataColumns');
        $displayFields = $columns->getDisplayFields($gridField);
        $displayFields = array_merge(
                $displayFields, array(
            'WhoCanViewHTML' => _t('SecuredAssetAdmin.WHOCANVIEW', 'Who can view?'),
            'WhoCanEditHTML' => _t('SecuredAssetAdmin.WHOCANEDIT', 'Who can edit?'),
            'EmbargoHTML' => _t('SecuredAssetAdmin.EmbargoingStatus', 'Embargoing Status'),
            'ExpireHTML' => _t('SecuredAssetAdmin.ExpiringStatus', 'Expiring Status'),
                )
        );
        $columns->setDisplayFields($displayFields);

        $columns->setFieldCasting(array(
            'WhoCanViewHTML' => 'HTMLText->RAW',
            'WhoCanEditHTML' => 'HTMLText->RAW',
        ));

        if($id == FileSecured::getSecuredRoot()->ID) {
            $form->Fields()->removeByName("DetailsView");
            $config->removeComponentsByType("GridFieldLevelup");
        } else {
            $config->getComponentByType("GridFieldLevelup")->setLinkSpec('admin/' . self::$url_segment . '/show/%d');
        }

        $gridField->setTitle(_t("SECUREDASSETADMIN.SecuriedFiles", "Advanced Assets"));
        if($id == FileSecured::getSecuredRoot()->ID) {
            $form->Fields()->removeByName("DetailsView");
        }

        // Need to use CMSSecuredFileAddController, so update the "Upload" button.
        if($folder->canCreate()) {
            $uploadBtn = new LiteralField(
                    'UploadButton', sprintf(
                            '<a class="ss-ui-button ss-ui-action-constructive cms-panel-link"'
                            . ' data-pjax-target="Content" data-icon="drive-upload"'
                            . ' href="%s">%s</a>', 
                            Controller::join_links(singleton('CMSSecuredFileAddController')->Link(), '?ID=' . $folder->ID), 
                            _t('Folder.UploadFilesButton', 'Upload')
                    )
            );
        } else {
            $uploadBtn = null;
        }

        foreach(array("ListView", "TreeView") as $viewName) {
            $view = $form->Fields()->fieldByName("Root." . $viewName);
            foreach($view->Fields() as $f) {
                if($f instanceof CompositeField) {
                    foreach($f->FieldList() as $cf) {
                        if($cf instanceof CompositeField) {
                            $cf->removeByName("UploadButton");
                            if($uploadBtn) {
                                $cf->insertBefore($uploadBtn, "AddFolderButton");
                            }
                        }
                    }
                }
            }
        }

        return $form;
    }

    /**
     * 
     * @return SS_List
     */
    public function SiteTreeAsUL() {
        $root = FileSecured::getSecuredRoot();
        return $this->getSiteTreeFor($this->stat('tree_class'), $root->ID, 'ChildFoldersOnlySecured');
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
        foreach($itemsDefault as $item) {
            if($i !== 0) {
                $items->push($item);
            }
            $i++;
        }
        if(isset($items[0]->Title)) {
            $items[0]->Title = _t("SECUREDASSETADMIN.SecuriedFiles", SECURED_FILES_MODULE_NAME);
        }

        return $items;
    }

    /**
     * 
     * @return array
     */
    public function providePermissions() {
        $title = _t("SECUREDASSETADMIN.MENUTITLE", LeftAndMain::menu_title_for_class($this->class));
        return array(
            "CMS_ACCESS_SecuredAssetAdmin" => array(
                'name' => _t('CMSMain.ACCESS', "Access to '{title}' section", array('title' => $title)),
                'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
            )
        );
    }

    /**
     * 
     * {@inheritdoc}
     * 
     * @param SS_HTTPRequest $request
     * @return HTMLText
     */
    public function addfolder($request) {
        $parentId = SecuredFilesystem::get_numeric_identifier($this, 'ParentID');
        $folder = DataObject::get_by_id("Folder", $parentId);
        if($folder && $folder->exists()) {
            if(!$folder->Secured) {
                $message = _t('SecuredFilesystem.messages.ERROR_ACCESS_ONLY_IN_FILES');
                return SecuredFilesystem::show_access_message($this, $message);
            }

            return parent::addfolder($request);
        } else {
            $message = _t('SecuredFilesystem.messages.ERROR_FOLDER_NOT_EXISTS');
            return SecuredFilesystem::show_access_message($this, $message);
        }
    }

    /**
     * 
     * Write web-server specific config files to the module's files, assets-sub directory.
     * 
     * @param string $folderSec
     * @param string $folderRes
     * @return void
     */
    public static function write_config_files($folderSec, $folderRes) {
        // Take the dummy config files from the module's root dir, then populate and move
        if(!file_exists($folderSec . DIRECTORY_SEPARATOR . '.htaccess')) {
            $data = new ArrayData(array(
                'base' => BASE_URL ? BASE_URL : '/',
                'frameworkDir' => FRAMEWORK_DIR,
            ));

            $dotHtAaccess = $data->renderWith($folderRes . DIRECTORY_SEPARATOR . 'htaccess.ss');
            $webDotConfig = $data->renderWith($folderRes . DIRECTORY_SEPARATOR . 'webconfig.ss');

            file_put_contents($folderSec . DIRECTORY_SEPARATOR . '.htaccess', $dotHtAaccess->getValue());
            file_put_contents($folderSec . DIRECTORY_SEPARATOR . 'web.config', $webDotConfig->getValue());
        }
    }

}
