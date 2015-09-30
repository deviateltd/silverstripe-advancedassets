<?php
/**
 *
 * This extension "augments" the CMS synchronisation logic to include SQL for "Secured = '0'" in specific circumstances.
 * 
 * @author Deviate Ltd 2014-2015 <http://deviate.net.nz>
 * @package silverstripe-advancedassets
 */
class FolderSecured extends DataExtension {

    /**
     * Ensures there are no merges between a secured folder and non-secured folder.
     * There is only one case that both secured child folder and non-secured child folders exist and that's when
     * $this->owner is on assets root.
     *
     * @param SQLQuery $query
     * @return void
     */
    public function augmentSQL(SQLQuery &$query) {
        $sql = $query->sql();
        $rawQuery = str_replace(array('"', "'"), '', $sql);
        $isDuplicateSql = stristr($rawQuery, "SELECT DISTINCT File.ClassName,") !== false;
        $isStdFilesSyncReq = $this->isStandardFileSyncRequest();
        if($isStdFilesSyncReq && $isDuplicateSql) {
            $query->addWhere('"Secured" = \'0\'');
        }
    }

    /**
     * This method hooks into the write that is performed by {@link Folder::constructChild()} which is
     * called by the main {@link Folder::syncChildren()} method. This simply ensures that the correct value for
     * the "File" table's "Secured" field is written-to.
     *
     * @param array $manipulation
     * @return null|void
     */
    public function augmentWrite(&$manipulation) {
        // Don't bother with remaining logic if the controller request is not a synchronisation request
        $isStdFilesSyncReq = $this->isStandardFileSyncRequest();
        if(!$isStdFilesSyncReq) {
            return;
        }

        $secured = $this->owner->Secured ? '1' : '0';
        foreach($manipulation as $table => $details) {
            if($table != 'File' || !isset($details['fields']['Secured'])) {
                continue;
            }

            $details['fields']['Secured'] = $secured;

            // Save back modifications to the manipulation
            $manipulation[$table] = $details;
        }
    }

    /**
     * Ascertains whether the current request is an Admin AJAX request for the CMS' synchronisation logic
     * from the CMS' standard "Files" admin section, to prevent "Secured" files from being synchronised at the root
     * level.
     *
     * @return boolean
     */
    private function isStandardFileSyncRequest() {
        $controller = Controller::curr();
        $isSubclass = is_subclass_of($controller, 'LeftAndMain');
        $isAjax = Director::is_ajax();
        $isAssetsRoot = (int)$this->owner->ID == 0;
        $isDoSync = false;
        if($urlParts = explode('/', $controller->getRequest()->getURL())) {
            $isDoSync = end($urlParts) === 'doSync' ? true : false;
        }

        return ($isSubclass && $isAjax && $isAssetsRoot && $isDoSync);
    }

}
