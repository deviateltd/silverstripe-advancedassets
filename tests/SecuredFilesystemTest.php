<?php
/**
 * 
 * @author Deviate Ltd 2014-2015 http://www.deviate.net.nz
 * @package silverstripe-advancedassets
 */
class SecuredFilesystemTest extends SapphireTest {
    
    /**
     * Excercises SecuredFilesystem::get_numeric_identifier().
     * Ensures expected outputs given a variety of inputs
     */
    public function testGetNumericIdentifier() {
        $controller = $this->getTestController(array('ID' => 40), 'GET');
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);
        
        $controller = $this->getTestController(array('ParentID' => 40), 'GET');
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);

        $controller = $this->getTestController(array('ParentID' => 40), 'POST');
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ParentID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(40, $result);
        
        $controller = $this->getTestController(array('DUMMY' => 40), 'GET');
        $result = SecuredFilesystem::get_numeric_identifier($controller, 'ID');
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);
    }
    
    /**
     * 
     * @param array $vars
     * @param string $method 
     * @return Controller
     */
    private function getTestController($vars, $method) {
        $controller = Controller::create();
        if($method === 'post') {
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
