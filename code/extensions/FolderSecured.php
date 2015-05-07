<?php
/**
 *
 * This extension only adds x2 customised methods for Folder object:
 * 
 * {@link FolderSecured::securedSyncChildren()} is a customised version of {@link Folder::syncChildren()}
 * with two places of overwritten marked as @customised
 * {@Link FolderSecured::constructChildSecuredWithSecuredFlag is a customised version of
 * {@link Folder::constructChild()} which also populates the File's "Secured" field
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * @todo How many of the "cloned" methods/props from {@link Folder} are actually neeed?
 */
class FolderSecured extends DataExtension {
    
    /**
     * 
     * @return array
     */
    public function securedSyncChildren() {
        $parentID = (int)$this->owner->ID; // parentID = 0 on the singleton, used as the 'root node';
        $added = 0;
        $deleted = 0;
        $skipped = 0;

        // First, merge any children that are duplicates
        //customised
        if($parentID === 0) {
            //make sure there is no merges between a secured folder and non secured folder.
            //there is only one case that there are both secured child folder and non-secured child folder exists,
            //that is when $this->owner is on assets root.
            $duplicateChildrenNames = DB::query("SELECT \"Name\" FROM \"File\""
                ." WHERE \"ParentID\"=$parentID AND \"Secured\"='0' GROUP BY \"Name\" HAVING count(*) > 1")->column();
        } else {
            $duplicateChildrenNames = DB::query("SELECT \"Name\" FROM \"File\""
                . " WHERE \"ParentID\" = $parentID GROUP BY \"Name\" HAVING count(*) > 1")->column();
        }
        
        if($duplicateChildrenNames) {
            foreach($duplicateChildrenNames as $childName) {
                $childName = Convert::raw2sql($childName);
                // Note, we do this in the database rather than object-model; otherwise we get all sorts of problems
                // about deleting files
                $children = DB::query("SELECT \"ID\" FROM \"File\""
                    . " WHERE \"Name\" = '$childName' AND \"ParentID\" = $parentID")->column();
                if($children) {
                    $keptChild = array_shift($children);
                    foreach($children as $removedChild) {
                        DB::query("UPDATE \"File\" SET \"ParentID\" = $keptChild WHERE \"ParentID\" = $removedChild");
                        DB::query("DELETE FROM \"File\" WHERE \"ID\" = $removedChild");
                    }
                } else {
                    user_error("Inconsistent database issue: SELECT ID FROM \"File\" WHERE Name = '$childName'"
                        . " AND ParentID = $parentID should have returned data", E_USER_WARNING);
                }
            }
        }

        // Get index of database content
        // We don't use DataObject so that things like subsites doesn't muck with this.
        $dbChildren = DB::query("SELECT * FROM \"File\" WHERE \"ParentID\" = $parentID");
        $hasDbChild = array();

        if($dbChildren) {
            foreach($dbChildren as $dbChild) {
                $className = $dbChild['ClassName'];
                if(!$className) $className = "File";
                $hasDbChild[$dbChild['Name']] = new $className($dbChild);
            }
        }
        $unwantedDbChildren = $hasDbChild;

        // if we're syncing a folder with no ID, we assume we're syncing the root assets folder
        // however the Filename field is populated with "NewFolder", so we need to set this to empty
        // to satisfy the baseDir variable below, which is the root folder to scan for new files in
        if(!$parentID) $this->owner->Filename = '';

        // Iterate through the actual children, correcting the database as necessary
        $baseDir = $this->owner->FullPath;

        // @todo this shouldn't call die() but log instead
        if($parentID && !$this->owner->Filename) die($this->owner->ID . " - " . $this->owner->FullPath);

        if(file_exists($baseDir)) {
            $actualChildren = scandir($baseDir);
            $ignoreRules = Config::inst()->get('Filesystem', 'sync_blacklisted_patterns');
            foreach($actualChildren as $actualChild) {
                if($ignoreRules) {
                    $skip = false;

                    foreach($ignoreRules as $rule) {
                        if(preg_match($rule, $actualChild)) {
                            $skip = true;

                            break;
                        }
                    }

                    if($skip) {
                        $skipped++;

                        continue;
                    }
                }

                // A record with a bad class type doesn't deserve to exist. It must be purged!
                if(isset($hasDbChild[$actualChild])) {
                    $child = $hasDbChild[$actualChild];
                    if(( !( $child instanceof Folder ) && is_dir($baseDir . $actualChild) )
                        || (( $child instanceof Folder ) && !is_dir($baseDir . $actualChild)) ) {
                        DB::query("DELETE FROM \"File\" WHERE \"ID\" = $child->ID");
                        unset($hasDbChild[$actualChild]);
                    }
                }

                if(isset($hasDbChild[$actualChild])) {
                    $child = $hasDbChild[$actualChild];
                    unset($unwantedDbChildren[$actualChild]);
                } else {
                    $added++;
                    $childID = $this->constructChildSecuredWithSecuredFlag($actualChild);
                    $child = DataObject::get_by_id("File", $childID);
                }

                if($child && is_dir($baseDir . $actualChild)) {
                    //customised
                    //when we synch on assets root
                    //we don't want to sync the secured root folder _securedfiles
                    //This is only the case where both secured child folder and non-secured child folder exist
                    if($parentID === 0 && $child->ID === FileSecured::getSecuredRoot()->ID) {
                        $skipped ++;
                    } else {
                        //customised
                        //Of cause, we need to call this customised version recursively
                        $childResult = $child->securedSyncChildren();
                        $added += $childResult['added'];
                        $deleted += $childResult['deleted'];
                        $skipped += $childResult['skipped'];
                    }

                }

                // Clean up the child record from memory after use. Important!
                $child->destroy();
                $child = null;
            }

            // Iterate through the unwanted children, removing them all
            if(isset($unwantedDbChildren)) foreach($unwantedDbChildren as $unwantedDbChild) {
                DB::query("DELETE FROM \"File\" WHERE \"ID\" = $unwantedDbChild->ID");
                $deleted++;
            }
        } else {
            DB::query("DELETE FROM \"File\" WHERE \"ID\" = $this->owner->ID");
        }

        return array(
            'added' => $added,
            'deleted' => $deleted,
            'skipped' => $skipped
        );
    }

    public function constructChildSecuredWithSecuredFlag($name) {
        // Determine the class name - File, Folder or Image
        $baseDir = $this->owner->FullPath;
        if(is_dir($baseDir . $name)) {
            $className = "Folder";
        } else {
            $className = File::get_class_for_file_extension(pathinfo($name, PATHINFO_EXTENSION));
        }

        if(Member::currentUser()) $ownerID = Member::currentUser()->ID;
        else $ownerID = 0;

        $filename = Convert::raw2sql($this->owner->Filename . $name);
        if($className == 'Folder' ) $filename .= '/';

        $name = Convert::raw2sql($name);
        $secured = $this->owner->Secured?'1':'0';

        DB::query("INSERT INTO \"File\"
			(\"ClassName\",\"ParentID\",\"OwnerID\",\"Name\",\"Filename\",\"Created\",\"LastEdited\",\"Title\",\"Secured\")
			VALUES ('$className', ".$this->owner->ID.", $ownerID, '$name', '$filename', "
            . DB::getConn()->now() . ',' . DB::getConn()->now() . ", '$name', '$secured')");

        return DB::getGeneratedID("File");
    }
}