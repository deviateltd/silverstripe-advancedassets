<?php
/**
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * {@link SecuredFileController::handleRequest()} handles requested file, based on accessibility
 */
class SecuredFileController extends Controller {
    
    /**
     * We calculate the timelimit based on the filesize. Set to 0 to give unlimited timelimit.
     * The calculation is: give enough time for the user with x kB/s connection to donwload the entire file.
     * E.g. The default 50kB/s equates to 348 minutes per 1GB file.
     * 
     * Depending on the network bandwidth, the script execution time might need to be prolonged if the requested file
     * is big, we use the threshold to calculate how long we want to prolong, default as 10,240 bits / second
     * 
     * @var number
     */
    private static $bandwidth_threshold = 10240;
    
    /**
     * Handle the requests, checking the request file is downloadable
     * 
     * @param SS_HTTPRequest $request
     * @param DataModel $model
     */
    public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
        if(!$request) {
            user_error("Controller::handleRequest() not passed a request!", E_USER_ERROR);
        }

        $this->pushCurrent();
        $this->urlParams = $request->allParams();
        $this->request = $request;
        $this->response = new SS_HTTPResponse();
        $this->setDataModel($model);
        $this->extend('onBeforeInit');

        // Init
        $this->baseInitCalled = false;
        $this->init();
        if(!$this->baseInitCalled) {
            user_error("init() method on class '$this->class' doesn't call Controller::init()."
                . "Make sure that you have parent::init() included.", E_USER_WARNING);
        }

        $this->extend('onAfterInit');
        $url = array_key_exists('url', $_GET) ? $_GET['url'] : $_SERVER['REQUEST_URI'];
        // make the $url normalised as "assets/somefolder/somefile.ext, so we could find the file record if it has.
        $url = Director::makeRelative(ltrim(str_replace(BASE_URL, '', $url), '/'));
        $file = File::find($url);
        if($file) {
            if($this->canSendToBrowser($file)) {
                //when requesting a re-sampled image, $file is the original image, hence we need to reset the file path
                if(preg_match('/_resampled\/[^-]+-/', $url)) {
                    $file = new Image();
                    $file->Filename = $url;
                }
                $this->sendFileToBrowser($file);
            } else {
                if($file instanceof Image) {
                    $this->sendLockpadSamepleImageToBrowser($file);
                } else {
                    $this->treatFileAccordingToStatus($file);
                }
            }
        }
    }

    /**
     * 
     * @param File $file
     * @return void
     */
    public function treatFileAccordingToStatus($file) {
        $member = Member::currentUser();
        if($member && $member->exists()) {
            $this->notAccessible();
        } else {
            $canViewByTime = $file->canViewFrontByTime();
            if(!$canViewByTime) {
                $this->notAccessible();
            } else {
                $this->redirectToLogIn();
            }
        }
    }

    /**
     * 
     * @return void
     */
    public function redirectToLogIn() {
        $backURL = $this->request->getURL(true);
        $this->response->setStatusCode(302);
        $this->response->addHeader('Location',  "/Security/login?BackURL=".urlencode($backURL));
        echo $this->response->output();
        exit(0);
    }

    /**
     * 
     * @return void
     */
    public function notAccessible() {
        $config = SiteConfig::current_site_config();
        if(!$content = $config->SecuredFileDefaultContent) {
            $content = "<p>" . _t('SecuredFileController.SecuredFileDefaultContent', "The document is not accessible") . "</p>";
        }
        if(!$title = $config->SecuredFileDefaultTitle) {
            $title = _t('SecuredFileController.SecuredFileDefaultTitle', "The document is not accessible");
        }

        if(isset($_GET['ContainerURL']) && $_GET['ContainerURL']) {
            $containerUrl = DBField::create_field('Varchar', $_GET['ContainerURL']);
            $backLink = '<p><a href="' . $containerUrl . '">Go back</a></p>';
            $content = $backLink . $content . $backLink;
        }
        if(class_exists('SiteTree')) {
            $tmpPage = new Page();
            $tmpPage->Title = $title;
            $tmpPage->Content = $content;
            // Disable ID-based caching  of the log-in page by making it a random number
            $tmpPage->ID = -1 * rand(1, 10000000);

            $controller = Page_Controller::create($tmpPage);
            $controller->setDataModel($this->model);
            $controller->init();
        } else {
            $controller = $this->customise(array(
                "Content" => $content,
                "Title" => $title,
            ));
        }

        echo $controller->renderWith(
            array('Page')
        )->Value;

        exit(0);
    }

    /**
     * 
     * @param File $file
     * @param Config $config
     * @return string
     */
    public function getDefaultPadlockImagePathByConfig($file, $config) {
        if($file->isExpired() ) {
            $lockpadImage = $config->LockpadImageNoLongerAvailable();
            if(!$lockpadImage || !$lockpadImage->exists()) {
                if($file->isEmbargoed()) {
                    $lockpadImage = $config->LockpadImageNotYetAvailable();
                    if(!$lockpadImage || !$lockpadImage->exists()) {
                        $lockpadImage = $config->LockpadImageNoAccess();
                    }
                }
            }
        } else if($file->isEmbargoed()) {
            $lockpadImage = $config->LockpadImageNotYetAvailable();
            if(!$lockpadImage || !$lockpadImage->exists()) {
                if($file->isExpired()) {
                    $lockpadImage = $config->LockpadImageNoLongerAvailable();
                    if(!$lockpadImage || !$lockpadImage->exists()) {
                        $lockpadImage = $config->LockpadImageNoAccess();
                    }
                }
            }
        } else {
            $lockpadImage = $config->LockpadImageNoAccess();
        }
        
        if(isset($lockpadImage) && $lockpadImage && $lockpadImage->exists()) {
            return $relativePath = $lockpadImage->Filename;
        }
    }

    /**
     * 
     * @param Config $config
     * @return string
     */
    public function getDefaultPadlockLoginImagePathByConfig($config) {
        $lockpadImage = $config->LockpadImageNeedLogIn();
        if($lockpadImage && $lockpadImage->exists()) {
            return $relativePath = $lockpadImage->Filename;
        }
    }

    /**
     * 
     * @param File $file
     * @param string $color
     * @return string
     */
    public function getDefaultPadlockImagePath($file, $color) {
        $originalFilePath = $file->getFullPath();
        if(file_exists($originalFilePath)) {
            $originalFileSize = filesize($originalFilePath);
        } else {
            $originalFileSize = 0;
        }

        $width = 256;
        if($originalFileSize <= 777) $width = 16;
        else if($originalFileSize <= 1269) $width = 24;
        else if($originalFileSize <= 2894) $width = 48;
        else if($originalFileSize <= 6797) $width = 96;

        $name = "padlock-" . $color . "-" . $width . "x" . $width . ".png";
        $relPath = ASSETS_DIR . DIRECTORY_SEPARATOR . "_defaultlockimages" . DIRECTORY_SEPARATOR . $name ;
        return $relPath;
    }

    /**
     * 
     * @param File $file
     * @param string $color
     * @return void
     */
    public function sendLockpadSamepleImageToBrowser($file, $color = 'color') {
        $path = null;
        $config = SiteConfig::current_site_config();
        $member = Member::currentUser();
        if($member && $member->exists()) {
            $this->getDefaultPadlockImagePathByConfig($file, $config);
        } else {
            $canViewByTime = $file->canViewFrontByTime();
            if(!$canViewByTime) {
                $relativePath = $this->getDefaultPadlockImagePathByConfig($file, $config);
            } else {
                $relativePath = $this->getDefaultPadlockLoginImagePathByConfig($config);
            }
        }

        if(!$relativePath) {
            $relativePath = $this->getDefaultPadlockImagePath($file, $color);
        }
        $path = Director::baseFolder() . DIRECTORY_SEPARATOR .$relativePath;

        $basename = basename($path);
        if(file_exists($path)) {
            $length = filesize($path);
        } else {
            $length = 0;
        }
        $type = HTTP::get_mime_type($relativePath);

        //handling time limitation
        if($threshold = $this->config()->bandwidth_threshold) {
            increase_time_limit_to((int)($length/$threshold));
        } else {
            increase_time_limit_to();
        }
        
        // send header
        header('Content-Description: File Transfer');

        /*
         * allow inline 'save as' popup, double quotation is present to prevent browser (eg. Firefox) from handling
         * wrongly a file with a name with whitespace, through a file in SilverStripe should not contain any whitespace
         * if the file is uploaded through SS interface
         * http://kb.mozillazine.org/Filenames_with_spaces_are_truncated_upon_download
         */
        header('Content-Type: '.$type);
        header('Content-Disposition: inline; filename='.$basename);
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $length);

        /**
         * issue fixes for IE6,7,8  when downloading a file over HTTPS (http://support.microsoft.com/kb/812935)
         * http://www.dotvoid.com/2009/10/problem-with-downloading-files-with-internet-explorer-over-https/
         */
        if(isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"] == "on")) {
            header('Pragma: ');
        }

        header('Expires: 0');
        header('Cache-Control: must-revalidate');

        /**
         * unload the php session file
         * see http://konrness.com/php5/how-to-prevent-blocking-php-requests
         */
        session_write_close();

        /**
         * if output buffering is active, we clear it to prevent the script from trying to to allocate memory for entire file.
         * 
         * trying to clean each buffer before read the file to the buffer, there are lots of  problem of
         * HP "Output Controll", depanding on php version,  .ini settings, libs added, etc.
         *
         * On different php version, i.e. 5.3 and 5.4 make  ob_start() differently and hence ob_flush(), ob_clean(), ob_end_clean(), 
         * ob_end_flush() could be sometimes working and sometimes not, depending on how ob_start(), besides ob_flush() are not
         * stable and bugs being new introduced to the function in 5.4.
         * We found the most relable function is ob_end_clean();
         *
         * TODO: dealing with clearing buffers using combinatioin of ob_get_status() and phpversion()
         */
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        flush();
        readfile($path);

        exit(0);
    }

    /**
     * 
     * @param File $file
     * @return void
     */
    public function sendFileToBrowser($file) {
        $path = $file->getFullPath();
        if(SapphireTest::is_running_test()) {
            return file_get_contents($path);
        }

        $basename = basename($path);
        $length = $file->getAbsoluteSize();
        $type = HTTP::get_mime_type($file->getRelativePath());

        //handling time limitation
        if($threshold = $this->config()->bandwidth_threshold) {
            increase_time_limit_to((int)($length/$threshold));
        } else {
            increase_time_limit_to();
        }
        
        // send header
        header('Content-Description: File Transfer');

        /*
         * allow inline 'save as' popup, double quotation is present to prevent browser (eg. Firefox) from handling
         * wrongly a file with a name with whitespace, through a file in SilverStripe should not contain any whitespace
         * if the file is uploaded through SS interface
         * http://kb.mozillazine.org/Filenames_with_spaces_are_truncated_upon_download
         */
        header('Content-Type: '.$type);
        header('Content-Disposition: inline; filename='.$basename);
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $length);

        /**
         * issue fixes for IE6,7,8  when downloading a file over HTTPS (http://support.microsoft.com/kb/812935)
         * http://www.dotvoid.com/2009/10/problem-with-downloading-files-with-internet-explorer-over-https/
         */
        if(isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"] == "on")) {
            header('Pragma: ');
        }

        header('Expires: 0');
        header('Cache-Control: must-revalidate');

        /**
         * unload the php session file
         * see http://konrness.com/php5/how-to-prevent-blocking-php-requests
         */
        session_write_close();

        // if output buffering is active, we clear it to prevent the script from trying to to allocate memory for entire file.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        flush();
        readfile($path);

        exit(0);
    }
    
    /**
     * 
     * @param File $file
     * @return boolean
     */
    public function canSendToBrowser(File $file = null) {
        $canViewFront = true;
        if($file instanceof File) {
            // Implement all file extensions with canViewFront()
            $cans = $file->extend('canViewFront');
            if($cans && is_array($cans)) {
                foreach($cans as $can) $canViewFront = $can && $canViewFront;
            } else {
                $canViewFront = false;
            }
        } else {
            $canViewFront = false;
        }

        return $canViewFront;
    }

}
