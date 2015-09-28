<?php
/**
 * 
 * At time of writing simply exercises initValidate() to ensure only selected users are able
 * to access the file-add controller
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 * @todo Complete all possible permutations for canXX() methods
 * @todo Why is a user with ADMIN always running tests?
 */
class CMSSecuredFileAddControllerTest extends FunctionalTest {

    /**
     *
     * @var string
     */
    protected static $fixture_file = 'fixtures/CMSSecuredFileAddControllerTest.yml';

    /**
     * User may proceed to file-add controller (Admin)
     */
    public function testInitValidateCanAddAsADMIN() {
        // First create and fetch a base folder for advanced assets
        $this->objFromFixture('Folder', 'is-secured');

        $this->loginWithPermission('ADMIN');
        $response = $this->get('/admin/advanced-assets/add/?locale=en_NZ&ID=44');
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * User may proceed to file-add controller (Edit_All)
     */
    public function testInitValidateCanAdd() {
        // First create and fetch a base folder for advanced assets
        $this->objFromFixture('Folder', 'is-secured');

        $member = $this->objFromFixture('Member', 'can-add');
        $this->logInAs($member);
        $response = $this->get('/admin/advanced-assets/add/?locale=en_NZ&ID=44');
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * User may not proceed to file-add controller (View_All)
     */
    public function testInitValidateCannotAdd() {
        // First create and fetch a base folder for advanced assets
        $this->objFromFixture('Folder', 'is-secured');

        // User may _not_ proceed to file-add controller
        $member = $this->objFromFixture('Member', 'can-view-only');
        $this->logInAs($member);
        $response = $this->get('/admin/advanced-assets/add/?locale=en_NZ&ID=44');
        $this->assertEquals(403, $response->getStatusCode());
    }
}
