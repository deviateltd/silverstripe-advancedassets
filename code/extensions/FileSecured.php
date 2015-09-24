<?php
/**
 * 
 * Extends {@link File} "transforming" it into an (optionally) secure object with
 * related canXX() methods.
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * @todo How many of the "cloned" methods/props from {@link File} are actually needed?
 * @todo Refactor canXX() methods to use bitwise logic to make checks far less fallible
 */
class FileSecured extends DataExtension implements PermissionProvider {
    
    private static $db = array(
        "Secured" => "Boolean",
        "CanViewType" => "Enum('Anyone,LoggedInUsers,OnlyTheseUsers,Inherit', 'Inherit')",
        "CanEditType" => "Enum('LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
        "EmbargoType" => "Enum('None,Indefinitely,UntilAFixedDate', 'None)",
        "EmbargoedUntilDate" => 'SS_DateTime',
        "ExpiryType" => "Enum('None,AtAFixedDate', 'None)",
        "ExpireAtDate" => 'SS_DateTime'
    );

    private static $many_many = array(
        "ViewerGroups" => "Group",
        "EditorGroups" => "Group",
    );

    /**
     * Cache for canView/Edit/Create/Delete permissions.
     * Keyed by permission type (e.g. 'edit'), with an array
     * of IDs mapped to their boolean permission ability (true=allow, false=deny).
     * See {@link batch_permission_check()} for details.
     * 
     * @var array
     */
    private static $cache_permissions = array();
    
    /**
     * 
     * @return string
     */
    private function showButtonsSecurity() {
        $buttons = 
            '<li class="ss-ui-button" data-panel="whocanview">Who Can View</li>' .
            '<li class="ss-ui-button" data-panel="whocanedit">Who Can Edit</li>';

        $componentEnabled = AdvancedAssetsFilesSiteConfig::is_security_enabled();
        
        return $componentEnabled ? $buttons : '';
    }
    
    /**
     * 
     * @return string
     */
    private function showButtonsEmbargoexpiry() {
        $buttons =
            '<li class="ss-ui-button" data-panel="embargo">Embargo</li>' .
            '<li class="ss-ui-button" data-panel="expiry">Expiry</li>';
        
        $componentEnabled = AdvancedAssetsFilesSiteConfig::is_embargoexpiry_enabled();
        
        return $componentEnabled ? $buttons : '';
    }

    /**
     * 
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields) {
        $controller = Controller::curr();
        if($controller instanceof SecuredAssetAdmin || $controller instanceof CMSSecuredFileAddController) {
            Requirements::combine_files(
                'securedassetsadmincmsfields.js',
                    array(
                        SECURED_FILES_MODULE_DIR . '/thirdparty/javascript/jquery-ui/timepicker/jquery-ui-sliderAccess.js',
                        SECURED_FILES_MODULE_DIR . '/thirdparty/javascript/jquery-ui/timepicker/jquery-ui-timepicker-addon.min.js',
                        SECURED_FILES_MODULE_DIR . "/javascript/SecuredFilesLeftAndMain.js",
                    )
            );
            
            Requirements::css(SECURED_FILES_MODULE_DIR . '/thirdparty/javascript/jquery-ui/timepicker/jquery-ui-timepicker-addon.min.css');
            Requirements::css(SECURED_FILES_MODULE_DIR . "/css/SecuredFilesLeftAndMain.css");
            Requirements::javascript(SECURED_FILES_MODULE_DIR . "/javascript/SecuredFilesLeftAndMain.js");

            if($this->isFile()) {
                $buttonsSecurity = $this->showButtonsSecurity();
                $buttonsEmbargoExpiry = $this->showButtonsEmbargoExpiry();
            
                // Embargo field
                $embargoTypeField = new OptionSetField(
                    "EmbargoType", "",
                    array(
                        "None" =>  _t('AdvancedSecuredFiles.NONENICE', "None"),
                        "Indefinitely" => _t('AdvancedSecuredFiles.INDEFINITELYNICE', "Hide document indefinitely"),
                        "UntilAFixedDate" => _t('AdvancedSecuredFiles.UNTILAFIXEDDATENICE', 'Hide until set date')
                    )
                );
                $embargoUntilDateField = DatetimeField::create('EmbargoedUntilDate','');
                $embargoUntilDateField->getDateField()->setConfig('showcalendar', true)
                    ->setConfig('dateformat', 'dd-MM-yyyy')->setConfig('datavalueformat', 'dd-MM-yyyy')
                    ->setAttribute('readonly', true);
                $embargoUntilDateField->getTimeField()->setAttribute('readonly', true);
                
                // Expiry field
                $expireTypeField = new OptionSetField(
                    "ExpiryType", "",
                    array(
                        "None" =>  _t('AdvancedSecuredFiles.NONENICE', "None"),
                        "AtAFixedDate" => _t('AdvancedSecuredFiles.ATAFIXEDDATENICE', 'Set file to expire on')
                    )
                );
                $expiryDatetime = DatetimeField::create('ExpireAtDate','');
                $expiryDatetime->getDateField()->setConfig('showcalendar', true)
                    ->setConfig('dateformat', 'dd-MM-yyyy')->setConfig('datavalueformat', 'dd-MM-yyyy')
                    ->setAttribute('readonly', true);
                $expiryDatetime->getTimeField()->setAttribute('readonly', true);
                
                $securitySettingsGroup = FieldGroup::create(
                    FieldGroup::create(
                        $embargoTypeField,
                        $embargoUntilDateField
                    )->addExtraClass('embargo option-change-datetime')->setName("EmbargoGroupField"),
                    FieldGroup::create(
                        $expireTypeField,
                        $expiryDatetime
                    )->addExtraClass('expiry option-change-datetime')->setName("ExpiryGroupField")
                );
            } else {
                $buttonsSecurity = $this->showButtonsSecurity();
                $buttonsEmbargoExpiry = '';
                $securitySettingsGroup = FieldGroup::create();
            }

            $canViewTypeField = new OptionSetField(
                "CanViewType","",
                array(
                    "Inherit"=>_t('AdvancedSecuredFiles.INHERIT', "Inherit from parent folder"),
                    "Anyone"=> _t('SiteTree.ACCESSANYONE', 'Anyone'),
                    "LoggedInUsers"=>_t('SiteTree.ACCESSLOGGEDIN', 'Logged-in users'),
                    "OnlyTheseUsers"=> _t('SiteTree.ACCESSONLYTHESE', 'Only these people (choose from list)'),
                )
            );

            $canEditTypeField = new OptionSetField(
                "CanEditType", "",
                array(
                    "Inherit" =>_t('AdvancedSecuredFiles.INHERIT', "Inherit from parent folder"),
                    "LoggedInUsers"=>_t('SiteTree.ACCESSLOGGEDIN', 'Logged-in users'),
                    "OnlyTheseUsers"=> _t('SiteTree.ACCESSONLYTHESE', 'Only these people (choose from list)'),
                )
            );

            $groupsMap = array();
            foreach(Group::get() as $group) {
                // Listboxfield values are escaped, use ASCII char instead of &raquo;
                $groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
            }
            asort($groupsMap);
            
            $viewerGroupsField = ListboxField::create("ViewerGroups", _t('AdvancedSecuredFiles.VIEWERGROUPS', "Viewer Groups"))
                ->setMultiple(true)
                ->setSource($groupsMap)
                ->setAttribute(
                    'data-placeholder',
                    _t('AdvancedSecuredFiles.GroupPlaceholder', 'Click to select group')
                );
            
            $editorGroupsField = ListBoxField::create("EditorGroups", _t('AdvancedSecuredFiles.EDITORGROUPS', "Editor Groups"))
                ->setMultiple(true)
                ->setSource($groupsMap)
                ->setAttribute(
                    'data-placeholder',
                    _t('AdvancedSecuredFiles.GroupPlaceholder', 'Click to select group')
                );

            $securitySettingsGroup->push(
                FieldGroup::create(
                    $canViewTypeField,
                    $viewerGroupsField
                )->addExtraClass('whocanview option-change-listbox')->setName("CanViewGroupField")
            );

            $securitySettingsGroup->push(
                FieldGroup::create(
                    $canEditTypeField,
                    $editorGroupsField
                )->addExtraClass('whocanedit option-change-listbox')->setName("CanEditGroupField")
            );

            $securitySettingsGroup->setName("SecuritySettingsGroupField")->addExtraClass('security-settings');

            $showAdvanced = (
                AdvancedAssetsFilesSiteConfig::is_security_enabled() ||
                ($this->isFile() && AdvancedAssetsFilesSiteConfig::is_embargoexpiry_enabled())
            );
            if($showAdvanced) {
                $fields->insertAfter(LiteralField::create(
                        'BottomTaskSelection', 
                        $this->owner->renderWith('componentField', ArrayData::create(array(
                            'ComponentSecurity' => AdvancedAssetsFilesSiteConfig::component_cms_icon('security'),
                            'ComponentEmbargoExpiry' => AdvancedAssetsFilesSiteConfig::component_cms_icon('embargoexpiry'),
                            'ButtonsSecurity' => $buttonsSecurity,
                            'ButtonsEmbargoExpiry' => $buttonsEmbargoExpiry
                        )))
                    ),
                    "ParentID"
                );
                $fields->insertAfter($securitySettingsGroup, "BottomTaskSelection");
            }
        }

        if(!is_a($this->owner,"Folder") && is_a($this->owner, "File")) {
            $parentIDField = $fields->dataFieldByName("ParentID");
            if($controller instanceof SecuredAssetAdmin) {
                $securedRoot = FileSecured::getSecuredRoot();
                $parentIDField-> setTreeBaseID($securedRoot->ID);
                $parentIDField->setFilterFunction(create_function('$node', "return \$node->Secured == 1;"));
            } else {
                $parentIDField->setFilterFunction(create_function('$node', "return \$node->Secured == 0;"));
            }

            // SilverStripe core has a bug for search function now, so disable it for now.
            $parentIDField->setShowSearch(false);
        }
    }

    /**
     * 
     * General catch-all for {@link {$this->canViewFrontByTime()} and {@link {$this->canViewFrontByUser()}.
     * 
     * @param Member $member
     * @return boolean
     */
    public function canViewFront($member = null) {
        if(!$this->canViewFrontByTime()) {
            return false;
        }
        if(!$this->canViewFrontByUser($member)) {
            return false;
        }
        
        return true;
    }

    /**
     * Returns if this is Document is embargoed or expired.
     * Also, returns if the document should be displayed on the front-end. Respecting the current reading mode
     * of the site and the embargo status.
     * I.e. if a document is embargoed until published, then it should still show up in draft mode.
     * 
     * @return boolean True or False depending on whether this document is embargoed
     */
    public function canViewFrontByTime() {
        $canViewFrontByTime = !$this->isEmbargoed() && !$this->isExpired();
        return $canViewFrontByTime;
    }

    /**
     * 
     * @param Member $member
     * @return boolean
     */
    public function canViewFrontByUser($member = null) {
        //If admin, bypass anyway
        if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
            $member = Member::currentUser();
        }
        
        if($member && Permission::checkMember($member, array("ADMIN", "SECURED_FILES_VIEW_ALL"))) {
            return true;
        }
        
        // check different CanViewType accordingly
        if(!$this->owner->CanViewType || $this->owner->CanViewType == 'Anyone') { // check for empty spec
            return true;
        } else if($this->owner->CanViewType == "LoggedInUsers") {
            return $member && $member->exists();
        } else if($this->owner->CanViewType == "OnlyTheseUsers") {
            return $member && $member->exists() && $member->inGroups($this->owner->ViewerGroups());
        } else if($this->owner->CanViewType == "Inherit") {
            $folder = Folder::get_by_id("Folder", $this->owner->ParentID);
            if($folder && $folder->exists()) {
                return $folder->canViewFrontByUser($member);
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Is this Document embargoed?
     * 
     * @return boolean
     */
    public function isEmbargoed() {
        $embargoUntilFixedDate = $this->owner->EmbargoType == 'UntilAFixedDate';
        $embargo = (
            !empty($this->owner->EmbargoedUntilDate) && 
            SS_Datetime::now()->Value < $this->owner->EmbargoedUntilDate
        );
        
        if($this->owner->EmbargoType == 'Indefinitely') {
            return true;
        }
        if($embargoUntilFixedDate && $embargo) {
            return true;
        }
        
        return false;
    }

    /**
     * Is this an expired Document?
     * 
     * @return boolean
     */
    public function isExpired() {
        $expireAtFixedDate = $this->owner->ExpiryType == 'AtAFixedDate';
        $expiredDate = (
            !empty($this->owner->ExpireAtDate) && 
            SS_Datetime::now()->Value >= $this->owner->ExpireAtDate
        );
        
        if($expireAtFixedDate && $expiredDate) {
            return true;
        }
        return false;
    }

    /**
     * Find the given folder or create it both as {@link Folder} database record
     * and on the filesystem. If necessary, creates parent folders as well. If it's
     * unable to find or make the folder, it will return null (as /assets is unable
     * to be represented by a Folder {@link DataObject}).
     *
     * @param string $folderPath    Absolute or relative path to the file.
     *                              If path is relative, it's interpreted relative 
     *                              to the "assets/" directory.
     * @return Folder | null
     */
    public static function find_or_make_secured($folderPath) {
        // Create assets directory, if it is missing
        if(!file_exists(ASSETS_PATH)) Filesystem::makeFolder(ASSETS_PATH);

        $folderPath = trim(Director::makeRelative($folderPath));
        // replace leading and trailing slashes
        $folderPath = preg_replace('/^\/?(.*)\/?$/', '$1', $folderPath);
        $parts = explode("/",$folderPath);

        $parentID = 0;
        $item = null;
        $filter = FileNameFilter::create();
        foreach($parts as $part) {
            if(!$part) {
                continue; // happens for paths with a trailing slash
            }

            // Ensure search includes folders with illegal characters removed, but
            // err in favour of matching existing folders if $folderPath
            // includes illegal characters itself.
            $partSafe = $filter->filter($part);
            $item = Folder::get()->filter(array(
                'ParentID' => $parentID,
                'Name' => array($partSafe, $part),
                'CanViewType' => 'Anyone',
                'CanEditType' => 'LoggedInUsers',
            ))->first();

            if(!$item) {
                $item = new Folder();
                $item->ParentID = $parentID;
                $item->Name = $partSafe;
                $item->Title = $part;
                $item->Secured = true;
                $item->write();
                // when initial the secured root folder, set its CanViewType to be
                if(!$parentID) {
                    $item->CanViewType = "Anyone";
                }
            }
            if(!file_exists($item->getFullPath())) {
                Filesystem::makeFolder($item->getFullPath());
            }
            $parentID = $item->ID;
        }

        return $item;
    }

    /**
     * Gets us the module's root directory. Because this is created on-the-fly, then we can never accurately know
     * its ID.
     *
     * @return Folder
     */
    public static function getSecuredRoot() {
        return DataObject::get_one("File", "\"ParentID\" = '0' AND \"Secured\" = '1'");
    }

    /**
     * 
     * @return void
     */
    public function onBeforeWrite() {
        if($this->owner->ParentID) {
            $folder = DataObject::get_by_id("Folder", $this->owner->ParentID);
            if($folder && $folder->exists() && $folder->Secured) {
                $this->owner->Secured = true;
            }
        }
        
        if($this->owner->EmbargoType != 'UntilAFixedDate') {
            $this->owner->EmbargoedUntilDate = null;
        }

        if($this->owner->ExpiryType != 'AtAFixedDate') {
            $this->owner->ExpireAtDate = null;
        }
    }

    /**
     * 
     * @return void
     */
    public function onAfterWrite() {
        if($this->owner->CanViewType != 'OnlyTheseUsers') {
            $viewerGroups = $this->owner->ViewerGroups();
            if($viewerGroups && $viewerGroups->exists()) {
                    $viewerGroups->removeAll();
            }
        }
        if($this->owner->CanEditType != 'OnlyTheseUsers') {
            $editorGroups = $this->owner->EditorGroups();
            if($editorGroups && $editorGroups->exists()) {
                $editorGroups->removeAll();
            }
        }
    }

    /**
     * 
     * @return SS_List
     */
    public function ChildFoldersExcludeSecured() {
        $folders = $this->owner->ChildFolders();
        $folders = $folders->exclude("Secured", "1");
        return $folders;
    }

    /**
     * 
     * @return SS_List
     */
    public function ChildFoldersOnlySecured() {
        $folders = $this->owner->ChildFolders();
        $folders = $folders->filter("Secured", "1");
        return $folders;
    }
    
    /**
     * 
     * @return null | string
     */
    public function getWhoCanViewHTML() {
        if(!$this->owner->Secured) {
            return;
        }
        
        switch($this->owner->CanViewType) {
            case 'Anyone':
            case 'LoggedInUsers':
            case 'OnlyTheseUsers':
                $ret = $this->getWhoCanViewNice();
                break;
            default: //case of "Inherit"
                $ret = "Inherited as: " . $this->getWhoCanViewNice();
        }
        
        return $ret;
    }

    /**
     * 
     * @return string
     */
    public function getWhoCanViewNice() {
        switch($this->owner->CanViewType) {
            case 'Anyone':
                $ret = _t("FileSecured.Anyone", 'Anyone');
                break;
            case 'LoggedInUsers':
                $ret = _t("FileSecured.LoggedInUsers", 'Anyone who can login to the site');
                break;
            case 'OnlyTheseUsers':
                $theseUsers = $this->owner->ViewerGroups();
                if($theseUsers && $theseUsers->exists()) {
                    $theseUsersNames = array();
                    foreach ($theseUsers as $user) {
                        $theseUsersNames[] = "<b>'".Convert::raw2xml($user->Title)."'</b>";
                    }
                    $ret = "Only " .implode(", ", $theseUsersNames);
                } else {
                    $ret = "No user group specified";
                }
                break;
            default:
                $parent = Folder::get_by_id("Folder", $this->owner->ParentID);
                if($parent && $parent->exists()) {
                    $ret = $parent->getWhoCanViewNice();
                } else {
                    $ret = _t("FileSecured.Anyone", "Anyone");
                }
        }
        return $ret;
    }

    /**
     * 
     * @return null | string
     */
    public function getWhoCanEditHTML() {
        if(!$this->owner->Secured) {
            return;
        }
        
        switch($this->owner->CanEditType) {
            case 'LoggedInUsers':
            case 'OnlyTheseUsers':
                $ret = $this->getWhoCanEditNice();
                break;
            default: //case of "Inherit"
                $ret = "Inherited as: " . $this->getWhoCanEditNice();
        }
        
        return $ret;
    }
    
    /**
     * 
     * @return string
     */
    public function getWhoCanEditNice() {
        switch($this->owner->CanEditType) {
            case 'LoggedInUsers':
                $ret = _t("FileSecured.LoggedInUsers", 'Anyone who can login to the site');
                break;
            case 'OnlyTheseUsers':
                $theseUsers = $this->owner->EditorGroups();
                if($theseUsers && $theseUsers->exists()) {
                    $theseUsersNames = array();
                    foreach ($theseUsers as $user) {
                        $theseUsersNames[] = "<b>'".Convert::raw2xml($user->Title)."'</b>";
                    }
                    $ret = "Only " .implode(", ", $theseUsersNames);
                } else {
                    $ret = _t("FileSecured.NoGroupSpecified", "No user group specified");
                }
                break;
            default:
                $parent = Folder::get_by_id("Folder", $this->owner->ParentID);
                if($parent && $parent->exists()) {
                    $ret = $parent->getWhoCanEditNice();
                } else {
                    $ret = _t("FileSecured.Anyone", "Anyone");
                }
        }
        
        return $ret;
    }

    /**
     * 
     * @return string
     */
    public function getEmbargoHTML() {
        if(!$this->owner->Secured) return;
        if($this->owner instanceof Folder) {
            $ret = "N/A";
        } else {
            switch($this->owner->EmbargoType) {
                case 'Indefinitely':
                    $ret = "Embargoed forever";
                    break;
                case 'UntilAFixedDate':
                    if($embargoDate =  $this->owner->EmbargoedUntilDate) {
                        $datetime = new SS_Datetime();
                        $datetime->setValue($embargoDate);
                        $now = $today = date('Y-m-d H:i:s');
                        if($embargoDate > $now) {
                            $embargo = _t("FileSecured.EmbargoedUntil", "Embargoed, till");
                        } else {
                            $embargo = _t("FileSecured.EmbargoedNotUntil", "Not embargoed now, once embargoed until ");
                        }
                        $time = Time::create();
                        $time->setValue($datetime->Time());
                        $date = Date::create();
                        $date->setValue($datetime->Date());
                        $ret = $embargo.$time->Nice().", ".$date->Long();
                    } else {
                        $ret = _t("FileSecured.EmbargoedNoDateSetNotEmbargoed", "No embargoing date/time is set, so treated as not embargoed");
                    }
                    break;
                default: //case 'None'
                    $ret = _t("FileSecured.EmbargoedNot", "Not embargoed");
            }
        }
        return $ret;
    }

    /**
     * 
     * @return string
     */
    public function getExpireHTML() {
        if(!$this->owner->Secured) {
            return;
        }
        
        if($this->owner instanceof Folder) {
            $ret = "N/A";
        } else {
            switch($this->owner->ExpiryType) {
                case 'AtAFixedDate':
                    if($expireDate =  $this->owner->ExpireAtDate) {

                        $datetime = new SS_Datetime();
                        $datetime->setValue($expireDate);
                        $now = $today = date('Y-m-d H:i:s');
                        if($expireDate > $now) {
                            $expire = _t("FileSecured.EmbargoedNotExpired", "Not expired, will expire ");
                        } else {
                            $expire = _t("FileSecured.EmbargoedExpired", "Expired ");
                        }
                        $time = Time::create();
                        $time->setValue($datetime->Time());
                        $date = Date::create();
                        $date->setValue($datetime->Date());
                        $ret = $expire." at ".$time->Nice().", ".$date->Long();
                    } else {
                        $ret = _t("FileSecured.EmbargoedNoDateSetNotExpired", "No embargoing date/time is set, so treated as not expired");
                    }
                    break;
                default: //case 'None':
                    $ret = "Not expired";
            }
        }
        return $ret;
    }

    /**
     * 
     * @return array
     */
    public function providePermissions() {
        //Access to '{title}' section
        return array(
            'SECURED_FILES_VIEW_ALL' => array(
                'name' => _t('SecuredFiles.VIEW_ALL_DESCRIPTION', 'View any secured file'),
                'category' => _t('Permissions.SECUREDFILES_CATEGORY', SECURED_FILES_MODULE_NAME . ' permissions'),
                'sort' => -100,
                'help' => _t('SecuredFiles.VIEW_ALL_HELP', 'Ability to view any advanced file, regardless of the settings on the "Who can view" tab. Requires the "Access to \'' . SECURED_FILES_MODULE_NAME . '\' section" permission')
            ),
            'SECURED_FILES_EDIT_ALL' => array(
                'name' => _t('SecuredFiles.EDIT_ALL_DESCRIPTION', 'Edit any secured file'),
                'category' => _t('Permissions.SECUREDFILES_CATEGORY', SECURED_FILES_MODULE_NAME . ' permissions'),
                'sort' => -50,
                'help' => _t('SecuredFiles.EDIT_ALL_HELP', 'Ability to edit any advanced file, regardless of the settings on the "Who can edit" tab.  Requires the "Access to \'' . SECURED_FILES_MODULE_NAME . '\' section" permission')
            ),
        );
    }

    /**
     * This function should return true if the current user can view this
     * file.
     *
     * Denies permission if any of the following conditions is TRUE:
     * - "Secured" is false, ie. not in Secured section;
     * - "CanViewType" directive is set to "Inherit" and any parent page return false for canView()
     * - "CanViewType" directive is set to "LoggedInUsers" and no user is logged in
     * - "CanViewType" directive is set to "OnlyTheseUsers" and user is not in the given groups
     * @uses ViewerGroups()
     *
     * @param Member|int|null $member
     * @return boolean True if the current user can view this secured file.
     */
    public function canView($member = null) {
        if(!$this->owner->Secured) {
            return true;
        }

        if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
            $member = Member::currentUserID();
        }

        // admin override
        if($member && Permission::checkMember($member, array("ADMIN", "SECURED_FILES_VIEW_ALL"))) {
            return true;
        }

        // check for empty spec
        if(!$this->owner->CanViewType || $this->owner->CanViewType == 'Anyone') {
            return true;
        }

        // check for inherit
        if($this->owner->CanViewType == 'Inherit') {
            if($this->owner->ParentID) {
                return $this->owner->Parent()->canView($member);
            }
            return true;
        }

        // check for any logged-in users
        if($this->owner->CanViewType == 'LoggedInUsers' && $member) {
            // Standard asset-admin permissions should not get you into the CMS' secured-area
            if(Permission::checkMember($member, array("CMS_ACCESS_AssetAdmin"))) {
                return false;
            }
            return true;
        }

        // check for specific groups
        if($member && is_numeric($member)) {
            $member = DataObject::get_by_id('Member', $member);
        }
        if(
            $this->owner->CanViewType == 'OnlyTheseUsers'
            && $member
            && $member->inGroups($this->owner->ViewerGroups())
        ) {
            return true;
        }

        return false;
    }

    /**
     * This function should return true if the current user can edit this
     * secured file.
     *
     * Denies permission if any of the following conditions is TRUE:
     * - canView() return false
     * - "CanEditType" directive is set to "Inherit" and any parent page return false for canEdit()
     * - "CanEditType" directive is set to "LoggedInUsers" and no user is logged in or doesn't have the CMS_Access_CMSMAIN permission code
     * - "CanEditType" directive is set to "OnlyTheseUsers" and user is not in the given groups
     *
     * @uses canView()
     * @uses EditorGroups()
     *
     * @param Member $member Set to FALSE if you want to explicitly test permissions without a valid user (useful for unit tests)
     * @return boolean True if the current user can edit this secured file.
     */
    public function canEdit($member = null) {
        if($member instanceof Member) {
            $memberID = $member->ID;
        }
        else if(is_numeric($member)) {
            $memberID = $member;
            $member = DataObject::get_by_id('Member', $memberID);
        } else {
            $member = Member::currentUser();
            $memberID = Member::currentUserID();
        }

        if($memberID && !$this->owner->Secured || !$this->owner->ID) {
            return true;
        }

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "SECURED_FILES_EDIT_ALL"))) {
            return true;
        }

        if($this->owner->ID) {
            // Regular canEdit logic is handled by can_edit_multiple
            $results = self::can_edit_multiple(array($this->owner->ID), $memberID);

            // If this page no longer exists in stage/live results won't contain the page.
            // Fail-over to false
            return isset($results[$this->owner->ID]) ? $results[$this->owner->ID] : false;
            // Default for unsaved pages
        }
    }

    /**
     * This function should return true if the current user can create new
     * secured file.
     *
     * @param Member $member
     * @return boolean True if the current user can create secured file.
     */
    public function canCreate($member = null) {
        if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
            $member = Member::currentUserID();
        }
        if($member && (!$this->owner->Secured || !$this->owner->ID)) {
            return true;
        }
        if($member && Permission::checkMember($member, array("ADMIN", "CMS_ACCESS_SecuredAssetAdmin", "SECURED_FILES_EDIT_ALL"))) {
            return true;
        }
        return false;
    }

    /**
     * This function should return true if the current user can delete this
     * secured file.
     *
     * Denies permission if any of the following conditions is TRUE:
     * - canEdit() returns FALSE
     * - any descendant files returns FALSE for canDelete() if this is a folder
     *
     * @uses canEdit()
     *
     * @param Member $member
     * @return boolean True if the current user can delete this secured file.
     */
    public function canDelete($member = null) {
        if($member instanceof Member) {
            $memberID = $member->ID;
        } else if(is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "SECURED_FILES_EDIT_ALL"))) {
            return true;
        }

        // Regular canEdit logic is handled by can_edit_multiple
        $results = self::can_delete_multiple(array($this->owner->ID), $memberID);

        // If this page no longer exists in stage/live results won't contain the page.
        // Fail-over to false
        return isset($results[$this->owner->ID]) ? $results[$this->owner->ID] : false;
    }

    /**
     * Get the 'can edit' information for a number of advanced files.
     * 
     * @param array $ids An array of IDs of the advanced files to look up.
     * @param int $memberID ID of member.
     * @param bool $useCached Return values from the permission cache if they exist.
     * @return array
     */
    public static function can_delete_multiple($ids, $memberID, $useCached = true) {
        $result = array_fill_keys($ids, false);
        $cacheKey = "delete-$memberID";

        // Look in the cache for values
        if($useCached && isset(self::$cache_permissions[$cacheKey])) {
            $cachedValues = array_intersect_key(self::$cache_permissions[$cacheKey], $result);

            // If we can't find everything in the cache, then look up the remainder separately
            $uncachedValues = array_diff_key($result, self::$cache_permissions[$cacheKey]);
            if($uncachedValues) {
                $cachedValues = self::can_delete_multiple(array_keys($uncachedValues), $memberID, false)
                    + $cachedValues;
            }
            return $cachedValues;
        }
        
        // You can only delete pages that you can edit
        $editableIDs = array_keys(array_filter(self::can_edit_multiple($ids, $memberID)));
        if($editableIDs) {
            $idList = implode(",", $editableIDs);

            // You can only delete pages whose children you can delete
            $childRecords = DataObject::get("File", "\"ParentID\" IN ($idList)");
            if($childRecords) {
                $children = $childRecords->map("ID", "ParentID");

                // Find out the children that can be deleted
                $deletableChildren = self::can_delete_multiple($children->keys(), $memberID);

                // Get a list of all the parents that have no undeletable children
                $deletableParents = array_fill_keys($editableIDs, true);
                foreach($deletableChildren as $id => $canDelete) {
                    if(!$canDelete) unset($deletableParents[$children[$id]]);
                }

                // Use that to filter the list of deletable parents that have children
                $deletableParents = array_keys($deletableParents);

                // Also get the $ids that don't have children
                $parents = array_unique($children->values());
                $deletableLeafNodes = array_diff($editableIDs, $parents);

                // Combine the two
                $deletable = array_merge($deletableParents, $deletableLeafNodes);

            } else {
                $deletable = $editableIDs;
            }
        } else {
            $deletable = array();
        }

        // Convert the array of deletable IDs into a map of the original IDs with true/false as the
        // value
        return array_fill_keys($deletable, true) + array_fill_keys($ids, false);
    }

    /**
     * Get the 'can edit' information for a number of files.
     *
     * @param array $ids An array of IDs of the files to look up.
     * @param int $memberID ID of member.
     * @param bool $useCached Return values from the permission cache if they exist.
     * @return array A map where the IDs are keys and the values are booleans stating whether the given
     * page can be edited.
     */
    public static function can_edit_multiple($ids, $memberID, $useCached = true) {
        return self::batch_permission_check($ids, $memberID, 'CanEditType', 'File_EditorGroups', null, $useCached);
    }

    /**
     * This method is NOT a full replacement for the individual can*() methods, e.g. {@link canEdit()}.
     * Rather than checking (potentially slow) PHP logic, it relies on the database group associations,
     * e.g. the "CanEditType" field plus the "File_EditorGroups" many-many table.
     * By batch checking multiple records, we can combine the queries efficiently.
     *
     * Caches based on $typeField data. To invalidate the cache, use {@link FileSecured::reset()}
     * or set the $useCached property to FALSE.
     *
     * @param array $ids Of {@link File} IDs
     * @param number $memberID Member ID
     * @param string $typeField A property on the data record, e.g. "CanEditType".
     * @param string $groupJoinTable A many-many table name on this record, e.g. "File_EditorGroups"
     * @param string $globalPermission If the member doesn't have this permission code, don't bother iterating deeper.
     * @param boolean $useCached
     * @return array An map of {@link File} ID keys, to boolean values
     * @todo Add test(s)
     */
    public static function batch_permission_check($ids, $memberID, $typeField, 
            $groupJoinTable, $globalPermission = null, $useCached = true) {
        if($globalPermission === NULL) {
            $globalPermission = array('CMS_ACCESS_LeftAndMain', 'CMS_ACCESS_SecuredAssetAdmin');
        }

        // Sanitise the IDs
        $ids = array_filter($ids, 'is_numeric');

        // This is the name used on the permission cache
        // converts something like 'CanEditType' to 'edit'.
        $cacheKey = strtolower(substr($typeField, 3, -4)) . "-$memberID";

        // Default result: nothing editable
        $result = array_fill_keys($ids, false);
        if($ids) {
            // Look in the cache for values
            if($useCached && isset(self::$cache_permissions[$cacheKey])) {
                $cachedValues = array_intersect_key(self::$cache_permissions[$cacheKey], $result);

                // If we can't find everything in the cache, then look up the remainder separately
                $uncachedValues = array_diff_key($result, self::$cache_permissions[$cacheKey]);
                if($uncachedValues) {
                    $cachedValues = self::batch_permission_check(array_keys($uncachedValues), $memberID, $typeField, $groupJoinTable, $globalPermission, false) + $cachedValues;
                }
                return $cachedValues;
            }

            // If a member doesn't have a certain permission then they can't edit anything
            if(!$memberID || ($globalPermission && !Permission::checkMember($memberID, $globalPermission))) {
                return $result;
            }

            $SQL_idList = implode($ids, ", ");

            // if page can't be viewed, don't grant edit permissions
            // to do - implement can_view_multiple(), so this can be enabled ala:
            // $ids = array_keys(array_filter(self::can_view_multiple($ids, $memberID)));

            // Get the groups that the given member belongs to
            $groupIDs = DataObject::get_by_id('Member', $memberID)->Groups()->column("ID");
            $SQL_groupList = implode(", ", $groupIDs);
            if(!$SQL_groupList) {
                $SQL_groupList = '0';
            }

            $combinedResult = array();

            // Start by filling the array with the pages that actually exist
            $table = "File";

            $result = array_fill_keys(
                ($ids) ? DB::query("SELECT \"ID\" FROM \"$table\" WHERE \"ID\" IN (".implode(", ", $ids).")")->column() : array(),
                false
            );

            // Get the uninherited permissions
            $uninheritedPermissions = DataObject::get("File")
                ->where("(\"$typeField\" = 'LoggedInUsers' OR
                    (\"$typeField\" = 'OnlyTheseUsers' AND \"$groupJoinTable\".\"FileID\" IS NOT NULL))
                    AND \"File\".\"ID\" IN ($SQL_idList)")
                ->leftJoin($groupJoinTable, "\"$groupJoinTable\".\"FileID\" = \"File\".\"ID\" AND \"$groupJoinTable\".\"GroupID\" IN ($SQL_groupList)");

            if($uninheritedPermissions) {
                // Set all the relevant items in $result to true
                $result = array_fill_keys($uninheritedPermissions->column('ID'), true) + $result;
            }

            // Get permissions that are inherited
            $potentiallyInherited = DataObject::get("File", "\"$typeField\" = 'Inherit'
                AND \"File\".\"ID\" IN ($SQL_idList)");

            if($potentiallyInherited) {
                // Group $potentiallyInherited by ParentID; we'll look at the permission of all those
                // parents and then see which ones the user has permission on
                $groupedByParent = array();
                foreach($potentiallyInherited as $item) {
                    if($item->ParentID) {
                        if(!isset($groupedByParent[$item->ParentID])) $groupedByParent[$item->ParentID] = array();
                        $groupedByParent[$item->ParentID][] = $item->ID;
                    }
                }

                if($groupedByParent) {
                    $actuallyInherited = self::batch_permission_check(array_keys($groupedByParent), $memberID, $typeField, $groupJoinTable);
                    if($actuallyInherited) {
                        $parentIDs = array_keys(array_filter($actuallyInherited));
                        foreach($parentIDs as $parentID) {
                            // Set all the relevant items in $result to true
                            $result = array_fill_keys($groupedByParent[$parentID], true) + $result;
                        }
                    }
                }
            }

            $combinedResult = $combinedResult + $result;
        }

        if(isset($combinedResult)) {
            // Cache the results
            if(empty(self::$cache_permissions[$cacheKey])) {
                self::$cache_permissions[$cacheKey] = array();
            }
            self::$cache_permissions[$cacheKey] = $combinedResult + self::$cache_permissions[$cacheKey];

            return $combinedResult;
        }
        
        return array();
    }
    
    /**
     * 
     * @return boolean
     */
    private function isFile() {
        return !is_a($this->owner,"Folder") && is_a($this->owner, "File");
    }
}
