<?php

/**
 * ProductManagementModel Class
 *
 * This class acts as a database proxy model for ProductManagementBundle functionalities.
 *
 * @vendor          BiberLtd
 * @package         Core\Bundles\ProductManagementBundle
 * @subpackage      Services
 * @name            ProductManagementModel
 *
 * @author          Can Berkol
 * @author          Said İmamoğlu
 *
 * @copyright       Biber Ltd. (www.biberltd.com)
 *
 * @version         1.5.1
 * @date            03.03.2015
 *
 * =============================================================================================================
 * !! INSTRUCTIONS ON IMPORTANT ASPECTS OF MODEL METHODS !!!
 *
 * Each model function must return a $response ARRAY.
 * The array must contain the following keys and corresponding values.
 *
 * $response = array(
 *              'result'    =>   An array that contains the following keys:
 *                               'set'         Actual result set returned from ORM or null
 *                               'total_rows'  0 or number of total rows
 *                               'last_insert_id' The id of the item that is added last (if insert action)
 *              'error'     =>   true if there is an error; false if there is none.
 *              'code'      =>   null or a semantic and short English string that defines the error concanated
 *                               with dots, prefixed with err and the initials of the name of model class.
 *                               EXAMPLE: err.amm.action.not.found success messages have a prefix called scc..
 *
 *                               NOTE: DO NOT FORGET TO ADD AN ENTRY FOR ERROR CODE IN BUNDLE'S
 *                               RESOURCES/TRANSLATIONS FOLDER FOR EACH LANGUAGE.
 * =============================================================================================================
 * TODOs:
 * Do not forget to implement SITE, ORDER, AND PAGINATION RELATED FUNCTIONALITY
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Services;

/** Extends CoreModel */
use BiberLtd\Bundle\CoreBundle\CoreModel;
/** Entities to be used */
use BiberLtd\Bundle\ProductManagementBundle\Entity as BundleEntity;
use BiberLtd\Bundle\FileManagementBundle\Entity as FileBundleEntity;
use BiberLtd\Bundle\MultiLanguageSupportBundle\Entity as MLSEntity;
use BiberLtd\Bundle\SiteManagementBundle\Entity as SiteManagementEntity;
/** Helper Models */
use BiberLtd\Bundle\FileManagementBundle\Services as FMMService;
use BiberLtd\Bundle\MultiLanguageSupportBundle\Services as MLSService;
use BiberLtd\Bundle\SiteManagementBundle\Services as SMMService;
/** Core Service */
use BiberLtd\Bundle\CoreBundle\Services as CoreServices;
use BiberLtd\Bundle\CoreBundle\Exceptions as CoreExceptions;

class ProductManagementModel extends CoreModel{
    /**
     * @name            __construct()
     *                  Constructor.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.2.3
     *
     * @param           object $kernel
     * @param           string $db_connection Database connection key as set in app/config.yml
     * @param           string $orm ORM that is used.
     */
    public function __construct($kernel, $db_connection = 'default', $orm = 'doctrine'){
        parent::__construct($kernel, $db_connection, $orm);
        /**
         * Register entity names for easy reference.
         */
        $this->entity = array(
            'active_product_locale' => array('name' => 'ProductManagementBundle:ActiveProductLocale', 'alias' => 'apl'),
            'active_product_category_locale' => array('name' => 'ProductManagementBundle:ActiveProductCategoryLocale', 'alias' => 'apcl'),
            'attributes_of_product' => array('name' => 'ProductManagementBundle:AttributesOfProduct', 'alias' => 'aop'),
            'attributes_of_product_category' => array('name' => 'ProductManagementBundle:AttributesOfProductCategory', 'alias' => 'aopc'),
            'brand' => array('name' => 'ProductManagementBundle:Brand', 'alias' => 'b'),
            'categories_of_product' => array('name' => 'ProductManagementBundle:CategoriesOfProduct', 'alias' => 'cop'),
            'file' => array('name' => 'FileManagementBundle:File', 'alias' => 'f'),
            'files_of_product' => array('name' => 'ProductManagementBundle:FilesOfProduct', 'alias' => 'fop'),
            'language' => array('name' => 'MultiLanguageSupportBundle:Language', 'alias' => 'l'),
            'product' => array('name' => 'ProductManagementBundle:Product', 'alias' => 'p'),
            'product_attribute' => array('name' => 'ProductManagementBundle:ProductAttribute', 'alias' => 'pa'),
            'product_attribute_localization' => array('name' => 'ProductManagementBundle:ProductAttributeLocalization', 'alias' => 'pal'),
            'product_attribute_values' => array('name' => 'ProductManagementBundle:ProductAttributeValues', 'alias' => 'pav'),
            'product_localization' => array('name' => 'ProductManagementBundle:ProductLocalization', 'alias' => 'pl'),
            'product_category' => array('name' => 'ProductManagementBundle:ProductCategory', 'alias' => 'pc'),
            'product_category_localization' => array('name' => 'ProductManagementBundle:ProductCategoryLocalization', 'alias' => 'pcl'),
            'products_of_site' => array('name' => 'ProductManagementBundle:ProductsOfSite', 'alias' => 'pos'),
            'related_product' => array('name' => 'ProductManagementBundle:RelatedProduct', 'alias' => 'rp'),
            'volume_pricing' => array('name' => 'ProductManagementBundle:VolumePricing', 'alias' => 'vp'),
        );
    }

    /**
     * @name            __destruct ()
     *                  Destructor.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     */
    public function __destruct(){
        foreach ($this as $property => $value) {
            $this->$property = null;
        }
    }

    /**
     * @name            addAttributesToProduct()
     *                  Associates attributes with a given product.
     *
     * @since           1.1.7
     * @version         1.4.2
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getMaxSortOrderOfAttributeInProduct()
     * @use             $this->getProductAttribute()
     * @use             $this->isAttributeAssociatedWithProduct()
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProduct()
     *
     * @param           array           $set        Collection of attribute and sortorder set
     *                                              Contains an array with two keys: attribute, and sortorder
     * @param           mixed           $product    'entity' or 'entity' id or sku.
     *
     * @return          array           $response
     */
    public function addAttributesToProduct($set, $product){
        $this->resetResponse();
        $count = 0;
        /** remove invalid product entries */
        foreach ($set as $attr) {
            if (!is_numeric($attr['attribute']) && !$attr['attribute'] instanceof BundleEntity\ProductAttribute) {
                unset($set[$count]);
            }
            $count++;
        }
        /** issue an error only if there is no valid file entries */
        if (count($set) < 1) {
            return $this->createException('InvalidAttributeSet', '', 'msg.error.invalid.parameter.product.attribute.set', false);
        }
        unset($count);
        $product = $this->validateAndGetProduct($product);

        $aopCcollection = array();
        $count = 0;
        /** Start persisting files */
        foreach ($set as $item) {
            /** If no entity is provided as product we need to check if it does exist */
            $aopCollection = array();
            /** Check if association exists */
            if (!$this->isAttributeAssociatedWithProduct($item['attribute'], $product, true)) {
                $aop = new BundleEntity\AttributesOfProduct();
                $now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
                $aop->setAttribute($item['attribute'])->setProduct($product)->setDateAdded($now)->setPriceFactorType('a');
                if (!is_null($item['sort_order'])) {
                    $aop->setSortOrder($item['sort_order']);
                } else {
                    $aop->setSortOrder($this->getMaxSortOrderOfAttributeInProduct($product, true) + 1);
                }
                /** persist entry */
                $this->em->persist($aop);
                $aopCollection[] = $aop;
                $count++;
            }
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['code'] = 'err.db.insert.failed';
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $aopCollection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        unset($count, $aopCollection);
        return $this->response;
    }
    /**
     * @name            addFilesToProduct ()
     *                  Associates files with a given product by creating new row in files_of_product_table.
     *
     * @since           1.0.2
     * @version         1.4.2
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->getMaxSortOrderOfProductFile()
     * @use             $this->isFileAssociatedWithProduct()
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProduct()
     *
     * @param           array           $set        Collection consists one of the following: 'entity' or entity 'id'
     *                                                  Contains an array with two keys: file, and sortorder
     * @param           mixed           $product    'entity' or 'entity' id.
     *
     * @return          array           $response
     */
    public function addFilesToProduct($set, $product){
        $this->resetResponse();
        $product = $this->validateAndGetProduct($product);
        $fmmodel = $this->kernel->getContainer()->get('filemanagement.model');

        $fop_collection = array();
        $count = 0;
        /** Start persisting files */
        foreach ($set as $file) {
            /** If no entity s provided as file we need to check if it does exist */
            if (!$file['file'] instanceof FileBundleEntity\File && !is_numeric($file['file'])) {
                return $this->createException('InvalidParameter', '$file parameter must hold BiberLtd\\Core\\Bundles\\FileManagementBundle\\Entity\\File Entity or integer representing database row id', 'msg.error.invalid.parameter.file');
            }
            if (is_numeric($file['file'])) {
                $response = $fmmodel->getFile($file['file'], 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Table : file, id: ' . $file['file'], 'msg.error.db.file.notfound');
                }
                $file['file'] = $response['result']['set'];
            }
            /** Check if association exists */
            if ($this->isFileAssociatedWithProduct($file['file'], $product, true)) {
                return $this->createException('DuplicateAssociation', 'File (' . $file['file']->getId() . ') and Product (' . $product->getId() . ') is already associated.', 'msg.error.db.duplicate.association');
            }

            $fop = new BundleEntity\FilesOfProduct();
            $now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
            $fop->setFile($file['file'])->setProduct($product)->setDateAdded($now);
            if (!is_null($file['sort_order'])) {
                $fop->setSortOrder($file['sort_order']);
            } else {
                $fop->setSortOrder($this->getMaxSortOrderOfProductFile($product, true) + 1);
            }
            $fop->setType($file['file']->getType());
            /** persist entry */
            $this->em->persist($fop);
            $fop_collection[] = $fop;
            $count++;
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['code'] = 'msg.error.db.insert.failed';
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $fop_collection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'msg.success.db.insert',
        );
        unset($count, $aplCollection);
        return $this->response;
    }

    /**
     * @name            addLocalesToProduct ()
     *                  Associates locales with a given product by creating new row in files_of_product_table.
     *
     * @since           1.2.3
     * @version         1.4.9
     * @author          Can Berkol
     *
     * @use             $this->isLocaleAssociatedWithProduct()
     * @use             $this->validateAndGetProduct()
     *
     * @param           array       $locales Language entities, ids or iso_codes
     * @param           mixed       $product entity, id or sku
     *
     * @return          array       $response
     */
    public function addLocalesToProduct($locales, $product){
        $this->resetResponse();
        /** issue an error only if there is no valid file entries */
        if (count($locales) < 1) {
            return $this->createException('InvalidCollection', 'The $locales parameter must be an array collection.', 'msg.error.invalid.parameter.locales');
        }
        $product = $this->validateAndGetProduct($product);

        $aplCollection = array();
        $count = 0;
        /** Start persisting locales */
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        foreach ($locales as $locale) {
            $locale = $this->validateAndGetLocale($locale);
            /** If no entity s provided as file we need to check if it does exist */
            /** Check if association exists */
            if ($this->isLocaleAssociatedWithProduct($locale, $product, true)) {
                $this->response = array(
                    'rowCount' => $this->response['rowCount'],
                    'result' => array(
                        'set' => null,
                        'total_rows' => 0,
                        'last_insert_id' => -1,
                    ),
                    'error' => true,
                    'code' => 'msg.db.error.association.exist',
                );
                return $this->response();
            }
            $apl = new BundleEntity\ActiveProductLocale();
            $apl->setLocale($locale)->setProduct($product);

            /** persist entry */
            $this->em->persist($apl);
            $aplCollection[] = $apl;
            $count++;
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['code'] = 'msg.error.db.insert.failed';
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $aplCollection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'msg.success.db.insert',
        );
        unset($count, $fop_collection);
        return $this->response;
    }
    /**
     * @name            addLocalesToProductCategory ()
     *                  Associates locales with a given product category by creating new row in files_of_product_table.
     *
     * @since           1.2.3
     * @version         1.4.2
     * @author          Can Berkol
     * @use             $this->createException()
     * @use             $this->validateAndGetLocale()
     * @use             $this->validateAndGetProductCategory()
     * @use             $this->isLocaleAssociatedWithProduct()
     *
     * @param           array           $locales        Language entities, ids or iso_codes
     * @param           mixed           $category       entity, id
     *
     * @return          array           $response
     */
    public function addLocalesToProductCategory($locales, $category){
        $this->resetResponse();
        /** issue an error only if there is no valid file entries */
        if (count($locales) < 1) {
            return $this->createException('InvalidCollection', 'The $locales parameter must be an array collection.', 'msg.error.invalid.parameter.locales');
        }
        $category = $this->validateAndGetProductCategory($category);

        $aplCollection = array();
        $count = 0;
        /** Start persisting locales */
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        foreach ($locales as $locale) {
            $locale = $this->validateAndGetLocale($locale);
            /** If no entity s provided as file we need to check if it does exist */
            /** Check if association exists */
            if ($this->isLocaleAssociatedWithProductCategory($locale, $category, true)) {
                $this->response = array(
                    'rowCount' => $this->response['rowCount'],
                    'result' => array(
                        'set' => null,
                        'total_rows' => 0,
                        'last_insert_id' => -1,
                    ),
                    'error' => true,
                    'code' => 'msg.error.db.association.exist',
                );
                return $this->response;
            }
            $apl = new BundleEntity\ActiveProductCategoryLocale();
            $apl->setLocale($locale)->setProductCategory($category);

            /** persist entry */
            $this->em->persist($apl);
            $aplCollection[] = $apl;
            $count++;
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['code'] = 'msg.error.db.insert.failed';
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $aplCollection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'msg.success.db.insert',
        );
        unset($count, $fop_collection);
        return $this->response;
    }
    /**
     * @name            addProductsToCategory ()
     *                  Associates products with a given product category by creating new row in files_of_product_table.
     *
     * @since           1.0.2
     * @version         1.4.2
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getMaxSortOrderOfProductInCategory()
     * @use             $this->isProductAssociatedWithCategory()
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProduct()
     * @use             $this->validateAndGetProductCategory()
     *
     * @param           array           $products       Collection consists one of the following: 'entity' or entity 'id'
     *                                                  Contains an array with two keys: file, and sortorder
     * @param           mixed           $category       'entity' or 'entity' id.
     *
     * @return          array           $response
     */
    public function addProductsToCategory($products, $category){
        $this->resetResponse();
        /** issue an error onlu if there is no valid file entries */
        if (count($products) < 1) {
            return $this->createException('InvalidCollection', '$products parameter must hold an array collection.', 'err.invalid.parameter.products');
        }
        $category = $this->validateAndGetProductCategory($category);
        $cop_collection = array();
        $count = 0;
        /** Start persisting files */
        foreach ($products as $product) {
            $productEntity = $this->validateAndGetProduct($product['product']);

            /** Check if association exists */
            if ($this->isProductAssociatedWithCategory($productEntity, $category, true)) {
                //                new CoreExceptions\DuplicateAssociationException($this->kernel, 'Product => Category');
                //                $this->response['code'] = 'msg.error.db.duplicate.association';
                /** If file association already exist move silently to next file */
                break;
            }
            /** prepare object */
            $cop = new BundleEntity\CategoriesOfProduct();
            $now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
            $cop->setProduct($productEntity)->setCategory($category)->setDateAdded($now);
            if (!is_null($product['sort_order'])) {
                $cop->setSortOrder($product['sort_order']);
            } else {
                $cop->setSortOrder($this->getMaxSortOrderOfProductInCategory($category, true) + 1);
            }
            /** persist entry */
            $this->em->persist($cop);
            $cop_collection[] = $cop;
            $count++;
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['code'] = 'msg.error.db.insert.failed';
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $cop_collection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'msg.success.db.insert',
        );
        unset($count, $cop_collection);
        return $this->response;
    }
    /**
     * @name            addProductToCategories()
     *                  Associates products with a given product category by creating new row in files_of_product_table.
     *
     * @since           1.2.5
     * @version         1.4.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProduct()
     * @use             $this->validateAndGetProductCategory()
     *
     * @param           mixed           $product            'entity' or 'entity' id.
     * @param           array           $categories         Collection consists one of the following: 'entity' or entity 'id'
     *
     * @return          array           $response
     */
    public function addProductToCategories($product, $categories){
        $this->resetResponse();
        /** issue an error only if there is no valid file entries */
        if (count($categories) < 1) {
            return $this->createException('InvalidCollection', 'The $categories parameter must be an array collection.', 'msg.error.invalid.collection.array');
        }
        $product = $this->validateAndGetProduct($product);
        $productCollection = array();
        $count = 0;
        /** Start persisting files */
        foreach ($categories as $category) {
            $category = $this->validateAndGetProductCategory($category);

            /** Check if association exists */
            if ($this->isProductAssociatedWithCategory($product, $category, true)) {
                new CoreExceptions\DuplicateAssociationException($this->kernel, 'Product => Category');
                $this->response['code'] = 'msg.error.db.duplicate.association';
                /** If file association already exist move silently to next file */
                break;
            }
            /** prepare object */
            $cop = new BundleEntity\CategoriesOfProduct();
            $now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
            $cop->setProduct($product)->setCategory($category)->setDateAdded($now);
            if (!is_null($product->getSortOrder())) {
                $cop->setSortOrder(1);
            } else {
                $cop->setSortOrder($this->getMaxSortOrderOfProductInCategory($category, true) + 1);
            }
            /** persist entry */
            $this->em->persist($cop);
            $productCollection[] = $cop;
            $count++;
        }

        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        }
        else {
            $this->response['code'] = 'msg.error.db.insert.failed';
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $productCollection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'msg.success.db.insert',
        );
        unset($count, $cop_collection);
        return $this->response;
    }
    /**
     * @name            addProductCategoriesToLocale ()
     *                  Associates products categories with a given locale.
     *
     * @since           1.2.3
     * @version         1.4.2
     * @author          Can Berkol
     *
     * @use             $this->isLocaleAssociatedWithProductCategory()
     * @use             $this->validateAndGetLocale()
     * @use             $this->validateAndGetProductCategory()
     *
     * @param           array       $categories         Product Category entities, ids or iso_codes
     * @param           mixed       $locale             Language entity, id or sku
     *
     * @return          array       $response
     */
    public function addProductCategoriesToLocale($categories, $locale){
        $this->resetResponse();
        /** issue an error only if there is no valid file entries */
        if (count($categories) < 1) {
            return $this->createException('InvalidCollection', 'The $categories parameter must be an array collection.', 'msg.error.invalid.collection.array');
        }
        if (!is_numeric($locale) && !$locale instanceof MLSEntity\Locale) {
            return $this->createException('InvalidParameter', 'You have provided "'.gettype($locale).'" value in $locale parameter. The parameter is allowed to hold an integer representing row id, a string representing language iso code or a BiberLtd\\Core\\Bundles\\MultiLanguageSupport\\Entity\\Language entity.', 'msg.error.invalid.parameter.language');
        }
        $locale = $this->validateAndGetLocale($locale);

        $aplCollection = array();
        $count = 0;
        /** Start persisting locales */
        foreach ($categories as $category) {
            $category = $this->validateAndGetProductCategory($category);
            /** If no entity s provided as file we need to check if it does exist */
            /** Check if association exists */
            if ($this->isLocaleAssociatedWithProductCategory($locale, $category, true)) {
                $this->response = array(
                    'rowCount' => $this->response['rowCount'],
                    'result' => array(
                        'set' => null,
                        'total_rows' => 0,
                        'last_insert_id' => -1,
                    ),
                    'error' => true,
                    'code' => 'msg.error.db.duplicate.association',
                );
                return $this->response;
            }
            $apl = new BundleEntity\ActiveProductCategoryLocale();
            $apl->setLocale($locale)->setProductCategory($category);

            /** persist entry */
            $this->em->persist($apl);
            $aplCollection[] = $apl;
            $count++;
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['code'] = 'msg.error.db.insert.failed';
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $aplCollection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'msg.success.db.insert',
        );
        unset($count, $aplCollection);
        return $this->response;
    }

    /**
     * @name            addProductsToLocale ()
     *                  Associates products with a given locale by creating new row in files_of_product_table.
     *
     * @since           1.2.3
     * @version         1.4.2
     * @author          Can Berkol
     *
     * @use             $this->resetResponse()
     *
     * @param           array           $products       Product entities, ids or iso_codes
     * @param           mixed           $locale         Language entity, id or sku
     *
     * @return          array           $response
     */
    public function addProductsToLocale($products, $locale){
        $this->resetResponse();
        /** remove invalid prodcut entries */
        foreach ($products as $product) {
            if (!is_numeric($product) && !$product instanceof BundleEntity\Product) {
                $this->createException('InvalidParameter', 'You have provided "'.gettype($product).'" value in in $products collection. The collection is allowed to hold an integer, a string or a BiberLtd\\Core\\Bundles\\ProductManagementBundle\\Entity\\Product entity.', 'msg.error.invalid.parameter.product');
                unset($locale);
            }
        }
        /** issue an error only if there is no valid file entries */
        if (count($products) < 1) {
            return $this->createException('InvalidCollection', 'The $products parameter must be an array collection.', 'msg.error.invalid.collection.array');
        }
        $locale = $this->validateAndGetLocale($locale);

        $aplCollection = array();
        $count = 0;
        /** Start persisting locales */
        foreach ($products as $product) {
            $product = $this->validateAndGetProduct($product);
            /** If no entity s provided as file we need to check if it does exist */
            /** Check if association exists */
            if ($this->isLocaleAssociatedWithProduct($locale, $product, true)) {
                $this->response = array(
                    'rowCount' => $this->response['rowCount'],
                    'result' => array(
                        'set' => null,
                        'total_rows' => 0,
                        'last_insert_id' => -1,
                    ),
                    'error' => true,
                    'code' => 'msg.error.db.dupplicate.association',
                );
                return $this->response();
            }
            $apl = new BundleEntity\ActiveProductLocale();
            $apl->setLocale($locale)->setProduct($product);

            /** persist entry */
            $this->em->persist($apl);
            $aplCollection[] = $apl;
            $count++;
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['code'] = 'msg.error.db.insert.failed';
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $aplCollection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'msg.success.db.insert',
        );
        unset($count, $aplCollection);
        return $this->response;
    }
    /**
     * @name            countProducts ()
     *                  Get the total count of products.
     *
     * @since           1.1.2
     * @version         1.1.2
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array           $filter             Multi-dimensional array
     * @param           string          $queryStr           Custom query
     *
     * @return          array           $response
     */
    public function countProducts($filter = null, $queryStr = null){
        $this->resetResponse();
        /**
         * Add filter checks to below to set join_needed to true.
         */
        $where_str = '';
        if (is_null($queryStr)) {
            $queryStr = 'SELECT COUNT(' . $this->entity['product']['alias'] . ')'
                . ' FROM ' . $this->entity['product']['name'] . ' ' . $this->entity['product']['alias'];
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }

        $queryStr .= $where_str;
        $query = $this->em->createQuery($queryStr);
        $result = $query->getSingleScalarResult();
        $this->response = array(
            'result' => array(
                'set' => $result,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            countProductsOfCategory ()
     *                  Get the total products of category.
     *
     * @since           1.3.3
     * @version         1.4.2
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->resetResponse()
     *
     * @param           mixed           $category
     * @param           array           $filter         Multi-dimensional array
     * @param           string          $queryStr       Custom query
     *
     * @return          array           $response
     */
    public function countProductsOfCategory($category, $filter = array(), $queryStr = null){
        $this->resetResponse();

        $category = $this->validateAndGetProductCategory($category);
        /**
         * Add filter checks to below to set join_needed to true.
         */
        $where_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT COUNT(' . $this->entity['categories_of_product']['alias'] . ')'
                . ' FROM ' . $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias'];
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['categories_of_product']['alias'] . '.category', 'comparison' => '=', 'value' => $category->getId()),
                )
            )
        );
        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }

        $queryStr .= $where_str;

        $query = $this->em->createQuery($queryStr);

        /**
         * Prepare & Return Response
         */
        $result = $query->getSingleScalarResult();

        $this->response = array(
            'result' => array(
                'set' => $result,
                'total_rows' => $result,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            deleteProductAttribute ()
     *                  Deletes an existing product attribute from database.
     *
     * @since           1.0.5
     * @version         1.1.7
     * @author          Can Berkol
     *
     * @use             $this->deleteProductAttributes()
     *
     * @param           mixed           $data           a single value of 'entity', 'id', 'url_key'
     *
     * @return          mixed           $response
     */
    public function deleteProductAttribute($data){
        return $this->deleteProductAttributes(array($data));
    }
    /**
     * @name            deleteAllAttributeValuesByAttribute ()
     *                  Deletes all attribute values of a product for t.
     *
     * @since           1.3.0
     * @version         1.3.0
     * @author          Can Berkol
     *
     * @use             $this->delete_entities()
     * @use             $this->createException()
     *
     * @param           mixed $attribute
     * @param           mized $product
     *
     * @return          array           $response
     *
     * @deprecated      Will be deleted in v1.2.0. Use $this->deleteAttributeValuesOfProductByAttribute() insetead.
     */
    public function deleteAllAttributeValuesByAttribute($attribute, $product){
        return $this->deleteAllAttributeValuesOfProductByAttribute($attribute, $product);
    }
    /**
     * @name            deleteAllAttributeValuesOfAttribute ()
     *                  Deletes all attribute values of a product by attribute.
     *
     * @since           1.4.2
     * @version         1.4.2
     * @author          Can Berkol
     *
     * @use             $this->resetResponse();
     * @use             $this->validateAndGetProduct()
     * @use             $this->validateAndGetProductAttribute()
     *
     * @param           mixed           $attribute
     * @param           mixed           $product
     *
     * @return          array           $response
     */
    public function deleteAllAttributeValuesOfProductByAttribute($attribute, $product){
        $this->resetResponse();
        /** Parameter must be an array */
        $attribute = $this->validateAndGetProductAttribute($attribute);
        $product = $this->validateAndGetProduct($product);
        $qStr = 'DELETE FROM ' . $this->entity['product_attribute_values']['name'] . ' ' . $this->entity['product_attribute_values']['alias']
            . ' WHERE ' . $this->entity['product_attribute_values']['alias'] . '.attribute = ' . $attribute->getId()
            . ' AND ' . $this->entity['product_attribute_values']['alias'] . '.product = ' . $product->getId();
        $query = $this->em->createQuery($qStr);
        $query->getResult();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => 0,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.delete',
        );
        return $this->response;
    }
    /**
     * @name            deleteProductAttributes()
     *                  Deletes provided product attributes from database.
     *
     * @since           1.0.4
     * @version         1.4.3
     * @author          Can Berkol
     *
     * @use             $this->delete_entities()
     * @use             $this->createException()
     *
     * @param           array           $collection             Collection consists one of the following: 'entity', 'id', 'sku', 'site', 'type', 'status'
     *
     * @return          array           $response
     */
    public function deleteProductAttributes($collection){
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidCollection', 'The $collection parameter must be an array collection.', 'msg.error.invalid.collection.array');
        }
        $countDeleted = 0;
        foreach ($collection as $entry){
            $entry = $this->validateAndGetProductAttribute($entry);
            $this->em->remove($entry);
            $countDeleted++;
        }
        if ($countDeleted < 0) {
            $this->response['error'] = true;
            $this->response['code'] = 'msg.error.db.delete.failed';

            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.delete',
        );
        return $this->response;
    }

    /**
     * @name            deleteProductCategories()
     *                  Deletes product categories.
     *
     * @since           1.0.5
     * @version         1.4.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->validateAndGetProductCategory()
     *
     * @param           array           $collection             Collection consists one of the following: 'entity', 'id', 'url_key', 'site'
     *
     * @return          array           $response
     */
    public function deleteProductCategories($collection){
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidCollection', 'The $collection parameter must be an array collection.', 'msg.error.invalid.collection.array');
        }
        $countDeleted = 0;
        foreach ($collection as $entry) {
            $entry = $this->validateAndGetProductCategory($entry);
            $localizations = $entry->getLocalizations();
            foreach($localizations as $localization){
                $this->em->remove($localization);
            }
            $this->em->remove($entry);
            $countDeleted++;
        }

        if ($countDeleted < 0) {
            $this->response['error'] = true;
            $this->response['code'] = 'msg.error.db.delete.failed';

            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.delete',
        );
        return $this->response;
    }
    /**
     * @name            deleteProductCategory()
     *                  Deletes an existing product category from database.
     *
     * @since           1.0.5
     * @version         1.1.9
     * @author          Can Berkol
     *
     * @use             $this->deleteProductAttributes()
     *
     * @param           mixed           $data               a single value of 'entity', 'id', 'url_key'
     *
     * @return          mixed           $response
     */
    public function deleteProductCategory($data){
        return $this->deleteProductCategories(array($data));
    }
    /**
     * @name            deleteProduct()
     *                  Deletes provided product from database.
     *
     * @since           1.0.0
     * @version         1.1.7
     * @author          Can Berkol
     *
     * @use             $this->deleteProducts()
     *
     * @param           array           $product            Product id, sku or entity
     *
     * @return          array           $response
     */
    public function deleteProduct($product){
        return $this->deleteProducts(array($product));
    }

    /**
     * @name            deleteProducts()
     *                  Deletes provided products from database.
     *
     * @since           1.0.0
     * @version         1.4.4
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProduct()
     *
     * @param           array           $collection         Collection consists one of the following: 'entity', 'id', 'sku', 'site', 'type', 'status'     *
     * @return          array           $response
     */
    public function deleteProducts($collection){
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidCollection', 'The $collection parameter must be an array collection.', 'msg.error.invalid.collection.array');
        }
        $countDeleted = 0;
        foreach ($collection as $entry) {
            $product = $this->validateAndGetProduct($entry);
            $this->em->remove($product);
            $countDeleted++;
        }
        if ($countDeleted < 0) {
            $this->response['error'] = true;
            $this->response['code'] = 'msg.error.db.delete';
            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.delete',
        );
        return $this->response;
    }

    /**
     * @name            doesProductAttributeExist()
     *                  Checks if entry exists in database.
     *
     * @since           1.0.5
     * @version         1.4.5
     * @author          Can Berkol
     *
     * @use             $this->getProductAttribute()
     * @use             $this->resetResponse()
     *
     * @param           mixed           $attribute          id, url_key
     * @param           string          $by                 all, entity, id, url_key
     * @param           bool            $bypass             If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesProductAttributeExist($attribute, $by = 'id', $bypass = false){
        $this->resetResponse();
        $exist = false;

        $response = $this->getProductAttribute($attribute, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = true;
        }
        if ($bypass) { return $exist; }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }

    /**
     * @name            doesProductAttributeValueExist()
     *                  Checks if entry exists in database.
     *
     * @since           1.2.2
     * @version         1.4.5
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->getAttributeValueOfProduct()
     *
     * @param           mixed       $attribute      id, url_key
     * @param           mixed       $product        id, url_key, sku
     * @param           mixed       $language       id, iso_code, url_key
     * @param           bool        $bypass         If set to true does not return response but only the result.
     *
     * @return          mixed       $response
     */
    public function doesProductAttributeValueExist($attribute, $product, $language, $bypass = false){
        $exist = false;
        $response = $this->getAttributeValueOfProduct($attribute, $product, $language);
        if (!$response['error']) {
            $exist = true;
        }
        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            doesProductExist()
     *                  Checks if entry exists in database.
     *
     * @since           1.0.0
     * @version         1.0.0
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed       $product        id, sku
     * @param           string      $by             all, entity, id, code, url_key
     * @param           bool        $bypass         If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesProductExist($product, $by = 'id', $bypass = false){
        $exist = false;
        $response = $this->getProduct($product, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = true;
        }
        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }
    /**
     * @name            doesProductCategoryExist ()
     *                  Checks if entry exists in database.
     *
     * @since           1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @use             $this->getProductCategory()
     *
     * @param           mixed           $category       id, entity, url, key
     * @param           string          $by             entity, id, url_key
     * @param           bool            $bypass         If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesProductCategoryExist($category, $by = 'id', $bypass = false){
        $exist = false;
        $response = $this->getProductCategory($category, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = true;
        }
        if ($bypass) { return $exist; }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }
    /**
     * @name            getAttributeValueOfProduct ()
     *                  Returns a product's requested attribute's value in given locale.
     *
     * @since           1.1.6
     * @version         1.2.4
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetLocale()
     * @use             $this->validateAndGetProduct()
     * @use             $this->validateAndGetProductAttribute()
     *
     * @param           mixed           $attribute  url_key, id, entity
     * @param           mixed           $product    id, sku
     * @param           mixed           $language   iso code, entity, id
     *
     * @return          mixed           $response
     */
    public function getAttributeValueOfProduct($attribute, $product, $language){
        $this->resetResponse();
        $attribute = $this->validateAndGetProductAttribute($attribute);
        $product = $this->validateAndGetProduct($product);
        $language = $this->validateAndGetLocale($language);

        $q_str = 'SELECT DISTINCT ' . $this->entity['product_attribute_values']['alias'] . ', ' . $this->entity['product_attribute']['alias']
            . ' FROM ' . $this->entity['product_attribute_values']['name'] . ' ' . $this->entity['product_attribute_values']['alias']
            . ' JOIN ' . $this->entity['product_attribute_values']['alias'] . '.attribute ' . $this->entity['product_attribute']['alias']
            . ' WHERE ' . $this->entity['product_attribute_values']['alias'] . '.product = ' . $product->getId()
            . ' AND ' . $this->entity['product_attribute_values']['alias'] . '.language = ' . $language->getId()
            . ' AND ' . $this->entity['product_attribute_values']['alias'] . '.attribute = ' . $attribute->getId();

        $query = $this->em->createQuery($q_str);
        $query->setMaxResults(1);
        $query->setFirstResult(0);
        $result = $query->getResult();
        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response['error'] = true;
            $this->response['code'] = 'msg.error.db.attribute.value.notfound';
            return $this->response;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result[0],
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }
    /**
     * @name            getCategoriesOfProductEntry ()
     *                  Gets an entry.
     *
     * @since           1.4.6
     * @version         1.4.6
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           miixed $product
     * @param           mixed $category
     *
     * @deprecated      Will be deleted in v1.2.0. Use $this->listCategoriesOfProduct() instead.
     *
     * @return          array       $response
     */
    public function getCategoriesOfProductEntry($product, $category)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if ($product instanceof BundleEntity\Product) {
            $product = $product->getId();
        } else if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if (!$response['error']) {
                $product = $response['result']['set']->getId();
            }
        } else if (is_string($product)) {
            $response = $this->getProduct($product, 'sku');
            if (!$response['error']) {
                $product = $response['result']['set']->getId();
            }
        }

        if ($category instanceof BundleEntity\ProductCategory) {
            $category = $category->getId();
        } else if (is_numeric($category)) {
            $response = $this->getProductCategory($category, 'id');
            if (!$response['error']) {
                $category = $response['result']['set']->getId();
            }
        }
        $qStr = 'SELECT ' . $this->entity['categories_of_product']['alias'] . ' FROM ' . $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias']
            . ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.product = ' . $product
            . ' AND ' . $this->entity['categories_of_product']['alias'] . '.category = ' . $category;
        $q = $this->em->createQuery($qStr);
        $result = $q->getResult();

        if (!is_null($result)) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $result[0],
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.select.done',
            );
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => null,
                'total_rows' => 0,
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'scc.err.entry.notexist',
        );

        return $this->response;
    }
    /**
     * @name            getMaxSortOrderOfAttributeInProduct ()
     *                  Returns the largest sort order value for a given attribute from attributes_of_product table.
     *
     * @since           1.1.7
     * @version         1.4.5
     * @author          Can Berkol
     *
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProduct()
     *
     * @param           mixed           $product            id, entity, sku, url_key
     * @param           bool            $bypass             if set to true return integer instead of response
     *
     * @return          mixed           bool | $response
     */
    public function getMaxSortOrderOfAttributeInProduct($product, $bypass = false){
        $this->resetResponse();
        $product = $this->validateAndGetProduct($product);
        $q_str = 'SELECT MAX(' . $this->entity['attributes_of_product']['alias'] . ') FROM ' . $this->entity['attributes_of_product']['name']
            . ' WHERE ' . $this->entity['attributes_of_product']['alias'] . '.product = ' . $product->getId();

        $query = $this->em->createQuery($q_str);
        $result = $query->getSingleScalarResult();

        if ($bypass) {
            return $result;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }

    /**
     * @name            getMaxSortOrderOfProductFile ()
     *                  Returns the largest sort order value for a given product from files_of_product table.
     *
     * @since           1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProduct()
     *
     * @param           mixed $product entity, id, sku
     * @param           bool $bypass if set to true return bool instead of response
     *
     * @return          mixed           bool | $response
     */
    public function getMaxSortOrderOfProductFile($product, $bypass = false){
        $this->resetResponse();;
        $product = $this->validateAndGetProduct($product);

        $q_str = 'SELECT MAX('.$this->entity['files_of_product']['alias'].'.sort_order) FROM '
            . $this->entity['files_of_product']['name'].' '.$this->entity['files_of_product']['alias']
            .' WHERE '.$this->entity['files_of_product']['alias'].'.product = '.$product->getId();

        $query = $this->em->createQuery($q_str);
        $result = $query->getSingleScalarResult();

        if ($bypass) {  return $result; }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }

    /**
     * @name            getMaxSortOrderOfProductInCategory ()
     *                  Returns the largest sort order value for a given category from categories_of_product table.
     *
     * @since           1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProductCategory()
     *
     * @param           mixed           $category       id, entity
     * @param           bool            $bypass         if set to true return bool instead of response
     *
     * @return          mixed           bool | $response
     */
    public function getMaxSortOrderOfProductInCategory($category, $bypass = false){
        $this->resetResponse();;
        $category = $this->validateAndGetProductCategory($category);
        $q_str = 'SELECT MAX(' . $this->entity['categories_of_product']['alias'] . ') FROM ' . $this->entity['categories_of_product']['name']
            . ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.category = ' . $category->getId();

        $query = $this->em->createQuery($q_str);
        $result = $query->getSingleScalarResult();

        if ($bypass) { return $result; }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }
    /**
     * @name            getMostRecentFileOfProduct()
     *                  Returns the most recent file that is associated to product.
     *
     * @since           1.2.6
     * @version         1.2.6
     * @author          Can Berkol
     *
     * @use             $this->listFilesOfProducts()
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProduct()
     *
     * @param           mixed $product
     *
     * @return          mixed           $response
     */
    public function getMostRecentFileOfProduct($product){
        $this->resetResponse();
        $product = $this->validateAndGetProduct($product);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['files_of_product']['alias'] . '.product', 'comparison' => '=', 'value' => $product->getId()),
                )
            )
        );

        $response = $this->listFilesOfProducts($filter, array('date_added' => 'desc'), array('start' => 0, 'count' => 1));
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }
    /**
     * @name            getParentOfProductCategory ()
     *                  Returns the parent category of given category.
     *
     * @since           1.2.4
     * @version         1.2.4
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $category id, url_key
     * @param           string $by entity, id, url_key
     *
     * @return          mixed           $response
     */
    public function getParentOfProductCategory($category, $by = 'id'){
        $response = $this->getProductCategory($category, $by);

        if ($response['error']) { return $response; }

        $category = $response['result']['set'];

        if ($category->getParent() == null) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'msg.error.product.category.parent.notexist',
            );
            return $this->response;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $category->getParent(),
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            getProduct ()
     *                  Returns details of a product.
     *
     * @since           1.0.1
     * @version         1.3.6
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->listProducts()
     * @use             $this->resetResponse()
     *
     * @param           mixed           $product        id, sku
     * @param           string          $by             id, sku, url_key
     *
     * @return          mixed           $response
     */
    public function getProduct($product, $by = 'id'){
        $this->resetResponse();
        $by_opts = array('id', 'sku', 'url_key');

        if (!is_string($by)){
            return $this->createException('InvalidParameter', '$by parameter must hold a string value.', 'msg.error.invalid.parameter.by');
        }
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidByOption', 'Accepted values are: '.implode(',', $by_opts).'. You have provided "'.$by.'"', 'msg.error.invalid.option');
        }
        if (!$product instanceof BundleEntity\Product && !is_numeric($product) && !is_string($product)) {
            return $this->createException('InvalidParameter', '$product parameter must hold BiberLtd\\Core\\Bundles\\ProductManagementBundle\\Entity\\Product entity, a string representing url_key, or sku, or an integer representing database row id.', 'msg.error.invalid.parameter.product');
        }
        if (is_object($product)) {
            /**
             * Prepare & Return Response
             */
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $product,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'msg.success.db.entry.exists',
            );
            return $this->response;
        }
        switch ($by) {
            case 'url_key':
                $column = $this->entity['product_localization']['alias'].'.'.$by;
                break;
            default:
                $column = $this->entity['product']['alias'].'.'.$by;
                break;
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $column, 'comparison' => '=', 'value' => $product),
                )
            )
        );

        $response = $this->listProducts($filter, null);
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }
    /**
     * @name            getProductAttribute ()
     *                  Returns details of a product attribute.
     *
     * @since           1.0.5
     * @version         1.1.0
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->listProductAttributes()
     *
     * @param           mixed           $attribute  id, url_key
     * @param           string          $by         id, sku
     *
     * @return          mixed           $response
     */
    public function getProductAttribute($attribute, $by = 'id'){
        $this->resetResponse();
        $by_opts = array('id', 'url_key');
        if (!is_string($by)){
            return $this->createException('InvalidParameter', '$by parameter must hold a string value.', 'msg.error.invalid.parameter.by');
        }
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidByOption', 'Accepted values are: '.implode(',', $by_opts).'. You have provided "'.$by.'"', 'msg.error.invalid.option');
        }
        if (!$attribute instanceof BundleEntity\ProductAttribute && !is_numeric($attribute) && !is_string($attribute)) {
            return $this->createException('InvalidParameter', '$attribute parameter must hold BiberLtd\\Core\\Bundles\\ProductManagementBundle\\Entity\\ProductAttribute entity, a string representing url_key, or an integer representing database row id.', 'msg.error.invalid.parameter.product.attribute');
        }
        if (is_object($attribute)) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $attribute,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        switch ($by) {
            case 'url_key':
                $column = $this->entity['product_attribute_localization']['alias'].'.'.$by;
                break;
            default:
                $column = $this->entity['product_attribute']['alias'].'.'.$by;
                break;
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $column, 'comparison' => '=', 'value' => $attribute),
                )
            )
        );

        $response = $this->listProductAttributes($filter, null, array('start' => 0, 'count' => 1));
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }

    /**
     * @name            getProductAttributeValue ()
     *                  Returns a ProductAttributeValueEntry
     *
     * @since           1.0.5
     * @version         1.1.6
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->resetResponse()
     *
     * @param           integer         $id
     *
     * @return          mixed           $response
     */
    public function getProductAttributeValue($id){
        $this->resetResponse();
        if (!is_integer($id)) {
            return $this->createException('InvalidParameter', '$id parameter must hold an integer value.', 'msg.error.invalid.parameter.id');
        }

        $result = $this->em->getRepository($this->entity['product_attribute_values']['name'])
                           ->findOneBy(array('id' => $id));

        $error = true;
        $code = 'msg.error.db.entry.notexist';
        $found = 0;
        if($result instanceof BundleEntity\ProductAttributeValues){
            $error = false;
            $code = 'msg.success.db.entity.exists';
            $found = 1;
        }
        if($error){ $result = null; }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => $found,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => $code,
        );

        return $this->response;
    }
    /**
     * @name            getProductBySku ()
     *                  Returns details of a product by its SKU.
     *
     * @since           1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           string          $sku        Stock keeping unit
     *
     * @return          mixed           $response
     */
    public function getProductBySku($sku){
        return $this->getProduct($sku, 'sku');
    }

    /**
     * @name            getProductByUrlKey ()
     *                  Returns details of a product by its Url key.
     *
     * @since           1.3.6
     * @version         1.3.6
     * @author          Said İmamoğlu
     *
     * @use             $this->getProduct()
     *
     * @param           string          $urlKey
     *
     * @return          mixed           $response
     */
    public function getProductByUrlKey($urlKey){
        return $this->getProduct($urlKey, 'url_key');
    }
    /**
     * @name            getProductCategory ()
     *                  Returns details of a product category.
     *
     * @since           1.0.3
     * @version         1.1.9
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->listProductCategories()
     *
     * @param           mixed           $category   id, url_key, entity
     * @param           string          $by         id, url_key
     *
     * @return          mixed           $response
     */
    public function getProductCategory($category, $by = 'id'){
        $this->resetResponse();
        $by_opts = array('id', 'url_key');
        if (!is_string($by)){
            return $this->createException('InvalidParameter', '$by parameter must hold a string value.', 'msg.error.invalid.parameter.by');
        }
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidByOption', 'Accepted values are: '.implode(',', $by_opts).'. You have provided "'.$by.'"', 'msg.error.invalid.option');
        }
        if (!$category instanceof BundleEntity\ProductAttribute && !is_numeric($category) && !is_string($category)) {
            return $this->createException('InvalidParameter', '$category parameter must hold BiberLtd\\Core\\Bundles\\ProductManagementBundle\\Entity\\ProductCategory entity, a string representing url_key or an integer representing database row id.', 'msg.error.invalid.parameter.product.category');
        }
        if (is_object($category)) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $category,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        $column = '';
        switch ($by) {
            case 'url_key':
                $column = $this->entity['product_category_localization']['alias'].'.'.$by;
                break;
            default:
                $column = $this->entity['product_category']['alias'].'.'.$by;
                break;
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $column, 'comparison' => '=', 'value' => $category),
                )
            )
        );
        $response = $this->listProductCategories($filter, null, null, null, false);
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }

    /**
     * @name            getProductCategoryLocalization ()
     *                  Gets a specific product's localization values from database.
     *
     * @since           1.0.1
     * @version         1.0.6
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $category
     * @param           mixed $language
     *
     * @return          array           $response
     */
    public function getProductCategoryLocalization($category, $language)
    {
        $this->resetResponse();
        if (!$category instanceof BundleEntity\ProductCategory && !is_integer($category) && !is_string($category)) {
            return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product');
        }
        /** Parameter must be an array */
        if (!$language instanceof MLSEntity\Language && !is_integer($language) && !is_string($language)) {
            return $this->createException('InvalidParameter', 'Language', 'err.invalid.parameter.language');
        }
        if ($category instanceof BundleEntity\ProductCategory) {
            $cid = $category->getId();
        } else if (is_numeric($category)) {
            $cid = $category;
        }
        if ($language instanceof MLSEntity\Language) {
            $lid = $language->getId();
        } else if (is_string($language)) {
            $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
            $response = $mlsModel->getLanguage($language, 'iso_code');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Language', 'err.invalid.parameter.product');
            }
            $language = $response['result']['set'];
            $lid = $language->getId();
        } else if (is_numeric($language)) {
            $lid = $language;
        }
        $q_str = 'SELECT ' . $this->entity['product_category_localization']['alias'] . ' FROM ' . $this->entity['product_category_localization']['name'] . ' ' . $this->entity['product_category_localization']['alias']
            . ' WHERE ' . $this->entity['product_category_localization']['alias'] . '.category = ' . $cid
            . ' AND ' . $this->entity['product_category_localization']['alias'] . '.language = ' . $lid;
        $query = $this->em->createQuery($q_str);
        /**
         * 6. Run query
         */
        $result = $query->getResult();
        /**
         * Prepare & Return Response
         */
        $totalRows = count($result);
        if ($totalRows < 1 || !$result || is_null($result)) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.exist',
            );
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result[0],
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.notexist',
        );
        return $this->response;
    }
    /**
     * @name            getProductLocalization ()
     *                  Gets a specific product's localization values from database.
     *
     * @since           1.0.1
     * @version         1.0.6
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $product
     * @param           mixed $language
     *
     * @return          array           $response
     */
    public function getProductLocalization($product, $language)
    {
        $this->resetResponse();
        if (!$product instanceof BundleEntity\Product && !is_integer($product) && !is_string($product)) {
            return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product');
        }
        /** Parameter must be an array */
        if (!$language instanceof MLSEntity\Language && !is_integer($language) && !is_string($language)) {
            return $this->createException('InvalidParameter', 'Language', 'err.invalid.parameter.language');
        }
        if ($product instanceof BundleEntity\Product) {
            $pid = $product->getId();
        } else if (is_string($product)) {
            $response = $this->getProduct($product, 'sku');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.invalid.parameter.product');
            }
            $product = $response['result']['set'];
            $pid = $product->getId();
        } else if (is_numeric($product)) {
            $pid = $product->getId();
        }
        if ($language instanceof MLSEntity\Language) {
            $lid = $language->getId();
        } else if (is_string($language)) {
            $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
            $response = $mlsModel->getLanguage($language, 'iso_code');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Language', 'err.invalid.parameter.product');
            }
            $language = $response['result']['set'];
            $lid = $language->getId();
        } else if (is_numeric($language)) {
            $lid = $language;
        }
        $q_str = 'SELECT ' . $this->entity['product_localization']['alias'] . ' FROM ' . $this->entity['product_localization']['name'] . ' ' . $this->entity['product_localization']['alias']
            . ' WHERE ' . $this->entity['product_localization']['alias'] . '.product = ' . $pid
            . ' AND ' . $this->entity['product_localization']['alias'] . '.language = ' . $lid;

        $query = $this->em->createQuery($q_str);
        /**
         * 6. Run query
         */
        $result = $query->getResult();
        /**
         * Prepare & Return Response
         */
        $totalRows = count($result);
        if ($totalRows < 1 || !$result || is_null($result)) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $result,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => null,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            getRandomCategoryOfProduct ()
     *                  Gets a category of product. If there are more than one category it will get randomly only one .
     *
     * @since           1.3.6
     * @version         1.3.6
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           miixed $product
     *
     * @return          array       $response
     */
    public function getRandomCategoryOfProduct($product)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if ($product instanceof BundleEntity\Product) {
            $product = $product->getId();
        } else if (is_numeric($product) || is_int($product)) {
            $response = $this->getProduct($product, 'id');
            if (!$response['error']) {
                $product = $response['result']['set']->getId();
            }
        } else if (is_string($product)) {
            $response = $this->getProduct($product, 'url_key');
            if (!$response['error']) {
                $product = $response['result']['set']->getId();
            }
        }
        /**
         * List product categories
         */
        $response = $this->listCategoriesOfProduct($product);
        if ($response['error']) {
            return $response;
        }
        $categoriesOfProduct = $response['result']['set'];
        unset($response);
        $i = count($categoriesOfProduct);
        $random = rand(0, $i - 1);
        $category = $categoriesOfProduct[$random];
        unset($categoriesOfProduct);
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $category,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'success.category.exist',
        );

        return $this->response;
    }

    /**
     * @name            getRandomCategoryOfProductHasParentByLevel()
     *                  Gets a category of product which has a parent by level. If there are more than one category it will get randomly only one .
     *
     * @since           1.4.1
     * @version         1.4.1
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           miixed $product
     * @param           int $level
     *
     * @deprecated      Will be deleted in v1.2.0. Use $this->getRandomCategoryOfProductHavingParentByLevel() instead.
     * @return          array       $response
     */
    public function getRandomCategoryOfProductHasParentByLevel($product,$level)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if ($product instanceof BundleEntity\Product) {
            $product = $product->getId();
        } else if (is_numeric($product) || is_int($product)) {
            $response = $this->getProduct($product, 'id');
            if (!$response['error']) {
                $product = $response['result']['set']->getId();
            }
        } else if (is_string($product)) {
            $response = $this->getProduct($product, 'url_key');
            if (!$response['error']) {
                $product = $response['result']['set']->getId();
            }
        }else{
            return $this->createException('InvalidParameter','Product','invalid.parameter.product',true);
        }

        if (!is_int($level)) {
            return $this->createException('InvalidParameter','Level','invalid.parameter.level',true);
        }
        /**
         * List product categories
         */
        $response = $this->listCategoriesOfProduct($product);
        if ($response['error']) {
            return $response;
        }
        foreach ($response['result']['set'] as $cat) {
            $idCollection[] = $cat->getId();
        }
        $categoriesOfProduct = array();
        if (count($idCollection) > 0) {
            $filter = array();
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $this->entity['product_category']['alias'] . '.id', 'comparison' => 'in', 'value' => $idCollection),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $this->entity['product_category']['alias'] . '.parent', 'comparison' => 'notnull', 'value' => ''),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $this->entity['product_category']['alias'] . '.level', 'comparison' => '=', 'value' => $level),
                    ),
                )
            );
            $response = $this->listProductCategories($filter);
            if (!$response['error']) {
                $categoriesOfProduct = $response['result']['set'];
            }
            unset($response);
        }
        if (count($categoriesOfProduct) > 0) {
            $i = count($categoriesOfProduct);
            $random = rand(0, $i - 1);
            $category = $categoriesOfProduct[$random];
            unset($categoriesOfProduct);
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $category,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'success.category.exist',
            );
        }else{
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => array(),
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'error.category.notexist',
            );
        }
        return $this->response;
    }

    /**
     * @name            insertProduct ()
     *                  Inserts one product attribute into database.
     *
     * @since           1.0.1
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @use             $this->insertProducts()
     *
     * @param           mixed $product Entity or post
     *
     * @return          array           $response
     */
    public function insertProduct($product)
    {
        return $this->insertProducts(array($product));
    }

    /**
     * @name            insertProductAttribute ()
     *                Inserts one product into database.
     *
     * @since            1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->insertProductAttribute()
     *
     * @param           mixed $attribute Entity or post
     * @param           mixed $by entity, or, post
     *
     * @return          array           $response
     */
    public function insertProductAttribute($attribute)
    {
        $this->resetResponse();
        return $this->insertProductAttributes(array($attribute));
    }

    /**
     * @name            insertProductAttributeLocalizations ()
     *                Inserts one or more product attribute localizations into database.
     *
     * @since            1.1.3
     * @version         1.1.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertProductAttributeLocalizations($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $insertedItems = array();
        foreach ($collection as $item) {
            if ($item instanceof BundleEntity\ProductAttributeLocalization) {
                $entity = $item;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else {
                foreach ($item['localizations'] as $language => $data) {
                    $entity = new BundleEntity\ProductAttributeLocalization;
                    $entity->setAttribute($item['entity']);
                    $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                    $response = $mlsModel->getLanguage($language, 'iso_code');
                    if (!$response['error']) {
                        $entity->setLanguage($response['result']['set']);
                    } else {
                        break 1;
                    }
                    foreach ($data as $column => $value) {
                        $set = 'set' . $this->translateColumnName($column);
                        $entity->$set($value);
                    }
                    $this->em->persist($entity);
                }
                $insertedItems[] = $entity;
                $countInserts++;
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }
    /**
     * @name            insertProductAttributeValue ()
     *                Inserts one product attribute value into database.
     *
     * @since            1.1.7
     * @version         1.1.7
     * @author          Can Berkol
     *
     * @use             $this->insertProductAttributeValues()
     *
     * @param           mixed $attrVal Entity or post
     * @param           mixed $by entity, or, post
     *
     * @return          array           $response
     */
    public function insertProductAttributeValue($attrVal)
    {
        return $this->insertProductAttributeValues(array($attrVal));
    }

    /**
     * @name            insertProductAttributes ()
     *                Inserts one or more product attribute values into database.
     *
     * @since            1.1.7
     * @version         1.1.7
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->insertProductAttributeLocalization()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertProductAttributeValues($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $insertedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductAttributeValues) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\ProductAttributeValues;
                if (isset($data->id)) {
                    unset($data->id);
                }
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'language':
                            $lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                            $response = $lModel->getLanguage($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                $response = $lModel->getLanguage($value, 'iso_code');
                                if (!$response['error']) {
                                    $entity->$set($response['result']['set']);
                                } else {
                                    new CoreExceptions\EntityDoesNotExist($this->kernel, $value);
                                }
                            }
                            unset($response, $sModel);
                            break;
                        case 'attribute':
                            $response = $this->getProductAttribute($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExist($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'product':
                            $response = $this->getProduct($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExist($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;

                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name            insertProductAttributes ()
     *                  Inserts one or more product attributes into database.
     *
     * @since           1.0.5
     * @version         1.1.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->insertProductAttributeLocalization()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertProductAttributes($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $countLocalizations = 0;
        $insertedItems = array();
        $localizations = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductAttribute) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\ProductAttribute;
                if (!property_exists($data, 'date_added')) {
                    $data->date_added = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (!property_exists($data, 'site')) {
                    $data->site = 1;
                }
                foreach ($data as $column => $value) {
                    $localeSet = false;
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'local':
                            $localizations[$countInserts]['localizations'] = $value;
                            $localeSet = true;
                            $countLocalizations++;
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                    if ($localeSet) {
                        $localizations[$countInserts]['entity'] = $entity;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;

                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /** Now handle localizations */
        if ($countInserts > 0 && $countLocalizations > 0) {
            $this->insertProductAttributeLocalizations($localizations);
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name            insertProductCategory ()
     *                Inserts one product category into database.
     *
     * @since            1.0.5
     * @version         1.1.8
     * @author          Can Berkol
     *
     * @use             $this->insertProductCategories()
     *
     * @param           mixed $category Entity or post
     *
     * @return          array           $response
     */
    public function insertProductCategory($category)
    {
        return $this->insertProductCategories(array($category));
    }

    /**
     * @name            insertProductCategories ()
     *                Inserts one or more product categories into database.
     *
     * @since            1.0.5
     * @version         1.1.8
     * @author          Can Berkol
     *
     * @use             $this->doesProductCategoryExist()
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertProductCategories($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $countLocalizations = 0;
        $insertedItems = array();
        $localizations = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductCategory) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\ProductCategory;
                if (!property_exists($data, 'date_added')) {
                    $data->date_added = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (!property_exists($data, 'count_children')) {
                    $data->count_children = 0;
                }
                if (!property_exists($data, 'site')) {
                    $data->site = 1;
                }
                foreach ($data as $column => $value) {
                    $localeSet = false;
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'local':
                            $localizations[$countInserts]['localizations'] = $value;
                            $localeSet = true;
                            $countLocalizations++;
                            break;
                        case 'preview_image':
                            $fModel = $this->kernel->getContainer()->get('filemanagement.model');
                            $response = $fModel->getFile($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $fModel);
                            break;
                        case 'parent':
                            if (!is_null($value)) {
                                $response = $this->getProductCategory($value, 'id');
                                if (!$response['error']) {
                                    $entity->$set($response['result']['set']);
                                } else {
                                    new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                                }
                            } else {
                                $entity->$set(null);
                            }
                            unset($response, $fModel);
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                    if ($localeSet) {
                        $localizations[$countInserts]['entity'] = $entity;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;

                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /** Now handle localizations */
        if ($countInserts > 0 && $countLocalizations > 0) {
            $this->insertProductCategoryLocalizations($localizations);
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => isset($entity) ? $entity->getId() : 0,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }
    /**
     * @name            insertProductCategoryLocalizations ()
     *                Inserts one or more product category localizations into database.
     *
     * @since            1.1.8
     * @version         1.1.8
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertProductCategoryLocalizations($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $insertedItems = array();
        foreach ($collection as $item) {
            if ($item instanceof BundleEntity\ProductCategoryLocalization) {
                $entity = $item;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else {
                foreach ($item['localizations'] as $language => $data) {
                    $entity = new BundleEntity\ProductCategoryLocalization;
                    $entity->setCategory($item['entity']);
                    $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                    $response = $mlsModel->getLanguage($language, 'iso_code');
                    if (!$response['error']) {
                        $entity->setLanguage($response['result']['set']);
                    } else {
                        break 1;
                    }
                    foreach ($data as $column => $value) {
                        $set = 'set' . $this->translateColumnName($column);
                        $entity->$set($value);
                    }
                    $this->em->persist($entity);
                }
                $insertedItems[] = $entity;
                $countInserts++;
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }
    /**
     * @name            insertProductLocalizations ()
     *                  Inserts one or more product  localizations into database.
     *
     * @since           1.1.7
     * @version         1.1.7
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertProductLocalizations($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $insertedItems = array();
        foreach ($collection as $item) {
            if ($item instanceof BundleEntity\ProductLocalization) {
                $entity = $item;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else {
                foreach ($item['localizations'] as $language => $data) {
                    $entity = new BundleEntity\ProductLocalization;
                    $entity->setProduct($item['entity']);
                    $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                    $response = $mlsModel->getLanguage($language, 'iso_code');
                    if (!$response['error']) {
                        $entity->setLanguage($response['result']['set']);
                    } else {
                        break 1;
                    }
                    foreach ($data as $column => $value) {
                        $set = 'set' . $this->translateColumnName($column);
                        $entity->$set($value);
                    }
                    $this->em->persist($entity);
                }
                $insertedItems[] = $entity;
                $countInserts++;
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }
    /**
     * @name            insertProducts ()
     *                  Inserts one or more products into database.
     *
     * @since           1.4.9
     * @version         1.1.7
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertProducts($collection, $by = 'post')
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $countLocalizations = 0;
        $insertedItems = array();
        $localizations = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Product) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\Product;
                if (!property_exists($data, 'date_added')) {
                    $data->date_added = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (!property_exists($data, 'quantity')) {
                    $data->quantity = 1;
                }
                if (!property_exists($data, 'price')) {
                    $data->price = 0;
                }
                if (!property_exists($data, 'site')) {
                    $data->site = 1;
                }
                if (!property_exists($data, 'count_view')) {
                    $data->count_view = 0;
                }
                if (!property_exists($data, 'count_like')) {
                    $data->count_like = 0;
                }
                foreach ($data as $column => $value) {
                    $localeSet = false;
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'brand':
                            $response = $this->getBrand($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            }
                            unset($response);
                            break;
                        case 'local':
                            $localizations[$countInserts]['localizations'] = $value;
                            $localeSet = true;
                            $countLocalizations++;
                            break;
                        case 'preview_file':
                            $fModel = $this->kernel->getContainer()->get('filemanagement.model');
                            $response = $fModel->getFile($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'supplier':
                            $sModel = $this->kernel->getContainer()->get('stockmanagement.model');
                            $response = $sModel->getSupplier($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            }
                            unset($response, $sModel);
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                    if ($localeSet) {
                        $localizations[$countInserts]['entity'] = $entity;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;

                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /** Now handle localizations */
        if ($countInserts > 0 && $countLocalizations > 0) {
            $this->insertProductLocalizations($localizations);
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name            isAttributeAssociatedWithProduct ()
     *                  Checks if the attribute is already associated with the product category.
     *
     * @since           1.1.7
     * @version         1.1.7
     * @author          Can Berkol
     *
     * @user            $this->createException
     *
     * @param           mixed $file 'entity' or 'entity' id
     * @param           mixed $product 'entity' or 'entity' id.
     * @param           bool $bypass true or false
     *
     * @return          mixed               bool or $response
     */
    public function isAttributeAssociatedWithProduct($attribute, $product, $bypass = false)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        if (!is_numeric($attribute) && !$attribute instanceof BundleEntity\ProductAttribute) {
            return $this->createException('InvalidParameter', 'ProductAttribute', 'err.invalid.parameter.product_attribute');
        }

        if (!is_numeric($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product');
        }
        /** If no entity is provided as file we need to check if it does exist */
        if (is_numeric($attribute)) {
            $response = $this->getProductAttribute($attribute, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'ProductAttribute', 'err.db.product.notexist');
            }
            $attribute = $response['result']['set'];
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        }
        $found = false;

        $q_str = 'SELECT COUNT(' . $this->entity['attributes_of_product']['alias'] . ')'
            . ' FROM ' . $this->entity['attributes_of_product']['name'] . ' ' . $this->entity['attributes_of_product']['alias']
            . ' WHERE ' . $this->entity['attributes_of_product']['alias'] . '.attribute = ' . $attribute->getId()
            . ' AND ' . $this->entity['attributes_of_product']['alias'] . '.product = ' . $product->getId();
        $query = $this->em->createQuery($q_str);

        $result = $query->getSingleScalarResult();

        /** flush all into database */
        if ($result > 0) {
            $found = true;
            $code = 'scc.db.entry.exist';
        } else {
            $code = 'scc.db.entry.noexist';
        }

        if ($bypass) {
            return $found;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $found == true ? $result : $found,
                'total_rows' => count($result),
                'last_insert_id' => null,
            ),
            'error' => $found == true ? false : true,
            'code' => $code,
        );
        return $this->response;
    }
    /**
     * @name            isFileAssociatedWithProduct ()
     *                Checks if the file is already associated with the product.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $file 'entity' or 'entity' id
     * @param           mixed $product 'entity' or 'entity' id.
     * @param           bool $bypass true or false
     *
     * @return          mixed           bool or $response
     */
    public function isFileAssociatedWithProduct($file, $product, $bypass = false)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        if (!is_numeric($file) && !$file instanceof FileBundleEntity\File) {
            return $this->createException('InvalidParameter', 'File', 'err.invalid.parameter.file');
        }

        if (!is_numeric($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product');
        }
        $fmmodel = new FMMService\FileManagementModel($this->kernel, $this->db_connection, $this->orm);
        /** If no entity is provided as file we need to check if it does exist */
        if (is_numeric($file)) {
            $response = $fmmodel->getFile($file, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'File', 'err.db.file.notexist');
            }
            $file = $response['result']['set'];
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        }
        $found = false;

        $q_str = 'SELECT COUNT(' . $this->entity['files_of_product']['alias'] . ')'
            . ' FROM ' . $this->entity['files_of_product']['name'] . ' ' . $this->entity['files_of_product']['alias']
            . ' WHERE ' . $this->entity['files_of_product']['alias'] . '.file = ' . $file->getId()
            . ' AND ' . $this->entity['files_of_product']['alias'] . '.product = ' . $product->getId();
        $query = $this->em->createQuery($q_str);

        $result = $query->getSingleScalarResult();

        /** flush all into database */
        if ($result > 0) {
            $found = true;
            $code = 'scc.db.entry.exist';
        } else {
            $code = 'scc.db.entry.noexist';
        }

        if ($bypass) {
            return $found;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $found,
                'total_rows' => $result,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => $code,
        );
        return $this->response;
    }
    /**
     * @name            isLocaleAssociatedWithProduct ()
     *                  Checks if the locale is already associated with the product.
     *
     * @since           1.2.3
     * @version         1.2.3
     * @author          Can Berkol
     *
     * @user            $this->createException
     *
     * @param           mixed $locale 'entity' or 'entity' id
     * @param           mixed $product 'entity' or 'entity' id.
     * @param           bool $bypass true or false
     *
     * @return          mixed                   bool or $response
     */
    public function isLocaleAssociatedWithProduct($locale, $product, $bypass = false)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        if (!is_numeric($locale) && !$locale instanceof MLSEntity\Language) {
            return $this->createException('InvalidParameter', 'Language', 'err.invalid.parameter.language');
        }

        if (!is_numeric($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product');
        }
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        /** If no entity is provided as file we need to check if it does exist */
        if (is_numeric($locale)) {
            $response = $mlsModel->getLanguage($locale, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Language', 'err.db.language.notexist');
            }
            $locale = $response['result']['set'];
        } else if (is_string($locale)) {
            $response = $mlsModel->getLanguage($locale, 'iso_code');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Language', 'err.db.language.notexist');
            }
            $locale = $response['result']['set'];
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        } else if (is_string($product)) {
            $response = $this->getProduct($product, 'sku');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        }
        $found = false;

        $q_str = 'SELECT COUNT(' . $this->entity['active_product_locale']['alias'] . ')'
            . ' FROM ' . $this->entity['active_product_locale']['name'] . ' ' . $this->entity['active_product_locale']['alias']
            . ' WHERE ' . $this->entity['active_product_locale']['alias'] . '.locale = ' . $locale->getId()
            . ' AND ' . $this->entity['active_product_locale']['alias'] . '.product = ' . $product->getId();
        $query = $this->em->createQuery($q_str);

        $result = $query->getSingleScalarResult();

        /** flush all into database */
        if ($result > 0) {
            $found = true;
            $code = 'scc.db.entry.exist';
        } else {
            $code = 'scc.db.entry.noexist';
        }

        if ($bypass) {
            return $found;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $found == true ? $result : $found,
                'total_rows' => count($result),
                'last_insert_id' => null,
            ),
            'error' => $found == true ? false : true,
            'code' => $code,
        );
        return $this->response;
    }

    /**
     * @name            isLocaleAssociatedWithProductCategory ()
     *                  Checks if the locale is already associated with the product.
     *
     * @since           1.2.3
     * @version         1.2.3
     * @author          Can Berkol
     *
     * @user            $this->createException
     *
     * @param           mixed $locale 'entity' or 'entity' id
     * @param           mixed $category 'entity' or 'entity' id.
     * @param           bool $bypass true or false
     *
     * @return          mixed                   bool or $response
     */
    public function isLocaleAssociatedWithProductCategory($locale, $category, $bypass = false)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        if (!is_numeric($locale) && !$locale instanceof MLSEntity\Language) {
            return $this->createException('InvalidParameter', 'Language', 'err.invalid.parameter.language');
        }

        if (!is_numeric($category) && !$category instanceof BundleEntity\ProductCategory) {
            return $this->createException('InvalidParameter', 'ProductCategory', 'err.invalid.parameter.product_category');
        }
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        /** If no entity is provided as file we need to check if it does exist */
        if (is_numeric($locale)) {
            $response = $mlsModel->getLanguage($locale, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Language', 'err.db.language.notexist');
            }
            $locale = $response['result']['set'];
        } else if (is_string($locale)) {
            $response = $mlsModel->getLanguage($locale, 'iso_code');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Language', 'err.db.language.notexist');
            }
            $locale = $response['result']['set'];
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($category)) {
            $response = $this->getProductCategory($category, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        }

        $found = false;

        $q_str = 'SELECT COUNT(' . $this->entity['active_product_category_locale']['alias'] . ')'
            . ' FROM ' . $this->entity['active_product_category_locale']['name'] . ' ' . $this->entity['active_product_category_locale']['alias']
            . ' WHERE ' . $this->entity['active_product_category_locale']['alias'] . '.locale = ' . $locale->getId()
            . ' AND ' . $this->entity['active_product_category_locale']['alias'] . '.product_category = ' . $category->getId();
        $query = $this->em->createQuery($q_str);

        $result = $query->getSingleScalarResult();

        /** flush all into database */
        if ($result > 0) {
            $found = true;
            $code = 'scc.db.entry.exist';
        } else {
            $code = 'scc.db.entry.noexist';
        }

        if ($bypass) {
            return $found;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $found == true ? $result : $found,
                'total_rows' => count($result),
                'last_insert_id' => null,
            ),
            'error' => $found == true ? false : true,
            'code' => $code,
        );
        return $this->response;
    }

    /**
     * @name            isProductAssociatedWithCategory ()
     *                  Checks if the product is already associated with the product category.
     *
     * @since           1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @user            $this->createException
     *
     * @param           mixed $file 'entity' or 'entity' id
     * @param           mixed $product 'entity' or 'entity' id.
     * @param           bool $bypass true or false
     *
     * @return          mixed           bool or $response
     */
    public function isProductAssociatedWithCategory($product, $category, $bypass = false)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        if (!is_numeric($product) && !is_string($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product');
        }

        if (!is_numeric($category) && !$category instanceof BundleEntity\ProductCategory) {
            return $this->createException('InvalidParameter', 'ProductCategory', 'err.invalid.parameter.product_category');
        }
        /** If no entity is provided as file we need to check if it does exist */
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        } else if (is_string($product)) {
            $response = $this->getProduct($product, 'sku');
            if ($response['error']) {
                $response = $this->getProduct($product, 'url_key');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
                }
            }
            $product = $response['result']['set'];
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($category)) {
            $response = $this->getProductCategory($category, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'ProductCategory', 'err.db.product_category.notexist');
            }
            $category = $response['result']['set'];
        }
        $found = false;

        $q_str = 'SELECT COUNT(' . $this->entity['categories_of_product']['alias'] . ')'
            . ' FROM ' . $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias']
            . ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.category = ' . $category->getId()
            . ' AND ' . $this->entity['categories_of_product']['alias'] . '.product = ' . $product->getId();
        $query = $this->em->createQuery($q_str);
        ;
        $result = $query->getSingleScalarResult();

        /** flush all into database */
        if ($result > 0) {
            $found = true;
            $code = 'scc.db.entry.exist';
        } else {
            $code = 'scc.db.entry.noexist';
        }

        if ($bypass) {
            return $found;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $found,
                'total_rows' => $result,
                'last_insert_id' => null,
            ),
            'error' => $found == true ? false : true,
            'code' => $code,
        );
        return $this->response;
    }

    /**
     * @name            listActiveLocalesOfProduct ()
     *                  List active locales of a given product.
     *
     * @since           1.2.3
     * @version         1.4.8
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $product entity, id, or sku
     *
     * @return          array           $response
     */
    public function listActiveLocalesOfProduct($product)
    {
        $this->resetResponse();
        if (!is_string($product) && !is_numeric($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
        }
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
        } elseif (is_string($product)) {
            $response = $this->getProduct($product, 'sku');
        }
        if (isset($response) && !$response['error']) {
            $product = $response['result']['set'];
        } else if (isset($response) && $response['error']) {
            return $this->createException('EntityDoesNotExist', 'Product', 'err.invalid.parameter.product');
        }
        $productId = $product->getId();
        $qStr = 'SELECT ' . $this->entity['active_product_locale']['alias']
            . ' FROM ' . $this->entity['active_product_locale']['name'] . ' ' . $this->entity['active_product_locale']['alias']
            . ' WHERE ' . $this->entity['active_product_locale']['alias'] . '.product = ' . $productId;
        $query = $this->em->createQuery($qStr);
        $result = $query->getResult();
        $locales = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getLocale()->getId();
            if (!isset($unique[$id])) {
                $locales[] = $entry->getLocale();
                $unique[$id] = $entry->getLocale();
            }
        }
        unset($unique);
        $totalRows = count($locales);
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $locales,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listActiveLocalesOfProductCategory ()
     *                  List active locales of a given product category.
     *
     * @since           1.2.3
     * @version         1.2.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $category entity, id
     *
     * @return          array           $response
     */
    public function listActiveLocalesOfProductCategory($category)
    {
        $this->resetResponse();
        if (!is_string($category) && !is_numeric($category) && !$category instanceof BundleEntity\ProductCategory) {
            return $this->createException('InvalidParameter', 'ProductCategory entity', 'err.invalid.parameter.product_category');
        }
        if (is_numeric($category)) {
            $response = $this->getProductCategory($category, 'id');
        } elseif (is_string($category)) {
            $response = $this->getProductCategory($category, 'sku');
        }
        if (isset($response) && !$response['error']) {
            $category = $response['result']['set'];
        } else if (isset($response) && $response['error']) {
            return $this->createException('EntityDoesNotExist', 'ProductCategory', 'err.invalid.parameter.product_category');
        }
        $catId = $category->getId();
        $qStr = 'SELECT ' . $this->entity['active_product_category_locale']['alias']
            . ' FROM ' . $this->entity['active_product_category_locale']['name'] . ' ' . $this->entity['active_product_category_locale']['alias']
            . ' WHERE ' . $this->entity['active_product_category_locale']['alias'] . '.product_category = ' . $catId;
        $query = $this->em->createQuery($qStr);
        $result = $query->getResult();
        $locales = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getLocale()->getId();
            if (!isset($unique[$id])) {
                $locales[] = $entry->getLocale();
                $unique[$id] = $entry->getLocale();
            }
        }
        unset($unique);
        $totalRows = count($locales);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $locales,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listAttributesOfProduct ()
     *                  List attributes of a given product
     *
     * @since           1.0.5
     * @version         1.1.0
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed $product Product entity, sku, urllistProductsOfCategoryInLocales key or id.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listAttributesOfProduct($product, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        if (!$product instanceof BundleEntity\Product && !is_numeric($product) && !is_string($product)) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
        }
        if (!is_object($product)) {
            switch ($product) {
                case is_numeric($product):
                    $response = $this->getProduct($product, 'id');
                    break;
                case is_string($product):
                    $response = $this->getProduct($product, 'sku');
                    if ($response['error']) {
                        $response = $this->getProduct($product, 'url_key');
                    }
                    break;
            }
            if ($response['error']) {
                return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
            }
            $product = $response['result']['set'];
        }
        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['attributes_of_product']['alias'] . ', ' . $this->entity['product_attribute']['alias']
            . ' FROM ' . $this->entity['attributes_of_product']['name'] . ' ' . $this->entity['attributes_of_product']['alias']
            . ' JOIN ' . $this->entity['attributes_of_product']['alias'] . '.attribute ' . $this->entity['product_attribute']['alias']
            . ' WHERE ' . $this->entity['attributes_of_product']['alias'] . '.product = ' . $product->getId();
        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    default:
                        $column = $this->entity['attributes_of_product']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        $q_str .= $order_str;

        $query = $this->em->createQuery($q_str);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }

        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows == 0) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $attributes = array();
        foreach ($result as $aop) {
            $attributes[] = $aop->getAttribute();
        }
        $totalRows = count($attributes);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $attributes,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listAttributesOfProductCategory ()
     *                  List attributes of a given product
     *
     * @since           1.2.4
     * @version         1.2.4
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $category ProductCategoru entity, id
     * @param           array $sortOrder Array
     *                                                          'column'            => 'asc|desc'
     * @param           array $limit
     *                                                          start
     *                                                          count
     *
     * @return          array           $response
     */
    public function listAttributesOfProductCategory($category, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        if (!$category instanceof BundleEntity\ProductCategory && !is_numeric($category) && !is_string($category)) {
            return $this->createException('InvalidParameter', 'ProductCategory entity', 'err.invalid.parameter.product_category');
        }
        if (!is_object($category)) {
            switch ($category) {
                case is_numeric($category):
                    $response = $this->getProductCategory($category, 'id');
                    break;
            }
            if ($response['error']) {
                return $this->createException('InvalidParameter', 'ProductCategory entity', 'err.invalid.parameter.product_category');
            }
            $category = $response['result']['set'];
        }
        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['attributes_of_product_category']['alias'] . ', ' . $this->entity['product_attribute']['alias']
            . ' FROM ' . $this->entity['attributes_of_product_category']['name'] . ' ' . $this->entity['attributes_of_product_category']['alias']
            . ' JOIN ' . $this->entity['attributes_of_product_category']['alias'] . '.product_attribute ' . $this->entity['product_attribute']['alias']
            . ' WHERE ' . $this->entity['attributes_of_product_category']['alias'] . '.product_category = ' . $category->getId();
        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    default:
                        $column = $this->entity['attributes_of_product_category']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        $q_str .= $order_str;

        $query = $this->em->createQuery($q_str);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }

        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows == 0) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $attributes = array();
        foreach ($result as $aop) {
            $attributes[] = $aop->getProductAttribute();
        }
        $totalRows = count($attributes);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $attributes,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listAllAttributeValuesOfProduct ()
     *                  Lists all attribute values of a given product
     *
     * @since           1.1.5
     * @version         1.1.5
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed $product Product entity, sku, url key or id.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listAllAttributeValuesOfProduct($product, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        if (!$product instanceof BundleEntity\Product && !is_numeric($product) && !is_string($product)) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
        }
        if (!is_object($product)) {
            switch ($product) {
                case is_numeric($product):
                    $response = $this->getProduct($product, 'id');
                    break;
                case is_string($product):
                    $response = $this->getProduct($product, 'sku');
                    if ($response['error']) {
                        $response = $this->getProduct($product, 'url_key');
                    }
                    break;
            }
            if ($response['error']) {
                return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
            }
            $product = $response['result']['set'];
        }
        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['product_attribute_values']['alias'] . ', ' . $this->entity['product_attribute']['alias']
            . ' FROM ' . $this->entity['product_attribute_values']['name'] . ' ' . $this->entity['product_attribute_values']['alias']
            . ' JOIN ' . $this->entity['product_attribute_values']['alias'] . '.attribute ' . $this->entity['product_attribute']['alias']
            . ' WHERE ' . $this->entity['product_attribute_values']['alias'] . '.product = ' . $product->getId();
        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'sort_order':
                        $column = $this->entity['product_attribute']['alias'] . '.' . $column;
                        break;
                    default:
                        $column = $this->entity['attributes_of_product']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        $q_str .= $order_str;

        $query = $this->em->createQuery($q_str);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }

        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows == 0) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listAllChildProductCategories ()
     *                Lists parent only product categories.
     *
     * @since            1.2.1
     * @version         1.2.1
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategoriess()
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listAllChildProductCategories($sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        /**
         * Prepare $filter
         */
        $column = $this->entity['product_category']['alias'] . '.parent';
        $condition = array('column' => $column, 'comparison' => 'notnull', 'value' => null);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );

        $response = $this->listProductCategories($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listAttributeValuesOfProduct ()
     *                List attributes of a given product
     *
     * @since            1.0.5
     * @version         1.1.0
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed $product Product entity, sku, url key or id.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listAttributeValuesOfProduct($product, $type, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        if (!$product instanceof BundleEntity\Product && !is_numeric($product) && !is_string($product)) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
        }
        if (!is_object($product)) {
            switch ($product) {
                case is_numeric($product):
                    $response = $this->getProduct($product, 'id');
                    break;
                case is_string($product):
                    $response = $this->getProduct($product, 'sku');
                    if ($response['error']) {
                        $response = $this->getProduct($product, 'url_key');
                    }
                    break;
            }
            if ($response['error']) {
                return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
            }
            $product = $response['result']['set'];
        }
        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['product_attribute_values']['alias'] . ', ' . $this->entity['product_attribute']['alias']
            . ' FROM ' . $this->entity['product_attribute_values']['name'] . ' ' . $this->entity['product_attribute_values']['alias']
            . ' JOIN ' . $this->entity['product_attribute_values']['alias'] . '.attribute ' . $this->entity['product_attribute']['alias']
            . ' WHERE ' . $this->entity['product_attribute_values']['alias'] . '.product = ' . $product->getId() . ' AND ' . $this->entity['product_attribute_values']['alias'] . '.attribute = ' . $type;
        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'sort_order':
                        $column = $this->entity['product_attribute']['alias'] . '.' . $column;
                        break;
                    default:
                        $column = $this->entity['attributes_of_product']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        $q_str .= $order_str;

        $query = $this->em->createQuery($q_str);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }

        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows == 0) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listCategoriesOfProduct ()
     *                  List categories of a given product.
     *
     * @since           1.0.5
     * @version         1.3.5
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed $product Product entity, sku, url key or id.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listCategoriesOfProduct($product, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        if (!$product instanceof BundleEntity\Product && !is_numeric($product) && !is_string($product)) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
        }
        if (!is_object($product)) {
            switch ($product) {
                case is_numeric($product):
                    $response = $this->getProduct($product, 'id');
                    break;
                case is_string($product):
                    $response = $this->getProduct($product, 'sku');
                    if ($response['error']) {
                        $response = $this->getProduct($product, 'url_key');
                    }
                    break;
            }
            if ($response['error']) {
                return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
            }
            $product = $response['result']['set'];
        }
        /**
         * Prepare $filter
         */
        $qStr = '';
        $selectStr = 'SELECT ' . $this->entity['categories_of_product']['alias'];
        $fromStr = ' FROM ' . $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias'];
        $joinStr = '';
        $whereStr = ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.product = ' . $product->getId();
        $orderStr = '';
        if (!is_null($sortOrder)) {
            foreach ($sortOrder as $column => $value) {
                switch ($column) {
                    case 'date_added':
                    case 'date_updated':
                        $joinStr = ' JOIN ' . $this->entity['categories_of_product']['alias'] . '.category ' . $this->entity['product_category']['alias'];
                        $column = $this->entity['product_category']['alias'] . '.' . $column;
                        break;
                    case 'sort_order':
                        $column = $this->entity['categories_of_product']['alias'] . '.' . $column;
                        break;
                }
                $orderStr .= $column . ' ' . strtoupper($value) . ', ';
            }
            $orderStr = ' ORDER BY ' . $orderStr;
        }
        $qStr = $selectStr . $fromStr . $joinStr . $whereStr . $orderStr;
        $qStr = rtrim(trim($qStr), ',');
        $query = $this->em->createQuery($qStr);
        if (!is_null($limit)) {
            $query->setFirstResult($limit['start']);
            $query->setMaxResults($limit['count']);
        }
        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows == 0) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $cats = array();
        foreach ($result as $cop) {
            $cats[] = $cop->getCategory();
        }
        $totalRows = count($cats);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $cats,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listChildCategoriesOfProductCategory ()
     *                Lists child categories of a product category
     *
     * @since            1.2.1
     * @version         1.2.1
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategoriess()
     *
     * @param           mixed $category id, entity
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listChildCategoriesOfProductCategory($category, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();

        if ($category instanceof BundleEntity\ProductCategory) {
            $category = $category->getId();
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['product_category']['alias'] . '.parent';
        $condition = array('column' => $column, 'comparison' => 'eq', 'value' => $category);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );

        $response = $this->listProductCategories($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listChildCategoriesOfProductCategoryWithPreviewImage ()
     *                Lists child categories of a product category that have been a preview image assigned.
     *
     * @since            1.2.9
     * @version         1.2.9
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategoriess()
     *
     * @param           mixed $category id, entity
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listChildCategoriesOfProductCategoryWithPreviewImage($category, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        if ($category instanceof BundleEntity\ProductCategory) {
            $category = $category->getId();
        }
        /**
         * Prepare parent category $filter
         */
        $column = $this->entity['product_category']['alias'] . '.parent';
        $condition = array('column' => $column, 'comparison' => 'eq', 'value' => $category);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );

        /**
         * Prepare preview image $filter
         */
        $column = $this->entity['product_category']['alias'] . '.preview_image';
        $condition = array('column' => $column, 'comparison' => 'notnull', 'value' => null);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        $response = $this->listProductCategories($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listCustomizableProducts ()
     *                  List customizable products.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listCustomizableProducts($sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.is_customizable';
        $condition = array('column' => $column, 'comparison' => '=', 'value' => 'y');
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listNotCustomizableProducts ()
     *                Lists non-customizable products.
     *
     * @since            1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listNotCustomizableProducts($sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.is_customizable';
        $condition = array('column' => $column, 'comparison' => '=', 'value' => 'n');
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listOutOfStockProducts ()
     *                List products that have zero or less quantity.
     *
     * @since            1.0.4
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @use             $this->listProducts()
     * @use             $this->createException
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listOutOfStockProducts($sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.quantity';
        $condition = array('column' => $column, 'comparison' => '<', 'value' => 1);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listParentOnlyProductCategories ()
     *                Lists parent only product categories.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategoriess()
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listParentOnlyProductCategories($sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        /**
         * Prepare $filter
         */
        $column = $this->entity['product_category']['alias'] . '.parent';
        $condition = array('column' => $column, 'comparison' => 'null', 'value' => null);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );

        $response = $this->listProductCategories($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listParentOnlyProductCategories ()
     *                Lists parent only product categories.
     *
     * @since            1.0.8
     * @version         1.0.8
     * @author          Said İmamoğlu
     *
     * @uses            $this->listProductCategoriess()
     *
     * @param           integer $level
     * @param           array $filter
     * @param           mixed $sortOrder
     * @param           mixed $limit
     *
     * @return          array           $response
     */
    public function listParentOnlyProductCategoriesOfLevel($level = 1, $filter = array(), $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        /**
         * Prepare $filter
         */
        $column = $this->entity['product_category']['alias'] . '.parent';
        $condition = array('column' => $column, 'comparison' => 'null', 'value' => null);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                ),
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['product_category']['alias'] . '.level', 'comparison' => '=', 'value' => $level),
                )
            )
        );

        $response = $this->listProductCategories($filter, $sortOrder, $limit, null, false);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsWithPriceBetween ()
     *                List products that are priced between two values.
     *
     * @since            1.0.4
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @uses            $this->listProductsPriced()
     *
     * @param           array $amounts Bottom and upper limit.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsPricedBetween($amounts, $sortOrder = null, $limit = null)
    {
        return $this->listProductsPriced($amounts, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductAttributes ()
     *                List product attributes from database based on a variety of conditions.
     *
     * @since            1.0.5
     * @version         1.1.0
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $filter Multi-dimensional array
     *
     *                                  Example:
     *                                  $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                               array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.id', 'comparison' => 'in', 'value' => array(3,4,5,6)),
     *                                                                  )
     *                                                  )
     *                                              );
     *                                 $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                              array(
     *                                                                      'glue' => 'or',
     *                                                                      'condition' => array('column' => 'p.status', 'comparison' => 'eq', 'value' => 'a'),
     *                                                              ),
     *                                                              array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.price', 'comparison' => '<', 'value' => 500),
     *                                                              ),
     *                                                             )
     *                                           );
     *
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listProductAttributes($filter = null, $sortOrder = null, $limit = null, $queryStr = null, $returnLocales = false)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['product_attribute_localization']['alias'] . ', ' . $this->entity['product_attribute']['alias']
                . ' FROM ' . $this->entity['product_attribute_localization']['name'] . ' ' . $this->entity['product_attribute_localization']['alias']
                . ' JOIN ' . $this->entity['product_attribute_localization']['alias'] . '.attribute ' . $this->entity['product_attribute']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'sort_order':
                    case 'date_added':
                        $column = $this->entity['product_attribute']['alias'] . '.' . $column;
                        break;
                    case 'name':
                    case 'url_key':
                        $column = $this->entity['product_attribute_localization']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }

        $queryStr .= $where_str . $group_str . $order_str;

        $query = $this->em->createQuery($queryStr);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }
        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();

        $attributes = array();
        $unique = array();
        $localizations  = array();
        foreach ($result as $entry) {
            $id = $entry->getAttribute()->getId();
            if (!isset($unique[$id])) {
                $attributes[$id] = $entry->getAttribute();
                $unique[$id] = $entry->getAttribute();
            }
            $localizations[$id][] = $entry;
        }
        unset($unique);
        $responseSet = array();
        if ($returnLocales) {
            foreach ($attributes as $key => $attribute) {
                $responseSet[$key]['entity'] = $attribute;
                $responseSet[$key]['localizations'] = $localizations[$key];
            }
        } else {
            $responseSet = $attributes;
        }
        $newCollection = array();
        foreach ($responseSet as $item) {
            $newCollection[] = $item;
        }
        unset($responseSet, $products);

        $totalRows = count($newCollection);
        if ($totalRows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $newCollection,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductAttributeValues ()
     *                List product attribute values from database based on a variety of conditions.
     *
     * @since            1.2.2
     * @version         1.2.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $filter Multi-dimensional array
     *
     *                                  Example:
     *                                  $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                               array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.id', 'comparison' => 'in', 'value' => array(3,4,5,6)),
     *                                                                  )
     *                                                  )
     *                                              );
     *                                 $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                              array(
     *                                                                      'glue' => 'or',
     *                                                                      'condition' => array('column' => 'p.status', 'comparison' => 'eq', 'value' => 'a'),
     *                                                              ),
     *                                                              array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.price', 'comparison' => '<', 'value' => 500),
     *                                                              ),
     *                                                             )
     *                                           );
     *
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listProductAttributeValues($filter = null, $sortOrder = null, $limit = null, $queryStr = null)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['product_attribute_values']['alias']
                . ' FROM ' . $this->entity['product_attribute_values']['name'] . ' ' . $this->entity['product_attribute_values']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'sort_order':
                    case 'date_added':
                        $column = $this->entity['product_attribute_values']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }

        $queryStr .= $where_str . $group_str . $order_str;

        $query = $this->em->createQuery($queryStr);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }
        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();

        $attributes = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getId();
            if (!isset($unique[$id])) {
                $attributes[] = $entry;
                $unique[$id] = $entry->getId();
            }
        }
        unset($unique);
        $totalRows = count($attributes);
        if ($totalRows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $attributes,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductCategories ()
     *                  List product categories from database based on a variety of conditions.
     *
     * @since           1.0.3
     * @version         1.3.2
     * @author          Can Berkol
     *
     * @user            $this->createException()
     *
     * @param           array $filter Multi-dimensional array
     *
     *                                  Example:
     *                                  $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                               array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.id', 'comparison' => 'in', 'value' => array(3,4,5,6)),
     *                                                                  )
     *                                                  )
     *                                              );
     *                                 $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                              array(
     *                                                                      'glue' => 'or',
     *                                                                      'condition' => array('column' => 'p.status', 'comparison' => 'eq', 'value' => 'a'),
     *                                                              ),
     *                                                              array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.price', 'comparison' => '<', 'value' => 500),
     *                                                              ),
     *                                                             )
     *                                           );
     *
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     * @param           bool $returnLocales Optimizes query and returns a set with keys ['entities'] and ['locales']
     *
     * @return          array           $response
     */
    public function listProductCategories($filter = null, $sortOrder = null, $limit = null, $queryStr = null, $returnLocales = false)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['product_category_localization']['alias'] . ', ' . $this->entity['product_category']['alias']
                . ' FROM ' . $this->entity['product_category_localization']['name'] . ' ' . $this->entity['product_category_localization']['alias']
                . ' JOIN ' . $this->entity['product_category_localization']['alias'] . '.category ' . $this->entity['product_category']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'count_children':
                    case 'date_added':
                    case 'date_updated':
                    case 'count_like':
                        $column = $this->entity['product_category']['alias'] . '.' . $column;
                        break;
                    case 'name':
                    case 'url_key':
                        $column = $this->entity['product_category_localization']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }
        if (!is_null($limit)) {
            $lqStr = 'SELECT ' . $this->entity['product_category']['alias'] . ' FROM ' . $this->entity['product_category']['name'] . ' ' . $this->entity['product_category']['alias'];
            $lqStr .= $where_str . $group_str . $order_str;
            $lQuery = $this->em->createQuery($lqStr);
            $lQuery = $this->addLimit($lQuery, $limit);
            $result = $lQuery->getResult();
            $selectedIds = array();
            foreach ($result as $entry) {
                $selectedIds[] = $entry->getId();
            }
            if (count($selectedIds) > 0) {
                $where_str .= ' AND ' . $this->entity['product_category_localization']['alias'] . '.category IN(' . implode(',', $selectedIds) . ')';
            }

        }

        $queryStr .= $where_str . $group_str . $order_str;

        $query = $this->em->createQuery($queryStr);

        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();
        $categories = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getCategory()->getId();
            if (!isset($unique[$id])) {
                $categories[$id] = $entry->getCategory();
                $unique[$id] = $entry->getCategory();
            }
            $localizations[$id][] = $entry;
        }
        $totalRows = count($categories);
        $responseSet = array();
        if ($returnLocales) {
            foreach ($categories as $key => $category) {
                $responseSet[$key]['entity'] = $category;
                $responseSet[$key]['localizations'] = $localizations[$key];
            }
        } else {
            $responseSet = $categories;
        }
        $newCollection = array();
        foreach ($responseSet as $item) {
            $newCollection[] = $item;
        }
        unset($responseSet, $categories);

        if ($totalRows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $newCollection,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductCategoriesOfLevel ()
     *                  Lists parent only product categories.
     *
     * @since           1.1.9
     * @version         1.1.9
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategoriesOfLevel()
     *
     * @param           array $level integer
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductCategoriesOfLevel($level = 1, $sortOrder = null, $limit = null, $qStr = null, $returnLocales = false)
    {
        $this->resetResponse();
        /**
         * Prepare $filter
         */
        if (is_numeric($level)) {
            $level = array($level);
        }
        $conditions = array();

        foreach ($level as $value) {
            $conditions[] = array(
                'glue' => 'or',
                'condition' => array('column' => $this->entity['product_category']['alias'] . '.level', 'comparison' => '=', 'value' => $value),
            );
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => $conditions
        );

        return $response = $this->listProductCategories($filter, $sortOrder, $limit, $qStr, $returnLocales);
    }

    /**
     * @name            listProducts ()
     *                  List products from database based on a variety of conditions.
     *
     * @since           1.0.0
     * @version         1.4.9
     * @author          Can Berkol
     *
     * @throws          InvalidSortOrderException
     * @throws          InvalidFilterException
     * @throws          InvalidLimitException
     *
     * @param           array $filter Multi-dimensional array
     *
     *                                  Example:
     *                                  $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                               array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.id', 'comparison' => 'in', 'value' => array(3,4,5,6)),
     *                                                                  )
     *                                                  )
     *                                              );
     *                                 $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                              array(
     *                                                                      'glue' => 'or',
     *                                                                      'condition' => array('column' => 'p.status', 'comparison' => 'eq', 'value' => 'a'),
     *                                                              ),
     *                                                              array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.price', 'comparison' => '<', 'value' => 500),
     *                                                              ),
     *                                                             )
     *                                           );
     *
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listProducts($filter = null, $sortOrder = null, $limit = null, $queryStr = null,$returnLocales = false)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['product_localization']['alias'] . ', ' . $this->entity['product']['alias']
                . ' FROM ' . $this->entity['product_localization']['name'] . ' ' . $this->entity['product_localization']['alias']
                . ' JOIN ' . $this->entity['product_localization']['alias'] . '.product ' . $this->entity['product']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'quantiy':
                    case 'price':
                    case 'count_view':
                    case 'sku':
                    case 'sort_order':
                    case 'date_added':
                    case 'date_updated':
                    case 'count_like':
                    case 'site':
                        $column = $this->entity['product']['alias'] . '.' . $column;
                        break;
                    case 'name':
                    case 'description':
                    case 'meta_keywords':
                    case 'meta_description':
                        $column = $this->entity['product_localization']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }

        if ($limit != null) {
            $lqStr = 'SELECT ' . $this->entity['product_localization']['alias'] . ', ' . $this->entity['product']['alias']
                . ' FROM ' . $this->entity['product_localization']['name'] . ' ' . $this->entity['product_localization']['alias']
                . ' JOIN ' . $this->entity['product_localization']['alias'] . '.product ' . $this->entity['product']['alias'];
            $lQuery = $this->em->createQuery($lqStr);
            $lQuery = $this->addLimit($lQuery, $limit);
            $result = $lQuery->getResult();
            $selectedIds = array();
            foreach ($result as $entry) {
                $selectedIds[] = $entry->getProduct()->getId();
            }
            if (count($selectedIds) > 0) {
                if(strlen($where_str) == 0){
                    $where_str .= ' WHERE  ' . $this->entity['product_localization']['alias'] . '.product IN(' . implode(',', $selectedIds) . ')';
                }
                else{
                    $where_str .= ' AND ' . $this->entity['product_localization']['alias'] . '.product IN(' . implode(',', $selectedIds) . ')';
                }
            }
        }

        $queryStr .= $where_str . $group_str . $order_str;
        $query = $this->em->createQuery($queryStr);

        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();

        $products = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getProduct()->getId();
            if (!isset($unique[$id])) {
                $products[$id] = $entry->getProduct();
                $unique[$id] = $entry->getProduct();
            }
            $localizations[$id][] = $entry;
        }
        unset($unique);
        $responseSet = array();
        if ($returnLocales) {
            foreach ($products as $key => $product) {
                $responseSet[$key]['entity'] = $product;
                $responseSet[$key]['localizations'] = $localizations[$key];
            }
        } else {
            $responseSet = $products;
        }
        $newCollection = array();
        foreach ($responseSet as $item) {
            $newCollection[] = $item;
        }
        unset($responseSet, $products);
        $totalRows = count($newCollection);
        if ($totalRows < 1) {
            $this->response['result']['set'] = array();
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $newCollection,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsAdded ()
     *                  List products that are added before, after, or in between of the given date(s).
     *
     * @since           1.0.3
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed $date One DateTime object or start and end DateTime objects.
     * @param           string $eq after, before, between
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listProductsAdded($date, $eq, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $eq_opts = array('after', 'before', 'between', 'on');
        if (!$date instanceof \DateTime && !is_array($date)) {
            return $this->createException('InvalidParameter', 'DateTime object or Array', 'err.invalid.parameter.date');
        }
        if (!in_array($eq, $eq_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.date_added';

        if ($eq == 'after' || $eq == 'before' || $eq == 'on') {
            switch ($eq) {
                case 'after':
                    $eq = '>';
                    break;
                case 'before':
                    $eq = '<';
                    break;
                case 'on':
                    $eq = '=';
                    break;
            }
            $condition = array('column' => $column, 'comparison' => $eq, 'value' => $date);
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => $condition,
                    )
                )
            );
        } else {
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '>', 'value' => $date[0]),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '<', 'value' => $date[1]),
                    )
                )
            );
        }
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsAddedAfter ()
     *                List products that are added after the given date.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsAdded()
     *
     * @param           array $date The date to be checked.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsAddedAfter($date, $sortOrder = null, $limit = null)
    {
        return $this->listProductsAdded($date, 'after', $sortOrder, $limit);
    }

    /**
     * @name            listProductsAddedBefore ()
     *                List products that are added before the given date.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsAdded()
     *
     * @param           array $date The date to be checked.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsAddedBefore($date, $sortOrder = null, $limit = null)
    {
        return $this->listProductsAdded($date, 'before', $sortOrder, $limit);
    }

    /**
     * @name            listProductsAddedBetween ()
     *                List products that are added between two dates.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsAdded()
     *
     * @param           array $dates The earlier and the later dates.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsAddedBetween($dates, $sortOrder = null, $limit = null)
    {
        return $this->listProductsAdded($dates, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductsAddedOn ()
     *                List products that are added on a given date.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsAdded()
     *
     * @param           array $date The date.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsAddedOn($date, $sortOrder = null, $limit = null)
    {
        return $this->listProductsAdded($date, 'on', $sortOrder, $limit);
    }

    /**
     * @name            listProductsInCategory ()
     *                List products associated with a number of groups.
     *
     * @since            1.2.1
     * @version         1.2.1
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed $categories Category entities, ids, url_keys.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsInCategory(array $categories, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $catIds = array();
        foreach ($categories as $category) {
            if (!$category instanceof BundleEntity\ProductCategory && !is_numeric($category) && !is_string($category)) {
                return $this->createException('InvalidParameter', 'ProductCategory entity', 'err.invalid.parameter.product_category');
            }
            if (!is_object($category)) {
                switch ($category) {
                    case is_numeric($category):
                        $catIds[] = $category;
                        break;
                    case is_string($category):
                        $response = $this->getProductCategory($category, 'url_key');
                        if ($response['error']) {
                            $catIds[] = $category->getId();
                        }
                        break;
                }
            } else {
                $catIds[] = $category->getId();
            }
        }
        $catIds = implode(',', $catIds);
        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['categories_of_product']['alias'] . ', ' . $this->entity['product']['alias']
            . ' FROM ' . $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias']
            . ' JOIN ' . $this->entity['categories_of_product']['alias'] . '.product ' . $this->entity['product']['alias']
            . ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.category IN (' . $catIds . ')';

        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'quantiy':
                    case 'price':
                    case 'sku':
                    case 'sort_order':
                    case 'date_added':
                    case 'date_updated':
                        $column = $this->entity['product']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }
        $q_str .= $order_str;
        $query = $this->em->createQuery($q_str);
        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $products = array();
        foreach ($result as $cop) {
            $products[] = $cop->getProduct();
        }
        $totalRows = count($products);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $products,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsInLocales ()
     *                  List products that are associated with a locale.
     *
     * @since           1.3.3
     * @version         1.3.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $locales entity, id, or sku
     * @param           array $sortOrder
     * @param           array $limit
     *
     * @return          array           $response
     */
    public function listProductsInLocales(array $locales, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $langIds = array();
        foreach ($locales as $locale) {
            if (!$locale instanceof MLSEntity\Language && !is_numeric($locale) && !is_string($locale)) {
                return $this->createException('InvalidParameter', 'Language entity', 'err.invalid.parameter.language');
            }
            if (!is_object($locale)) {
                switch ($locale) {
                    case is_numeric($locale):
                        $langIds[] = $locale;
                        break;
                    case is_string($locale):
                        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                        $response = $mlsModel->getLanguage($locale, 'iso_code');
                        if ($response['error']) {
                            $langIds[] = $locale->getId();
                        }
                        break;
                }
            } else {
                $langIds[] = $locale->getId();
            }
        }
        $langIds = implode(',', $langIds);
        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['active_product_locale']['alias'] . ', ' . $this->entity['language']['alias'] . ', ' . $this->entity['product']['alias']
            . ' FROM ' . $this->entity['active_product_locale']['name'] . ' ' . $this->entity['active_product_locale']['alias']
            . ' JOIN ' . $this->entity['active_product_locale']['alias'] . '.product ' . $this->entity['product']['alias']
            . ' WHERE ' . $this->entity['active_product_locale']['alias'] . '.locale IN (' . $langIds . ')';

        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'quantiy':
                    case 'price':
                    case 'sku':
                    case 'sort_order':
                    case 'date_added':
                    case 'date_updated':
                        $column = $this->entity['product']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }
        $q_str .= $order_str;
        $query = $this->em->createQuery($q_str);
        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $products = array();
        foreach ($result as $cop) {
            $products[] = $cop->getProduct();
        }
        $totalRows = count($products);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $products,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductCategoriesInLocales ()
     *                  List product categories that are associated with a locale.
     *
     * @since           1.2.3
     * @version         1.2.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $locales entity, id, or sku
     * @param           array $sortOrder
     * @param           array $limit
     *
     * @return          array           $response
     */
    public function listProductCategoriesInLocales(array $locales, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $langIds = array();
        foreach ($locales as $locale) {
            if (!$locale instanceof MLSEntity\Language && !is_numeric($locale) && !is_string($locale)) {
                return $this->createException('InvalidParameter', 'Language entity', 'err.invalid.parameter.language');
            }
            if (!is_object($locale)) {
                switch ($locale) {
                    case is_numeric($locale):
                        $langIds[] = $locale;
                        break;
                    case is_string($locale):
                        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                        $response = $mlsModel->getLanguage($locale, 'iso_code');
                        if (!$response['error']) {
                            $locale = $response['result']['set'];
                            $langIds[] = $locale->getId();
                        }
                        break;
                }
            } else {
                $langIds[] = $locale->getId();
            }
        }
        $langIds = implode(',', $langIds);
        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['active_product_category_locale']['alias']
            . ' FROM ' . $this->entity['active_product_category_locale']['name'] . ' ' . $this->entity['active_product_category_locale']['alias']
            . ' JOIN ' . $this->entity['active_product_category_locale']['alias'] . '.product_category ' . $this->entity['product_category']['alias']
            . ' WHERE ' . $this->entity['active_product_category_locale']['alias'] . '.locale IN (' . $langIds . ')';

        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'sort_order':
                        $column = $this->entity['product_category']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }
        $q_str .= $order_str;
        $query = $this->em->createQuery($q_str);
        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $categories = array();
        foreach ($result as $apl) {
            $categories[] = $apl->getProductCategory();
        }
        $totalRows = count($categories);
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $categories,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsLiked ()
     *                  List products that are liked less, more than or in between.
     *
     * @since           1.0.1
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed $likes Number of likes. Or upper and bottom limits.
     * @param           string $eq less, more, between
     * @param           array $sortOrder Array
     *                                                  'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listProductsLiked($likes, $eq, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $eq_opts = array('less', 'more', 'between');
        if (!is_integer($likes) && !is_array($likes)) {
            return $this->createException('InvalidParameter', 'integer or Array', 'err.invalid.parameter.likes');
        }
        if (!in_array($eq, $eq_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.count_like';

        if ($eq == 'less' || $eq == 'more') {
            switch ($eq) {
                case 'more':
                    $eq = '>';
                    break;
                case 'less':
                    $eq = '<';
                    break;
            }
            $condition = array('column' => $column, 'comparison' => $eq, 'value' => $likes);
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => $condition,
                    )
                )
            );
        } else {
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '>', 'value' => $likes[0]),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '<', 'value' => $likes[1]),
                    )
                )
            );
        }
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsLikedBetween ()
     *                List products that are liked between two numbers..
     *
     * @since            1.0.1
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsLiked()
     *
     * @param           array $likes Bottom and upper limit.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsLikedBetween($likes, $sortOrder = null, $limit = null)
    {
        return $this->listProductsLiked($likes, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductsLikedLessThan ()
     *                List products that are liked less than given amount.
     *
     * @since            1.0.1
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsLiked()
     *
     * @param           integer $likes Number of likes.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsLikedLessThan($likes, $sortOrder = null, $limit = null)
    {
        return $this->listProductsLiked($likes, 'less', $sortOrder, $limit);
    }

    /**
     * @name            listProductsLikedMoreThan ()
     *                List products that are liked more than given amount.
     *
     * @since            1.0.0
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsLiked()
     *
     * @param           integer $likes Number of likes.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsLikedMoreThan($likes, $sortOrder = null, $limit = null)
    {
        return $this->listProductsLiked($likes, 'more', $sortOrder, $limit);
    }

    /**
     * @name            listProductsOfCategory ()
     *                List products associated with a given category.
     *
     * @since            1.0.9
     * @version         1.1.9
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed $category Category entity, id, url_key.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     * @param           array $withCategory
     *
     * @return          array           $response
     */
    public function listProductsOfCategory($category, $sortOrder = null, $limit = null, $withCategory = false)
    {
        $this->resetResponse();
        if (!$category instanceof BundleEntity\ProductCategory && !is_numeric($category) && !is_string($category)) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
        }
        if (!is_object($category)) {
            switch ($category) {
                case is_numeric($category):
                    $response = $this->getProductCategory($category, 'id');
                    break;
                case is_string($category):
                    $response = $this->getProductCategory($category, 'url_key');
                    break;
            }
            if ($response['error']) {
                return $this->createException('InvalidParameter', 'ProductCategory entity', 'err.invalid.parameter.product_category');
            }
            $category = $response['result']['set'];
        }

        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['categories_of_product']['alias'] . ', ' . $this->entity['product']['alias']
            . ' FROM ' . $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias']
            . ' JOIN ' . $this->entity['categories_of_product']['alias'] . '.product ' . $this->entity['product']['alias']
            . ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.category = ' . $category->getId();

        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                $sorting = false;
                if (!in_array($column, array('name', 'url_key'))) {
                    $sorting = true;
                    switch ($column) {
                        case 'id':
                        case 'quantiy':
                        case 'price':
                        case 'sku':
                        case 'date_added':
                        case 'date_updated':
                            $column = $this->entity['product']['alias'] . '.' . $column;
                            break;
                        case 'sort_order':
                            $column = $this->entity['categories_of_product']['alias'] . '.' . $column;
                            break;
                    }
                    $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
                }
            }
            if ($sorting) {
                $order_str = rtrim($order_str, ', ');
                $order_str = ' ORDER BY ' . $order_str . ' ';
            }
        }
        $q_str .= $order_str;
        $query = $this->em->createQuery($q_str);
        $result = $query->getResult();
        if (count($result) > 0) {
            if ($sortOrder != null) {
                $collection = array();
                foreach ($result as $item) {
                    $collection[] = $item->getProduct()->getId();
                }
                unset($result);
                $filter = array();
                $filter[] = array(
                    'glue' => 'and',
                    'condition' => array(
                        array(
                            'glue' => 'and',
                            'condition' => array('column' => $this->entity['product']['alias'] . '.id', 'comparison' => 'in', 'value' => $collection),
                        )
                    )
                );
                return $this->listProducts($filter, $sortOrder, $limit);
            }

        }

        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => array(),
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        //        $products = $result;
        $products = array();
        if (!$withCategory) {
            foreach ($result as $cop) {
                $products[] = $cop->getProduct();
            }
        }
        $totalRows = count($products);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $products,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsOfCategory ()
     *                List products associated with a given category.
     *
     * @since            1.0.9
     * @version         1.1.9
     * @author          Said İmamoğlu
     *
     * @use             $this->getProduct()
     *
     * @param           mixed $category Category entity, id, url_key.
     * @param           mixed $filter Category entity, id, url_key.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     * @param           array $withCategory
     *
     * @return          array           $response
     */
    public function listProductsOfCategoryByFilter($filter = array(), $sortOrder = null, $limit = null, $withCategory = false)
    {
        $this->resetResponse();
        if (!$category instanceof BundleEntity\ProductCategory && !is_numeric($category) && !is_string($category)) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter.product');
        }
        if (!is_object($category)) {
            switch ($category) {
                case is_numeric($category):
                    $response = $this->getProductCategory($category, 'id');
                    break;
                case is_string($category):
                    $response = $this->getProductCategory($category, 'url_key');
                    break;
            }
            if ($response['error']) {
                return $this->createException('InvalidParameter', 'ProductCategory entity', 'err.invalid.parameter.product_category');
            }
            $category = $response['result']['set'];
        }

        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['categories_of_product']['alias'] . ', ' . $this->entity['product']['alias']
            . ' FROM ' . $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias']
            . ' JOIN ' . $this->entity['categories_of_product']['alias'] . '.product ' . $this->entity['product']['alias']
            . ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.category = ' . $category->getId();

        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                $sorting = false;
                if (!in_array($column, array('name', 'url_key'))) {
                    $sorting = true;
                    switch ($column) {
                        case 'id':
                        case 'quantiy':
                        case 'price':
                        case 'sku':
                        case 'date_added':
                        case 'date_updated':
                            $column = $this->entity['product']['alias'] . '.' . $column;
                            break;
                        case 'sort_order':
                            $column = $this->entity['categories_of_product']['alias'] . '.' . $column;
                            break;
                    }
                    $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
                }
            }
            if ($sorting) {
                $order_str = rtrim($order_str, ', ');
                $order_str = ' ORDER BY ' . $order_str . ' ';
            }
        }
        $q_str .= $order_str;
        $query = $this->em->createQuery($q_str);
        $result = $query->getResult();
        if (count($result) > 0) {
            if ($sortOrder != null) {
                $collection = array();
                foreach ($result as $item) {
                    $collection[] = $item->getProduct()->getId();
                }
                unset($result);
                $filter = array();
                $filter[] = array(
                    'glue' => 'and',
                    'condition' => array(
                        array(
                            'glue' => 'and',
                            'condition' => array('column' => $this->entity['product']['alias'] . '.id', 'comparison' => 'in', 'value' => $collection),
                        )
                    )
                );
                return $this->listProducts($filter, $sortOrder, $limit);
            }

        }

        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => array(),
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        //        $products = $result;
        $products = array();
        if (!$withCategory) {
            foreach ($result as $cop) {
                $products[] = $cop->getProduct();
            }
        }
        $totalRows = count($products);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $products,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsOfCategoryInLocales ()
     *                  List products of categories that are associated with a locale.
     *
     * @since           1.2.3
     * @version         1.2.3
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           mixed $locales entity, id, or sku
     * @param           array $sortOrder
     * @param           array $limit
     *
     * @return          array           $response
     */
    public function listProductsOfCategoryInLocales($category, array $locales, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        /**
         * Prepare language ids
         */
        $langIds = array();
        foreach ($locales as $locale) {
            if (!$locale instanceof MLSEntity\Language && !is_numeric($locale) && !is_string($locale)) {
                return $this->createException('InvalidParameter', 'Language entity', 'err.invalid.parameter.language');
            }
            if (!is_object($locale)) {
                switch ($locale) {
                    case is_numeric($locale):
                        $langIds[] = $locale;
                        break;
                    case is_string($locale):
                        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                        $response = $mlsModel->getLanguage($locale, 'iso_code');
                        if (!$response['error']) {
                            $locale = $response['result']['set'];
                            $langIds[] = $locale->getId();
                        }
                        break;
                }
            } else {
                $langIds[] = $locale->getId();
            }
        }
        $langIds = implode(',', $langIds);

        /**
         * List products of category
         */
        $responsePoc = $this->listProductsOfCategory($category);
        if ($responsePoc['error']) {
            return $responsePoc;
        }
        $productIdCollection = array();
        $productCollection = array();
        foreach ($responsePoc['result']['set'] as $productEntity) {
            $productIdCollection[] = $productEntity->getId();
            $productCollection[$productEntity->getId()] = $productEntity;
        }
        $productIdCollection = implode(',',$productIdCollection);
        /**
         * Prepare $filter
         */
        $q_str = 'SELECT ' . $this->entity['active_product_locale']['alias']
            . ' FROM ' . $this->entity['active_product_locale']['name'] . ' ' . $this->entity['active_product_locale']['alias']
            . ' JOIN ' . $this->entity['active_product_locale']['alias'] . '.product ' . $this->entity['product']['alias']
            . ' WHERE ' . $this->entity['active_product_locale']['alias'] . '.locale IN (' . $langIds . ')'
            . ' AND '. $this->entity['active_product_locale']['alias'] . '.product IN (' . $productIdCollection . ')';

        /**
         * Prepare ORDER BY section of query.
         */
        $order_str = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'quantiy':
                    case 'price':
                    case 'sku':
                    case 'sort_order':
                    case 'date_added':
                    case 'date_updated':
                        $column = $this->entity['product']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }
        $q_str .= $order_str;
        $query = $this->em->createQuery($q_str);
        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $products = array();
        foreach ($result as $cop) {
            $products[] = $cop->getProduct();
        }
        $totalRows = count($products);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $products,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;

    }
    /**
     * @name            listProductsOfSite ()
     *                List products that belong to a site.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed $site Site entity or id.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsOfSite($site, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        if (!$site instanceof SiteMangementEntiy\Site && !is_numeric($site)) {
            return $this->createException('InvalidParameter', 'Site entity', 'err.invalid.parameter.site');
        }
        if (!is_object($site)) {
            $SMMModel = new SMMService\SiteManagementModel($this->kernel);

            $response = $SMMModel->getSite($site, 'id');
            if ($response['error']) {
                return $this->createException('InvalidParameter', 'Site entity', 'err.invalid.parameter.site');
            }
            $site = $response['result']['set'];
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.site';
        $condition = array('column' => $column, 'comparison' => '=', 'value' => $site->getId());
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsUpdated ()
     *                List products that are updated before, after, or in between of the given date(s).
     *
     * @since            1.0.3
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed $date One DateTime object or start and end DateTime objects.
     * @param           string $eq after, before, between
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listProductsUpdated($date, $eq, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $eq_opts = array('after', 'before', 'between', 'on');
        if (!$date instanceof \DateTime && !is_array($date)) {
            return $this->createException('InvalidParameter', 'DateTime object or Array', 'err.invalid.parameter.date');
        }
        if (!in_array($eq, $eq_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.date_added';

        if ($eq == 'after' || $eq == 'before' || $eq == 'on') {
            switch ($eq) {
                case 'after':
                    $eq = '>';
                    break;
                case 'before':
                    $eq = '<';
                    break;
                case 'on':
                    $eq = '=';
                    break;
            }
            $condition = array('column' => $column, 'comparison' => $eq, 'value' => $date);
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => $condition,
                    )
                )
            );
        } else {
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '>', 'value' => $date[0]),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '<', 'value' => $date[1]),
                    )
                )
            );
        }
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsUpdatedAfter ()
     *                List products that are updated after the given date.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsUpdated()
     *
     * @param           array $date The date to be checked.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsUpdatedAfter($date, $sortOrder = null, $limit = null)
    {
        return $this->listProductsUpdated($date, 'after', $sortOrder, $limit);
    }

    /**
     * @name            listProductsUpdatedBefore ()
     *                List products that are updated before the given date.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsUpdated()
     *
     * @param           array $date The date to be checked.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsUpdatedBefore($date, $sortOrder = null, $limit = null)
    {
        return $this->listProductsUpdated($date, 'before', $sortOrder, $limit);
    }

    /**
     * @name            listProductsUpdatedBetween ()
     *                List products that are updated between two dates.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsUpdated()
     *
     * @param           array $dates The earlier and the later dates.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsUpdatedBetween($dates, $sortOrder = null, $limit = null)
    {
        return $this->listProductsUpdated($dates, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductsUpdatedOn ()
     *                List products that are updated on a given date.
     *
     * @since            1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsUpdated()
     *
     * @param           array $date The date.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsUpdatedOn($date, $sortOrder = null, $limit = null)
    {
        return $this->listProductsUpdated($date, 'on', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithPrice ()
     *                List products that costs more, less, or in between of the given price.
     *
     * @since            1.0.4
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           decimal $price Price..
     * @param           string $eq after, before, between
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listProductsWithPrice($price, $eq, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $eq_opts = array('more', 'less', 'between');
        if (!is_numeric($price)) {
            return $this->createException('InvalidParameter', 'numeric value', 'err.invalid.parameter.price');
        }
        if (!in_array($eq, $eq_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.price';

        if ($eq == 'more' || $eq == 'less') {
            switch ($eq) {
                case 'more':
                    $eq = '>';
                    break;
                case 'less':
                    $eq = '<';
                    break;
            }
            $condition = array('column' => $column, 'comparison' => $eq, 'value' => $price);
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => $condition,
                    )
                )
            );
        } else {
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '>', 'value' => $price[0]),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '<', 'value' => $price[1]),
                    )
                )
            );
        }
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsWithQuantities ()
     *                List products that have more, less, or in between amount of given quantity.
     *
     * @since            1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           integer $quantity Quantity..
     * @param           string $eq after, before, between
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listProductsWithQuantities($quantity, $eq, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $eq_opts = array('more', 'less', 'between');
        if (!is_numeric($quantity)) {
            return $this->createException('InvalidParameter', 'integer value', 'err.invalid.parameter.quantity');
        }
        if (!in_array($eq, $eq_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.quantity';

        if ($eq == 'more' || $eq == 'less') {
            switch ($eq) {
                case 'more':
                    $eq = '>';
                    break;
                case 'less':
                    $eq = '<';
                    break;
            }
            $condition = array('column' => $column, 'comparison' => $eq, 'value' => $quantity);
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => $condition,
                    )
                )
            );
        } else {
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '>', 'value' => $quantity[0]),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '<', 'value' => $quantity[1]),
                    )
                )
            );
        }
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsWithQuantitiesBetween ()
     *                List products that have quantities between two numbers.
     *
     * @since            1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @uses            $this->listProductsWithQuantities()
     *
     * @param           array $quantities Bottom and upper limit.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsWithQuantitiesBetween($quantities, $sortOrder = null, $limit = null)
    {
        return $this->listProductsWithQuantities($quantities, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithQuantitiesLessThan ()
     *                List products that have less quantity than searched.
     *
     * @since            1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @uses            $this->listProductsQuantities()
     *
     * @param           integer $quantity Stock quantity.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsWithQuantitiesLessThan($quantity, $sortOrder = null, $limit = null)
    {
        return $this->listProductsWithQuantities($quantity, 'less', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithQuantitiesMoreThan ()
     *                List products that have more quantity than searched.
     *
     * @since            1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @uses            $this->listProductsQuantities()
     *
     * @param           integer $quantity Stock quantity
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsWithQuantitiesMoreThan($quantity, $sortOrder = null, $limit = null)
    {
        return $this->listProductsWithQuantities($quantity, 'more', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithPriceLessThan ()
     *                List products that are priced less than given amount.
     *
     * @since            1.0.4
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @uses            $this->listProductsPriced()
     *
     * @param           decimal $amount Amount to compare
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsWithPriceLessThan($amount, $sortOrder = null, $limit = null)
    {
        return $this->listProductsPriced($amount, 'less', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithPriceMoreThan ()
     *                List products that are priced more than given amount.
     *
     * @since            1.0.4
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @uses            $this->listProductsPriced()
     *
     * @param           decimal $amount Amount to compare
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsWithPriceMoreThan($amount, $sortOrder = null, $limit = null)
    {
        return $this->listProductsPriced($amount, 'more', $sortOrder, $limit);
    }

    /**
     * @name            listProductsViewed ()
     *                List products that are viewed less, more than or in between.
     *
     * @since            1.0.6
     * @version         1.0.6
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed $views Number of views. Or upper and bottom limits.
     * @param           string $eq less, more, between
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listProductsViewed($views, $eq, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        $eq_opts = array('less', 'more', 'between');
        if (!is_integer($views) && !is_array($views)) {
            return $this->createException('InvalidParameter', 'integer or Array', 'err.invalid.parameter.views');
        }
        if (!in_array($eq, $eq_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['product']['alias'] . '.count_view';

        if ($eq == 'less' || $eq == 'more') {
            switch ($eq) {
                case 'more':
                    $eq = '>';
                    break;
                case 'less':
                    $eq = '<';
                    break;
            }
            $condition = array('column' => $column, 'comparison' => $eq, 'value' => $views);
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => $condition,
                    )
                )
            );
        } else {
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '>', 'value' => $views[0]),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '<', 'value' => $views[1]),
                    )
                )
            );
        }
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listProductsViewedBetween ()
     *                List products that are viewed between two numbers.
     *
     * @since            1.0.6
     * @version         1.0.6
     * @author          Can Berkol
     *
     * @uses            $this->listProductsViewed()
     *
     * @param           array $views Bottom and upper limit.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsViewedBetween($views, $sortOrder = null, $limit = null)
    {
        return $this->listProductsViewed($views, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductsViewedLessThan ()
     *                List products that are viewed less than given amount.
     *
     * @since            1.0.6
     * @version         1.0.6
     * @author          Can Berkol
     *
     * @uses            $this->listProductsViewed()
     *
     * @param           integer $viewes Number of views.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsViewedLessThan($viewes, $sortOrder = null, $limit = null)
    {
        return $this->listProductsViewed($viewes, 'less', $sortOrder, $limit);
    }

    /**
     * @name            listProductsViewedMoreThan ()
     *                List products that are viewed more than given amount.
     *
     * @since            1.0.6
     * @version         1.0.6
     * @author          Can Berkol
     *
     * @uses            $this->listProductsLiked()
     *
     * @param           integer $views Number of views.
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listProductsViewedMoreThan($views, $sortOrder = null, $limit = null)
    {
        return $this->listProductsViewed($views, 'more', $sortOrder, $limit);
    }

    /**
     * @name            listRelatedProductsOfProduct ()
     *                  List products that have zero or less quantity.
     *
     * @since           1.3.4
     * @version         1.3.4
     * @author          Can Berkol
     *
     * @use             $this->listProducts()
     * @use             $this->createException
     *
     * @param           mixed $product (id, sku, or object)
     * @param           array $sortOrder
     * @param           array $limit
     *
     * @return          array           $response
     */
    public function listRelatedProductsOfProduct($product, $sortOrder = null, $limit = null)
    {
        $this->resetResponse();
        if (is_object($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter');
        }
        if (is_numeric($product) || is_string($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                $response = $this->getProduct($product, 'sku');
            }
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product with id / sku ' . $product, 'err.entry.notexist');
            }
            $product = $response['result']['set'];
        }

        $qStr = 'SELECT ' . $this->entity['related_product']['alias'] . ' FROM ' . $this->entity['related_product']['name'] . ' ' . $this->entity['related_product']['alias']
            . ' WHERE ' . $this->entity['related_product']['alias'] . '.product = ' . $product->getId();

        $query = $this->em->createQuery($qStr);

        if (is_null($sortOrder) && !is_null($limit)) {
            $query->setFirstResult($limit['start']);
            $query->setMaxResults($limit['count']);
        }
        $result = $query->getResult();
        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => 0,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $relatedProducts = array();
        $relatedProductIds = array();
        foreach ($result as $rpObject) {
            $relatedProduct = $rpObject->getRelatedProduct();
            $relatedProducts[] = $relatedProduct;
            $relatedProductIds[] = $relatedProduct->getId();
        }

        if (is_null($sortOrder)) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $relatedProducts,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }

        $column = $this->entity['product']['alias'] . '.id';
        $condition = array('column' => $column, 'comparison' => 'in', 'value' => $relatedProductIds);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        $response = $this->listProducts($filter, $sortOrder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            listValuesOfProductAttributes ()
     *                  Returns a product's requested attribute's values.
     *
     * @since           1.2.4
     * @version         1.2.4
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           mixed $attribute url_key, id, entity
     * @param           mixed $product id, sku
     * @param           mixed $language iso code, entity, id
     *
     * @return          mixed           $response
     */
    public function listValuesOfProductAttributes($product, $attribute, $language)
    {
        $this->resetResponse();
        if (!is_string($attribute) && !is_integer($attribute) && !is_object($attribute)) {
            return $this->createException('InvalidParameterValue', 'string, integer or object', 'err.invalid.parameter.attribute');
        }
        if (!is_string($product) && !is_integer($product) && !is_object($product)) {
            return $this->createException('InvalidParameterValue', 'string, integer or object', 'err.invalid.parameter.product');
        }
        if (!is_string($language) && !is_integer($language) && !is_object($language)) {
            return $this->createException('InvalidParameterValue', 'string, integer or object', 'err.invalid.parameter.language');
        }
        if (is_object($attribute)) {
            if (!$attribute instanceof BundleEntity\ProductAttribute) {
                return $this->createException('InvalidParameter', 'ProductAttribute', 'err.invalid.parameter.product_attribute');
            }
        }
        if (is_string($attribute)) {
            $response = $this->getProductAttribute($attribute, 'url_key');
            if (!$response['error']) {
                $attribute = $response['result']['set'];
            }
            unset($response);
        } else if (is_integer($attribute)) {
            $response = $this->getProductAttribute($attribute, 'id');
            if (!$response['error']) {
                $attribute = $response['result']['set'];
            }
            unset($response);
        }
        if (is_object($product)) {
            if (!$product instanceof BundleEntity\Product) {
                return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product');
            }
        }
        if (is_string($product)) {
            $response = $this->getProduct($product, 'sku');
            if (!$response['error']) {
                $product = $response['result']['set'];
            }
            unset($response);
        } else if (is_integer($product)) {
            $response = $this->getProduct($product, 'id');
            if (!$response['error']) {
                $product = $response['result']['set'];
            }

            unset($response);
        }
        if (is_object($language)) {
            if (!$language instanceof MLSEntity\Language) {
                return $this->createException('InvalidParameter', 'Language', 'err.invalid.parameter.language');
            }
        }
        if (is_string($language)) {
            $MLSModel = new MLSService\MultiLanguageSupportModel($this->kernel, $this->db_connection, $this->orm);
            $response = $MLSModel->getLanguage($language, 'iso_code');
            if (!$response['error']) {
                $language = $response['result']['set'];
            }
            unset($response);
        } else if (is_integer($language)) {
            $MLSModel = new MLSService\MultiLanguageSupportModel($this->kernel, $this->db_connection, $this->orm);
            $response = $MLSModel->getLanguage($language, 'id');
            if (!$response['error']) {
                $language = $response['result']['set'];
            }
            unset($response);
        }
        $q_str = 'SELECT DISTINCT ' . $this->entity['product_attribute_values']['alias'] . ', ' . $this->entity['product_attribute']['alias']
            . ' FROM ' . $this->entity['product_attribute_values']['name'] . ' ' . $this->entity['product_attribute_values']['alias']
            . ' JOIN ' . $this->entity['product_attribute_values']['alias'] . '.attribute ' . $this->entity['product_attribute']['alias']
            . ' WHERE ' . $this->entity['product_attribute_values']['alias'] . '.product = ' . $product->getId()
            . ' AND ' . $this->entity['product_attribute_values']['alias'] . '.attribute = ' . $attribute->getId();

        $query = $this->em->createQuery($q_str);
        $query->setMaxResults(1);
        $query->setFirstResult(0);
        $result = $query->getResult();

        $totalRows = count($result);
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        if ($totalRows < 1) {
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.entry.notexist';
        }

        return $this->response;
    }

    /**
     * @name            markCategoriesAsFeatured ()
     *                  Mark given categories as featured.
     *
     * @since           1.3.1
     * @version         1..3.1
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $categories array of ids or entities.
     *
     * @return          mixed           $response
     */
    public function markCategoriesAsFeatured($categories)
    {
        $this->resetResponse();
        $catIds = array();
        foreach ($categories as $category) {
            if (!$category instanceof BundleEntity\ProductCategory && !is_numeric($category)) {
                return $this->createException('InvalidParameter', 'ProductCategory', 'err.invalid.parameter.categories');
            }
            if ($category instanceof BundleEntity\ProductCategory) {
                $catIds[] = $category->getId();
            } else {
                $catIds[] = $category;
            }
        }
        $catIds = implode(',', $catIds);
        if (count($catIds) < 1) {
            $this->response = array(
                'result' => array(
                    'set' => null,
                    'total_rows' => 0,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'msg.error.invalid.collection.id',
            );
        }
        $qStr = 'UPDATE ' . $this->entity['product_category']['name'] . ' ' . $this->entity['product_category']['alias']
            . ' SET ' . $this->entity['product_category']['alias'] . '.is_featured = \'y\''
            . ' WHERE ' . $this->entity['product_category']['alias'] . '.id IN(' . $catIds . ')';

        $query = $this->em->createQuery($qStr);
        $result = $query->getResult();

        $totalRows = count($categories);
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $categories,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.update',
        );

        return $this->response;
    }

    /**
     * @name            relateProductsWithProduct ()
     *                  Relates a list of products with a given product
     *
     * @since           1.3.4
     * @version         1.3.4
     * @author          Can Berkol
     *
     * @param           array $collection
     * @param           mixed $product (id, sku, or object)
     *
     * @return          array           $response
     */
    public function relateProductsWithProduct($collection, $product)
    {
        $this->resetResponse();
        if (is_object($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter');
        }
        if (!$product instanceof BundleEntity\Product) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                $response = $this->getProduct($product, 'sku');
            }
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product with id /sku ' . $product, 'err.invalid.parameter');
            }
            $product = $response['result']['set'];
        }
        $countRelated = 0;
        $relatedProducts = array();
        foreach ($collection as $item) {
            if (!$item instanceof BundleEntity\Product && !is_numeric($item) && !is_string($item)) {
                break;
            }
            $relatedProduct = new BundleEntity\RelatedProduct;
            $relatedProduct->setProduct($product);
            $relatedProduct->setRelatedProduct($item);
            $this->em->persist($relatedProduct);
            $relatedProducts[] = $relatedProduct;
            unset($relatedProduct, $response);
            $countRelated++;
        }

        if ($countRelated > 0) {
            $this->em->flush();
        }
        $this->response = array(
            'rowCount' => $relatedProducts,
            'result' => array(
                'set' => $relatedProducts,
                'total_rows' => $relatedProducts,
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }

    /**
     * @name            removeCategoriesFromProduct ()
     *                  Removes the association of files with products.
     *
     * @since           1.0.6
     * @version         1.5.1
     * @author          Can Berkol
     *
     * @use             $this->doesProductExist()
     * @use             $this->isFileAssociatedWithProduct()
     *
     * @throws          CoreExceptions\DuplicateAssociationException
     * @throws          CoreExceptions\EntityDoesNotExistException
     * @throws          CoreExceptions\InvalidParameterException
     *
     * @param           array $categories Collection consists one of the following: 'entity' or entity 'id'
     *                                                  The entity can be a ProductCategory entity or or a CategoriesOfProduct entity.
     * @param           mixed $product 'entity' or 'entity' id.
     *
     * @return          array           $response
     */
    public function removeCategoriesFromProduct($categories, $product)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        $count = 0;
        /** remove invalid file entries */
        foreach ($categories as $category) {
            if (!is_numeric($category) && !$category instanceof BundleEntity\ProductCategory && !$category instanceof BundleEntity\CategoriesOfProduct) {
                unset($category[$count]);
            }
            $count++;
        }
        if (count($categories) < 1) {
            $this->response = array(
                'result' => array(
                    'set' => null,
                    'total_rows' => null,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.collection.empty',
            );
        }
        if (!is_numeric($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', '$product', 'err.invalid.parameter.product');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        }
        $cop_count = 0;
        $to_remove = array();
        $count = 0;
        /** Start persisting entries */
        foreach ($categories as $category) {
            if (is_numeric($category)) {
                $response = $this->getProductCategory($category, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'ProductCategory', 'err.db.product_category.notexist');
                }
                $to_remove[] = $response['result']['set'];
            }
            if (is_object($category) && $category instanceof BundleEntity\CategoriesOfProduct) {
                $this->em->remove($category);
                $cop_count++;
            }
            else{
                $to_remove[] = $category->getId();
            }
            $count++;
        }
        /** flush all into database */
        if ($cop_count > 0) {
            $this->em->flush();
        }
        if (count($to_remove) > 0) {
            $ids = implode(',', $to_remove);
            $table = $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias'];
            $q_str = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.product = ' . $product->getId()
                . ' AND ' . $this->entity['categories_of_product']['alias'] . '.category IN(' . $ids . ')';

            $query = $this->em->createQuery($q_str);
            /**
             * 6. Run query
             */
            $query->getResult();
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $to_remove,
                'total_rows' => $count,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        unset($count, $to_remove);
        return $this->response;
    }

    /**
     * @name            removeFilesFromProduct ()
     *                  Removes the association of files with products.
     *
     * @since           1.0.6
     * @version         1.0.6
     * @author          Can Berkol
     *
     * @use             $this->doesProductExist()
     * @use             $this->isFileAssociatedWithProduct()
     * @use             $FMMService->doesFileExist()
     *
     * @throws          CoreExceptions\DuplicateAssociationException
     * @throws          CoreExceptions\EntityDoesNotExistException
     * @throws          CoreExceptions\InvalidParameterException
     *
     * @param           array $files Collection consists one of the following: 'entity' or entity 'id'
     *                                                  The entity can be a File entity or or a FilesOfProduct entity.
     * @param           mixed $product 'entity' or 'entity' id.
     *
     * @return          array           $response
     */
    public function removeFilesFromProduct($files, $product)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        $count = 0;
        /** remove invalid file entries */
        foreach ($files as $file) {
            if (!is_numeric($file) && !$file instanceof FileBundleEntity\File && !$file instanceof FileBundleEntity\FilesOfProduct) {
                unset($files[$count]);
            }
            $count++;
        }
        /** issue an error only if there is no valid file or files of product entries */
        if (count($files) < 1) {
            return $this->createException('InvalidParameter', '$files', 'err.invalid.parameter.files');
        }
        unset($count);
        if (!is_numeric($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', '$product', 'err.invalid.parameter.product');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        }
        $fmmodel = new FMMService\FileManagementModel($this->kernel, $this->db_connection, $this->orm);

        $fop_count = 0;
        $to_remove = array();
        $count = 0;
        /** Start persisting files */
        foreach ($files as $file) {
            /** If no entity is provided as file we need to check if it does exist */
            if (is_numeric($file)) {
                $response = $fmmodel->getFile($file, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'File', 'err.db.file.notexist');
                }
                $to_remove[] = $file;
            }
            if ($file instanceof BundleEntity\FilesOfProduct) {
                $this->em->remove($file);
                $fop_count++;
            } else {
                /** Check if association exists */
                if ($this->isFileAssociatedWithProduct($file, $product, true)) {
                    new CoreExceptions\DuplicateAssociationException($this->kernel, 'File => Product');
                    $this->response['code'] = 'err.db.entry.notexist';
                    /** If file association already exist move silently to next file */
                    break;
                }
                $to_remove[] = $file->getId();
            }
            $count++;
        }
        /** flush all into database */
        if ($fop_count > 0) {
            $this->em->flush();
        }
        if (count($to_remove) > 0) {
            $ids = implode(',', $to_remove);
            $table = $this->entity['files_of_product']['name'] . ' ' . $this->entity['files_of_product']['alias'];
            $q_str = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['files_of_product']['alias'] . 'product = ' . $product->getId
                . ' AND ' . $this->entity['files_of_product']['alias'] . 'file IN(' . $ids . ')';

            $query = $this->em->createQuery($q_str);
            /**
             * 6. Run query
             */
            $query->getResult();
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $to_remove,
                'total_rows' => $count,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        unset($count, $to_remove);
        return $this->response;
    }

    /**
     * @name            removeLocalesFromProduct ()
     *                  Removes the locales from products
     *
     * @since           1.2.3
     * @version         1.5.0
     * @author          Can Berkol
     *
     * @use             $this->doesProductExist()
     * @use             $this->isFileAssociatedWithProduct()
     *
     * @param           array $locales id, entity, iso_Code
     * @param           mixed $product id, entity, sku
     *
     * @return          array           $response
     */
    public function removeLocalesFromProduct($locales, $product)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        /** remove invalid file entries */
        foreach ($locales as $locale) {
            if (!is_numeric($locale) && !$locale instanceof MLSEntity\Language && !$locale instanceof BundleEntity\ActiveProductLocale) {
                unset($locale);
            }
        }
        /** issue an error only if there is no valid file or files of product entries */
        if (count($locales) < 1) {
            return $this->createException('InvalidParameter', '$locales', 'err.invalid.parameter.locales');
        }
        if (!is_numeric($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', '$product', 'err.invalid.parameter.product');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        } else if (is_string($product)) {
            $response = $this->getProduct($product, 'sku');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
            }
            $product = $response['result']['set'];
        }
        $aplCount = 0;
        $to_remove = array();
        $count = 0;
        /** Start persisting files */
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');

        foreach ($locales as $locale) {
            /** If no entity is provided as file we need to check if it does exist */
            if (is_numeric($locale)) {
                $response = $mlsModel->getLanguage($locale, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Language', 'err.db.language.notexist');
                }
                $entity = $response['result']['set'];
                $to_remove[] = $entity->getId();
            } else if (is_string($locale)) {
                $response = $mlsModel->getLanguage($locale, 'iso_code');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Language', 'err.db.language.notexist');
                }
                $entity = $response['result']['set'];
                $to_remove[] = $entity->getId();
            }
            else if ($locale instanceof BundleEntity\ActveProductCategoryLocale) {
                $this->em->remove($locale);
                $aplCount++;
            }
            $count++;
        }

        /** flush all into database */
        if ($aplCount > 0) {
            $this->em->flush();
        }
        if (count($to_remove) > 0) {
            $ids = implode(',', $to_remove);
            $table = $this->entity['active_product_locale']['name'] . ' ' . $this->entity['active_product_locale']['alias'];
            $q_str = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['active_product_locale']['alias'] . '.product = ' . $product->getId()
                . ' AND ' . $this->entity['active_product_locale']['alias'] . '.locale IN(' . $ids . ')';

            $query = $this->em->createQuery($q_str);
            $query->getResult();
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $to_remove,
                'total_rows' => $count,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        unset($count, $to_remove);
        return $this->response;
    }

    /**
     * @name            removeLocalesFromProductCategory ()
     *                  Removes the locales from products
     *
     * @since           1.2.3
     * @version         1.4.7
     * @author          Can Berkol
     *
     * @use             $this->doesProductExist()
     * @use             $this->isFileAssociatedWithProduct()
     *
     * @param           array $locales id, entity, iso_Code
     * @param           mixed $category id, entity
     *
     * @return          array       $response
     */
    public function removeLocalesFromProductCategory($locales, $category)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        /** remove invalid file entries */
        foreach ($locales as $locale) {
            if (!is_numeric($locale) && !$locale instanceof MLSEntity\Language && !$locale instanceof BundleEntity\ActiveProductCategoryLocale) {
                unset($locale);
            }
        }
        if (count($locales) < 1) {
            return $this->createException('InvalidParameter', '$locales', 'err.invalid.parameter.locales');
        }
        if (!is_numeric($category) && !$category instanceof BundleEntity\ProductCategory) {
            return $this->createException('InvalidParameter', '$product', 'err.invalid.parameter.product');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($category)) {
            $response = $this->getProductCategory($category, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'ProductCategory', 'err.db.product.notexist');
            }
            $category = $response['result']['set'];
        }
        $aplCount = 0;
        $to_remove = array();
        $count = 0;
        /** Start persisting files */
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        foreach ($locales as $locale) {
            /** If no entity is provided as file we need to check if it does exist */
            if (is_numeric($locale)) {
                $response = $mlsModel->getLanguage($locale, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Language', 'err.db.language.notexist');
                }
                $entity = $response['result']['set'];
                $to_remove[] = $entity->getId();
            } else if (is_string($locale)) {
                $response = $mlsModel->getLanguage($locale, 'iso_code');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Language', 'err.db.language.notexist');
                }
                $entity = $response['result']['set'];
                $to_remove[] = $entity->getId();
            }
            else if ($locale instanceof BundleEntity\ActveProductCategoryLocale) {
                $this->em->remove($locale);
                $aplCount++;
            }
            $count++;
        }
        /** flush all into database */
        if ($aplCount > 0) {
            $this->em->flush();
        }
        if (count($to_remove) > 0) {
            $ids = implode(',', $to_remove);
            $table = $this->entity['active_product_category_locale']['name'] . ' ' . $this->entity['active_product_category_locale']['alias'];
            $q_str = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['active_product_category_locale']['alias'] . '.product_category = ' . $category->getId()
                . ' AND ' . $this->entity['active_product_category_locale']['alias'] . '.locale IN(' . $ids . ')';

            $query = $this->em->createQuery($q_str);
            $query->getResult();
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $to_remove,
                'total_rows' => $count,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        unset($count, $to_remove);
        return $this->response;
    }

    /**
     * @name            removeProductsFromCategory ()
     *                   Removes the products from category.
     *
     * @since           1.2.0
     * @version         1.2.8
     * @author          Can Berkol
     *
     *
     * @use             $this->createException()
     *
     * @param           array $categories Collection consists one of the following: 'entity' or entity 'id'
     *                                                  The entity can be a ProductCategory entity or or a CategoriesOfProduct entity.
     * @param           mixed $product 'entity' or 'entity' id.
     *
     * @param           string $by null
     * @return          array           $response
     */
    public function removeProductsFromCategory($products, $category, $by = null)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        $count = 0;
        /** remove invalid file entries */
        foreach ($products as $product) {
            if (!is_numeric($product) && !is_String($product) && !$product instanceof BundleEntity\Product && !$product instanceof BundleEntity\CategoriesOfProduct) {
                unset($product[$count]);
            }
            $count++;
        }
        /** issue an error only if there is no valid file or files of product entries */
        if (count($products) < 1) {
            return $this->createException('InvalidParameter', '$products', 'err.invalid.parameter.products');
        }
        unset($count);
        if (!is_numeric($category) && !$category instanceof BundleEntity\ProductCategory) {
            return $this->createException('InvalidParameter', '$category', 'err.invalid.parameter.category');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($category)) {
            $response = $this->getProductCategory($category, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'ProductCategory', 'err.db.product.notexist');
            }
            $category = $response['result']['set'];
        }
        $cop_count = 0;
        $to_remove = array();
        $count = 0;
        /** Start persisting files */
        foreach ($products as $product) {
            if (is_null($by)) {
                switch ($product) {
                    case is_numeric($product):
                        $by = 'id';
                        break;
                    case is_string($product):
                        $by = 'sku';
                        break;
                    case is_object($product):
                        $by = 'entity';
                        break;
                }
            }
            switch ($by) {
                case 'id':
                    $response = $this->getProduct($product, 'id');
                    if ($response['error']) {
                        return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
                    }
                    $to_remove[] = $response['result']['set']->getId();
                    break;
                case 'sku':
                    $response = $this->getProduct($product, 'sku');
                    if ($response['error']) {
                        return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
                    }
                    $to_remove[] = $response['result']['set']->getId();
                    break;
                case 'entity':
                    if ($product instanceof BundleEntity\CategoriesOfProduct) {
                        $this->em->remove($product);
                        $cop_count++;
                    }
                    break;
            }
            $count++;
        }
        /** flush all into database */
        if ($cop_count > 0) {
            $this->em->flush();
        }

        if (count($to_remove) > 0) {
            $ids = implode(',', $to_remove);
            $table = $this->entity['categories_of_product']['name'] . ' ' . $this->entity['categories_of_product']['alias'];
            $q_str = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['categories_of_product']['alias'] . '.category = ' . $category->getId()
                . ' AND ' . $this->entity['categories_of_product']['alias'] . '.product IN(' . $ids . ')';

            $query = $this->em->createQuery($q_str);
            /**
             * 6. Run query
             */
            $query->getResult();
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $to_remove,
                'total_rows' => $count,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        unset($count, $to_remove);
        return $this->response;
    }

    /**
     * @name            removeProductCategoriesFromLocale ()
     *                  Removes the locales from products
     *
     * @since           1.2.3
     * @version         1.2.3
     * @author          Can Berkol
     *
     * @use             $this->doesProductExist()
     *
     * @param           array $categories id, entity
     * @param           mixed $locale id, entity, iso_code
     *
     * @return          array           $response
     */
    public function removeProductCategoriesFromLocale($categories, $locale)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        /** remove invalid file entries */
        foreach ($categories as $category) {
            if (!is_numeric($category) && !$category instanceof BundleEntity\ProductCategory && !$locale instanceof BundleEntity\ActiveProductCategoryLocale) {
                unset($category);
            }
        }
        /** issue an error only if there is no valid file or files of product entries */
        if (count($categories) < 1) {
            return $this->createException('InvalidParameter', '$products', 'err.invalid.parameter.products');
        }
        if (!is_numeric($locale) && !$locale instanceof MLSEntity\Locale) {
            return $this->createException('InvalidParameter', '$locale', 'err.invalid.parameter.locale');
        }
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($locale)) {
            $response = $mlsModel->getLanguage($locale, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Lovcale', 'err.db.locale.notexist');
            }
            $locale = $response['result']['set'];
        } else if (is_string($locale)) {
            $response = $mlsModel->getLanguage($locale, 'iso_code');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Locale', 'err.db.locale.notexist');
            }
            $locale = $response['result']['set'];
        }
        $aplCount = 0;
        $to_remove = array();
        $count = 0;
        /** Start persisting files */

        foreach ($categories as $category) {
            /** If no entity is provided as file we need to check if it does exist */
            if (is_numeric($category)) {
                $response = $this->getProductCategory($category, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
                }
                $to_remove[] = $category;
            } else if (is_string($category)) {
                $response = $this->getProductCategory($category, 'sku');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
                }
                $to_remove[] = $category;
            }
            if ($category instanceof BundleEntity\ActveProductCategoryLocale) {
                $this->em->remove($category);
                $aplCount++;
            } else {
                /** Check if association exists */
                if ($this->isLocaleAssociatedWithProductCategory($locale, $category, true)) {
                    $to_remove[] = $category->getId();
                }
            }
            $count++;
        }
        /** flush all into database */
        if ($aplCount > 0) {
            $this->em->flush();
        }
        if (count($to_remove) > 0) {
            $ids = implode(',', $to_remove);
            $table = $this->entity['active_product_category_locale']['name'] . ' ' . $this->entity['active_product_category_locale']['alias'];
            $q_str = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['active_product_category_locale']['alias'] . '.locale = ' . $locale->getId
                . ' AND ' . $this->entity['active_product_category_locale']['alias'] . '.category IN(' . $ids . ')';

            $query = $this->em->createQuery($q_str);
            $query->getResult();
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $to_remove,
                'total_rows' => $count,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        unset($count, $to_remove);
        return $this->response;
    }

    /**
     * @name            removeProductsFromLocales ()
     *                  Removes the locales from products
     *
     * @since           1.2.3
     * @version         1.2.3
     * @author          Can Berkol
     *
     * @use             $this->doesProductExist()
     *
     * @param           array $products id, entity, sku
     * @param           mixed $locale id, entity, iso_code
     *
     * @return          array           $response
     */
    public function removeProductsFromLocale($products, $locale)
    {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        /** remove invalid file entries */
        foreach ($products as $product) {
            if (!is_numeric($product) && !$product instanceof BundleEntity\Product && !$locale instanceof BundleEntity\ActiveProductLocale) {
                unset($product);
            }
        }
        /** issue an error only if there is no valid file or files of product entries */
        if (count($products) < 1) {
            return $this->createException('InvalidParameter', '$products', 'err.invalid.parameter.products');
        }
        if (!is_numeric($locale) && !$locale instanceof MLSEntity\Locale) {
            return $this->createException('InvalidParameter', '$product', 'err.invalid.parameter.product');
        }
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($locale)) {
            $response = $mlsModel->getLanguage($locale, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Locale', 'err.db.locale.notexist');
            }
            $locale = $response['result']['set'];
        } else if (is_string($locale)) {
            $response = $mlsModel->getLanguage($locale, 'iso_code');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Locale', 'err.db.locale.notexist');
            }
            $locale = $response['result']['set'];
        }
        $aplCount = 0;
        $to_remove = array();
        $count = 0;
        /** Start persisting files */

        foreach ($products as $product) {
            /** If no entity is provided as file we need to check if it does exist */
            if (is_numeric($product)) {
                $response = $this->getProduct($product, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
                }
                $to_remove[] = $product;
            } else if (is_string($product)) {
                $response = $this->getProduct($product, 'sku');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Product', 'err.db.product.notexist');
                }
                $to_remove[] = $product;
            }
            if ($product instanceof BundleEntity\ActveProductLocale) {
                $this->em->remove($product);
                $aplCount++;
            } else {
                /** Check if association exists */
                if ($this->isLocaleAssociatedWithProduct($locale, $product, true)) {
                    $to_remove[] = $product->getId();
                }
            }
            $count++;
        }
        /** flush all into database */
        if ($aplCount > 0) {
            $this->em->flush();
        }
        if (count($to_remove) > 0) {
            $ids = implode(',', $to_remove);
            $table = $this->entity['active_product_locale']['name'] . ' ' . $this->entity['active_product_locale']['alias'];
            $q_str = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['active_product_locale']['alias'] . '.locale = ' . $locale->getId
                . ' AND ' . $this->entity['active_product_locale']['alias'] . '.product IN(' . $ids . ')';

            $query = $this->em->createQuery($q_str);
            $query->getResult();
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $to_remove,
                'total_rows' => $count,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        unset($count, $to_remove);
        return $this->response;
    }

    /**
     * @name            unrelateProductsFromProduct ()
     *                  Relates a list of products from a given product
     *
     * @since           1.3.4
     * @version         1.3.4
     * @author          Can Berkol
     *
     * @param           array $collection
     * @param           mixed $product (id, sku, or object)
     *
     * @return          array           $response
     */
    public function unrelateProductsFromProduct($collection, $product)
    {
        $this->resetResponse();
        if (is_object($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', 'Product entity', 'err.invalid.parameter');
        }
        if (!$product instanceof BundleEntity\Product) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                $response = $this->getProduct($product, 'sku');
            }
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product with id /sku ' . $product, 'err.invalid.parameter');
            }
            $product = $response['result']['set'];
        }
        $countUnrelated = 0;
        $unrelatedProductIds = array();
        foreach ($collection as $item) {
            if (!$item instanceof BundleEntity\Product && !is_numeric($item) && !is_string($item)) {
                break;
            }
            $response = $this->getProduct($item, 'id');
            if ($response['error']) {
                $response = $this->getProduct($item, 'sku');
            }
            if ($response['error']) {
                break;
            }
            $item = $response['result']['set'];
            $unrelatedProductIds[] = $item->getId();

            $countUnrelated++;
        }
        $inContent = implode(',', $unrelatedProductIds);
        $qStr = 'DELETE FROM ' . $this->entity['related_product']['name'] . ' ' . $this->entity['related_product']['alias']
            . ' WHERE ' . $this->entity['related_product']['alias'] . '.product = ' . $product->getId()
            . ' AND ' . $this->entity['related_product']['alias'] . '.related_product IN(' . $inContent . ')';

        $query = $this->em->createQuery($qStr);

        $query->getResult();

        $this->response = array(
            'result' => array(
                'set' => null,
                'total_rows' => 0,
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'scc.db.done',
        );
        return $this->response;
    }

    /**
     * @name            updateCategoriesOfProductEntry ()
     *                  Updates entries
     *
     * @since           1.1.8
     * @version         1.1.8
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           miixed $entry
     *
     * @return          array       $response
     */
    public function updateCategoriesOfProductEntry($entry)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if ($entry instanceof BundleEntity\CategoriesOfProduct) {
            $copeRecord = $entry;
            $this->em->persist($copeRecord);
        } else if ($entry instanceof \stdClass) {
            $response = $this->getCategoriesOfProductEntry($entry->product, $entry->category);
            if (!$response['error']) {
                $copeRecord = $response['result']['set'];
                foreach ($entry as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'sort_order':
                        case 'date_added':
                            //case 'site':
                            $copeRecord->$set($value);
                            break;
                    }
                }
                $this->em->persist($copeRecord);
            }
        }
        $this->em->flush();

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $copeRecord,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.select.done',
        );

        return $this->response;
    }

    /**
     * @name            updateProduct ()
     *                  Updates single product. The data must be either a post data (array) or an entity
     *
     * @since            1.0.1
     * @version         1.1.4
     * @author          Can Berkol
     *
     * @use             $this->updateProducts()
     *
     * @param           mixed $data entity or post data
     *
     * @return          mixed           $response
     */
    public function updateProduct($data)
    {
        return $this->updateProducts(array($data));
    }

    /**
     * @name            updateProductAttribute ()
     *                Updates single product att ribute. The data must be either a post data (array) or an entity
     *
     * @since            1.0.6
     * @version         1.1.3
     * @author          Can Berkol
     *
     * @use             $this->updateProductAttributes()
     *
     * @param           mixed $data entity or post data
     *
     * @return          mixed           $response
     */
    public function updateProductAttribute($data)
    {
        return $this->updateProductAttributes(array($data));
    }

    /**
     * @name            updateProductAttributes ()
     *                Updates one or more product details in database.
     *
     * @since            1.0.6
     * @version         1.0.6
     * @author          Can Berkol
     *
     * @use             $this->doesProductAttributeExist()
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Product entities or array of entity details.
     *
     * @return          array           $response
     */
    public function updateProductAttributes($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countUpdates = 0;
        $countLocalizations = 0;
        $updatedItems = array();
        $localizations = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductAttribute) {
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            } else if (is_object($data)) {
                if (!property_exists($data, 'id') || !is_numeric($data->id)) {
                    return $this->createException('InvalidParameter', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (property_exists($data, 'date_added')) {
                    unset($data->date_added);
                }
                if (!property_exists($data, 'site')) {
                    $data->site = 1;
                }
                $response = $this->getProductAttribute($data->id, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'ProductAttribute with id ' . $data->id, 'err.invalid.entity');
                }
                $oldEntity = $response['result']['set'];
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'local':
                            foreach ($value as $langCode => $translation) {
                                $localization = $oldEntity->getLocalization($langCode, true);
                                $newLocalization = false;
                                if (!$localization) {
                                    $newLocalization = true;
                                    $localization = new BundleEntity\ProductAttributeLocalization();
                                    $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                                    $response = $mlsModel->getLanguage($langCode, 'iso_code');
                                    $localization->setLanguage($response['result']['set']);
                                    $localization->setAttribute($oldEntity);
                                }
                                foreach ($translation as $transCol => $transVal) {
                                    $transSet = 'set' . $this->translateColumnName($transCol);
                                    $localization->$transSet($transVal);
                                }
                                if ($newLocalization) {
                                    $this->em->persist($localization);
                                }
                                $localizations[] = $localization;
                            }
                            $oldEntity->setLocalizations($localizations);
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'id':
                            break;
                        default:
                            $oldEntity->$set($value);
                            break;
                    }
                    if ($oldEntity->isModified()) {
                        $this->em->persist($oldEntity);
                        $countUpdates++;
                        $updatedItems[] = $oldEntity;
                    }
                }
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countUpdates > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }

    /**
     * @name            updateProductAttributeValue ()
     *                Updates single product attribute value. The data must be either a post data (array) or an entity
     *
     * @since            1.1.6
     * @version         1.1.6
     * @author          Can Berkol
     *
     * @use             $this->updateProductAttributeValues()
     *
     * @param           mixed $data entity or post data
     *
     * @return          mixed           $response
     */
    public function updateProductAttributeValue($data)
    {
        return $this->updateProductAttributeValues(array($data));
    }

    /**
     * @name            updateProductAttributeValues ()
     *                Updates one or more product details in database.
     *
     * @since            1.1.6
     * @version         1.1.6
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Product entities or array of entity details.
     *
     * @return          array           $response
     */
    public function updateProductAttributeValues($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countUpdates = 0;
        $updatedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductAttributeValues) {
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            } else if (is_object($data)) {
                if (!property_exists($data, 'id') || !is_numeric($data->id)) {
                    return $this->createException('InvalidParameter', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (property_exists($data, 'date_added')) {
                    unset($data->date_added);
                }
                $response = $this->getProductAttributeValue($data->id, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'ProductAttribute with id ' . $data->id, 'err.invalid.entity');
                }
                $oldEntity = $response['result']['set'];

                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'attribute':
                            $pModel = $this->kernel->getContainer()->get('productManagement.model');
                            $response = $pModel->getProductAttribute($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $pModel);
                            break;
                        case 'language':
                            $lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                            $response = $lModel->getLanguage($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $lModel);
                            break;
                        case 'product':
                            $pModel = $this->kernel->getContainer()->get('productManagement.model');
                            $response = $pModel->getProduct($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $pModel);
                            break;
                        case 'id':
                            break;
                        default:
                            $oldEntity->$set($value);
                            break;
                    }
                    if ($oldEntity->isModified()) {
                        $this->em->persist($oldEntity);
                        $countUpdates++;
                        $updatedItems[] = $oldEntity;
                    }
                }
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countUpdates > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }

    /**
     * @name            updateProductCategory ()
     *                Updates single product category. The data must be either a post data (array) or an entity
     *
     * @since            1.0.6
     * @version         1.1.8
     * @author          Can Berkol
     *
     * @use             $this->updateProductCategories()
     *
     * @param           mixed $data entity or post data
     *
     * @return          mixed           $response
     */
    public function updateProductCategory($data)
    {
        return $this->updateProductCategories(array($data));
    }

    /**
     * @name            updateProductCategories ()
     *                  Updates one or more product category details in database.
     *
     * @since           1.0.6
     * @version         1.1.8
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Product entities or array of entity details.
     *
     * @return          array           $response
     */
    public function updateProductCategories($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countUpdates = 0;
        $updatedItems = array();
        $localizations = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductCategory) {
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            } else if (is_object($data)) {
                if (!property_exists($data, 'id') || !is_numeric($data->id)) {
                    return $this->createException('InvalidParameter', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (property_exists($data, 'date_added')) {
                    unset($data->date_added);
                }
                if (!property_exists($data, 'site')) {
                    $data->site = 1;
                }
                $response = $this->getProductCategory($data->id, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'ProductCategory with id ' . $data->id, 'err.invalid.entity');
                }
                $oldEntity = $response['result']['set'];
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'local':
                            foreach ($value as $langCode => $translation) {
                                $response = $this->getProductCategoryLocalization($oldEntity, $langCode);
                                $newLocalization = false;
                                if ($response['error']) {
                                    $newLocalization = true;
                                    $localization = new BundleEntity\ProductCategoryLocalization();
                                    $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                                    $response = $mlsModel->getLanguage($langCode, 'iso_code');
                                    $localization->setLanguage($response['result']['set']);
                                    $localization->setCategory($oldEntity);
                                } else {
                                    $localization = $response['result']['set'];
                                    unset($response);
                                }
                                foreach ($translation as $transCol => $transVal) {
                                    $transSet = 'set' . $this->translateColumnName($transCol);
                                    $localization->$transSet($transVal);
                                }
                                if ($newLocalization) {
                                    $this->em->persist($localization);
                                }
                                $localizations[] = $localization;
                            }
                            $oldEntity->setLocalizations($localizations);
                            break;
                        case 'parent':
                            if (!is_null($value)) {
                                $response = $this->getProductCategory($value, 'id');
                                if (!$response['error']) {
                                    $oldEntity->$set($response['result']['set']);
                                } else {
                                    new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                                }
                            } else {
                                $oldEntity->$set(null);
                            }
                            unset($response, $fModel);
                            break;
                        case 'preview_image':
                            if (!is_null($value)) {
                                $fModel = $this->kernel->getContainer()->get('filemanagement.model');
                                $response = $fModel->getFile($value, 'id');
                                if (!$response['error']) {
                                    $oldEntity->$set($response['result']['set']);
                                } else {
                                    new CoreExceptions\SiteDoesNotExistException($this->kernel, 'file' . $value);
                                }
                                unset($response, $fModel);
                            }

                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, 'site' . $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'id':
                            break;
                        default:
                            $oldEntity->$set($value);
                            break;
                    }
                    if ($oldEntity->isModified()) {
                        $this->em->persist($oldEntity);
                        $countUpdates++;
                        $updatedItems[] = $oldEntity;
                    }
                }
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countUpdates > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }

    /**
     * @name            updateProducts ()
     *                  Updates one or more product details in database.
     *
     * @since            1.0.1
     * @version         1.1.4
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Product entities or array of entity details.
     * @param           array $by entity, post
     *
     * @return          array           $response
     */
    public function updateProducts($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countUpdates = 0;
        $updatedItems = array();
        $localizations = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Product) {
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            } else if (is_object($data)) {
                if (!property_exists($data, 'id') || !is_numeric($data->id)) {
                    return $this->createException('InvalidParameter', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (property_exists($data, 'date_added')) {
                    unset($data->date_added);
                }
                if (!property_exists($data, 'site')) {
                    $data->site = 1;
                }
                $response = $this->getProduct($data->id, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Product with id ' . $data->id, 'err.invalid.entity');
                }
                $oldEntity = $response['result']['set'];
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'local':
                            foreach ($value as $langCode => $translation) {
                                $localization = $oldEntity->getLocalization($langCode, true);
                                $newLocalization = false;
                                if (!$localization) {
                                    $newLocalization = true;
                                    $localization = new BundleEntity\ProductLocalization();
                                    $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                                    $response = $mlsModel->getLanguage($langCode, 'iso_code');
                                    $localization->setLanguage($response['result']['set']);
                                    $localization->setProduct($oldEntity);
                                }
                                foreach ($translation as $transCol => $transVal) {
                                    $transSet = 'set' . $this->translateColumnName($transCol);
                                    $localization->$transSet($transVal);
                                }
                                if ($newLocalization) {
                                    $this->em->persist($localization);
                                }
                                $localizations[] = $localization;
                            }
                            $oldEntity->setLocalizations($localizations);
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'preview_file':
                            $fModel = $this->kernel->getContainer()->get('filemanagement.model');
                            $response = $fModel->getFile($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $fModel);
                            break;
                        case 'id':
                            break;
                        default:
                            $oldEntity->$set($value);
                            break;
                    }
                    if ($oldEntity->isModified()) {
                        $this->em->persist($oldEntity);
                        $countUpdates++;
                        $updatedItems[] = $oldEntity;
                    }
                }
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countUpdates > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }

    /**
     * @name            listFilesOfProduct ()
     *                  Lists all files of a given product.
     *
     * @since           1.2.6
     * @version         1.2.6
     * @author          Can Berkol
     *
     * @throws          $this->createException()
     *
     * @param           mixed $product
     *
     * @param           array $sortOrder Array
     *                                          'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listFilesOfProduct($product, $sortOrder = null, $limit = null, $queryStr = null)
    {
        $this->resetResponse();
        if (!$product instanceof BundleEntity\Product && !is_numeric($product) && !is_string($product)) {
            return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product');
        }

        if ($product instanceof BundleEntity\Product) {
            $product = $product->getId();
        } else if (!is_numeric($product)) {
            $response = $this->getProduct($product, 'sku');
            if (!$response['error']) {
                $product = $response['result']['set']->getId();
            } else {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.invalid.parameter.product');
            }
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['files_of_product']['alias'] . '.product', 'comparison' => '=', 'value' => $product),
                )
            )
        );

        $response = $this->listFilesOfProducts($filter, $sortOrder, $limit);
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection,
                'total_rows' => count($collection),
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            listFilesOfProducts ()
     *                List files of products from database based on a variety of conditions.
     *
     * @since            1.1.3
     * @version         1.4.1
     * @author          Said İmamoğlu
     *
     * @throws          $this->createException()
     *
     * @param           array $filter Multi-dimensional array
     *
     *                                  Example:
     *                                  $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                               array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.id', 'comparison' => 'in', 'value' => array(3,4,5,6)),
     *                                                                  )
     *                                                  )
     *                                              );
     *                                 $filter[] = array(
     *                                              'glue' => 'and',
     *                                              'condition' => array(
     *                                                              array(
     *                                                                      'glue' => 'or',
     *                                                                      'condition' => array('column' => 'p.status', 'comparison' => 'eq', 'value' => 'a'),
     *                                                              ),
     *                                                              array(
     *                                                                      'glue' => 'and',
     *                                                                      'condition' => array('column' => 'p.price', 'comparison' => '<', 'value' => 500),
     *                                                              ),
     *                                                             )
     *                                           );
     *
     *
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listFilesOfProducts($filter = null, $sortOrder = null, $limit = null, $queryStr = null)
    {
        $this->resetResponse();
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['files_of_product']['alias']
                . ' FROM ' . $this->entity['files_of_product']['name'] . ' ' . $this->entity['files_of_product']['alias']
                . ' JOIN ' . $this->entity['files_of_product']['alias'] . '.product ' . $this->entity['product']['alias']
                . ' JOIN ' . $this->entity['files_of_product']['alias'] . '.file ' . $this->entity['file']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'file':
                    case 'product':
                    case 'type':
                    case 'date_added':
                        $column = $this->entity['files_of_product']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }

        $queryStr .= $where_str . $group_str . $order_str;
        $query = $this->em->createQuery($queryStr);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }
        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();

        $products = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getFile()->getId();
            if (!isset($unique[$id])) {
                $products[] = $entry->getFile();
                $unique[$id] = $entry->getFile();
            }
        }
        unset($unique);
        $totalRows = count($products);
        if ($totalRows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $products,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            getProductCategory ()
     *                  Returns details of a product category.
     *
     * @since           1.0.3
     * @version         1.0.8
     * @author          Can Berkol
     *
     * @deprecated      Will be deleted in version 1.3.0, use getMostRecentFileOfProduct() instead.
     *
     * @use             $this->createException()
     * @use             $this->listProductCategories()
     *
     * @param           mixed $category id, url_key
     * @param           string $by entity, id, url_key
     *
     * @deprecated      Will be deleted in v1.2.0.
     *
     * @return          mixed           $response
     */
    public function getFileOfProduct($product, $by = 'id')
    {
        $this->resetResponse();
        if (!is_object($product) && !is_numeric($product) && !is_string($product)) {
            return $this->createException('InvalidParameter', 'Product', 'err.invalid.parameter.product_category');
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['files_of_product']['alias'] . '.product', 'comparison' => '=', 'value' => $product),
                )
            )
        );

        $response = $this->listFilesOfProducts($filter, null, array('start' => 0, 'count' => 1));
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }


    /**
     * @name        deleteBrand ()
     * Deletes an existing item from database.
     *
     * @since            1.2.8
     * @version         1.2.8
     * @author          Said İmamoğlu
     *
     * @use             $this->deleteBrands()
     *
     * @param           mixed $item Entity, id or url key of item
     * @param           string $by
     *
     * @return          mixed           $response
     */
    public function deleteBrand($item, $by = 'entity')
    {
        return $this->deleteBrands(array($item), $by);
    }

    /**
     * @name            deleteBrands ()
     * Deletes provided items from database.
     *
     * @since        1.2.8
     * @version         1.2.8
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Brand entities, ids, or codes or url keys
     *
     * @return          array           $response
     */
    public function deleteBrands($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterValue', 'Array', 'err.invalid.parameter.collection');
        }
        $countDeleted = 0;
        foreach ($collection as $entry) {
            if ($entry instanceof BundleEntity\Brand) {
                $this->em->remove($entry);
                $countDeleted++;
            } else {
                switch ($entry) {
                    case is_numeric($entry):
                        $response = $this->getBrand($entry, 'id');
                        break;
                    case is_string($entry):
                        $response = $this->getProductCategory($entry, 'url_key');
                        break;
                }
                if ($response['error']) {
                    $this->createException('EntryDoesNotExist', $entry, 'err.invalid.entry');
                }
                $entry = $response['result']['set'];
                $this->em->remove($entry);
                $countDeleted++;
            }
        }

        if ($countDeleted < 0) {
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.fail.delete';

            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.deleted',
        );
        return $this->response;
    }

    /**
     * @name            listBrands ()
     * Lists stock data from database with given params.
     *
     * @author          Said İmamoğlu
     * @version         1.2.8
     * @since           1.2.8
     *
     * @param           array $filter
     * @param           array $sortOrder
     * @param           array $limit
     * @param           string $queryStr
     *
     * @use             $this->createException()
     * @use             $this->prepareWhere()
     * @use             $this->addLimit()
     *
     * @return          array $this->response
     */
    public function listBrands($filter = null, $sortOrder = null, $limit = null, $queryStr = null)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }

        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['brand']['alias']
                . ' FROM ' . $this->entity['brand']['name'] . ' ' . $this->entity['brand']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                $order_str .= ' ' . $this->entity['brand']['alias'] . '.' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }
        $queryStr .= $where_str . $group_str . $order_str;

        $query = $this->em->createQuery($queryStr);

        $query = $this->addLimit($query, $limit);

        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();
        $stocks = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getId();
            if (!isset($unique[$id])) {
                $stocks[$id] = $entry;
                $unique[$id] = $entry->getId();
            }
        }

        $totalRows = count($stocks);

        if ($totalRows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $newCollection = array();
        foreach ($stocks as $stock) {
            $newCollection[] = $stock;
        }
        unset($stocks, $unique);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $newCollection,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name        getBrand ()
     * Returns details of a gallery.
     *
     * @since        1.2.8
     * @version         1.2.8
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->listBrands()
     *
     * @param           mixed $stock id, url_key
     * @param           string $by entity, id, url_key
     *
     * @return          mixed           $response
     */
    public function getBrand($stock, $by = 'id')
    {
        $this->resetResponse();
        $by_opts = array('id', 'sku', 'product');
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $by_opts), 'err.invalid.parameter.by');
        }
        if (!is_object($stock) && !is_numeric($stock) && !is_string($stock)) {
            return $this->createException('InvalidParameter', 'ProductCategory or numeric id', 'err.invalid.parameter.product_category');
        }
        if (is_object($stock)) {
            if (!$stock instanceof BundleEntity\Brand) {
                return $this->createException('InvalidParameter', 'ProductCategory', 'err.invalid.parameter.product_category');
            }
            /**
             * Prepare & Return Response
             */
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $stock,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        $column = '';
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['brand']['alias'] . '.' . $by, 'comparison' => '=', 'value' => $stock),
                )
            )
        );
        $response = $this->listBrands($filter, null, null, null, false);
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name        doesBrandExist ()
     * Checks if entry exists in database.
     *
     * @since           1.2.8
     * @version         1.2.8
     * @author          Said İmamoğlu
     *
     * @use             $this->getBrand()
     *
     * @param           mixed $item id, url_key
     * @param           string $by id, url_key
     *
     * @param           bool $bypass If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesBrandExist($item, $by = 'id', $bypass = false)
    {
        $this->resetResponse();
        $exist = false;

        $response = $this->getBrand($item, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = $response['result']['set'];
            $error = false;
        } else {
            $exist = false;
            $error = true;
        }

        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name        insertBrand ()
     * Inserts one or more item into database.
     *
     * @since        1.0.1
     * @version         1.0.3
     * @author          Said İmamoğlu
     *
     * @use             $this->insertFiles()
     *
     * @param           array $item Collection of entities or post data.
     *
     * @return          array           $response
     */

    public function insertBrand($item)
    {
        $this->resetResponse();
        return $this->insertBrands(array($item));
    }

    /**
     * @name            insertBrands ()
     * Inserts one or more items into database.
     *
     * @since           1.0.1
     * @version         1.0.3
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @throws          InvalidParameterException
     * @throws          InvalidMethodException
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */

    public function insertBrands($collection)
    {
        $countInserts = 0;
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Brand) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\Brand();
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'date_added':
                        case 'date_updated':
                            new $entity->$set(\DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone'))));
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        /**
         * Save data.
         */
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name            unmarkCategoriesAsFeatured ()
     *                  Mark given categories as featured.
     *
     * @since           1.3.1
     * @version         1..3.1
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $categories array of ids or entities.
     *
     * @return          mixed           $response
     */
    public function unmarkCategoriesAsFeatured($categories)
    {
        $this->resetResponse();
        $catIds = array();
        foreach ($categories as $category) {
            if (!$category instanceof BundleEntity\ProductCategory && !is_numeric($category)) {
                return $this->createException('InvalidParameter', 'ProductCategory', 'err.invalid.parameter.categories');
            }
            if ($category instanceof BundleEntity\ProductCategory) {
                $catIds[] = $category->getId();
            } else {
                $catIds[] = $category;
            }
        }
        $catIds = implode(',', $catIds);
        if (count($catIds) < 1) {
            $this->response = array(
                'result' => array(
                    'set' => null,
                    'total_rows' => 0,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'msg.error.invalid.collection.id',
            );
        }
        $qStr = 'UPDATE ' . $this->entity['product_category']['name'] . ' ' . $this->entity['product_category']['alias']
            . ' SET ' . $this->entity['product_category']['alias'] . '.is_featured = \'n\''
            . ' WHERE ' . $this->entity['product_category']['alias'] . '.id IN(' . $catIds . ')';

        $query = $this->em->createQuery($qStr);
        $result = $query->getResult();

        $totalRows = count($categories);
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $categories,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.update',
        );

        return $this->response;
    }

    /**
     * @name            updateBrand ()
     * Updates single item. The item must be either a post data (array) or an entity
     *
     * @since           1.2.8
     * @version         1.2.8
     * @author          Said İmamoğlu
     *
     * @use             $this->resetResponse()
     * @use             $this->updateBrands()
     *
     * @param           mixed $item Entity or Entity id of a folder
     *
     * @return          array   $response
     *
     */

    public function updateBrand($item)
    {
        $this->resetResponse();
        return $this->updateBrands(array($item));
    }

    /**
     * @name            updateBrands ()
     * Updates one or more item details in database.
     *
     * @since           1.2.8
     * @version         1.2.8
     * @author          Said İmamoğlu
     *
     * @use             $this->update_entities()
     * @use             $this->createException()
     * @use             $this->listBrands()
     *
     *
     * @throws          InvalidParameterException
     *
     * @param           array $collection Collection of item's entities or array of entity details.
     *
     * @return          array   $response
     *
     */

    public function updateBrands($collection)
    {
        $countInserts = 0;
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Brand) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $response = $this->getBrand($data->id, 'id');
                if ($response['error']) {
                    new CoreExceptions\EntityDoesNotExistException($this->kernel);
                }
                $oldEntity = $response['result']['set'];
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'date_added':
                        case 'date_updated':
                            new $oldEntity->$set(\DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone'))));
                            break;
                        case 'id':
                            break;
                        default:
                            $oldEntity->$set($value);
                            break;
                    }
                }
                $this->em->persist($oldEntity);
                $insertedItems[] = $oldEntity;
                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        /**
         * Save data.
         */
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => 1,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name    listProductsOfBrand ()
     * Lists products filtered given brand
     *
     * @author  Said İmamoğlu
     * @version 1.2.8
     * @since   1.2.8
     *
     * @use     $this->listProducts()
     *
     * @param   $brand
     * @return  array   Response
     */

    public function listProductsOfBrand($brand)
    {
        $filter = array();
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => '.brand', 'comparison' => '=', 'value' => $brand),
                )
            )
        );
        return $this->listProducts($filter);
    }

    /**
     * @name    listProductsOfSupplier ()
     * Lists products filtered given supplier
     *
     * @author  Said İmamoğlu
     * @version 1.2.8
     * @since   1.2.8
     *
     * @use     $this->listProducts()
     *
     * @param   integer $supplier
     * @return  array   Response
     */

    public function listProductsOfSupplier($supplier)
    {
        $filter = array();
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => '.brand', 'comparison' => '=', 'value' => $supplier),
                )
            )
        );
        return $this->listProducts($filter);
    }

    /**
     * @name            insertVolumePricing ()
     *                Inserts one volume pricing into database.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->insertProductAttributeValues()
     *
     * @param           mixed $volumePricing Entity or post
     *
     * @return          array           $response
     */
    public function insertVolumePricing($volumePricing)
    {
        return $this->insertVolumePricings(array($volumePricing));
    }

    /**
     * @name            insertVolumePricings ()
     *                  Inserts one or more volume pricings into database.
     *
     * @since           1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->insertProductAttributeLocalization()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertVolumePricings($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $insertedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\VolumePricing) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\VolumePricing;
                if (isset($data->id)) {
                    unset($data->id);
                }
                if (!property_exists($data, 'date_added')) {
                    $data->date_added = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (!property_exists($data, 'limit_direction')) {
                    $data->date_updated = 'xm';
                }
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'product':
                            $response = $this->getProduct($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExist($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;

                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name            updateVolumePricing ()
     *                Updates one or more volume pricings into database.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->updateProductAttributeValues()
     *
     * @param           mixed $volumePricing entity or post data
     *
     * @return          mixed           $response
     */
    public function updateVolumePricing($volumePricing)
    {
        return $this->updateVolumePricings(array($volumePricing));
    }

    /**
     * @name            updateVolumePricings ()
     *                Updates one or more volume pricings into database.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Product entities or array of entity details.
     *
     * @return          array           $response
     */
    public function updateVolumePricings($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countUpdates = 0;
        $updatedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\VolumePricing) {
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            } else if (is_object($data)) {
                if (!property_exists($data, 'id') || !is_numeric($data->id)) {
                    return $this->createException('InvalidParameter', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (property_exists($data, 'date_added')) {
                    unset($data->date_added);
                }
                $response = $this->getVolumePricing($data->id, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Volume Pricing with id ' . $data->id, 'err.invalid.entity');
                }
                $oldEntity = $response['result']['set'];
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'product':
                            $pModel = $this->kernel->getContainer()->get('productManagement.model');
                            $response = $pModel->getProduct($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $pModel);
                            break;
                        case 'id':
                            break;
                        default:
                            $oldEntity->$set($value);
                            break;
                    }
                    if ($oldEntity->isModified()) {
                        $this->em->persist($oldEntity);
                        $countUpdates++;
                        $updatedItems[] = $oldEntity;
                    }
                }
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countUpdates > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }

    /**
     * @name            deleteVolumePricing ()
     *                  Deletes an existing product attribute from database.
     *
     * @since           1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->deleteVolumePricings()
     *
     * @param           mixed $data a single value of 'entity', 'id', 'url_key'
     *
     * @return          mixed           $response
     */
    public function deleteVolumePricing($data)
    {
        return $this->deleteVolumePricings(array($data));
    }

    /**
     * @name            deleteProductAttributes ()
     *                  Deletes provided product attributes from database.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection consists one of the following: 'entity', 'id', 'sku', 'site', 'type', 'status'
     *
     * @return          array           $response
     */
    public function deleteVolumePricings($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterValue', 'Array', 'err.invalid.parameter.collection');
        }
        $countDeleted = 0;
        foreach ($collection as $entry) {
            if ($entry instanceof BundleEntity\VolumePricing) {
                $this->em->remove($entry);
                $countDeleted++;
            } else {
                switch ($entry) {
                    case is_numeric($entry):
                        $response = $this->getVolumePricing($entry, 'id');
                        break;
                    case is_string($entry):
                        $response = $this->getVolumePricing($entry, 'url_key');
                        break;
                }
                if ($response['error']) {
                    $this->createException('EntryDoesNotExist', $entry, 'err.invalid.entry');
                }
                $entry = $response['result']['set'];
                $this->em->remove($entry);
                $countDeleted++;
            }
        }
        if ($countDeleted < 0) {
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.fail.delete';

            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.deleted',
        );
        return $this->response;
    }

    /**
     * @name            listVolumePricings ()
     *                List volume prices for given filter.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $filter Multi-dimensional array
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listVolumePricings($filter = array(), $sortOrder = null, $limit = null, $queryStr = null)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['volume_pricing']['alias'] . ', ' . $this->entity['volume_pricing']['alias']
                . ' FROM ' . $this->entity['volume_pricing']['name'] . ' ' . $this->entity['volume_pricing']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                $column = $this->entity['volume_pricing']['alias'] . '.' . $column;
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }


        $queryStr .= $where_str . $group_str . $order_str;
        $query = $this->em->createQuery($queryStr);
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }
        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();
        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            getVolumePricing ()
     *                Returns a Volume Pricing
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           mixed $id integer
     *
     * @return          mixed           $response
     */
    public function getVolumePricing($id)
    {
        $this->resetResponse();
        if (!is_integer($id)) {
            return $this->createException('InvalidParameterValue', 'integer', 'err.invalid.parameter.id');
        }

        $result = $this->em->getRepository($this->entity['volume_pricing']['name'])
                           ->findOneBy(array('id' => $id));

        $error = true;
        $code = 'err.db.entry.notexist';
        $found = 0;
        if ($result instanceof BundleEntity\VolumePricing) {
            $error = false;
            $code = 'scc.db.entity.exist';
            $found = 1;
        }
        if ($error) {
            $result = null;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => $found,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => $code,
        );

        return $this->response;
    }

    /**
     * @name            listVolumePricings ()
     *                List pricings of product with given type.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param   mixed $product
     * @param   array $filter
     * @param   array $sortOrder
     * @param   array $limit
     * @param   string $queryStr
     *
     * @return          array           $response
     */
    public function listVolumePricingsOfProduct($product, $filter = array(), $sortOrder = null, $limit = null, $queryStr = null)
    {
        $product = $this->validateAndGetProduct($product);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->getEntityDefinition('volume_pricing', 'alias') . '.product', 'comparison' => '=', 'value' => $product->getId()),
                )
            )
        );
        return $this->listVolumePricings($filter, $sortOrder, $limit, $queryStr);
    }

    /**
     * @name            listPricingsOfProductWithType ()
     *                List pricings of product with given type.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param   int $product
     * @param   int $quantity
     * @param   array $filter
     * @param   array $sortOrder
     * @param   array $limit
     * @param   string $queryStr
     *
     * @return          array           $response
     */
    public function listVolumePricingsOfProductWithQuantityGreaterThan($product, $quantity, $filter = array(), $sortOrder = null, $limit = null, $queryStr = null)
    {
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->getEntityDefinition('volume_pricing', 'alias') . '.quantity_limit', 'comparison' => '>=', 'value' => $quantity),
                ),
            )
        );
        return $this->listVolumePricingsOfProduct($product, $filter, $sortOrder, $limit, $queryStr);
    }

    /**
     * @name            listPricingsOfProductWithType ()
     *                List pricings of product with given type.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param   int $product
     * @param   int $quantity
     * @param   array $filter
     * @param   array $sortOrder
     * @param   array $limit
     * @param   string $queryStr
     *
     * @return          array           $response
     */
    public function listVolumePricingsOfProductWithQuantityLowerThan($product, $quantity, $filter = array(), $sortOrder = null, $limit = null, $queryStr = null)
    {
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->getEntityDefinition('volume_pricing', 'alias') . '.quantity_limit', 'comparison' => '=<', 'value' => $quantity),
                ),
            )
        );
        return $this->listVolumePricingsOfProduct($product, $filter, $sortOrder, $limit, $queryStr);
    }

    /**
     * @name            getVolumePricingOfProductWithMaximumQuantity ()
     *                Gets volume pricing of product with maximum quantity limit.
     *
     * @since            1.4.4
     * @version         1.4.4
     * @author          Said İmamoğlu
     *
     * @use             $this->listVolumePricingsOfProduct()
     *
     * @param   mixed $product
     *
     * @return          array           $response
     */
    public function getVolumePricingOfProductWithMaximumQuantity($product)
    {
        $product = $this->validateAndGetProduct($product);
        $response = $this->listVolumePricingsOfProduct($product, array(), array('quantity_limit' => 'desc'), array('start' => 0, 'count' => 1));
        if ($response['error']) {
            return $response;
        }
        $response['result']['set'] = $response['result']['set'][0];
        return $response;
    }

    /**
     * @name            listVolumePricingsOfProductWithClosestQuantity ()
     *                Gets closest quantity limit of product with given quantity limit.
     *
     * @since            1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->listVolumePricingsOfProductWithQuantityLowerThan()
     *
     * @param   int $product
     * @param   int $quantity
     *
     * @return          array           $response
     */
    public function listVolumePricingsOfProductWithClosestQuantity($product, $quantity)
    {
        $response = $this->listVolumePricingsOfProductWithQuantityLowerThan($product, $quantity, array(), array('quantity_limit' => 'desc'), array('start' => 0, 'count' => 1));
        if ($response['error']) {
            return $response;
        }
        $response['result']['set'] = $response['result']['set'][0];
        return $response;
    }

    /**
     * @name            listProductCategoriesOfParentByLevel ()
     *                  Lists product categories of given parent category by given level
     *
     * @since           1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->listProductCategories()
     *
     * @param   int $category
     * @param   int $level
     * @param   array $filter
     * @param   array $sortOrder
     * @param   array $limit
     * @param   string $queryStr
     *
     * @return          array           $response
     */
    public function listProductCategoriesOfParentByLevel($category, $level, $filter = array(), $sortOrder = null, $limit = null, $queryStr = null)
    {
        $filter = array();
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->getEntityDefinition('product_category', 'alias') . '.parent', 'comparison' => '=', 'value' => $category),
                ),
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->getEntityDefinition('product_category', 'alias') . '.level', 'comparison' => '=', 'value' => $level),
                ),
            )
        );
        return $this->listProductCategories($filter, $sortOrder, $limit, $queryStr);
    }

    /**
     * @name            listProductCategoriesOfLevelByParent ()
     *                Lists product categories of given level by given parent category
     *
     * @since           1.3.2
     * @version         1.3.2
     * @author          Said İmamoğlu
     *
     * @use             $this->listProductCategories()
     *
     * @param   int $category
     * @param   int $level
     * @param   array $filter
     * @param   array $sortOrder
     * @param   array $limit
     * @param   string $queryStr
     *
     * @return          array           $response
     */
    public function listProductCategoriesOfLevelByParent($level, $category, $filter = array(), $sortOrder = null, $limit = null, $queryStr = null)
    {
        return $this->listProductCategoriesOfParentByLevel($category, $level, $filter, $sortOrder, $limit, $queryStr);
    }

    /**
     * @name            listFeaturedParentProductCategories ()
     *                Lists product categories of given level by given parent category
     *
     * @since           1.3.7
     * @version         1.3.7
     * @author          Said İmamoğlu
     *
     * @use             $this->listProductCategories()
     *
     * @param   array $filter
     * @param   array $sortOrder
     * @param   array $limit
     * @param   string $queryStr
     *
     * @return          array           $response
     */
    public function listFeaturedParentProductCategories($filter = array(), $sortOrder = null, $limit = null, $queryStr = null)
    {
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['product_category']['alias'] . '.is_featured', 'comparison' => '=', 'value' => 'y'),
                ),
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['product_category']['alias'] . '.parent', 'comparison' => 'isnull', 'value' => null),
                ),
            )
        );
        return $this->listProductCategories($filter, $sortOrder, $limit, $queryStr);
    }

    /**
     * @name            incrementCountViewOfProduct ()
     *                  Increments view count of product
     *
     * @since           1.3.8
     * @version         1.3.8
     * @author          Said İmamoğlu
     *
     * @use             $this->getProduct()
     * @use             $this->updateProduct()
     *
     * @param   mixed $product
     * @param   int $count
     * @return          array           $response
     */
    public function incrementCountViewOfProduct($product, $count)
    {
        if ((!is_int($product) && !$product instanceof BundleEntity\Product && !$product instanceof \stdClass) || (!is_int($count) && $count > 1)) {
            return $this->createException('InvalidParameters', 'Product or Count', 'err.invalid.parameter');
        }
        /** If product is a instance of Product , we dont need to get Product info */
        $checkCount = true;
        if ($product instanceof BundleEntity\Product) {
            $count = $product->getCountView() + $count;
            $checkCount = false;
            $product = $product->getId();
        }
        if ($product instanceof \stdClass) {
            $product = $product->id;
        }
        if ($checkCount) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Product', 'err.invalid.parameter');
            }
            $count = $response['result']['set']->getCountView() + $count;
            unset($response);
        }

        $productEntity = new \stdClass();
        $productEntity->id = $product;
        $productEntity->count_view = $count;
        return $this->updateProduct($productEntity);
    }

    /** ************************************************
     * PRIVATE METHODS
     * ************************************************* */

    /**
     * @name            validateAndGetLocale()
     *                  Validates $locale parameter and returns BiberLtd\Core\Bundles\MultiLanguageSupport\Entity\Language if found in database.
     *
     * @since           1.4.2
     * @version         1.4.2
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed           $locale
     *
     * @return          object          BiberLtd\Core\Bundles\ProductManagementBundle\Entity\Language
     */
    private function validateAndGetLocale($locale){
        if (!is_string($locale) && !is_numeric($locale) && !$locale instanceof MLSEntity\Language) {
            return $this->createException('InvalidLanguageException', 'You have provided "'.gettype($locale).'" value in $locale parameter.', 'msg.error.invalid.parameter.locale');
        }
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');

        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($locale)) {
            $response = $mlsModel->getLanguage($locale, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : language, id: '.$locale,  'msg.error.db.language.notfound');
            }
            $locale = $response['result']['set'];
        } else if (is_string($locale)) {
            $response = $mlsModel->getLanguage($locale, 'iso_code');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : language, iso_code: '.$locale,  'msg.error.db.language.notfound');
            }
            $locale = $response['result']['set'];
        }
        return $locale;
    }
    /**
     * @name            validateAndGetProduct()
     *                  Validates $product parameter and returns BiberLtd\Core\Bundles\ProductManagementBundle\Entity\Product if found in database.
     *
     * @since           1.4.0
     * @version         1.4.0
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getProduct()
     *
     * @param           mixed           $product
     *
     * @return          object          BiberLtd\Core\Bundles\ProductManagementBundle\Entity\Product
     */
    private function validateAndGetProduct($product){
        if (!is_numeric($product) && !is_string($product) && !$product instanceof BundleEntity\Product) {
            return $this->createException('InvalidParameter', '$product parameter must hold BiberLtd\\Core\\Bundles\\ProductManagementBundle\\Entity\\Product Entity, string representing url_key or sku, or integer representing database row id', 'msg.error.invalid.parameter.product');
        }
        if ($product instanceof BundleEntity\Product) {
            return $product;
        }
        if (is_numeric($product)) {
            $response = $this->getProduct($product, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table: product, id: ' . $product, 'msg.error.db.product.notfound');
            }
            $product = $response['result']['set'];
        } else if (is_string($product)) {
            $response = $this->getProduct($product, 'sku');
            if ($response['error']) {
                $response = $this->getProduct($product, 'url_key');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Table : product, id / sku / url_key: ' . $product, 'msg.error.db.product.notfound');
                }
            }
            $product = $response['result']['set'];
        }

        return $product;
    }
    /**
     * @name            validateAndGetProductAttribute()
     *                  Validates $attribute parameter and returns BiberLtd\Core\Bundles\ProductManagementBundle\Entity\ProductAttribute if found in database.
     *
     * @since           1.4.2
     * @version         1.4.2
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getProductAttribute()
     *
     * @param           mixed           $attribute
     *
     * @return          object          BiberLtd\Core\Bundles\ProductManagementBundle\Entity\ProductAttribute
     */
    private function validateAndGetProductAttribute($attribute){
        if (!is_numeric($attribute) && !$attribute instanceof BundleEntity\ProductAttribute) {
            return $this->createException('InvalidParameter', '$attribute parameter must hold BiberLtd\\Core\\Bundles\\ProductManagementBundle\\Entity\\ProductAttribute Entity or integer representing database row id', 'msg.error.invalid.parameter.product.attribute');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($attribute)) {
            $response = $this->getProductAttribute($attribute, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : product_attributey, id: ' . $attribute,  'msg.error.db.product.attribute.notfound');
            }
            $attribute = $response['result']['set'];
        }
        else if (is_string($attribute)) {
            $response = $this->getProductAttribute($attribute, 'url_key');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : product_attributey, url_key: ' . $attribute,  'msg.error.db.product.attribute.notfound');
            }
            $attribute = $response['result']['set'];
        }
        return $attribute;
    }
    /**
     * @name            validateAndGetProductCategory()
     *                  Validates $category parameter and returns BiberLtd\Core\Bundles\ProductManagementBundle\Entity\ProductCategory if found in database.
     *
     * @since           1.4.2
     * @version         1.4.2
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getProduct()
     *
     * @param           mixed           $category
     *
     * @return          object          BiberLtd\Core\Bundles\ProductManagementBundle\Entity\Product
     */
    private function validateAndGetProductCategory($category){
        if (!is_numeric($category) && !$category instanceof BundleEntity\ProductCategory) {
            return $this->createException('InvalidParameter', '$category parameter must hold BiberLtd\\Core\\Bundles\\ProductManagementBundle\\Entity\\ProductCategory Entity or integer representing database row id', 'msg.error.invalid.parameter.product.category');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($category)) {
            $response = $this->getProductCategory($category, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : product_category, id: ' . $category,  'msg.error.db.product.category.notfound');
            }
            $category = $response['result']['set'];
        }
        return $category;
    }

    /**
     * @name            listProductAttributeValuesOfProduct ()
     *                List product attribute values of product from database based on a variety of conditions.
     *
     * @since            1.4.4
     * @version         1.4.4
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $product
     * @param           array $filter Multi-dimensional array
     * @param           array $sortOrder Array
     * @param           array $limit
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listProductAttributeValuesOfProduct($product,$filter = null, $sortOrder = null, $limit = null, $queryStr = null){
        $product = $this->validateAndGetProduct($product);
        $filter = array();
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['product_attribute_values']['alias'] . '.product', 'comparison' => '=', 'value' => $product->getId()),
                )
            )
        );
        return $this->listProductAttributeValues($filter);

    }
}

/**
 * Change Log
 * **************************************
 * v1.5.1                      Can Berkol
 * 03.03.2015
 * **************************************
 * U removeCategoriesFromProduct()
 * *
 * **************************************
 * v1.5.0                      Can Berkol
 * 25.12.2014
 * **************************************
 * U removeLocalesFromProducts()
 *
 * **************************************
 * v1.4.9                      Can Berkol
 * 24.12.2014
 * **************************************
 * U addLocalesToProduct()
 * U insertProducts()
 * U listProducts()
 *
 * **************************************
 * v1.4.8                      Can Berkol
 * 23.12.2014
 * **************************************
 * U listActiveLocalesOfProduct()
 *
 * **************************************
 * v1.4.7                      Can Berkol
 * 10.12.2014
 * **************************************
 * U removeLocalesFromProductCategory()
 *
 * **************************************
 * v1.4.6                      Can Berkol
 * 22.09.2014
 * **************************************
 * A getCategoriesOfProductEntry()
 *
 * **************************************
 * v1.4.5                      Can Berkol
 * 05.07.2014
 * **************************************
 * D getCategoriesOfProductEntry() in favor for listCategoriesOfProduct()
 * U deleteProducts()
 * U doesProductAttributeExist() Now returns bool in result set.
 * U doesProductAttributeValueExist() Now uses getAttributeValueOfProduct and therefore parameters changed. Now returns a boolean value in result  set.
 * U getMaxSortOrderOfAttributeInProduct()
 *
 * **************************************
 * v1.4.4                   Said İmamoğlu
 * 04.07.2014
 * **************************************
 * A getVolumePricingOfProductWithMaximumQuantity()
 *
 * **************************************
 * v1.4.3                      Can Berkol
 * 30.06.2014
 * **************************************
 * U deleteProductAttributes()
 * U deleteProductCategories()
 *
 * **************************************
 * v1.4.2                      Can Berkol
 * 28.06.2014
 * **************************************
 * A deleteAllAttributeValuesOfProductByAttribute()
 * A validateAndGetLocale()
 * A validateAndGetProductAttribute()
 * A validateAndGetProductCategory()
 * R deleteAllAttributeValuesByAttribute()
 *
 * **************************************
 * v1.4.1                      Can Berkol
 * 24.06.2014
 * **************************************
 * U addFilesToProduct()
 * U listFilesOfProducts()
 * A getRandomCategoryOfProductHasParentByLevel()
 *
 * **************************************
 * v1.4.0                      Can Berkol
 * 17.06.2014
 * **************************************
 * A validateAndGetProduct()
 * U addAttributesToProduct()
 * U addFilesToProduct()
 *
 * **************************************
 * v1.3.9                   Said İmamoğlu
 * 13.06.2014
 * **************************************
 * A listProductsOfCategoryByFilter()
 *
 * **************************************
 * v1.3.8                   Said İmamoğlu
 * 04.06.2014
 * **************************************
 * A listProductsOfCategoryByF()
 *
 * **************************************
 * v1.3.7                   Said İmamoğlu
 * 04.06.2014
 * **************************************
 * A listFeaturedParentProductCategories()
 *
 * **************************************
 * v1.3.6                   Said İmamoğlu
 * 30.05.2014
 * **************************************
 * A getProductByUrlKey()
 * U getProduct()
 *
 * **************************************
 * v1.3.5                      Can Berkol
 * 22.05.2014
 * **************************************
 * U listCategoriesOfProduct()
 *
 * **************************************
 * v1.3.4                      Can Berkol
 * 22.05.2014
 * **************************************
 * A relateProducts()
 * A listRelatedProductsOfProducts()
 * A unrelateProducts()
 *
 * **************************************
 * v1.3.3                      Can Berkol
 * 20.05.2014
 * **************************************
 * U listProductsInLocales
 *
 * **************************************
 * v1.3.2                   Said İmamoğlu
 * 16.05.2014
 * **************************************
 * A insertVolumePricing()
 * A insertVolumePricings()
 * A updateVolumePricing()
 * A updateVolumePricings()
 * A deleteVolumePricing()
 * A deleteVolumePricings()
 * A listVolumePricings()
 * A getVolumePricing()
 * A listVolumePricingsOfProduct()
 * A listVolumePricingsOfProductWithQuantityGreaterThan()
 * A listVolumePricingsOfProductWithQuantityLowerThan()
 * A listVolumePricingsOfProductWithClosestQuantity()
 *
 * **************************************
 * v1.3.1                      Can Berkol
 * 06.05.2014
 * **************************************
 * A markCategoriesAsFeatured()
 * A unmarkCategoriesAsFeatured()
 *
 * **************************************
 * v1.3.0                      Can Berkol
 * 08.04.2014
 * **************************************
 * A deleteAllAttributeValuesByAttribute()
 *
 * **************************************
 * v1.2.9                      Can Berkol
 * 29.03.2014
 * **************************************
 * A listChildCategoriesOfProductCategoryWithPreviewImage()
 *
 * **************************************
 * v1.2.8                      Can Berkol
 * 28.03.2014
 * **************************************
 * U removeProductsFromCategory()
 * A deletBrand()
 * A deletBrands()
 * A listBrands()
 * A getBrands()
 * A doesBrandExist()
 * A insertBrand()
 * A insertBrands()
 * A updateBrand()
 * A updateBrands()
 * A listProductsOfBrand()
 * A listProductsOfSupplier()
 *
 * **************************************
 * v1.2.7                      Can Berkol
 * 20.03.2014
 * **************************************
 * U listProductCategories()
 * U listProducts()
 *
 * **************************************
 * v1.2.6                      Can Berkol
 * 18.03.2014
 * **************************************
 * A listFilesOfProduct()
 * A getMostRecentFileOfProduct()
 * R getFileOfProduct()
 *
 * **************************************
 * v1.2.5                   Said İmamoğlu
 * 14.03.2014
 * **************************************
 * A addProductToCategories()
 *
 * **************************************
 * v1.2.4                      Can Berkol
 * 10.03.2014
 * **************************************
 * A getParentOfProductCategory()
 * A listAttributesOfProductCategory()
 * B getAttributeValueOfProduct()
 *
 * **************************************
 * v1.2.3                      Can Berkol
 * 06.03.2014
 * **************************************
 * A addLocalesToProduct()
 * A addLocalesToProductCategory()
 * A addProductToLocales()
 * A addProductCategoryToLocales()
 * A isLocaleAssociatedWithProduct()
 * A isLocaleAssociatedWithProductCategory()
 * A listActiveLocalesOfProduct()
 * A listActiveLocalesOfProductCategory()
 * A listProductsInLocales()
 * A listProductCategoriesInLocales()
 * A removeLocaleFromProducts()
 * A removeLocaleFromProductCategories()
 * A removeProductFromLocales()
 * A removeProductCategoryFromLocales()
 *
 * **************************************
 * v1.2.2                   Said İmamoğlu
 * 05.03.2014
 * **************************************
 * A listProductAttributeValues()
 * A doesProductAttributeValueExist()
 *
 * **************************************
 * v1.2.1                      Can Berkol
 * 05.03.2014
 * **************************************
 * A listAllChildProductCategories()
 * A listProductsInCategory()
 * A listChildCategoriesOfProductCategory()
 * D listChildProductCategories()
 * **************************************
 *
 * v1.2.0                      Can Berkol
 * 18.02.2014
 * **************************************
 * A removeProductsFromCategory()
 *
 * **************************************
 * v1.1.9                      Can Berkol
 * 17.02.2014
 * **************************************
 * A listProductCategoriesOfLevel()
 * U getProductCategory()
 * U listProductsOfCategory()
 *
 * **************************************
 * v1.1.8                   Said İmamoğlu
 *                             Can Berkol
 * 14.02.2014
 * **************************************
 * A insertProductCategoryLocalizations()
 * A listParentOnlyProductCategoriesOfLevel()
 * U insertProductCategory()
 * U insertProductCategories()
 * U updateProductCategory()
 * U updateProductCategories()
 *
 * **************************************
 * v1.1.7                      Can Berkol
 * 13.02.2014
 * **************************************
 * A addAttributesToProduct()
 * A deleteProduct()
 * A insertProductAttributeValue()
 * A insertProductAttributeValues()
 * A insertProductLocalizations()
 * A isAttributeAssociatedWithProduct()
 * A getMaxSortOrderOfAttributeInProduct()
 * U deleteProducts()
 * U insertProduct()
 *
 * **************************************
 * v1.1.6                      Can Berkol
 * 07.02.2014
 * **************************************
 * A getAttributeOfProduct()
 * A updateProductAttributeValue()
 * A updateProductAttributeValues()
 * U getProductAttributeValue()
 *
 * **************************************
 * v1.1.4                      Can Berkol
 * 03.02.2014
 * **************************************
 * U listCategoriesOfProduct()
 *
 * **************************************
 * v1.1.3                      Can Berkol
 *                          Said İmamoğlu
 * 27.01.2014
 * **************************************
 * A listFilesOfProducts()
 * A insertProductAttributeLocalizations()
 * U deleteProductAttributes()
 * U insertProductAttributes()
 *
 * **************************************
 * v1.1.2                      Can Berkol
 * 19.01.2014
 * **************************************
 * A countProducts()
 *
 * **************************************
 * v1.1.1                   Said İmamoğlu
 * 15.01.2014
 * **************************************
 * A listAttributeValuesOfProduct()
 *
 * **************************************
 * v1.1.0                      Can Berkol
 * 29.11.2013
 * *************************************
 * B getProductAttribute()      function call fixed.
 * B listProductAttributeValues() Query fixed.
 * U listAttributesOfProduct  sorting and limiting implemented
 *
 * **************************************
 * v1.0.9                      Can Berkol
 * 29.11.2013
 * **************************************
 * A listProductsOfCategory()
 * B getProduct() return $this->resetResponse(); => return $this->response;
 *
 * **************************************
 * v1.0.8                      Can Berkol
 * 27.11.2013
 * **************************************
 * B getProduct() return $this->resetResponse(); => return $this->response;
 * B getProductAttribute()
 * B getProductCategory()
 *
 * **************************************
 * v1.0.7                      Can Berkol
 * 16.11.2013
 * **************************************
 * A getProductLocalization()
 * D getProductLocalizations()
 *
 * **************************************
 * v1.0.6                      Can Berkol
 * 13.11.2013
 * **************************************
 * A listProductsViewed()
 * A listProductsViewedBetween()
 * A listProductsViewedMoreThan()
 * A listProductsViewedLessThan()
 * A removeFilesFromProduct()
 * A removeCategoriesFromProduct
 * A updateProductAttribute()
 * A updateProductAttributes()
 * A updateProductCategories()
 * A updateProductCategory()
 *
 * **************************************
 * v1.0.5                      Can Berkol
 * 12.11.2013
 * **************************************
 * A deleteProductAttribute()
 * A deleteProductCategories()
 * A deleteProductCategory()
 * A doesProductAttributeExist()
 * A getProductAttribute()
 * A inserProductAttribute()
 * A insertProductAttributes()
 * A insertProductCategories()
 * A insertProductCategory()
 * A listAttributesOfProduct()
 * A listCategoriesOfProduct()
 * A listCustomizableProducts()
 * A listNotCustomizableProducts()
 * A listProductAttributes()
 * A listProductsWithQuantities()
 * A listProductsWithQuantitiesBetween()
 * A listProductsWithQuantitiesMoreThan()
 * A listProductsWithQuantitiesLessThan()
 *
 * **************************************
 * v1.0.4                      Can Berkol
 * 08.11.2013
 * **************************************
 * A deleteProductAttributes()
 * A listOutOfStockProducts()
 * A listProductsWithPrice()
 * A listProductsWithPriceBetween()
 * A listProductsWithPriceLessThan()
 * A listProductsWithPriceMoreThan()
 * U listProductsAdded()
 * U listProductsUpdated()
 *
 * **************************************
 * v1.0.3                      Can Berkol
 * 07.11.2013
 * **************************************
 * A addProductsToCategory()
 * A doesProductCategoryExist()
 * A getMaxSortOrderOfProductInCategory()
 * A getMaxSortOrderOfProductFile()
 * A getProductBySku()
 * A getProductCategory()
 * A listChildProductCategories()
 * A listParentOnlyProductCategories()
 * A listProductCategories()
 * A listProductsAdded()
 * A listProductsAddedAfter()
 * A listProductsAddedBefore()
 * A listProductsAddedBetween()
 * A listProductsAddedOn()
 * A listProductsOfSite()
 * A listProductsUpdated()
 * A listProductsUpdatedAfter()
 * A listProductsUpdatedBefore()
 * A listProductsUpdatedBetween()
 * A listProductsUpdatedOn()
 * M Functions now use createException() method.
 * A Function names are now camelCase.
 *
 * **************************************
 * v1.0.2                      Can Berkol
 * 05.11.2013
 * **************************************
 * A add_files_to_product()
 * A is_file_associated_with_product()
 *
 * **************************************
 * v1.0.1                      Can Berkol
 * 15.09.2013
 * **************************************
 * A getProduct_localizations()
 * A list_products_liked()
 * A list_products_between()
 * A list_products_liked_more_than()
 * U list_products_liked_less_than()
 *
 * **************************************
 * v1.0.0                      Can Berkol
 * 14.09.2013
 * **************************************
 * A delete_product()
 * A delete_products()
 * A does_product_exist()
 * A list_products()
 * A list_products_liked_less_than()
 * A update_product()
 * A update_products()
 */

