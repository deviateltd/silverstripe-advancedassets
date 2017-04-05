<?php
/**
 *
 * Prevents a zillion requests for an image and the consequent PHP engine's overhead when for example a page containing
 * xx images inserted thru CMS WYSIWTG is requested.
 *
 * If all these images are "secured", this would mean loading these images via the {@link SecuredFileController}.
 * Once the page loaded, te browser will send 100a zillion0 requests to the server,
 *
 * For argument's sake, of the zillion, let's say 10 are actually viewable by the current user at the current time,
 * the remainder either require the user to be logged-in, or ar eonly for access by some specific group, or expired,
 * or embargoed.
 *
 * Without being able to change the original image's src, all zillion requests to {@link SecuredFileController} result
 * in the same zillion browser/screen paint operations even for those images that fall in same 'category',
 * e.g. 'viewable logged-in only user', the controller will paint the same logged-in-only lockpad image many many
 * times to the page.
 *
 * With this extension, the remaining image src's are replaced with several different LockPad images first, and these
 * images are directly loaded into the browser without even touching PHP.
 *
 * Summary: Avoids zillions of browser/screen paints and unnecessary controller traffic,
 *
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 */
class SecuredFileRichLinksExtension extends Extension
{

    private static $casting = array(
        'SecuredFileRichLinks' => 'HTMLText'
    );
    
    /**
     *
     * @var string
     */
    public $_need_login_image;
    
    /**
     *
     * @var string
     */
    public $_no_access_image;
    
    /**
     *
     * @var string
     */
    public $_no_longer_image;
    
    /**
     *
     * @var string
     */
    public $_not_yet_image;

    /**
     * 
     * @param File $file
     * @param Config $config
     * @return type
     * @todo ...umm refactor?
     */
    public function getDefaultPadlockImagePathByConfig($file, $config)
    {
        if ($file->isExpired()) {
            if (!$this->_no_longer_image) {
                $lockpadImage = $config->LockpadImageNoLongerAvailable();
                if (!$lockpadImage || !$lockpadImage->exists()) {
                    if ($file->isEmbargoed()) {
                        if (!$this->_not_yet_image) {
                            $lockpadImage = $config->LockpadImageNotYetAvailable();
                            if (!$lockpadImage || !$lockpadImage->exists()) {
                                if (!$this->_no_access_image) {
                                    $lockpadImage = $config->LockpadImageNoAccess();
                                    if ($lockpadImage && $lockpadImage->exists()) {
                                        $this->_no_access_image = $lockpadImage->Filename;
                                        return $this->_no_access_image;
                                    }
                                } else {
                                    return $this->_no_access_image;
                                }
                            } else {
                                $this->_not_yet_image = $lockpadImage->Filename;
                                return $this->_not_yet_image;
                            }
                        } else {
                            return $this->_not_yet_image;
                        }
                    }
                } else {
                    $this->_no_longer_image = $lockpadImage->Filename;
                    return $this->_no_longer_image;
                }
            } else {
                return $this->_no_longer_image;
            }
        } elseif ($file->isEmbargoed()) {
            if (!$this->_not_yet_image) {
                $lockpadImage = $config->LockpadImageNotYetAvailable();
                if (!$lockpadImage || !$lockpadImage->exists()) {
                    if ($file->isExpired()) {
                        if (!$this->_no_longer_image) {
                            $lockpadImage = $config->LockpadImageNoLongerAvailable();
                            if (!$lockpadImage || !$lockpadImage->exists()) {
                                if (!$this->_no_access_image) {
                                    $lockpadImage = $config->LockpadImageNoAccess();
                                    if ($lockpadImage && $lockpadImage->exists()) {
                                        $this->_no_access_image = $lockpadImage->Filename;
                                        return $this->_no_access_image;
                                    }
                                } else {
                                    return $this->_no_access_image;
                                }
                            } else {
                                $this->_no_longer_image = $lockpadImage->Filename;
                                return $this->_no_longer_image;
                            }
                        } else {
                            return $this->_no_longer_image;
                        }
                    }
                } else {
                    $this->_not_yet_image = $lockpadImage->Filename;
                    return $this->_not_yet_image;
                }
            } else {
                return $this->_not_yet_image;
            }
        } else {
            if (!$this->_no_access_image) {
                $lockpadImage = $config->LockpadImageNoAccess();
                if ($lockpadImage && $lockpadImage->exists()) {
                    $this->_no_access_image = $lockpadImage->Filename;
                    return $this->_no_access_image;
                }
            } else {
                return $this->_no_access_image;
            }
        }
    }

    /**
     * 
     * @param Config $config
     * @return string
     */
    public function getDefaultPadlockLoginImagePathByConfig($config)
    {
        if (!$this->_need_login_image) {
            $lockpadImage = $config->LockpadImageNeedLogIn();
            if ($lockpadImage && $lockpadImage->exists()) {
                $this->_need_login_image = $lockpadImage->Filename;
                return $this->_need_login_image;
            }
        } else {
            return $this->_need_login_image;
        }
    }

    /**
     * 
     * @param Image $image
     * @return string
     */
    public function getDefaultPadlockImagePath($image)
    {
        $originalFilePath = $image->getFullPath();
        if (file_exists($originalFilePath)) {
            $originalFileSize = filesize($originalFilePath);
        } else {
            $originalFileSize = 0;
        }

        $width = 256;
        if ($originalFileSize <= 777) {
            $width = 16;
        } elseif ($originalFileSize <= 1269) {
            $width = 24;
        } elseif ($originalFileSize <= 2894) {
            $width = 48;
        } elseif ($originalFileSize <= 6797) {
            $width = 96;
        }

        $name = "padlock-color-".$width."x".$width.".png";
        return $src = ASSETS_DIR . DIRECTORY_SEPARATOR . "_defaultlockimages" . DIRECTORY_SEPARATOR . $name ;
    }

    /**
     * 
     * @return string
     */
    public function SecuredFileRichLinks()
    {
        // Note:
        // Assume we can use Regexes because the link will always be formatted
        // in the same way coming from the CMS.
        $content = $this->owner->value;
        $originals = array();
        $replacements = array();
        $fileStack = array();

        // find all image <img> for processing
        preg_match_all('/<img.*class="([^"]*)".*src="([^"]*)".*>/U', $content, $imatches);

        //make replacement images ready
        $config = SiteConfig::current_site_config();
        for ($i = 0; $i < count($imatches[0]); $i++) {
            $image = DataObject::get_one('Image', "\"Filename\" = '".Convert::raw2sql($imatches[2][$i])."'");
            if ($image && $image->exists() && $image->Secured) {
                $icanViewByTime = $image->canViewFrontByTime();
                $icanViewByUser = $image->canViewFrontByUser();

                if ($icanViewByTime && $icanViewByUser) {
                    $class = $imatches[1][$i];
                    $replaceNeeded = false;
                } elseif (!$icanViewByTime) {
                    $class = $imatches[1][$i];
                    if ($class) {
                        $class .= " secured unavailable";
                    } else {
                        $class = "secured unavailable";
                    }
                    $replaceNeeded = true;
                } else { // $canViewByUser is false only
                    $class = $imatches[1][$i];
                    if ($class) {
                        $class .= " secured";
                    } else {
                        $class = "secured";
                    }
                    $replaceNeeded = true;
                }

                if ($replaceNeeded) {
                    $member = Member::currentUser();
                    if ($member && $member->exists()) {
                        $src = $this->getDefaultPadlockImagePathByConfig($image, $config);
                    } else {
                        if (!$icanViewByTime) {
                            $src = $this->getDefaultPadlockImagePathByConfig($image, $config);
                        } else {
                            $src = $this->getDefaultPadlockLoginImagePathByConfig($config);
                        }
                    }

                    if (!$src) {
                        $src = $this->getDefaultPadlockImagePath($image);
                    }

                    $pattern = '/(<img.*class=\")([^\"]*)(\".*src=\")([^\"]*)(\"[^>]*>)/iU';

                    $replacement = '$1'.$class.'$3'.$src.'$5';
                    $newImgTag = preg_replace($pattern, $replacement, $imatches[0][$i], -1);

                    $originals[] = $imatches[0][$i];
                    $replacements[] = $newImgTag;
                }
            }
        }
        
        // Find all file links for processing.
        preg_match_all('/<a.*href="\[file_link,id=([0-9]+)\].*".*>.*<\/a>/U', $content, $matches);
        // Attach the file type and size to each of the links.
        for ($i = 0; $i < count($matches[0]); $i++) {
            $file = DataObject::get_by_id('File', $matches[1][$i]);
            if ($file && $file->exists() && !in_array($file->ID, $fileStack)) {
                $fileStack[] = $file->ID;
                $size = $file->getSize();
                $ext = strtoupper($file->getExtension());
                if($file->Secured){
                    $canViewByTime = $file->canViewFrontByTime();
                    $canViewByUser = $file->canViewFrontByUser();

                    if ($canViewByTime && $canViewByUser) {
                        $class = "";
                    } elseif (!$canViewByTime) {
                        $class = "secured unavailable";
                    } else { // $canViewByUser is false only
                        $class = "secured";
                    }

                    if ($class !== "") {
                        $newLink = "<a class=\"".$class."\" ".substr($matches[0][$i], 2);
                        if (!$canViewByTime) {
                            $newLink = str_replace(array("<a", " target=\"_blank\"", "href", "</a>"), array("<span", "", "data-file-link", "</span>"), $newLink);
                        }
                    } else {
                        $newLink = $matches[0][$i];
                    }
                } else {
                    $newLink = $matches[0][$i];
                }

                $newLink .= "<span class='fileExt'> [$ext, $size]</span>";
                $originals[] = $matches[0][$i];
                $replacements[] = $newLink;
            }
        }
        if (!empty($originals)) {
            $content = str_replace($originals, $replacements, $content);
        }

        // Inject extra attributes into the external links.
        $pattern = '/(<a.*)(href=\"https?:\/\/[^\"]*\"[^>]*>.*)(<\/a>)/iU';
        $replacement = sprintf(
            '$1class="external" rel="external" title="%s" $2 <span class="nonvisual-indicator">(external link)</span>$3',
            _t('SecuredFileRichLinks.OpenLinkTitle', 'Open external link')
        );
        $content = preg_replace($pattern, $replacement, $content, -1);

        return $content;
    }
}
