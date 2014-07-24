<?php

/**
 * ManageController
 *
 * Default controller of ProductManagementBundle
 *
 * @vendor          BiberLtd
 * @package         ProductManagementBundle
 * @subpackage      Controller
 * @name	    ManageController
 *
 * @author          Said İmamoğlu
 *
 * @copyright       Biber Ltd. (www.biberltd.com)
 *
 * @version         1.0.0
 * @date            27.11.2013
 *
 */

namespace BiberLtd\Core\Bundles\ProductManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpKernel\Exception,
    Symfony\Component\HttpFoundation\Response;

class ManageController extends Controller {

    /**
     * @name 		productListAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productListAction() {
        
    }
    /**
     * @name 		productEditAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productEditAction() {
        
    }
    /**
     * @name 		productNewAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productNewAction() {
        
    }
    /**
     * @name 		productDeleteAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productDeleteAction() {
        
    }
    /**
     * @name 		productCategoryListAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productCategoryListAction() {
        
    }
    /**
     * @name 		productCategoryEditAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productCategoryEditAction() {
        
    }
    /**
     * @name 		productCategoryNewAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productCategoryNewAction() {
        
    }
    /**
     * @name 		productCategoryDeleteAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productCategoryDeleteAction() {
        
    }
    /**
     * @name 		productAttributeListAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productAttributeListAction() {
        
    }
    /**
     * @name 		productAttributeEditAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productAttributeEditAction() {
        
    }
    /**
     * @name 		productAttributeNewAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productAttributeNewAction() {
        
    }
    /**
     * @name 		productAttributeDeleteAction()
     * List products 
     *
     * @since		1.0.6
     * @version         1.0.6
     * @author          Said İmamoğlu
     *
     * @use             $this->doesProductCategoryExist()
     * @use             $this->createException()
     *
     * @param           array           $collection      Collection of Product entities or array of entity details.
     * @param           array           $by              entity, post
     *
     * @return          array           $response
     */
    public function productAttributeDeleteAction() {
        
    }

}

/**
 * Change Log:
 * **************************************
 * v1.0.0                     Said İmamoğlu
 * 27.11.2013
 * **************************************
 * A productListAction()
 * A productEditAction()
 * A productNewAction()
 * A productDeleteAction()
 * A productCategoryListAction()
 * A productCategoryEditAction()
 * A productCategoryNewAction()
 * A productCategoryDeleteAction()
 * A productAttributeListAction()
 * A productAttributeEditAction()
 * A productAttributeNewAction()
 * A productAttributeDeleteAction()
 * 
 * **************************************
 * v1.0.0                      Can Berkol
 * 01.08.2013
 * **************************************
 * A
 *
 */