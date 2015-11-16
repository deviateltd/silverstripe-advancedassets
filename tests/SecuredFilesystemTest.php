<?php
/**
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 */
class SecuredFilesystemTest extends SapphireTest {
    
    /**
     * Exercises SecuredFilesystem::get_numeric_identifier() with both types of expected controller
     * Ensures expected outputs given a variety of inputs
     *
     * @todo Add 'ParentID' ensure this works as expected
     * @todo  Test with "unexpected" class, assert exedcption or error thrown as expecced (Zero is returned)
     */
    public function testGetNumericIdentifierGet() {
        $controller = $this->getTestController(array('ID' => '40'), 'GET', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ID' => '40'), 'GET', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ID' => '0'), 'GET', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('ID' => '0'), 'GET', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('ParentID' => '40'), 'GET', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ParentID' => '40'), 'GET', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ParentID' => '0'), 'GET', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('ParentID' => '0'), 'GET', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('DUMMY' => '40'), 'GET', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('DUMMY' => '40'), 'GET', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('ID' => '40'), 'GET', Controller::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);
    }

    /**
     * Exercises SecuredFilesystem::get_numeric_identifier() with both types of expected controller
     * Ensures expected outputs given a variety of inputs
     *
     * @todo Add 'ParentID' ensure this works as expected
     * @todo  Test with "unexpected" class, assert exedcption or error thrown as expecced (Zero is returned)
     */
    public function testGetNumericIdentifierPost() {
        $controller = $this->getTestController(array('ID' => '40'), 'POST', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ID' => '40'), 'POST', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ID' => '0'), 'POST', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('ID' => '0'), 'POST', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('ParentID' => '40'), 'POST', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ParentID' => '40'), 'POST', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ParentID' => '0'), 'POST', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('ParentID' => '0'), 'POST', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('DUMMY' => '40'), 'POST', AssetAdmin::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('DUMMY' => '40'), 'POST', CMSFileAddController::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);

        $controller = $this->getTestController(array('ID' => '40'), 'POST', Controller::create());
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);
    }
    
    /**
     * 
     * @param array $vars
     * @param string $method
     * @pram Controller $controller
     * @return Controller
     */
    private function getTestController($vars, $method, $controller) {
        if($method == 'POST') {
            $getVars = array();
            $postVars = $vars;
        } else {
            $getVars = $vars;
            $postVars = array();
        }
        
        $request = new SS_HTTPRequest($method, '/admin/assets', $getVars, $postVars);
        $controller->setRequest($request);
        return $controller;
    }
}
