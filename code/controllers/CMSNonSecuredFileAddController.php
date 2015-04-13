<?php
/**
 * Author: Normann
 * Date: 13/08/14
 * Time: 2:38 PM
 */

class CMSNonSecuredFileAddController extends CMSFileAddController{
    private static $url_segment = 'assets/add';
    private static $url_priority = 65;

    public function init(){
        parent::init();
        $this->initValidate();
    }

    public function initValidate() {
        if(isset($_GET['ID'])&& is_numeric($_GET['ID'])){
            $folder = DataObject::get_by_id("Folder", $_GET['ID']);
            if($folder && $folder->exists()){
                if($folder->Secured){
                    die('not found');
                }
            }else{
                die('not found');
            }
        }
    }

    public function Breadcrumbs($unlinked = false) {
        $items = parent::Breadcrumbs($unlinked);
        $originalLink = singleton('AssetAdmin')->Link('show');
        $changedLink = singleton('NonSecuredAssetAdmin')->Link('show');
        foreach($items as $item) {
            $item->Link = str_replace($originalLink, $changedLink, $item->Link);
        }
        return $items;
    }

    /**
     * @param null $id Not used.
     * @param null $fields Not used.
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
                Controller::join_links(singleton('NonSecuredAssetAdmin')->Link('show'),  $folder->ID),
                _t('AssetAdmin.BackToFolder', 'Back to folder')
            )
        );
        $fields = $form->Fields();
        $fields->removeByName("BackLink");
        $fields->push($backLink);
        return $form;
    }
}