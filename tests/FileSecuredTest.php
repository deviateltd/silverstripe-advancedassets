<?php
/**
 * 
 * Attempts to excercise all the customised canXX() methods on {@link FileSecured}. 
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * @todo Complete all possible permutations for canXX() methods
 * @todo Why is a user with ADMIN always running tests?
 */
class FileSecuredTest extends SapphireTest {
    
    /**
     * 
     * @var string
     */
    protected static $fixture_file = 'fixtures/FileSecuredTest.yml';
    
    /**
     * 
     */
    public function testCanView() {
        $member = $this->objFromFixture('Member', 'can-view-secured-files');
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canView($member));
        
        $member = $this->objFromFixture('Member', 'can-view-unsecured-files-only');
        // For completeness - essentially replicate standard CMS permissions checking
        $file = $this->createUnsecuredFile();
        $this->assertTrue($file->canView($member));
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertFalse($file->canView($member));
        
        $member = $this->objFromFixture('Member', 'can-view-secured-files');
        $file = $this->createUnsecuredFile();
        $this->assertTrue($file->canView($member));
        
        // CMS user, but without any secured-specific permissions
        $member = $this->objFromFixture('Member', 'can-view-unsecured-files-only');
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertFalse($file->canView($member));
     
        // Set permissions on Parent - go from there:
        // Parent is very permissive - allow
        $folder = $this->createSecuredFolder('CanViewType', 'Anyone', array(
            'ParentID' => 1
        ));
        $member = $this->objFromFixture('Member', 'can-view-unsecured-files-only');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canView($member));
        $folder = $this->createSecuredFolder('CanViewType', 'LoggedInUsers', array(
            'ParentID' => 1
        ));
        $member = $this->objFromFixture('Member', 'can-view-secured-files');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertTrue($file->canView($member));
        
        // Parent is NOT very permissive - deny
        $folder = $this->createSecuredFolder('CanViewType', 'LoggedInUsers', array(
            'ParentID' => 1
        ));
        $member = $this->objFromFixture('Member', 'can-view-unsecured-files-only');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => $folder->ID
        ));
        $this->assertFalse($file->canView($member));
    }
    
    /**
     * Users may well be logged into the CMS, but can I see file(s) in the front-end too?
     * (and other stories)
     * 
     * See testCanViewFrontByUser() and testCanViewFrontByTime() for more complete tests
     */
    public function testCanViewFront() {
        $member = $this->objFromFixture('Member', 'can-view-secured-files');
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFront($member));
        
        $member = $this->objFromFixture('Member', 'can-view-unsecured-files-only');
        // For completeness - essentially replicate standard CMS permissions checking
        $file = $this->createUnsecuredFile();
        $this->assertTrue($file->canViewFront($member));
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFront($member));
        
        $member = $this->objFromFixture('Member', 'can-view-secured-files');
        $file = $this->createUnsecuredFile();
        $this->assertTrue($file->canViewFront($member));
        
        // CMS user, but without any secured-specific permissions
        $member = $this->objFromFixture('Member', 'can-view-unsecured-files-only');
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFront($member));
        
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFront());
        
        $member = $this->objFromFixture('Member', 'can-view-secured-files');
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
        $this->assertTrue($file->canViewFront($member));
    }
    
    /**
     * 
     */
    public function testCanViewFrontByTime() {
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers', array(
            'ParentID' => 1,
            'EmbargoType' => 'None'
        ));
        $this->assertTrue($file->canViewFrontByTime());
        
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers', array(
            'ParentID' => 1,
            'EmbargoType' => 'Indefinitely'
        ));
        $this->assertFalse($file->canViewFrontByTime());
        
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers', array(
            'ParentID' => 1,
            'EmbargoType' => 'UntilAFixedDate',
            'EmbargoedUntilDate' => '2030-12-01 01:00:00'
        ));
        $this->assertFalse($file->canViewFrontByTime());
        
        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers', array(
            'ParentID' => 1,
            'EmbargoType' => 'UntilAFixedDate',
            'EmbargoedUntilDate' => '2003-12-01 01:00:00'
        ));
        $this->assertTrue($file->canViewFrontByTime());
    }
 
    /**
     * 
     */
    public function testCanViewFrontByUser() {
        $file = $this->createSecuredFile('CanViewType', 'Anyone');
        $this->assertTrue($file->canViewFrontByUser());
        
        // For logged-in users only - deny
        // @todo How to prevent unit-test invoking a logged-in user?
//        $member = $this->objFromFixture('Member', 'can-view-unsecured-files-only');
//        $file = $this->createSecuredFile('CanViewType', 'LoggedInUsers');
//        $this->assertFalse($file->canViewFrontByUser($member));
        
        $member = $this->objFromFixture('Member', 'can-view-unsecured-files-only');
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => 1
        ));
        $this->assertTrue($file->canViewFrontByUser($member));
        
        // Permissions on Parent have not been set, assume all is OK
        $file = $this->createSecuredFile('CanViewType', 'Inherit', array(
            'ParentID' => 0
        ));
        $this->assertTrue($file->canViewFrontByUser());
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
    private function createSecuredFile($can, $type, $props = array()) {
        $file = File::create();
        $file->Secured = true;
        $file->$can = $type;
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
        $folder = Folder::create();
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
