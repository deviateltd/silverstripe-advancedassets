<?php
/**
 * Author: Normann
 * Date: 3/11/2014
 * Time: 6:06 PM
 */

class SecuredFilesHtmlEditorField_Toolbar extends Extension{
    function updateMediaForm(Form $form){
        $fields = $form->Fields();
        $parentIDField = $fields->dataFieldByName('ParentID');

        if(!Permission::check(array("ADMIN", "SECURED_FILES_VIEW_ALL"))){
            $parentIDField->setFilterFunction(create_function('$node', "return \$node->Secured == 0;"));
        }

        if($parentID = $parentIDField->Value()) {
            $folder = DataObject::get_by_id('Folder', $parentID);
            if($folder->Secured){
                $gridField = $fields->dataFieldByName('Files');
                $config = $gridField->getConfig();
                $columns = $config->getComponentByType('GridFieldDataColumns');
                $displayFields = $columns->getDisplayFields($gridField);
                $displayFields = array_merge(
                    $displayFields,
                    array(
                        'WhoCanViewHTML'=> _t('SecuredAssetAdmin.WHOCANVIEW', 'Who can view?'),
                        'EmbargoHTML'   => _t('SecuredAssetAdmin.EmbargoingStatus', 'Embargoing Status'),
                        'ExpireHTML'    => _t('SecuredAssetAdmin.ExpiringStatus', 'Expiring Status'),
                    )
                );
                $columns->setDisplayFields($displayFields);
            }
        }
    }

    public function updateLinkForm(Form $form){
        $fields = $form->Fields();
        $fileField = $fields->dataFieldByName('file');
        if(!Permission::check(array("ADMIN", "SECURED_FILES_VIEW_ALL"))){
            $fileField->setFilterFunction(create_function('$node', "return \$node->Secured == 0;"));
        }
    }

    //$this->extend('updateFieldsForImage', $fields, $url, $file);
    public function updateFieldsForImage(FieldList $fields, $url, Image $file){
        if(is_a($file, "HtmlEditorField_Image")){
            $image = $file->getFile();
            if($image && $image->exists() && $image->Secured){
                $previewImage = $fields->fieldByName('FilePreview');
                $previewData = $previewImage->fieldByName('FilePreviewData');
                $previewData->insertAfter(
                    $whoCanView = ReadonlyField::create("WhoCanViewHTML",
                        _t('SecuredAssetAdmin.WHOCANVIEW', 'Who can view?'),
                        $image->WhoCanViewHTML
                    ),
                    'LastEdited'
                );
                $previewData->insertAfter(
                    $embargo = ReadonlyField::create("EmbargoHTML",
                        _t('SecuredAssetAdmin.Embargoing', 'Embargoing'),
                        $image->EmbargoHTML
                    ),
                    'WhoCanViewHTML'
                );
                $previewData->insertAfter(
                    $expire = ReadonlyField::create("ExpireHTML",
                        _t('SecuredAssetAdmin.Expiring', 'Expiring'),
                        $image->ExpireHTML
                    ),
                    'EmbargoHTML'
                );

                $whoCanView->dontEscape=true;
                $embargo->dontEscape=true;
                $expire->dontEscape=true;
            }
        }
    }
}