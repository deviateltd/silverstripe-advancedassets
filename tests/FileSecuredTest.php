<?php
/**
 * 
 * Attempts to excercise all the customised canXX() methods on {@link FileSecured}. 
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * @todo Why is a user with ADMIN always running tests?
 * @todo Complete commented assertions. Note: FolderSecured may need its own canView() definition
 */
class FileSecuredTest extends FunctionalTest {
    
    /**
     * 
     * @var string
     */
    protected static $fixture_file = 'fixtures/FileSecuredTest.yml';

    /**
     * @var string
     */
    protected static $test_folder = 'test-secured';

    /**
     * Remove test dir(s) after test runs
     */
    public function tearDown() {
        $testFolder = ASSETS_PATH . '/' . self::$test_folder;
        if(file_exists($testFolder)) {
            rmdir($testFolder);
        }

        parent::tearDown();
    }

    /**
     * Can ADMIN CMS users, view individual SECURED files in the CMS?
     */
    public function testCanViewInCMSAsAdmin() {
        $member = $this->objFromFixture('Member', 'can-view-is-admin');

        // LoggedInUsers: canView = yes
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canView($member));

        // Inherit: canView = yes
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertTrue($file->canView($member));

        // Inherit: canView = yes (No parent folder specified, so inherits from nothing)
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertTrue($file->canView($member));

        // Inherit: OnlyTheseUsers = yes
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canView($member));
    }

    /**
     * Can AdvancedAsset CMS users, view individual SECURED files in the CMS?
     */
    public function testCanViewInCMSAsSecuredAssetAdmin() {
        $member = $this->objFromFixture('Member', 'can-view-secured-asset-admin');

        // LoggedInUsers: canView = yes
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canView($member));

        // Inherit: canView = yes (No parent folder specified, so inherits from nothing)
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertTrue($file->canView($member));

        // OnlyTheseUsers: canView = no
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertFalse($file->canView($member));

        // Anyone: canView = yes
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canView($member));

        // Unsecured file: canView = yes
        $file = $this->createUnSecuredFile();
        $this->assertTrue($file->canView($member));

        /*
         * With reference to the above assertions, we assume individual file-level permissions work.
         * So now we test individual files' "Inherit" permissions, based on their immediate parent
         */

        // Inherits 'LoggedInUsers': canView = yes
        $folder = $this->createSecuredFolder('CanViewType', 'LoggedInUsers', array(
            'ParentID' => 1
        ));
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canView($member));

        // Inherits 'Inherit': canView = yes
        $folder = $this->createSecuredFolder('CanViewType', 'Inherit', array(
            'ParentID' => 1
        ));
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canView($member));

        // Inherits 'OnlyTheseUsers': canView = no
        $folder = $this->createSecuredFolder('CanViewType', 'OnlyTheseUsers', array(
            'ParentID' => 1
        ));
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canView($member));

        // Inherits 'Anyone': canView = yes
        $folder = $this->createSecuredFolder('CanViewType', 'Anyone', array(
            'ParentID' => 1
        ));
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canView($member));
    }

    /**
     * Can Standard CMS asset-admin users, view individual SECURED files in the CMS?
     */
    public function testCanViewInCMSAsStandardAssetAdmin() {
        $member = $this->objFromFixture('Member', 'can-view-standard-asset-admin-only');

        // LoggedInUsers: canView = no
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertFalse($file->canView($member));

        // Inherit: canView = yes (No parent folder specified, so inherits from nothing)
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertTrue($file->canView($member));

        // OnlyTheseUsers: canView = no
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertFalse($file->canView($member));

        // Anyone: canView = yes
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canView($member));

        /*
         * With reference to the above assertions, we assume individual file-level permissions work.
         * So now we test individual files' "Inherit" permissions, based on their immediate parent
         */

        // Inherits 'LoggedInUsers': canView = no
        $folder = $this->createSecuredFolder('CanViewType', 'LoggedInUsers', array(
            'ParentID' => 1
        ));
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canView($member));

        // Inherits 'Inherit': canView = no (Standard "Files" admin perms, nothing for
        $folder = $this->createSecuredFolder('CanViewType', 'Inherit', array(
            'ParentID' => 1
        ));
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canView($member));

        // Inherits 'OnlyTheseUsers': canView = no
        $folder = $this->createSecuredFolder('CanViewType', 'OnlyTheseUsers', array(
            'ParentID' => 1
        ));
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canView($member));

        // Inherits 'Anyone': canView = yes
        $folder = $this->createSecuredFolder('CanViewType', 'Anyone', array(
            'ParentID' => 1
        ));
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canView($member));
    }

    /**
     * Can AdvancedAsset CMS users, also view individual UNSECURED files in the CMS?
     */
    public function testCanViewStandardAssetsInCMSAsSecuredAssetAdmin() {
        $member = $this->objFromFixture('Member', 'can-view-secured-asset-admin');

        $file = $this->createUnSecuredFile();
        $this->assertTrue($file->canView($member));
    }

    /**
     * Can Standard Asset CMS users, also view individual UNSECURED files in the CMS?
     * Essentially just replicates standard CMS tests for the same thing
     */
    public function testCanViewStandardAssetsInCMSAsStandardAssetAdmin() {
        $member = $this->objFromFixture('Member', 'can-view-standard-asset-admin-only');

        $file = $this->createUnSecuredFile();
        $this->assertTrue($file->canView($member));
    }

    /**
     * Users not logged-into the CMS, but can they see file(s) in the front-end too?
     *
     * See testCanViewFrontByUser() and testCanViewFrontByTime() for more complete tests
     */
    public function testCanViewFrontNotLoggedIn() {
        // No logged-in users, but no  canViewFront = no
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertFalse($file->canViewFront());

        // No logged-in users, but set to "Inherit" with no parent: canViewFront = no (Erring)
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertFalse($file->canViewFront());

        // No logged-in users, but set to "OnlyTheseUsers": canViewFront = no
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertFalse($file->canViewFront());

        // No logged-in users, but set to "Anyone": canViewFront = yes
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFront());
    }

    /**
     * Users may well be logged into the CMS, but can I see file(s) in the front-end too?
     * (and other stories)
     *
     * See testCanViewFrontByUser() and testCanViewFrontByTime() for more complete tests
     */
    public function testCanViewFrontLoggedIn() {
        // Standard AssetAdmin users
        $member = $this->objFromFixture('Member', 'can-view-standard-asset-admin-only');

        // Logged-in users: canViewFront = yes
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFront($member));

        // Logged-in users, but set to "Inherit" with no parent: canViewFront = no
        // What this is _actually_ testing is what happens when a File has no parent by which to judge inheritance.
        // In this case, the logic is conservative in nature and returns false
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertFalse($file->canViewFront($member));

        // Logged-in users, but set to "OnlyTheseUsers": canViewFront = no
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertFalse($file->canViewFront($member));

        // Logged-in users, but set to "Anyone": canViewFront = yes
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFront($member));

        // Advanced AssetAdmin users
        $member = $this->objFromFixture('Member', 'can-view-secured-asset-admin');

        // Logged-in users: canViewFront = no
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFront($member));

        // Logged-in users, but set to "Inherit" with no parent: canViewFront = yes
        // What this is _actually_ testing is what happens when a File has no parent by which to judge inheritance.
        // In this case, the logic is conservative in nature and returns false
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertFalse($file->canViewFront($member));

        // Logged-in users, but set to "OnlyTheseUsers": canViewFront = no
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertFalse($file->canViewFront($member));

        // Logged-in users, but set to "Anyone": canViewFront = yes
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFront($member));

        // Users with permission to see all
        $member = $this->objFromFixture('Member', 'can-view-secure-assets-in-frontend');

        // Logged-in users: canViewFront = no
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFront($member));

        // Logged-in users, but set to "Inherit" with no parent: canViewFront = yes
        // What this is _actually_ testing is what happens when a File has no parent by which to judge inheritance.
        // In this case, the logic checks $member status in FileSecured::canViewFront()
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertTrue($file->canViewFront($member));

        // Logged-in users, but set to "OnlyTheseUsers": canViewFront = yes
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertTrue($file->canViewFront($member));

        // Logged-in users, but set to "Anyone": canViewFront = yes
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFront($member));
    }

    /**
     * Simply tests the return status of FileSecured::canViewFrontByTime() which looks weird out of context.
     */
    public function testCanViewFrontByTime() {
        $file = $this->createSecuredFile(null, null, array(
            'ParentID' => 1,
            'EmbargoType' => 'None'
        ));
        $this->assertTrue($file->canViewFrontByTime());

        $file = $this->createSecuredFile(null, null, array(
            'ParentID' => 1,
            'EmbargoType' => 'Indefinitely'
        ));
        $this->assertFalse($file->canViewFrontByTime(true));

        $file = $this->createSecuredFile(null, null, array(
            'ParentID' => 1,
            'EmbargoType' => 'UntilAFixedDate',
            'EmbargoedUntilDate' => '2030-12-01 01:00:00'
        ));
        $this->assertFalse($file->canViewFrontByTime());

        $file = $this->createSecuredFile(null, null, array(
            'ParentID' => 1,
            'EmbargoType' => 'UntilAFixedDate',
            'EmbargoedUntilDate' => '2003-12-01 01:00:00'
        ));
        $this->assertTrue($file->canViewFrontByTime());
    }

    /**
     *
     */
    public function testCanViewFrontByAnyone() {
        // Logged-in only I'm afraid:canViewFrontByUser Deny
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertFalse($file->canViewFrontByUser());

        // With "Inherit" set and no parent folder, we get conservative: Deny
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertFalse($file->canViewFrontByUser());

        // OnlyTheseUsers = must be logged-in: Deny
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertFalse($file->canViewFrontByUser());

        // Anyone: Allow
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFrontByUser());

        // With parent folders
        $folder = $this->createSecuredFolder('CanViewType', 'LoggedInUsers');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canViewFrontByUser());

        // Nothing for parent folder to inherit from
        $folder = $this->createSecuredFolder('CanViewType', 'Inherit');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canViewFrontByUser());

        $folder = $this->createSecuredFolder('CanViewType', 'OnlyTheseUsers');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canViewFrontByUser());

        $folder = $this->createSecuredFolder('CanViewType', 'Anyone');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canViewFrontByUser());
    }

    /**
     * Can users accessing the frontend, while logged-in, access what they should and shouldn't?
     */
    public function testCanViewFrontByLoggedInUsers() {
        // For standard "Files" admin, logged-in users only - allow
        $member = $this->objFromFixture('Member', 'can-view-standard-asset-admin-only');

        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFrontByUser($member));

        // For logged-in users only - deny (Nothing to inherit from)
        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertFalse($file->canViewFrontByUser($member));

        // For logged-in users only - allow
        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertFalse($file->canViewFrontByUser($member));

        // For logged-in users only - allow
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFrontByUser($member));

        // With parent folders
        $folder = $this->createSecuredFolder('CanViewType', 'LoggedInUsers');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canViewFrontByUser($member));

        // Deny, nothing for parent folder to inherit from
        $folder = $this->createSecuredFolder('CanViewType', 'Inherit');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canViewFrontByUser($member));

        $folder = $this->createSecuredFolder('CanViewType', 'OnlyTheseUsers');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canViewFrontByUser($member));

        $folder = $this->createSecuredFolder('CanViewType', 'Anyone');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canViewFrontByUser($member));

        // For advanced-assets "Files" admin, logged-in users only - allow
        $member = $this->objFromFixture('Member', 'can-view-secured-asset-admin');

        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFrontByUser($member));

        $file = $this->createSecuredFile('CanViewType', 'Inherit');
        $this->assertFalse($file->canViewFrontByUser($member));

        $file = $this->createSecuredFile('CanViewType', 'OnlyTheseUsers');
        $this->assertFalse($file->canViewFrontByUser($member));

        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFrontByUser($member));
    }

    /**
     * Utility method.
     * 
     * @return File
     */
    private function createUnsecuredFile() {
        $file = File::create();
        $file->ParentID = 1;
        $file->Secured = false;
        $file->write();
        
        return $file;
    }
    
    /**
     * Utility method to create a {@link FileSecured} object and save to the test DB.
     * 
     * @param string $can
     * @param string $type
     * @param array $props
     * @return File
     */
    private function createSecuredFile($can = null, $type = null, $props = array()) {
        $file = File::create();
        $file->Secured = true;
        if($can && $type) {
            $file->$can = $type;
        }
        foreach($props as $prop=>$val) {
            $file->$prop = $val;
        }
        $file->write();
        
        return $file;
    }
    
    /**
     * Utility method to create a {@link FolderSecured} object and save to the test DB.
     * 
     * @param string $can
     * @param string $type
     * @param array $props
     * @return Folder
     */
    private function createSecuredFolder($can, $type, $props = array()) {
        $folder = Folder::find_or_make(self::$test_folder);
        $folder->Secured = true;
        $folder->$can = $type;
        $folder->ParentID = 1;
        foreach($props as $prop=>$val) {
            $folder->$prop = $val;
        }
        $folder->write();
        
        return $folder;
    }
}
