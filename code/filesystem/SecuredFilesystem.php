<?php
/**
 * Author: Normann
 * Date: 14/08/14
 * Time: 2:35 PM
 *
 * {@link SecuredFilesystem::sync_secured()} overwrite {@link Filesystem::sync()} in a way that a folder
 * syncs its children safely, i.e, don't sync any secured child folder when the folder is non-secured,
 * and vise-versa.
 */

class SecuredFilesystem extends Filesystem {
    public static function sync_secured($folderID = null) {
        $folder = DataObject::get_by_id('Folder', (int) $folderID);
        if(!($folder && $folder->exists())) $folder = singleton('Folder');
        //{@link FolderSecured::securedSyncChildren()}
        $results = $folder->securedSyncChildren();
        $finished = false;
        while(!$finished) {
            $orphans = DB::query("SELECT \"C\".\"ID\" FROM \"File\" AS \"C\"
				LEFT JOIN \"File\" AS \"P\" ON \"C\".\"ParentID\" = \"P\".\"ID\"
				WHERE \"P\".\"ID\" IS NULL AND \"C\".\"ParentID\" > 0");
            $finished = true;
            if($orphans) foreach($orphans as $orphan) {
                $finished = false;
                // Delete the database record but leave the filesystem alone
                $file = DataObject::get_by_id("File", $orphan['ID']);
                $file->deleteDatabaseOnly();
                unset($file);
            }
        }
        return _t(
            'Filesystem.SYNCRESULTS',
            'Sync complete: {createdcount} items created, {deletedcount} items deleted',
            array('createdcount' => (int)$results['added'], 'deletedcount' => (int)$results['deleted'])
        );
    }
}