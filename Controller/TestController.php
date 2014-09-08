<?php
/**
 * TestController
 *
 * This controller is used to install default / test values to the system.
 * The controller can only be accessed from allowed IP address.
 *
 * @package		MemberManagementBundleBundle
 * @subpackage	Controller
 * @name	    TestController
 *
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (www.biberltd.com)
 *
 * @version     1.0.0
 *
 */

namespace BiberLtd\Bundle\ProductManagementBundle\Controller;

use BiberLtd\Bundle\ProductManagementBundle\Services\ProductManagementModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpKernel\Exception,
    Symfony\Component\HttpFoundation\Response,
    BiberLtd\Core\CoreController;

class TestController extends CoreController{

    public function testAction(){
        $brand = new \stdClass();
        $brand->id = 2;
        $brand->name = 'Test-2';
        $brand->logo = 'test2.png';
        $pModel = $this->get('productmanagement.model');
//        $response = $pModel->insertBrand($brand);
        $response = $pModel->updateBrand($brand);
        if ($response['error']) {
            exit('eklenmedi');
        }
        echo $response['result']['set'][0]->getId();die;
    }
}