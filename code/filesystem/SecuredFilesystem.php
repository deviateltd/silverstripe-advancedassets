<?php
/**
 * 
 * @author Deviate Ltd 2014-2015 <http://deviate.net.nz>
 * @package silverstripe-advancedassets
 * @todo Modify show_access_message() to show messages within the CMS.
 */
class SecuredFilesystem extends Filesystem {
    
    /**
     * 
     * Adapted and simplified from {@link Security::permissionFailure()}.
     * 
     * @param Controller $controller
     * @param string $message
     * @return SS_HTTPResponse $response
     */
    public static function show_access_message($controller, $message = '') {
        $response = $controller->getResponse();
        $response->setBody($message);
        $response->setStatusDescription($message);
        $response->setStatusCode(403);
        return $response;
    }
    
    /**
     * Utility static to avoid repetition.
     * 
     * @param Controller $controller
     * @param string $identifier e.g. 'ParentID' or 'ID'
     * @retun number
     */
    public static function get_numeric_identifier(Controller $controller, $identifier = 'ID') {
        $params = $controller->getRequest()->getVars();
        $useId = function() use($controller, $params, $identifier) {
            if(!isset($params[$identifier])) {
                if(!isset($controller->urlParams[$identifier])) {
                    return 0;
                }
                return $controller->urlParams[$identifier];
            }
            return $params[$identifier];
        };
        
        $id = $useId();
        return (int) !empty($id) && is_numeric($id) ? $id : 0;
    }
}
