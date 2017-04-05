<?php
/**
 * Created by Deviate Ltd. NZ.
 * Author: normann.lou
 * Date/Time: 24/03/2017 / 5:10 PM
 */

class SecuredUploadField_SelectHandler extends UploadField_SelectHandler {
    /**
     * @param $folderID The ID of the folder to display.
     * @return FormField
     */
    protected function getListField($folderID)
    {
        $selectComposite = parent::getListField($folderID);

        if (!Permission::check(array("ADMIN", "SECURED_FILES_VIEW_ALL"))) {
            $parentIDField = $selectComposite->fieldByName('ParentID');
            $parentIDField->setFilterFunction(create_function('$node', "return \$node->Secured == 0;"));

            //$fileField->setFilterFunction(create_function('$node', "return \$node->Secured == 0;"));
        }
        $this->extend('updateListField', $selectComposite);
        return $selectComposite;
    }
}