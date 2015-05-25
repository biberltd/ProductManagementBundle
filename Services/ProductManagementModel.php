<?php
/**
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
 * @version         1.5.4
 *
 * @date            25.05.2015
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Services;

/** Extends CoreModel */
use BiberLtd\Bundle\CoreBundle\CoreModel;
/** Entities to be used */
use BiberLtd\Bundle\CoreBundle\Responses\ModelResponse;
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
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.5.3
     *
     * @param           object 		$kernel
     * @param           string 		$dbConnection
     * @param           string 		$orm
     */
    public function __construct($kernel, $dbConnection = 'default', $orm = 'doctrine'){
        parent::__construct($kernel, $dbConnection, $orm);

        $this->entity = array(
            'apl' 		=> array('name' => 'ProductManagementBundle:ActiveProductLocale', 'alias' => 'apl'),
            'apcl' 		=> array('name' => 'ProductManagementBundle:ActiveProductCategoryLocale', 'alias' => 'apcl'),
            'aop' 		=> array('name' => 'ProductManagementBundle:AttributesOfProduct', 'alias' => 'aop'),
            'aopc' 		=> array('name' => 'ProductManagementBundle:AttributesOfProductCategory', 'alias' => 'aopc'),
            'b' 		=> array('name' => 'ProductManagementBundle:Brand', 'alias' => 'b'),
            'cop' 		=> array('name' => 'ProductManagementBundle:CategoriesOfProduct', 'alias' => 'cop'),
            'f' 		=> array('name' => 'FileManagementBundle:File', 'alias' => 'f'),
            'fop' 		=> array('name' => 'ProductManagementBundle:FilesOfProduct', 'alias' => 'fop'),
            'l' 		=> array('name' => 'MultiLanguageSupportBundle:Language', 'alias' => 'l'),
            'p' 		=> array('name' => 'ProductManagementBundle:Product', 'alias' => 'p'),
            'pa' 		=> array('name' => 'ProductManagementBundle:ProductAttribute', 'alias' => 'pa'),
            'pal' 		=> array('name' => 'ProductManagementBundle:ProductAttributeLocalization', 'alias' => 'pal'),
            'pav' 		=> array('name' => 'ProductManagementBundle:ProductAttributeValues', 'alias' => 'pav'),
            'pc' 		=> array('name' => 'ProductManagementBundle:ProductCategory', 'alias' => 'pc'),
			'pcl' 		=> array('name' => 'ProductManagementBundle:ProductCategoryLocalization', 'alias' => 'pcl'),
			'pl' 		=> array('name' => 'ProductManagementBundle:ProductLocalization', 'alias' => 'pl'),
			'pos' 		=> array('name' => 'ProductManagementBundle:ProductsOfSite', 'alias' => 'pos'),
            'rp' 		=> array('name' => 'ProductManagementBundle:RelatedProduct', 'alias' => 'rp'),
            'vp' 		=> array('name' => 'ProductManagementBundle:VolumePricing', 'alias' => 'vp'),
        );
    }

    /**
     * @name            __destruct ()
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
     *
     * @since           1.1.7
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getMaxSortOrderOfAttributeInProduct()
     * @use             $this->getProduct()
     * @use             $this->getProductAttribute()
     * @use             $this->isAttributeAssociatedWithProduct()
     *
     * @param           array           $collection  Contains an array with two keys: attribute, and sort_order
     * @param           mixed           $product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function addAttributesToProduct($collection, $product){
        $timeStamp = time();

		$validAttributes = array();
        foreach ($collection as $attr) {
			$response = $this->getProductAttribute($attr['attribute']);
			if(!$response->error->exist){
				$validAttributes[]['attr'] = $response->result->set;
				$validAttributes[]['sort_order'] = $attr['sort_order'];
			}
        }
		unset($collection);
        /** issue an error only if there is no valid file entries */
        if (count($validAttributes) < 1) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. $collection parameter must be an array collection', 'E:S:001');
        }
        unset($count);
        $response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

        $aopCcollection = array();
        $count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
        foreach ($validAttributes as $item) {
            /** If no entity is provided as product we need to check if it does exist */
            $aopCollection = array();
            /** Check if association exists */
            if (!$this->isAttributeAssociatedWithProduct($item['attr'], $product, true)) {
                $aop = new BundleEntity\AttributesOfProduct();
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
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($aopCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
    }
    /**
     * @name            addFilesToProduct ()
     *
     * @since           1.0.2
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->getMaxSortOrderOfProductFile()
     * @use             $this->isFileAssociatedWithProduct()
     * @use             $this->resetResponse()
     * @use             $this->getProduct()
     *
     * @param           array           $collection
     * @param           mixed           $product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function addFilesToProduct($collection, $product){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $fileModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);

        $fopCollection = array();
        $count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
        foreach ($collection as $file) {
			$response = $fileModel->getFile($file['file']);
			if($response->error->exist){
				return $response;
			}
			$file['file'] = $response->result->set;
            if (!$this->isFileAssociatedWithProduct($file['file'], $product, true)) {
				$fop = new BundleEntity\FilesOfProduct();
				$fop->setFile($file['file'])->setProduct($product)->setDateAdded($now);
				if (!is_null($file['sort_order'])) {
					$fop->setSortOrder($file['sort_order']);
				} else {
					$fop->setSortOrder($this->getMaxSortOrderOfProductFile($product, true) + 1);
				}
				$fop->setType($file['file']->getType());
				/** persist entry */
				$this->em->persist($fop);
				$fopCollection[] = $fop;
				$count++;
			}
        }

		if($count > 0){
			$this->em->flush();
			return new ModelResponse($fopCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
    }

    /**
     * @name            addLocalesToProduct()
     *
     * @since           1.2.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->isLocaleAssociatedWithProduct()
     * @use             $this->getProduct()
     *
     * @param           array       $locales
     * @param           mixed       $product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function addLocalesToProduct($locales, $product){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

		if (count($locales) < 1) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. $locales parameter must be an array collection', 'E:S:001');
		}

        $aplCollection = array();
        $count = 0;
        /** Start persisting locales */
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        foreach ($locales as $locale) {
            $response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				return $response;
			}
			$locale = $response->result->set;
            if (!$this->isLocaleAssociatedWithProduct($locale, $product, true)) {
				$apl = new BundleEntity\ActiveProductLocale();
				$apl->setLocale($locale)->setProduct($product);

				/** persist entry */
				$this->em->persist($apl);
				$aplCollection[] = $apl;
				$count++;
            }
        }
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($aplCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
    }
    /**
     * @name            addLocalesToProductCategory ()
     *
     * @since           1.2.3
     * @version         1.5.3
	 *
     * @author          Can Berkol
	 *
     * @use             $this->createException()
     * @use             $this->isLocaleAssociatedWithProduct()
     *
     * @param           array           $locales
     * @param           mixed           $category
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function addLocalesToProductCategory($locales, $category){
		$timeStamp = time();
		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;

		if (count($locales) < 1) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. $locales parameter must be an array collection', 'E:S:001');
		}

		$aplCollection = array();
		$count = 0;
		/** Start persisting locales */
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				return $response;
			}
			$locale = $response->result->set;
			if (!$this->isLocaleAssociatedWithProductCategory($locale, $category, true)) {
				$apl = new BundleEntity\ActiveProductCategoryLocale();
				$apl->setLocale($locale)->setCategory($category);

				/** persist entry */
				$this->em->persist($apl);
				$aplCollection[] = $apl;
				$count++;
			}
		}
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($aplCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
    }
    /**
     * @name            addProductsToCategory ()
     *
     * @since           1.0.2
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getMaxSortOrderOfProductInCategory()
     * @use             $this->isProductAssociatedWithCategory()
     * @use             $this->resetResponse()
     *
     * @param           array           $collection 		Contains an array with two keys: file, and sortorder
     * @param           mixed           $category
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function addProductsToCategory(array $collection, $category){
        $timeStamp = time();
		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
        $copCollection = array();
        $count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
        foreach ($collection as $product) {
            $response = $this->getProduct($product['product']);
			if($response->error->exist){
				return $response;
			}
			$productEntity = $response->result->set;
            /** Check if association exists */
            if ($this->isProductAssociatedWithCategory($productEntity, $category, true)) {
                break;
            }
            /** prepare object */
            $cop = new BundleEntity\CategoriesOfProduct();
            $cop->setProduct($productEntity)->setCategory($category)->setDateAdded($now);
            if (!is_null($product['sort_order'])) {
                $cop->setSortOrder($product['sort_order']);
            } else {
                $cop->setSortOrder($this->getMaxSortOrderOfProductInCategory($category, true) + 1);
            }
            /** persist entry */
            $this->em->persist($cop);
			$copCollection[] = $cop;
            $count++;
        }
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($copCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
    }
    /**
     * @name            addProductToCategories()
     *
     * @since           1.2.5
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->resetResponse()
     *
     * @param           mixed           $product
     * @param           array           $collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function addProductToCategories($product, $collection){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $productCollection = array();
        $count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
        foreach ($collection as $category) {
            $response = $this->getProductCategory($category);
			if($response->error->exist){
				break;
			}
            if ($this->isProductAssociatedWithCategory($product, $category, true)) {
                break;
            }
            /** prepare object */
            $cop = new BundleEntity\CategoriesOfProduct();
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
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($productCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
    }
    /**
     * @name            addProductCategoriesToLocale ()
     *
     * @since           1.2.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->isLocaleAssociatedWithProductCategory()
     *
     * @param           array       $categories
     * @param           mixed       $locale
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function addProductCategoriesToLocale($categories, $locale){
		$timeStamp = time();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if($response->error->exist){
			return $response;
		}
		$locale = $response->result->set;
        $aplCollection = array();
        $count = 0;
        /** Start persisting locales */
        foreach ($categories as $category) {
            $response = $this->getProductCategory($category);
            if($response->error->exist){
				break;
			}
			$category = $response->result->set;
            if ($this->isLocaleAssociatedWithProductCategory($locale, $category, true)) {
                break;
            }
            $apl = new BundleEntity\ActiveProductCategoryLocale();
            $apl->setLocale($locale)->setProductCategory($category);

            /** persist entry */
            $this->em->persist($apl);
            $aplCollection[] = $apl;
            $count++;
        }
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($aplCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
    }

    /**
     * @name            addProductsToLocale ()
     *
     * @since           1.2.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->resetResponse()
     *
     * @param           array           $products
     * @param           mixed           $locale
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function addProductsToLocale($products, $locale){
		$timeStamp = time();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if($response->error->exist){
			return $response;
		}
		$locale = $response->result->set;
		$aplCollection = array();
		$count = 0;
		/** Start persisting locales */
		foreach ($products as $product) {
			$response = $this->getProduct($product);
			if($response->error->exist){
				break;
			}
			$category = $response->result->set;
			if ($this->isLocaleAssociatedWithProduct($locale, $product, true)) {
				break;
			}
			$apl = new BundleEntity\ActiveProductLocale();
			$apl->setLocale($locale)->setProduct($category);

			/** persist entry */
			$this->em->persist($apl);
			$aplCollection[] = $apl;
			$count++;
		}
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($aplCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
    }
    /**
     * @name            countProducts ()
     *
     * @since           1.1.2
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed           $filter
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function countProducts($filter = null){
		$timeStamp = time();
        $wStr = '';
		$qStr = 'SELECT COUNT('.$this->entity['p']['alias'].')'
                .' FROM '.$this->entity['p']['name'].' '.$this->entity['p']['alias'];

        if ($filter != null) {
            $filterStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $filterStr;
        }

		$qStr .= $wStr;
        $q = $this->em->createQuery($qStr);
        $result = $q->getSingleScalarResult();
		$count = 0;
		if(!$result){
			$count = $result;
		}
		return new ModelResponse($count, 1, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
    }

    /**
     * @name            countProductsOfCategory ()
     *
     * @since           1.3.3
     * @version         1.5.3
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->resetResponse()
     *
     * @param           mixed           $category
     * @param           array           $filter
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function countProductsOfCategory($category, $filter = null){
		$timeStamp = time();

        $response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
        $wStr = '';

		$qStr = 'SELECT COUNT('.$this->entity['cop']['alias'].')'
			. ' FROM ' . $this->entity['cop']['name'].' '.$this->entity['cop']['alias'];

        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['cop']['alias'].'.category', 'comparison' => '=', 'value' => $category->getId()),
                )
            )
        );

        if ($filter != null) {
            $fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
        }
		$qStr .= $wStr;
        $q = $this->em->createQuery($qStr);

        $result = $q->getSingleScalarResult();

		$count = 0;
		if(!$result){
			$count = $result;
		}
		return new ModelResponse($count, 1, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
    }
	/**
	 * @name            deleteBrand()
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use				$this->deleteProductCategories()
	 *
	 * @param           mixed 			$brand
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteBrand($brand){
		return $this->deleteBrands(array($brand));
	}

	/**
	 * @name            deleteBrands()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteBrands($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\Brand){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getBrand($entry);
				if(!$response->error->exists){
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}

	/**
	 * @name            doesBrandExist()
	 *
	 * @since           1.0.5
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->getBrand()
	 * @use             $this->resetResponse()
	 *
	 * @param           mixed           $brand
	 * @param           bool            $bypass
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function doesBrandExist($brand, $bypass = false){
		$response = $this->getBrand($brand);
		$exist = true;
		if($response->error->exist){
			$exist = false;
			$response->result->set = false;
		}
		if($bypass){
			return $exist;
		}
		return $response;
	}
	/**
	 * @name            deleteProductAttribute()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use				$this->deleteProductAttributes()
	 *
	 * @param           mixed 			$attribute
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProductAttribute($attribute){
		return $this->deleteProductAttributes(array($attribute));
	}

	/**
	 * @name            deleteProductAttributes()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProductAttributes($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\ProductAttribute){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getProductAttribute($entry);
				if(!$response->error->exists){
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}

    /**
     * @name            deleteAllAttributeValuesOfAttribute ()
     *
     * @since           1.4.2
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->resetResponse();
     *
     * @param           mixed           $attribute
     * @param           mixed           $product
     *
     * @return          array           $response
     */
    public function deleteAllAttributeValuesOfProductByAttribute($attribute, $product){
		$timeStamp = time();
		$response = $this->getProductAttribute($attribute);
		if($response->error->exist){
			return $response;
		}
		$attribute = $response->result->set;
        $response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $qStr = 'DELETE FROM ' . $this->entity['pav']['name'] . ' ' . $this->entity['pav']['alias']
            . ' WHERE ' . $this->entity['pav']['alias'] . '.attribute = ' . $attribute->getId()
            . ' AND ' . $this->entity['pav']['alias'] . '.product = ' . $product->getId();
        $query = $this->em->createQuery($qStr);
        $query->getResult();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
    }

	/**
	 * @name            deleteProductCategory()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use				$this->deleteProductCategories()
	 *
	 * @param           mixed 			$category
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProductCategory($category){
		return $this->deleteProductCategories(array($category));
	}

	/**
	 * @name            deleteProductCategories()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProductCategories($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\ProductCategory){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getProductCategory($entry);
				if(!$response->error->exists){
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}
	/**
	 * @name            deleteProduct()
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use				$this->deleteProductCategories()
	 *
	 * @param           mixed 			$product
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProduct($product){
		return $this->deleteProducts(array($product));
	}

	/**
	 * @name            deleteProducts()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProducts($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\Product){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getProduct($entry);
				if(!$response->error->exists){
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}
	/**
	 * @name            deleteVolumePricing()
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use				$this->deleteVolumePricings()
	 *
	 * @param           mixed 			$pricing
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteVolumePricing($pricing){
		return $this->deleteVolumePricings(array($pricing));
	}

	/**
	 * @name            deleteVolumePricings()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteVolumePricings($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\VolumePricing){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getVolumePricing($entry);
				if(!$response->error->exists){
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}
    /**
     * @name            doesProductAttributeExist()
     *
     * @since           1.0.5
     * @version         1.5.3
	 *
     * @author          Can Berkol
     *
     * @use             $this->getProductAttribute()
     * @use             $this->resetResponse()
     *
     * @param           mixed           $attribute
     * @param           bool            $bypass
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function doesProductAttributeExist($attribute, $bypass = false){
        $response = $this->getProductAttribute($attribute);
		$exist = true;
		if($response->error->exist){
			$exist = false;
			$response->result->set = false;
		}
		if($bypass){
			return $exist;
		}
        return $response;
    }

    /**
     * @name            doesProductAttributeValueExist()
     *                  Checks if entry exists in database.
     *
     * @since           1.2.2
     * @version         1.5.3
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->getAttributeValueOfProduct()
     *
     * @param           mixed       $attribute
     * @param           mixed       $product
     * @param           mixed       $language
     * @param           bool        $bypass
     *
     * @return          mixed       $response
     */
    public function doesProductAttributeValueExist($attribute, $product, $language, $bypass = false){
        $exist = false;
        $response = $this->getAttributeValueOfProduct($attribute, $product, $language);
        if (!$response->error->exist){
            $exist = true;
        }
        if ($bypass) {
            return $exist;
        }
		$response->result->set = $exist;
        return $response;
    }

	/**
	 * @name            doesProductExist()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->getProductAttribute()
	 * @use             $this->resetResponse()
	 *
	 * @param           mixed           $product
	 * @param           bool            $bypass
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function doesProductExist($product, $bypass = false){
		$response = $this->getProduct($product);
		$exist = true;
		if($response->error->exist){
			$exist = false;
			$response->result->set = false;
		}
		if($bypass){
			return $exist;
		}
		return $response;
	}
	/**
	 * @name            doesProductCategoryExist()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->getProductAttribute()
	 * @use             $this->resetResponse()
	 *
	 * @param           mixed           $category
	 * @param           bool            $bypass
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function doesProductCategoryExist($category, $bypass = false){
		$response = $this->getProductCategory($category);
		$exist = true;
		if($response->error->exist){
			$exist = false;
			$response->result->set = false;
		}
		if($bypass){
			return $exist;
		}
		return $response;
	}
    /**
     * @name            getAttributeValueOfProduct ()
     *
     * @since           1.1.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->resetResponse()
     *
     * @param           mixed           $attribute
     * @param           mixed           $product
     * @param           mixed           $language
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function getAttributeValueOfProduct($attribute, $product, $language){
		$timeStamp = time();
		$response = $this->getProductAttribute($attribute);
		if($response->error->exist){
			return $response;
		}
		$attribute = $response->result->set;

		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$resposne = $mlsModel->getLanguage($language);

		if($response->error->exist){
			return $response;
		}
		$language = $response->result->set;

        $qStr = 'SELECT DISTINCT ' . $this->entity['pav']['alias'] . ', ' . $this->entity['pa']['alias']
            . ' FROM ' . $this->entity['pav']['name'] . ' ' . $this->entity['pav']['alias']
            . ' JOIN ' . $this->entity['pav']['alias'] . '.attribute ' . $this->entity['pa']['alias']
            . ' WHERE ' . $this->entity['pav']['alias'] . '.product = ' . $product->getId()
            . ' AND ' . $this->entity['pav']['alias'] . '.language = ' . $language->getId()
            . ' AND ' . $this->entity['pav']['alias'] . '.attribute = ' . $attribute->getId();

        $query = $this->em->createQuery($qStr);
        $query->setMaxResults(1);
        $query->setFirstResult(0);
        $result = $query->getResult();
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }
	/**
	 * @name            getBrand()
	 *
	 * @since           1.5.3
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $brand
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getBrand($brand){
		$timeStamp = time();
		if($brand instanceof BundleEntity\Brand){
			return new ModelResponse($brand, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($brand){
			case is_numeric($brand):
				$result = $this->em->getRepository($this->entity['b']['name'])->findOneBy(array('id' => $brand));
				break;
			case is_string($brand):
				$result = $this->em->getRepository($this->entity['b']['name'])->findOneBy(array('name' => $brand));
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
    /**
     * @name            getMaxSortOrderOfAttributeInProduct ()
     *
     * @since           1.1.7
     * @version         1.5.3
     * @author          Can Berkol
     *
     *
     * @param           mixed           $product            id, entity, sku, url_key
     * @param           bool            $bypass             if set to true return integer instead of response
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function getMaxSortOrderOfAttributeInProduct($product, $bypass = false){
        $timeStamp = time();
        $response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $qStr = 'SELECT MAX(' . $this->entity['aop']['alias'] . ') FROM ' . $this->entity['aop']['name']
            . ' WHERE ' . $this->entity['aop']['alias'] . '.product = ' . $product->getId();

        $query = $this->em->createQuery($qStr);
        $result = $query->getSingleScalarResult();

        if ($bypass) {
            return $result;
        }
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }

    /**
     * @name            getMaxSortOrderOfProductFile ()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol

     *
     * @param           mixed 			$product
     * @param           bool 			$bypass
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function getMaxSortOrderOfProductFile($product, $bypass = false){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

        $qStr = 'SELECT MAX('.$this->entity['fop']['alias'].'.sort_order) FROM '
            . $this->entity['fop']['name'].' '.$this->entity['fop']['alias']
            .' WHERE '.$this->entity['fop']['alias'].'.product = '.$product->getId();

        $query = $this->em->createQuery($qStr);
        $result = $query->getSingleScalarResult();

		if ($bypass) {
			return $result;
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }

    /**
     * @name            getMaxSortOrderOfProductInCategory()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetProductCategory()
     *
     * @param           mixed           $category
     * @param           bool            $bypass
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function getMaxSortOrderOfProductInCategory($category, $bypass = false){
		$timeStamp = time();
		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
        $qStr = 'SELECT MAX(' . $this->entity['cop']['alias'] . ') FROM ' . $this->entity['cop']['name']
            . ' WHERE ' . $this->entity['cop']['alias'] . '.category = ' . $category->getId();

        $query = $this->em->createQuery($qStr);
        $result = $query->getSingleScalarResult();

		if ($bypass){
			return $result;
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }
    /**
     * @name            getMostRecentFileOfProduct()
     *
     * @since           1.2.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->listFilesOfProducts()
     *
     * @param           mixed 			$product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function getMostRecentFileOfProduct($product){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['fop']['alias'] . '.product', 'comparison' => '=', 'value' => $product->getId()),
                )
            )
        );

        $response = $this->listFilesOfProducts($filter, array('date_added' => 'desc'), array('start' => 0, 'count' => 1));
        if ($response->error->exist) {
            return $response;
        }
		return new ModelResponse($response->result->set, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }
    /**
     * @name            getParentOfProductCategory ()
     *
     * @since           1.2.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed 			$category
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function getParentOfProductCategory($category){
        $response = $this->getProductCategory($category);

        if (!$response->error->exist) {
			$response->result->set = $response->result->set->getParent();
			if(is_null($response->result->set)){
				$response->error->exist = true;
			}
		}
        return $response;
    }
    /**
     * @name            getProduct()
     *
     * @since           1.0.1
     * @version         1.5.3
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           mixed           $product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
	public function getProduct($product){
		$timeStamp = time();
		if($product instanceof BundleEntity\Product){
			return new ModelResponse($product, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($product){
			case is_numeric($product):
				$result = $this->em->getRepository($this->entity['p']['name'])->findOneBy(array('id' => $product));
				break;
			case is_string($product):
				$result = $this->em->getRepository($this->entity['p']['name'])->findOneBy(array('sku' => $product));
				if(is_null($result)){
					$response = $this->getProductByUrlKey($product);
					if(!$response->error->exist){
						$result = $response->result->set;
					}
				}
				unset($response);
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getProductByUrlKey()
	 *
	 * @since           1.5.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->listProducts()
	 * @use             $this->createException()
	 *
	 * @param           mixed 			$urlKey
	 * @param			mixed			$language
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductByUrlKey($urlKey, $language = null){
		$timeStamp = time();
		if(!is_string($urlKey)){
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pl']['alias'].'.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if(!is_null($language)){
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if(!$response->error->exists){
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['pl']['alias'].'.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listProducts($filter, null, array('start' => 0, 'count' => 1));

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name            getProductAttribute()
	 *
	 * @since           1.0.1
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $attr
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductAttribute($attr){
		$timeStamp = time();
		if($attr instanceof BundleEntity\ProductAttribute){
			return new ModelResponse($attr, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($attr){
			case is_numeric($attr):
				$result = $this->em->getRepository($this->entity['pa']['name'])->findOneBy(array('id' => $attr));
				break;
			case is_string($attr):
				$response = $this->getProductAttributeByUrlKey($attr);
				if(!$response->error->exist){
					$result = $response->result->set;
				}
				unset($response);
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getProductAttributeByUrlKey()
	 *
	 * @since           1.5.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->listProducts()
	 * @use             $this->createException()
	 *
	 * @param           mixed 			$urlKey
	 * @param			mixed			$language
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductAttributeByUrlKey($urlKey, $language = null){
		$timeStamp = time();
		if(!is_string($urlKey)){
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pal']['alias'].'.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if(!is_null($language)){
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if(!$response->error->exists){
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['pal']['alias'].'.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listProductAtrributes($filter, null, array('start' => 0, 'count' => 1));

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
    /**
     * @name            getProductAttributeValue()
     *
     * @since           1.0.5
     * @version         1.5.3
	 *
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
		$timeStamp = time();
        $result = $this->em->getRepository($this->entity['pav']['name'])
                           ->findOneBy(array('id' => $id));

		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }
    /**
     * @name            getProductBySku()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           string          $sku
     *
     * @return          mixed           $response
     */
    public function getProductBySku($sku){
        return $this->getProduct($sku);
    }

	/**
	 * @name            getProductCategory()
	 *
	 * @since           1.0.1
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $category
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductCategory($category){
		$timeStamp = time();
		if($category instanceof BundleEntity\ProductCategory){
			return new ModelResponse($category, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($category){
			case is_numeric($category):
				$result = $this->em->getRepository($this->entity['p']['name'])->findOneBy(array('id' => $category));
				break;
			case is_string($category):
				$result = $this->em->getRepository($this->entity['p']['name'])->findOneBy(array('sku' => $category));
				if(is_null($result)){
					$response = $this->getProductCategoryByUrlKey($category);
					if(!$response->error->exist){
						$result = $response->result->set;
					}
				}
				unset($response);
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getProductCategoryByUrlKey()
	 *
	 * @since           1.5.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->listProducts()
	 * @use             $this->createException()
	 *
	 * @param           mixed 			$urlKey
	 * @param			mixed			$language
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductCategoryByUrlKey($urlKey, $language = null){
		$timeStamp = time();
		if(!is_string($urlKey)){
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pcl']['alias'].'.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if(!is_null($language)){
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if(!$response->error->exists){
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['pcl']['alias'].'.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listProductCategories($filter, null, array('start' => 0, 'count' => 1));

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}

    /**
     * @name            getRandomCategoryOfProduct ()
     *
     * @since           1.3.6
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           mixed 			$product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function getRandomCategoryOfProduct($product){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

        $response = $this->listCategoriesOfProduct($product);
        if ($response->error->exist) {
            return $response;
        }
        $categoriesOfProduct = $response->result->set;
        unset($response);
        $i = count($categoriesOfProduct);
        $random = rand(0, $i - 1);
        $category = $categoriesOfProduct[$random];
        unset($categoriesOfProduct);
		return new ModelResponse($category, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }
	/**
	 * @name            getVolumePricing()
	 *
	 * @since           1.0.5
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 * @use             $this->resetResponse()
	 *
	 * @param           integer         $pricing
	 *
	 * @return          mixed           $response
	 */
	public function getVolumePricing($pricing){
		$timeStamp = time();

		$result = $this->em->getRepository($this->entity['vp']['name'])
			->findOneBy(array('id' => $pricing));

		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getVolumePricingOfProductWithMaximumQuantity ()
	 *
	 * @since           1.4.4
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listVolumePricingsOfProduct()
	 *
	 * @param  			mixed 			$product
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getVolumePricingOfProductWithMaximumQuantity($product){
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		return $this->listVolumePricingsOfProduct($product, array(), array('quantity_limit' => 'desc'), array('start' => 0, 'count' => 1));
	}
	/**
	 * @name            incrementCountViewOfProduct ()
	 *
	 * @since           1.3.8
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->getProduct()
	 * @use             $this->updateProduct()
	 *
	 * @param   		mixed 			$product
	 * @param   		int 			$count
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function incrementCountViewOfProduct($product, $count){
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		$product->setCountView($product->getCountView() + $count);
		return $this->updateProduct($product);
	}
	/**
	 * @name            insertBrand()
	 *
	 * @since           1.0.5
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->insertBrands()
	 *
	 * @param           mixed 				$brand
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertBrand($brand){
		return $this->insertBrands(array($brand));
	}

	/**
	 * @name            insertBrands()
	 *
	 * @since           1.0.5
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertBrands($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\Brand) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			} else if (is_object($data)) {
				$entity = new BundleEntity\Brand;
				if (!property_exists($data, 'date_added')) {
					$data->date_added = $now;
				}
				if (!property_exists($data, 'date_updated')) {
					$data->date_updated = nnow;
				}
				if (!property_exists($data, 'count_children')) {
					$data->count_children = 0;
				}
				foreach ($data as $column => $value) {
					$localeSet = false;
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
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
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
    /**
     * @name            insertProduct ()
     *
     * @since           1.0.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->insertProducts()
     *
     * @param           mixed 			$product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function insertProduct($product){
        return $this->insertProducts(array($product));
    }

    /**
     * @name            insertProductAttribute()
     *
     * @since           1.0.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->insertProductAttributes()
     *
     * @param           mixed			$attribute
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function insertProductAttribute($attribute){
        return $this->insertProductAttributes(array($attribute));
    }

    /**
     * @name            insertProductAttributeLocalizations()
	 *
     * @since           1.1.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
	public function insertProductAttributeLocalizations($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
		foreach($collection as $data){
			if($data instanceof BundleEntity\ProductAttributeLocalization){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if(is_object($data)){
				$entity = new BundleEntity\ProductAttributeLocalization();
				foreach($data as $column => $value){
					$set = 'set'.$this->translateColumnName($column);
					switch($column){
						case 'language':
							$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
							$response = $lModel->getLanguage($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						case 'attribute':
							$response = $this->getProductAttribute($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						default:
							$entity->$set($value);
							break;
					}
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
    /**
     * @name            insertProductAttributeValue()
     *
     * @since           1.1.7
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->insertProductAttributeValues()
     *
     * @param           mixed			$value
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function insertProductAttributeValue($value){
        return $this->insertProductAttributeValues(array($value));
    }

    /**
     * @name            insertProductAttributeValues()
     *
     * @since           1.1.7
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function insertProductAttributeValues($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
        $countInserts = 0;
        $insertedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductAttributeValues) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            }
			else if (is_object($data)) {
                $entity = new BundleEntity\ProductAttributeValues;
                if (isset($data->id)) {
                    unset($data->id);
                }
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'language':
                            $lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                            $response = $lModel->getLanguage($value);
                            if($response->error->exists){
								return $response;
							}
							$entity->$set($response->result->set);
                            unset($response, $lModel);
                            break;
                        case 'attribute':
                            $response = $this->getProductAttribute($value);
							if($response->error->exists){
								return $response;
							}
							$entity->$set($response->result->set);
                            break;
                        case 'product':
                            $response = $this->getProduct($value, 'id');
							if($response->error->exists){
								return $response;
							}
							$entity->$set($response->result->set);
                            unset($response);
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;

                $countInserts++;
            }
        }
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
    /**
     * @name            insertProductAttributes()
     *                  Inserts one or more product attributes into database.
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 				$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function insertProductAttributes($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
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
            }
			else if (is_object($data)) {
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
                            $response = $sModel->getSite($value);
                            if($response->error->exist){
								return $response;
							}
							$entity->$set($response->result->set);
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
            }
        }
		if ($countInserts > 0) {
			$this->em->flush();
		}
		/** Now handle localizations */
		if ($countInserts > 0 && $countLocalizations > 0) {
			$response = $this->insertProductAttributeLocalizations($localizations);
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}

    /**
     * @name            insertProductCategory()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->insertProductCategories()
     *
     * @param           mixed 				$category
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function insertProductCategory($category){
        return $this->insertProductCategories(array($category));
    }

    /**
     * @name            insertProductCategories()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function insertProductCategories($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
        $countInserts = 0;
        $countLocalizations = 0;
        $insertedItems = array();
        $localizations = array();
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductCategory) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\ProductCategory;
                if (!property_exists($data, 'date_added')) {
                    $data->date_added = $now;
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = nnow;
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
                            $response = $fModel->getFile($value);
                            if($response->error->exist){
								break;
							}
							$entity->$set($response->result->set);
                            unset($response, $fModel);
                            break;
                        case 'parent':
							$response = $this->getProductCategory($value, 'id');
							if($response->error->exist){
								break;
							}
							$entity->$set($response->result->set);
                            unset($response, $fModel);
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
							if($response->error->exist){
								return $response;
							}
							$entity->$set($response->result->set);
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
            }
        }
		/** Now handle localizations */
		if ($countInserts > 0 && $countLocalizations > 0) {
			$response = $this->insertProductCategoryLocalizations($localizations);
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
    /**
     * @name            insertProductCategoryLocalizations()
     *
     * @since           1.1.8
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
	public function insertProductCategoryLocalizations($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
		foreach($collection as $data){
			if($data instanceof BundleEntity\ProductCategoryLocalization){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if(is_object($data)){
				$entity = new BundleEntity\ProductCategoryLocalization();
				foreach($data as $column => $value){
					$set = 'set'.$this->translateColumnName($column);
					switch($column){
						case 'language':
							$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
							$response = $lModel->getLanguage($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						case 'category':
							$response = $this->getProductCategory($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						default:
							$entity->$set($value);
							break;
					}
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
    /**
     * @name            insertProductLocalizations ()
	 *
	 * @since           1.1.8
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductLocalizations($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
		foreach($collection as $data){
			if($data instanceof BundleEntity\ProductLocalizations){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if(is_object($data)){
				$entity = new BundleEntity\ProductLocalizations();
				foreach($data as $column => $value){
					$set = 'set'.$this->translateColumnName($column);
					switch($column){
						case 'language':
							$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
							$response = $lModel->getLanguage($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						case 'product':
							$response = $this->getProduct($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						default:
							$entity->$set($value);
							break;
					}
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
    /**
     * @name            insertProducts()
     *
     * @since           1.1.7
     * @version         1.5.3
	 *
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     *
     * @param           array 			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function insertProducts($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
        $countInserts = 0;
        $countLocalizations = 0;
        $insertedItems = array();
        $localizations = array();
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Product) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\Product;
                if (!property_exists($data, 'date_added')) {
                    $data->date_added = $now;
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = $now;
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
                            $response = $this->getBrand($value);
                            if($response->error->exist){
								break;
							}
							$entity->$set($response->result->set);
                            unset($response);
                            break;
                        case 'local':
                            $localizations[$countInserts]['localizations'] = $value;
                            $localeSet = true;
                            $countLocalizations++;
                            break;
                        case 'preview_file':
                            $fModel = $this->kernel->getContainer()->get('filemanagement.model');
                            $response = $fModel->getFile($value);
							if($response->error->exist){
								break;
							}
							$entity->$set($response->result->set);
                            unset($response, $fModel);
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
							if($response->error->exist){
								return $response;
							}
							$entity->$set($response->result->set);
                            unset($response, $sModel);
                            break;
                        case 'supplier':
                            $sModel = $this->kernel->getContainer()->get('stockmanagement.model');
                            $response = $sModel->getSupplier($value);
							if($response->error->exist){
								break;
							}
							$entity->$set($response->result->set);
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
            }
        }
        /** Now handle localizations */
        if ($countInserts > 0 && $countLocalizations > 0) {
            $this->insertProductLocalizations($localizations);
        }
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());

	}
	/**
	 * @name            insertVolumePricing ()
	 *
	 * @since           1.3.2
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->insertVolumePricings()
	 *
	 * @param           mixed 			$volumePricing
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertVolumePricing($volumePricing){
		return $this->insertVolumePricings(array($volumePricing));
	}

	/**
	 * @name            insertVolumePricings().
	 *
	 * @since           1.3.2
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 				$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertVolumePricings($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\VolumePricing) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if (is_object($data)) {
				$entity = new BundleEntity\VolumePricing;
				if (isset($data->id)) {
					unset($data->id);
				}
				if (!property_exists($data, 'date_added')) {
					$data->date_added = $now;
				}
				if (!property_exists($data, 'date_updated')) {
					$data->date_updated = $now;
				}
				if (!property_exists($data, 'limit_direction')) {
					$data->date_updated = 'xm';
				}
				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'product':
							$response = $this->getProduct($value);
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
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
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
    /**
     * @name            isAttributeAssociatedWithProduct()
     *
     * @since           1.1.7
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @user            $this->createException
     *
     * @param           mixed 		$attribute
     * @param           mixed 		$product
	 *
     * @param           bool 		$bypass
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function isAttributeAssociatedWithProduct($attribute, $product, $bypass = false){
		$timeStamp = time();
		$response = $this->getProductAttribute($attribute);
		if($response->error->exist){
			return $response;
		}
		$attribute = $response->result->set;

		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $found = false;

        $qStr = 'SELECT COUNT(' . $this->entity['aop']['alias'] . ')'
            . ' FROM ' . $this->entity['aop']['name'] . ' ' . $this->entity['aop']['alias']
            . ' WHERE ' . $this->entity['aop']['alias'] . '.attribute = ' . $attribute->getId()
            . ' AND ' . $this->entity['aop']['alias'] . '.product = ' . $product->getId();
        $q = $this->em->createQuery($qStr);

        $result = $q->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());

	}
	/**
	 * @name            isFileAssociatedWithBlogPost()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed       $file
	 * @param           mixed       $product
	 * @param           bool        $bypass     true or false
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function isFileAssociatedWithProduct($file, $product, $bypass = false){
		$timeStamp = time();
		$fModel = new FileService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);

		$response = $fModel->getFile($file);
		if($response->error->exist){
			return $response;
		}
		$post = $response->result->set;

		$response = $this->getProduct($product);

		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['fop']['alias'] . ')'
			. ' FROM ' . $this->entity['fop']['name'] . ' ' . $this->entity['fop']['alias']
			. ' WHERE ' . $this->entity['fop']['alias'] . '.file = ' . $file->getId()
			. ' AND ' . $this->entity['fop']['alias'] . '.product = ' . $product->getId();
		$query = $this->em->createQuery($qStr);

		$result = $query->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
    /**
     * @name            isLocaleAssociatedWithProduct ()
     *
     * @since           1.2.3
     * @version         1.5.3
	 *
     * @author          Can Berkol
     *
     * @user            $this->createException
     *
     * @param           mixed 		$locale
     * @param           mixed 		$product
     * @param           bool 		$bypass
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function isLocaleAssociatedWithProduct($locale, $product, $bypass = false){
		$timeStamp = time();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');

		$response = $mlsModel->getLanguage($locale);
		if($response->error->exist){
			return $response;
		}
        $locale = $response->result->set;

		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $found = false;

        $qStr = 'SELECT COUNT(' . $this->entity['apl']['alias'] . ')'
            . ' FROM ' . $this->entity['apl']['name'] . ' ' . $this->entity['apl']['alias']
            . ' WHERE ' . $this->entity['apl']['alias'] . '.locale = ' . $locale->getId()
            . ' AND ' . $this->entity['apl']['alias'] . '.product = ' . $product->getId();
        $query = $this->em->createQuery($qStr);

        $result = $query->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }

	/**
	 * @name            isLocaleAssociatedWithProductCategory ()
	 *
	 * @since           1.2.3
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 *
	 * @user            $this->createException
	 *
	 * @param           mixed 		$locale
	 * @param           mixed 		$category
	 * @param           bool 		$bypass
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function isLocaleAssociatedWithProductCategory($locale, $category, $bypass = false){
		$timeStamp = time();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');

		$response = $mlsModel->getLanguage($locale);
		if($response->error->exist){
			return $response;
		}
		$locale = $response->result->set;

		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['apcl']['alias'] . ')'
			. ' FROM ' . $this->entity['apcl']['name'] . ' ' . $this->entity['apcl']['alias']
			. ' WHERE ' . $this->entity['apcl']['alias'] . '.locale = ' . $locale->getId()
			. ' AND ' . $this->entity['apcl']['alias'] . '.category = ' . $category->getId();
		$query = $this->em->createQuery($qStr);

		$result = $query->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name            isProductAssociatedWithCategory()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 *
	 * @user            $this->createException
	 *
	 * @param           mixed 		$product
	 * @param           mixed 		$category
	 * @param           bool 		$bypass
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function isProductAssociatedWithCategory($product, $category, $bypass = false){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['cop']['alias'] . ')'
			. ' FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias']
			. ' WHERE ' . $this->entity['cop']['alias'] . '.product = ' . $product->getId()
			. ' AND ' . $this->entity['cop']['alias'] . '.category = ' . $category->getId();
		$query = $this->em->createQuery($qStr);

		$result = $query->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

    /**
     * @name            listActiveLocalesOfProduct ()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed $product 		entity
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listActiveLocalesOfProduct($product){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $qStr = 'SELECT ' . $this->entity['apl']['alias']
            . ' FROM ' . $this->entity['apl']['name'] . ' ' . $this->entity['apl']['alias']
            . ' WHERE ' . $this->entity['apl']['alias'] . '.product = ' . $product->getId();
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
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($locales, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name            listActiveLocalesOfProductCategory()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed 		$category 		entity
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveLocalesOfProductCategory($category){
		$timeStamp = time();
		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$qStr = 'SELECT ' . $this->entity['apcl']['alias']
			. ' FROM ' . $this->entity['apcl']['name'] . ' ' . $this->entity['apcl']['alias']
			. ' WHERE ' . $this->entity['apcl']['alias'] . '.category = ' . $category->getId();
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
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($locales, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
    /**
     * @name            listActiveProductsOfCategory ()
     *
     * @since           1.5.2
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed 	$category
     * @param           array 	$sortOrder
     * @param           array 	$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listActiveProductsOfCategory($category, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}

        $qStr = 'SELECT ' . $this->entity['cop']['alias'] . ', ' . $this->entity['product']['alias']
            . ' FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias']
            . ' JOIN ' . $this->entity['cop']['alias'] . '.product ' . $this->entity['product']['alias']
            . ' WHERE ' . $this->entity['cop']['alias'] . '.category = ' . $category->getId();

        $oStr = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                $sorting = false;
                if (!in_array($column, array('name', 'url_key'))) {
                    $sorting = true;
                    switch ($column) {
                        case 'id':
                        case 'quantity':
                        case 'price':
                        case 'sku':
                        case 'date_added':
                        case 'date_updated':
                            $column = $this->entity['p']['alias'] . '.' . $column;
                            break;
                        case 'sort_order':
                            $column = $this->entity['cop']['alias'] . '.' . $column;
                            break;
                    }
                    $oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
                }
            }
			if($sorting){
				$oStr = rtrim($oStr, ', ');
				$oStr = ' ORDER BY ' . $oStr . ' ';
			}
        }
		$qStr .= $oStr;
        $query = $this->em->createQuery($qStr);
        $result = $query->getResult();
        if (count($result) < 1) {

        }
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
					'condition' => array('column' => $this->entity['p']['alias'] . '.id', 'comparison' => 'in', 'value' => $collection),
				),
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['p']['alias'] . '.status', 'comparison' => '=', 'value' => 'a'),
				)
			)
		);
		return $this->listProducts($filter, $sortOrder, $limit);
    }
    /**
     * @name            listAttributesOfProduct()
     *
     * @since           1.0.5
     * @version         1.5.3
	 *
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed 			$product
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listAttributesOfProduct($product, $sortOrder = null, $limit = null){
        $timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $qStr = 'SELECT ' . $this->entity['aop']['alias'] . ', ' . $this->entity['aop']['alias']
            . ' FROM ' . $this->entity['aop']['name'] . ' ' . $this->entity['aop']['alias']
            . ' JOIN ' . $this->entity['aop']['alias'] . '.attribute ' . $this->entity['pa']['alias']
            . ' WHERE ' . $this->entity['aop']['alias'] . '.product = ' . $product->getId();

        $oStr = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    default:
                        $column = $this->entity['aop']['alias'] . '.' . $column;
                        break;
                }
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
        }

        $qStr .= $oStr;

        $q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

        $result = $q->getResult();

        $totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

    /**
     * @name            listAttributesOfProductCategory()
     *
     * @since           1.2.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed 			$category
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listAttributesOfProductCategory($category, $sortOrder = null, $limit = null){
		$timeStamp = time();
		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$qStr = 'SELECT ' . $this->entity['aopc']['alias'] . ', ' . $this->entity['aopc']['alias']
			. ' FROM ' . $this->entity['aopc']['name'] . ' ' . $this->entity['aopc']['alias']
			. ' JOIN ' . $this->entity['aopc']['alias'] . '.attribute ' . $this->entity['pa']['alias']
			. ' WHERE ' . $this->entity['aopc']['alias'] . '.category = ' . $category->getId();

		$oStr = '';
		if ($sortOrder != null) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					default:
						$column = $this->entity['aopc']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}

		$qStr .= $oStr;

		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

    /**
     * @name            listAllAttributeValuesOfProduct()
     *
     * @since           1.1.5
     * @version         1.5.3
	 *
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed 			$product
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listAllAttributeValuesOfProduct($product, $sortOrder = null, $limit = null){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $qStr = 'SELECT ' . $this->entity['pav']['alias'] . ', ' . $this->entity['pa']['alias']
            . ' FROM ' . $this->entity['pav']['name'] . ' ' . $this->entity['pav']['alias']
            . ' JOIN ' . $this->entity['pav']['alias'] . '.attribute ' . $this->entity['pa']['alias']
            . ' WHERE ' . $this->entity['pav']['alias'] . '.product = ' . $product->getId();

        $oStr = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'sort_order':
                        $column = $this->entity['pa']['alias'] . '.' . $column;
                        break;
                    default:
                        $column = $this->entity['aop']['alias'] . '.' . $column;
                        break;
                }
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
        }

		$qStr .= $oStr;

		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

        $result = $q->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

    /**
     * @name            listAllChildProductCategories ()
     *
     * @since           1.2.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategories()
     *
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listAllChildProductCategories($sortOrder = null, $limit = null){
        $column = $this->entity['pc']['alias'] . '.parent';
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

        return $this->listProductCategories($filter, $sortOrder, $limit);
    }

    /**
     * @name            listAttributeValuesOfProduct ()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed 			$product
     * @param           mixed 			$type
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listAttributeValuesOfProduct($product, $type, $sortOrder = null, $limit = null){
        $this->resetResponse();
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

        $qStr = 'SELECT ' . $this->entity['pav']['alias'] . ', ' . $this->entity['pa']['alias']
            . ' FROM ' . $this->entity['pav']['name'] . ' ' . $this->entity['pav']['alias']
            . ' JOIN ' . $this->entity['pav']['alias'] . '.attribute ' . $this->entity['pa']['alias']
            . ' WHERE ' . $this->entity['pav']['alias'] . '.product = ' . $product->getId() . ' AND ' . $this->entity['pav']['alias'] . '.attribute = ' . $type;

        $oStr = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'sort_order':
                        $column = $this->entity['pa']['alias'] . '.' . $column;
                        break;
                    default:
                        $column = $this->entity['aop']['alias'] . '.' . $column;
                        break;
                }
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
        }

        $qStr .= $oStr;

		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();
		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            listBrands()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$filter
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listBrands($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['b']['alias'].', '.$this->entity['b']['alias']
			.' FROM '.$this->entity['b']['name'].' '.$this->entity['b']['alias'];

		if(!is_null($sortOrder)){
			foreach($sortOrder as $column => $direction){
				switch ($column) {
					case 'id':
					case 'name':
					case 'date_added':
					case 'date_updated':
						$column = $this->entity['p']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if(!is_null($filter)){
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = array();
		foreach($result as $entry){
			$id = $entry->getAttribute()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getAttribute();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            listCategoriesOfProduct(
	 *
	 * @since           1.0.1
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 * @use             $this->getProduct()
	 * @use             $this->listProductCategories()
	 *
	 * @param           mixed 			$product
	 * @param           array 			$filter
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listCategoriesOfProduct($product, $filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		$response  = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		$qStr = 'SELECT ' . $this->entity['cop']['alias']
			. ' FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias']
			. ' WHERE ' . $this->entity['cop']['alias'] . '.product = ' . $product->getId();
		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$catsOfProduct= array();
		if (count($result) > 0) {
			foreach ($result as $cop) {
				$catsOfProduct[] = $cop->getCategory()->getId();
			}
		}
		if (count($catsOfProduct) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		$columnI = $this->entity['pc']['alias'] . '.id';
		$conditionI = array('column' => $columnI, 'comparison' => 'in', 'value' => $catsOfProduct);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => $conditionI,
				)
			)
		);
		return $this->listProductCategories($filter, $sortOrder, $limit);
	}

    /**
     * @name            listChildCategoriesOfProductCategory ()
     *
     * @since           1.2.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategories()
     *
     * @param           mixed 				$category
     * @param           array 				$sortOrder
     * @param           array 				$limit
     *
	 * @return			BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listChildCategoriesOfProductCategory($category, $sortOrder = null, $limit = null){
		$timeStamp = time();

        $column = $this->entity['pc']['alias'] . '.parent';
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
        return $this->listProductCategories($filter, $sortOrder, $limit);
    }

    /**
     * @name            listChildCategoriesOfProductCategoryWithPreviewImage()
     *
     * @since           1.2.9
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategories()
     *
     * @param           mixed 			$category
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listChildCategoriesOfProductCategoryWithPreviewImage($category, $sortOrder = null, $limit = null){
        $response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;

        $column = $this->entity['pc']['alias'] . '.parent';
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

        $column = $this->entity['pc']['alias'] . '.preview_image';
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
        return $this->listProductCategories($filter, $sortOrder, $limit);
    }

    /**
     * @name            listCustomizableProducts ()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           array 		$sortOrder
     * @param           array 		$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listCustomizableProducts($sortOrder = null, $limit = null){
        $column = $this->entity['p']['alias'] . '.is_customizable';
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
        return $this->listProducts($filter, $sortOrder, $limit);
    }
	/**
	 * @name            listFeaturedParentProductCategories ()
	 *
	 * @since           1.3.7
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listProductCategories()
	 *
	 * @param   		array 			$filter
	 * @param   		array 			$sortOrder
	 * @param   		array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listFeaturedParentProductCategories($filter = null, $sortOrder = null, $limit = null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pc']['alias'] . '.is_featured', 'comparison' => '=', 'value' => 'y'),
				),
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pc']['alias'] . '.parent', 'comparison' => 'isnull', 'value' => null),
				),
			)
		);
		return $this->listProductCategories($filter, $sortOrder, $limit);
	}
	/**
	 * @name            listFilesOfProduct()
	 *
	 * @since           1.1.3
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @throws          $this->createException()
	 *
	 * @param           mixed 		$product
	 * @param           array 		$filter
	 * @param           array 		$sortOrder
	 * @param           array 		$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listFilesOfProduct($product, $filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}

		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		$oStr = $wStr = $gStr = '';

		$qStr = 'SELECT '.$this->entity['fop']['alias']
			.' FROM '.$this->entity['fop']['name'].' '.$this->entity['fop']['alias'];
		/**
		 * Prepare ORDER BY part of query.
		 */
		if ($sortOrder != null) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					default:
						$oStr .= ' '.$this->entity['fop']['alias'].'.'.$column.' '.$direction.', ';
						break;
				}
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['fop']['alias'].'.id', 'comparison' => '=', 'value' => $product->getId()),
				)
			)
		);
		if(!is_null($filter)){
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
    /**
     * @name            listNotCustomizableProducts ()
     *
     * @since           1.0.5
     * @version         1.5.3
	 *
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listNotCustomizableProducts($sortOrder = null, $limit = null){
		$column = $this->entity['p']['alias'] . '.is_customizable';
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
		return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listOutOfStockProducts()
     *
     * @since           1.0.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->listProducts()
     * @use             $this->createException
     *
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listOutOfStockProducts($sortOrder = null, $limit = null){
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
        return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listParentOnlyProductCategories ()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategories()
     *
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listParentOnlyProductCategories($sortOrder = null, $limit = null){
        $column = $this->entity['pc']['alias'] . '.parent';
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
        return $this->listProductCategories($filter, $sortOrder, $limit);
    }

    /**
     * @name            listParentOnlyProductCategoriesOfLevel()
     *
     * @since           1.0.8
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @uses            $this->listProductCategories()
     *
     * @param           integer 		$level
     * @param           array 			$filter
     * @param           mixed 			$sortOrder
     * @param           mixed 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listParentOnlyProductCategoriesOfLevel($level = 1, $filter = null, $sortOrder = null, $limit = null){
        $column = $this->entity['pc']['alias'] . '.parent';
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
                    'condition' => array('column' => $this->entity['pc']['alias'] . '.level', 'comparison' => '=', 'value' => $level),
                )
            )
        );
        return $this->listProductCategories($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsPricedBetween()
     *
     * @since           1.0.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsPriced()
     *
     * @param           array 			$amounts
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsPricedBetween($amounts, $sortOrder = null, $limit = null){
        return $this->listProductsPriced($amounts, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductAttributes()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 			$filter
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
	public function listProductAttributes($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['pa']['alias'].', '.$this->entity['pa']['alias']
			.' FROM '.$this->entity['pal']['name'].' '.$this->entity['pal']['alias']
			.' JOIN '.$this->entity['pal']['alias'].'.attribute '.$this->entity['pa']['alias'];

		if(!is_null($sortOrder)){
			foreach($sortOrder as $column => $direction){
				switch($column){
					case 'id':
					case 'sort_order':
					case 'date_added':
					case 'date_updated':
					case 'date_removed':
					case 'site':
						$column = $this->entity['pa']['alias'].'.'.$column;
						break;
					case 'name':
					case 'url_key':
						$column = $this->entity['pal']['alias'].'.'.$column;
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if(!is_null($filter)){
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = array();
		foreach($result as $entry){
			$id = $entry->getAttribute()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getAttribute();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

    /**
     * @name            listProductAttributeValues ()
     *
     * @since           1.2.2
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array 		$filter
     * @param           array 		$sortOrder
     * @param           array 		$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductAttributeValues($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
        $oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['pav']['alias']
                . ' FROM ' . $this->entity['pav']['name'] . ' ' . $this->entity['pav']['alias'];

        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'sort_order':
                    case 'value':
                        $column = $this->entity['pav']['alias'] . '.' . $column;
                        break;
                }
                $oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
        }

        if ($filter != null) {
            $fStr = $this->prepareWhere($filter);
            $wStr .= ' WHERE ' . $fStr;
        }

        $qStr .= $wStr . $gStr . $oStr;

        $q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

        $result = $q->getResult();

        $attributes = array();
        $unique = array();
		foreach($result as $entry){
			$id = $entry->getAttribute()->getId();
			if(!isset($unique[$id])){
				$attributes[] = $entry->getAttribute();
			}
		}
		$totalRows = count($attributes);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($attributes, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name            listProductCategories()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$filter
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductCategories($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['pc']['alias'].', '.$this->entity['pc']['alias']
			.' FROM '.$this->entity['pcl']['name'].' '.$this->entity['pcl']['alias']
			.' JOIN '.$this->entity['pcl']['alias'].'.category '.$this->entity['pc']['alias'];

		if(!is_null($sortOrder)){
			foreach($sortOrder as $column => $direction){
				switch($column){
					case 'id':
					case 'sort_order':
					case 'date_added':
					case 'date_updated':
					case 'date_removed':
					case 'site':
					case 'parent':
					case 'count_children':
						$column = $this->entity['pc']['alias'].'.'.$column;
						break;
					case 'name':
					case 'url_key':
						$column = $this->entity['pcl']['alias'].'.'.$column;
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if(!is_null($filter)){
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = array();
		foreach($result as $entry){
			$id = $entry->getAttribute()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getAttribute();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name            listProductCategoriesOfParentHavingLevel()
	 *
	 * @since           1.0.3
	 * @since           1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listProductCategories()
	 *
	 * @param   		mixed 			$category
	 * @param   		int 			$level
	 * @param   		array 			$filter
	 * @param   		array 			$sortOrder
	 * @param   		array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductCategoriesOfParentHavingLevel($category, $level, $filter = null, $sortOrder = null, $limit = null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->getEntityDefinition('pc', 'alias') . '.parent', 'comparison' => '=', 'value' => $category),
				),
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->getEntityDefinition('pc', 'alias') . '.level', 'comparison' => '=', 'value' => $level),
				),
			)
		);
		return $this->listProductCategories($filter, $sortOrder, $limit);
	}
	/**
	 * @name            listProductAttributeValuesOfProduct()
	 *
	 * @since           1.4.4
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed 		$product
	 * @param           array 		$filter
	 * @param           array 		$sortOrder
	 * @param           array 		$limit
	 *
	 * @return          array           $response
	 */
	public function listProductAttributeValuesOfProduct($product, $filter = null, $sortOrder = null, $limit = null){
		$response = $this->getProduct($product);
		if($product->error->exist){
			return $product;
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pav']['alias'] . '.product', 'comparison' => '=', 'value' => $product->getId()),
				)
			)
		);
		return $this->listProductAttributeValues($filter);
	}
    /**
     * @name            listProductCategoriesOfLevel()
     *
     * @since           1.1.9
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductCategoriesOfLevel()
     *
     * @param           integer 		$level
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductCategoriesOfLevel($level = 1, $sortOrder = null, $limit = null){
		$conditions[] = array(
			'glue' => 'or',
			'condition' => array('column' => $this->entity['pc']['alias'] . '.level', 'comparison' => '=', 'value' => $level),
		);

        $filter[] = array(
            'glue' => 'and',
            'condition' => $conditions
        );
        return $response = $this->listProductCategories($filter, $sortOrder, $limit);
    }

	/**
	 * @name            listProducts()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$filter
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProducts($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['p']['alias'].', '.$this->entity['p']['alias']
			.' FROM '.$this->entity['pl']['name'].' '.$this->entity['pl']['alias']
			.' JOIN '.$this->entity['pl']['alias'].'.category '.$this->entity['p']['alias'];

		if(!is_null($sortOrder)){
			foreach($sortOrder as $column => $direction){
				switch ($column) {
					case 'id':
					case 'quantity':
					case 'price':
					case 'count_view':
					case 'sku':
					case 'sort_order':
					case 'date_added':
					case 'date_updated':
					case 'count_like':
					case 'site':
						$column = $this->entity['p']['alias'] . '.' . $column;
						break;
					case 'name':
					case 'description':
					case 'meta_keywords':
					case 'meta_description':
						$column = $this->entity['pl']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if(!is_null($filter)){
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = array();
		foreach($result as $entry){
			$id = $entry->getAttribute()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getAttribute();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

    /**
     * @name            listProductsAdded()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed 			$date
     * @param           string 			$eq 			after, before, between
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsAdded($date, $eq, $sortOrder = null, $limit = null){
        // $eqOpts = array('after', 'before', 'between', 'on');
        $column = $this->entity['p']['alias'] . '.date_added';

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
        }
		else {
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
        return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsAddedAfter ()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsAdded()
     *
     * @param           mixed 			$date
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsAddedAfter($date, $sortOrder = null, $limit = null){
        return $this->listProductsAdded($date, 'after', $sortOrder, $limit);
    }

	/**
	 * @name            listProductsAddedBefore()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProductsAdded()
	 *
	 * @param           mixed 			$date
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsAddedBefore($date, $sortOrder = null, $limit = null){
		return $this->listProductsAdded($date, 'before', $sortOrder, $limit);
	}

    /**
     * @name            listProductsAddedBetween()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsAdded()
     *
     * @param           array 			$dates
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsAddedBetween($dates, $sortOrder = null, $limit = null){
        return $this->listProductsAdded($dates, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductsAddedOn ()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsAdded()
     *
     * @param           mixed 			$date
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsAddedOn($date, $sortOrder = null, $limit = null){
        return $this->listProductsAdded($date, 'on', $sortOrder, $limit);
    }

    /**
     * @name            listProductsInCategory ()
     *
     * @since           1.2.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->getProductCategory()
     *
     * @param           mixed 			$categories
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsInCategory(array $categories, $sortOrder = null, $limit = null){
        $timeStamp = time();
        $catIds = array();
        foreach ($categories as $category) {
            $response = $this->getProductCategory($category);
			if($response->error->exist){
				continue;
			}
			$category = $response->result->set;
			$catIds = $category->getId();
        }
        $catIds = implode(',', $catIds);

        $qStr = 'SELECT ' . $this->entity['cop']['alias'] . ', ' . $this->entity['p']['alias']
            . ' FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias']
            . ' JOIN ' . $this->entity['cop']['alias'] . '.product ' . $this->entity['p']['alias']
            . ' WHERE ' . $this->entity['cop']['alias'] . '.category IN (' . $catIds . ')';

        $oStr = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'quantity':
                    case 'price':
                    case 'sku':
                    case 'sort_order':
                    case 'date_added':
                    case 'date_updated':
                        $column = $this->entity['p']['alias'] . '.' . $column;
                        break;
                }
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
        }
		$qStr .= $oStr;
        $q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);
        $result = $q->getResult();

        $totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

    /**
     * @name            listProductsInLocales ()
     *
     * @since           1.3.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 			$locales
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsInLocales(array $locales, $sortOrder = null, $limit = null){
		$timeStamp = time();
        $langIds = array();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        foreach ($locales as $locale) {
            $response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				break;
			}
			$locale = $response->result->set;
			$langIds[] = $locale;
        }
        $langIds = implode(',', $langIds);

        $qStr = 'SELECT ' . $this->entity['apl']['alias'] . ', ' . $this->entity['l']['alias'] . ', ' . $this->entity['p']['alias']
            . ' FROM ' . $this->entity['apl']['name'] . ' ' . $this->entity['apl']['alias']
            . ' JOIN ' . $this->entity['apl']['alias'] . '.product ' . $this->entity['p']['alias']
            . ' WHERE ' . $this->entity['apl']['alias'] . '.locale IN (' . $langIds . ')';

        $oStr = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'quantity':
                    case 'price':
                    case 'sku':
                    case 'sort_order':
                    case 'date_added':
                    case 'date_updated':
                        $column = $this->entity['p']['alias'] . '.' . $column;
                        break;
                }
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
        }
        $qStr .= $oStr;
        $q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);
        $result = $q->getResult();
        $products = array();
        foreach ($result as $cop) {
            $products[] = $cop->getProduct();
        }

		$totalRows = count($products);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($products, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }

	/**
	 * @name            listProductCategoriesInLocales ()
	 *
	 * @since           1.3.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$locales
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductCategoriesInLocales (array $locales, $sortOrder = null, $limit = null){
		$timeStamp = time();
		$langIds = array();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				break;
			}
			$locale = $response->result->set;
			$langIds[] = $locale;
		}
		$langIds = implode(',', $langIds);

		$qStr = 'SELECT ' . $this->entity['apcl']['alias'] . ', ' . $this->entity['l']['alias'] . ', ' . $this->entity['p']['alias']
			. ' FROM ' . $this->entity['apcl']['name'] . ' ' . $this->entity['apcl']['alias']
			. ' JOIN ' . $this->entity['apcl']['alias'] . '.category ' . $this->entity['p']['alias']
			. ' WHERE ' . $this->entity['apcl']['alias'] . '.locale IN (' . $langIds . ')';

		$oStr = '';
		if ($sortOrder != null) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'id':
					case 'url_key':
					case 'sort_order':
					case 'date_added':
					case 'date_updated':
						$column = $this->entity['p']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}
		$qStr .= $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);
		$result = $q->getResult();

		$categories = array();
		foreach ($result as $cop) {
			$categories[] = $cop->getCategory();
		}

		$totalRows = count($categories);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($categories, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

    /**
     * @name            listProductsLiked ()
     *
     * @since           1.0.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed 			$likes
     * @param           string 			$eq 		less, more, between
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsLiked($likes, $eq, $sortOrder = null, $limit = null){
        //$eq_opts = array('less', 'more', 'between');

        $column = $this->entity['p']['alias'] . '.count_like';

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
        return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsLikedBetween ()
     *
     * @since           1.0.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsLiked()
     *
     * @param           array 			$likes
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsLikedBetween($likes, $sortOrder = null, $limit = null){
        return $this->listProductsLiked($likes, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductsLikedLessThan ()
     *
     * @since           1.0.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsLiked()
     *
     * @param           integer 		$likes
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsLikedLessThan($likes, $sortOrder = null, $limit = null){
        return $this->listProductsLiked($likes, 'less', $sortOrder, $limit);
    }

    /**
     * @name            listProductsLikedMoreThan ()
     *
     * @since           1.0.0
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsLiked()
     *
     * @param           integer 		$likes
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsLikedMoreThan($likes, $sortOrder = null, $limit = null){
        return $this->listProductsLiked($likes, 'more', $sortOrder, $limit);
    }
	/**
	 * @name            listProductsOfBrand()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProducts()
	 *
	 * @param           mixed 			$brand
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsOfBrand($brand, $sortOrder = null, $limit = null){
		$response = $this->getBrand($brand);
		if($response->error->exist){
			return $response;
		}
		$brand = $response->result->set;
		$column = $this->entity['p']['alias'] . '.brand';
		$condition = array('column' => $column, 'comparison' => '=', 'value' => $brand->getId());
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => $condition,
				)
			)
		);
		return $this->listProducts($filter, $sortOrder, $limit);
	}
    /**
     * @name            listProductsOfCategory ()
     *
     * @since           1.0.9
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->getProduct()
     *
     * @param           mixed 			$category
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsOfCategory($category, $sortOrder = null, $limit = null){
        $timeStamp = time();
		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;

        $qStr = 'SELECT ' . $this->entity['cop']['alias'] . ', ' . $this->entity['p']['alias']
            . ' FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias']
            . ' JOIN ' . $this->entity['cop']['alias'] . '.product ' . $this->entity['p']['alias']
            . ' WHERE ' . $this->entity['cop']['alias'] . '.category = ' . $category->getId();

        $oStr = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                $sorting = false;
                if (!in_array($column, array('name', 'url_key'))) {
                    $sorting = true;
                    switch ($column) {
                        case 'id':
                        case 'quantity':
                        case 'price':
                        case 'sku':
                        case 'date_added':
                        case 'date_updated':
                            $column = $this->entity['p']['alias'] . '.' . $column;
                            break;
                        case 'sort_order':
                            $column = $this->entity['cop']['alias'] . '.' . $column;
                            break;
                    }
					$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
                }
            }
            if ($sorting) {
				$oStr = rtrim($oStr, ', ');
				$oStr = ' ORDER BY ' . $oStr . ' ';
            }
        }
        $qStr .= $oStr;
        $query = $this->em->createQuery($qStr);
        $result = $query->getResult();

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
		}
		return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsOfCategoryInLocales ()
     *
     * @since           1.2.3
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           mixed 			$category
     * @param           array 			$locales
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsOfCategoryInLocales($category, array $locales, $sortOrder = null, $limit = null){
        $timeStamp = time();
        $langIds = array();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        foreach ($locales as $locale) {
            $response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				continue;
			}
			$langIds[] = $response->result->set->getId();
        }
        $langIds = implode(',', $langIds);

        $response = $this->listProductsOfCategory($category);
        if ($response->error->exist) {
            return $response;
        }
		$products = $response->result->set;
        $productIdCollection = array();
        $productCollection = array();
        foreach ($products as $productEntity) {
            $productIdCollection[] = $productEntity->getId();
            $productCollection[$productEntity->getId()] = $productEntity;
        }
        $productIdCollection = implode(',',$productIdCollection);

        $qStr = 'SELECT ' . $this->entity['apl']['alias']
            . ' FROM ' . $this->entity['apl']['name'] . ' ' . $this->entity['apl']['alias']
            . ' JOIN ' . $this->entity['apl']['alias'] . '.product ' . $this->entity['p']['alias']
            . ' WHERE ' . $this->entity['apl']['alias'] . '.locale IN (' . $langIds . ')'
            . ' AND '. $this->entity['apl']['alias'] . '.product IN (' . $productIdCollection . ')';

        $oStr = '';
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'quantity':
                    case 'price':
                    case 'sku':
                    case 'sort_order':
                    case 'date_added':
                    case 'date_updated':
                        $column = $this->entity['p']['alias'] . '.' . $column;
                        break;
                }
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
        }
		$qStr .= $oStr;
        $q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);
        $result = $q->getResult();

		$totalRows = 0;
		if(!is_null($result)){
			$products = array();
			foreach ($result as $cop) {
				$products[] = $cop->getProduct();
			}
			$totalRows = count($products);
		}

		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($products, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());


	}
    /**
     * @name            listProductsOfSite()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed 			$site
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsOfSite($site, $sortOrder = null, $limit = null){
		$SMMModel = new SMMService\SiteManagementModel($this->kernel);
        $response = $SMMModel->getSite($site);
		if($response->error->exist){
			return $response;
		}
		$site = $response->result->set;
        $column = $this->entity['p']['alias'] . '.site';
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
        return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsUpdated ()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed 			$date
     * @param           string 			$eq
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsUpdated($date, $eq, $sortOrder = null, $limit = null){
        // $eq_opts = array('after', 'before', 'between', 'on');
        $column = $this->entity['p']['alias'] . '.date_added';

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
       return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsUpdatedAfter ()
     *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsUpdated()
     *
     * @param           array 			$date
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsUpdatedAfter($date, $sortOrder = null, $limit = null){
        return $this->listProductsUpdated($date, 'after', $sortOrder, $limit);
    }

    /**
     * @name            listProductsUpdatedBefore ()
	 *
     * @since           1.0.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsUpdated()
     *
     * @param           array 			$date
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsUpdatedBefore($date, $sortOrder = null, $limit = null){
        return $this->listProductsUpdated($date, 'before', $sortOrder, $limit);
    }

	/**
	 * @name            listProductsUpdatedBetween()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProductsUpdated()
	 *
	 * @param           array 			$date
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsUpdatedBetween($date, $sortOrder = null, $limit = null){
		return $this->listProductsUpdated($date, 'between', $sortOrder, $limit);
	}

	/**
	 * @name            listProductsUpdatedOn()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProductsUpdated()
	 *
	 * @param           array 			$date
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsUpdatedOn($date, $sortOrder = null, $limit = null){
		return $this->listProductsUpdated($date, 'on', $sortOrder, $limit);
	}

    /**
     * @name            listProductsWithPrice ()
     *
     * @since           1.0.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           decimal 		$price
     * @param           string 			$eq 				after, before, between
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsWithPrice($price, $eq, $sortOrder = null, $limit = null){
       // $eq_opts = array('more', 'less', 'between');
        $column = $this->entity['p']['alias'] . '.price';

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
        return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithQuantity()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           integer 		$quantity
     * @param           string 			$eq 			after, before, between
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsWithQuantities($quantity, $eq, $sortOrder = null, $limit = null){
        //$eq_opts = array('more', 'less', 'between');

        $column = $this->entity['p']['alias'] . '.quantity';

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
        return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithQuantityBetween()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsWithQuantities()
     *
     * @param           array 			$quantities
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsWithQuantityBetween($quantities, $sortOrder = null, $limit = null){
        return $this->listProductsWithQuantity($quantities, 'between', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithQuantitiesLessThan ()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsQuantities()
     *
     * @param           integer 		$quantity
     * @param           array 			$sortOrder
     * @param           array			$limit
     *
     * @return          array           $response
     */
    public function listProductsWithQuantityLessThan($quantity, $sortOrder = null, $limit = null){
        return $this->listProductsWithQuantity($quantity, 'less', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithQuantitiesMoreThan ()
     *
     * @since           1.0.5
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsQuantities()
     *
     * @param           integer 	$quantity
     * @param           array 		$sortOrder
     * @param           array 		$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsWithQuantitiesMoreThan($quantity, $sortOrder = null, $limit = null){
        return $this->listProductsWithQuantities($quantity, 'more', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithPriceLessThan()
     *
     * @since           1.0.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsPriced()
     *
     * @param           decimal 	$amount
	 * @param           array 		$sortOrder
	 * @param           array 		$limit
     *
     * @return         BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsWithPriceLessThan($amount, $sortOrder = null, $limit = null){
        return $this->listProductsPriced($amount, 'less', $sortOrder, $limit);
    }

    /**
     * @name            listProductsWithPriceMoreThan ()
     *
     * @since           1.0.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsPriced()
     *
     * @param           decimal 	$amount
	 * @param           array 		$sortOrder
	 * @param           array 		$limit
     *
     * @return         	BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsWithPriceMoreThan($amount, $sortOrder = null, $limit = null){
        return $this->listProductsPriced($amount, 'more', $sortOrder, $limit);
    }

    /**
     * @name            listProductsViewed ()
     *
     * @since           1.0.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed 			$views
     * @param           string 			$eq
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsViewed($views, $eq, $sortOrder = null, $limit = null){
        //$eq_opts = array('less', 'more', 'between');
        $column = $this->entity['p']['alias'] . '.count_view';

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
        return $this->listProducts($filter, $sortOrder, $limit);
    }

    /**
     * @name            listProductsViewedBetween()
     *
     * @since           1.0.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @uses            $this->listProductsViewed()
     *
     * @param           array 			$views
     * @param           array 			$sortOrder
     * @param           array 			$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listProductsViewedBetween($views, $sortOrder = null, $limit = null){
        return $this->listProductsViewed($views, 'between', $sortOrder, $limit);
    }

	/**
	 * @name            listProductsViewedLessThan()
	 *
	 * @since           1.0.6
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProductsViewed()
	 *
	 * @param           array 			$views
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
    public function listProductsViewedLessThan($views, $sortOrder = null, $limit = null){
        return $this->listProductsViewed($views, 'less', $sortOrder, $limit);
    }

	/**
	 * @name            listProductsViewedMoreThan()
	 *
	 * @since           1.0.6
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProductsViewed()
	 *
	 * @param           array 			$views
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
    public function listProductsViewedMoreThan($views, $sortOrder = null, $limit = null){
        return $this->listProductsViewed($views, 'more', $sortOrder, $limit);
    }

    /**
     * @name            listRelatedProductsOfProduct ()
     *
     * @since           1.3.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->listProducts()
     * @use             $this->createException
     *
     * @param           mixed 		$product (id, sku, or object)
     * @param           array 		$sortOrder
     * @param           array 		$limit
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listRelatedProductsOfProduct($product, $sortOrder = null, $limit = null){
        $timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $qStr = 'SELECT ' . $this->entity['rp']['alias'] . ' FROM ' . $this->entity['rp']['name'] . ' ' . $this->entity['rp']['alias']
            . ' WHERE ' . $this->entity['rp']['alias'] . '.product = ' . $product->getId();

        $q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

        $result = $q->getResult();
        $totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
        $relatedProducts = array();
        $relatedProductIds = array();
        foreach ($result as $rpObject) {
            $relatedProduct = $rpObject->getRelatedProduct();
            $relatedProducts[] = $relatedProduct;
            $relatedProductIds[] = $relatedProduct->getId();
        }


        $column = $this->entity['p']['alias'] . '.id';
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
        return $this->listProducts($filter, $sortOrder, $limit);
	}

    /**
     * @name            listValuesOfProductAttributes ()
     *
     * @since           1.2.4
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           mixed 			$attribute
     * @param           mixed 			$product
     * @param           mixed 			$language
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function listValuesOfProductAttributes($product, $attribute, $language){
        $timeStamp = time();
        $response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

		$response = $this->getProductAttribute($attribute);
		if($response->error->exist){
			return $response;
		}
		$ættribute = $response->result->set;

		$MLSModel = new MLSService\MultiLanguageSupportModel($this->kernel, $this->dbConnection, $this->orm);

		$response = $MLSModel->getLanguage($language);
		if($response->error->exist){
			return $response;
		}
		$language = $response->result->set;

        $qStr = 'SELECT DISTINCT ' . $this->entity['pav']['alias'] . ', ' . $this->entity['pa']['alias']
            . ' FROM ' . $this->entity['pav']['name'] . ' ' . $this->entity['pav']['alias']
            . ' JOIN ' . $this->entity['pav']['alias'] . '.attribute ' . $this->entity['pa']['alias']
            . ' WHERE ' . $this->entity['pav']['alias'] . '.product = ' . $product->getId()
            . ' AND ' . $this->entity['pav']['alias'] . '.attribute = ' . $attribute->getId();

        $query = $this->em->createQuery($qStr);

        $result = $query->getResult();

        $totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
    }
	/**
	 * @name            listVolumePricings()
	 *
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$filter
	 * @param           array 			$sortOrder
	 * @param           array 			$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricings($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['vp']['alias'].', '.$this->entity['vp']['alias']
			.' FROM '.$this->entity['vp']['name'].' '.$this->entity['vp']['alias'];

		if(!is_null($sortOrder)){
			foreach($sortOrder as $column => $direction){
				switch ($column) {
					case 'id':
					case 'quantity_limit':
					case 'price':
					case 'discounted_price':
					case 'sort_order':
					case 'date_added':
					case 'date_updated':
						$column = $this->entity['vp']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if(!is_null($filter)){
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = array();
		foreach($result as $entry){
			$id = $entry->getVolumePricing()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getVolumePricing();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            listVolumePricingsOfProduct()
	 *
	 * @since           1.3.2
	 * @version         1.5.3
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param   		mixed 		$product
	 * @param   		array 		$filter
	 * @param   		array 		$sortOrder
	 * @param   		array 		$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricingsOfProduct($product, $filter = array(), $sortOrder = null, $limit = null){
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->getEntityDefinition('vp', 'alias') . '.product', 'comparison' => '=', 'value' => $product->getId()),
				)
			)
		);
		return $this->listVolumePricings($filter, $sortOrder, $limit);
	}
	/**
	 * @name            BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 *
	 * @since           1.3.2
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listVolumePricingsOfProductWithQuantityLowerThan()
	 *
	 * @param   		mixed 			$product
	 * @param   		integer			$quantity
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricingsOfProductWithClosestQuantity($product, $quantity){
		return $this->listVolumePricingsOfProductWithQuantityLowerThan($product, $quantity, null, array('quantity_limit' => 'desc'), array('start' => 0, 'count' => 1));
	}
	/**
	 * @name            listVolumePricingsOfProductWithQuantityGreaterThan()
	 *
	 * @since           1.3.2
	 * @version         1.5.3
	 *
	 * @author          Can Berlpş
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param   		mixed 		$product
	 * @param   		int 		$quantity
	 * @param   		array 		$sortOrder
	 * @param   		array 		$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricingsOfProductWithQuantityGreaterThan($product, $quantity, $sortOrder = null, $limit = null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->getEntityDefinition('vp', 'alias') . '.quantity_limit', 'comparison' => '>=', 'value' => $quantity),
				),
			)
		);
		return $this->listVolumePricingsOfProduct($product, $filter, $sortOrder, $limit);
	}

	/**
	 * @name            listPricingsOfProductWithType ()
	 *
	 * @since           1.3.2
	 * @version         1.5.3
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param   		mixed 		$product
	 * @param   		int 		$quantity
	 * @param   		array 		$sortOrder
	 * @param   		array 		$limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricingsOfProductWithQuantityLowerThan($product, $quantity, $sortOrder = null, $limit = null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->getEntityDefinition('vp', 'alias') . '.quantity_limit', 'comparison' => '=<', 'value' => $quantity),
				),
			)
		);
		return $this->listVolumePricingsOfProduct($product, $filter, $sortOrder, $limit);
	}
    /**
     * @name            markCategoriesAsFeatured()
     *
     * @since           1.3.1
     * @version         1.5.3
	 *
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $categories array of ids or entities.
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function markCategoriesAsFeatured($categories){
        $timeStamp = time();
        $catIds = array();
        foreach ($categories as $category) {
           $response = $this->getProductCategory($category);
			if($response->error->exist){
				continue;
			}
			$catIds[] = $response->result->set->getId();
        }
        $catIds = implode(',', $catIds);
        if (count($catIds) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
        }
        $qStr = 'UPDATE ' . $this->entity['pc']['name'] . ' ' . $this->entity['pc']['alias']
            . ' SET ' . $this->entity['pc']['alias'] . '.is_featured = \'y\''
            . ' WHERE ' . $this->entity['pc']['alias'] . '.id IN(' . $catIds . ')';

        $query = $this->em->createQuery($qStr);
        $result = $query->getResult();

		return new ModelResponse($result, count($catIds), 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
    }

    /**
     * @name            relateProductsWithProduct()
     *
     * @since           1.3.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @param           array 		$collection
     * @param           mixed 		$product (id, sku, or object)
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function relateProductsWithProduct($collection, $product){
        $timeStamp = time();
        $response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $countRelated = 0;
        $relatedProducts = array();
        foreach ($collection as $item) {
			$response = $this->getProduct($item);
			if($response->error->exist){
				continue;
			}
			$relatedProduct = $response->result->set;
            $relatedProductEntry = new BundleEntity\RelatedProduct;
			$relatedProductEntry->setProduct($product);
			$relatedProductEntry->setRelatedProduct($relatedProduct);
            $this->em->persist($relatedProduct);
            $relatedProducts[] = $relatedProduct;
            unset($relatedProduct, $response);
            $countRelated++;
        }

        if ($countRelated > 0) {
            $this->em->flush();
        }
		return new ModelResponse($relatedProducts, $countRelated, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
    }

	/**
	 * @name            removeCategoriesFromProduct ()
	 *
	 * @since           1.0.6
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @param           array 			$categories
	 * @param           mixed			$product
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeCategoriesFromProduct($categories, $product){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		$idsToRemove = array();
		foreach ($categories as $category) {
			$response = $this->getProductCategory($category);
			if($response->error->exist){
				return $response;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM '.$this->entity['cop']['name'].' '.$this->entity['cop']['alias']
			.' WHERE '.$this->entity['cop']['alias'].'.product '.$product->getId()
			.' AND '.$this->entity['cop']['alias'].'.category '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}

	/**
	 * @name            removeFilesFromProduct ()
	 *
	 * @since           1.0.6
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @param           array 			$files
	 * @param           mixed			$product
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeFilesFromProduct($files, $product){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		$idsToRemove = array();
		$fmModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);
		foreach ($files as $file) {
			$response = $fmModel->getFile($file);
			if($response->error->exist){
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM '.$this->entity['fop']['name'].' '.$this->entity['fop']['alias']
			.' WHERE '.$this->entity['fop']['alias'].'.product '.$product->getId()
			.' AND '.$this->entity['fop']['alias'].'.file '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}

	/**
	 * @name            removeLocalesFromProduct ()
	 *
	 * @since           1.2.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @param           array 			$locales
	 * @param           mixed			$product
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeLocalesFromProduct($locales, $product){
		$timeStamp = time();
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		$idsToRemove = array();
		$mModel = new MLSService\MultiLanguageSupportModel($this->kernel, $this->dbConnection, $this->orm);
		foreach ($locales as $locale) {
			$response = $mModel->getLanguage($locale);
			if($response->error->exist){
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM '.$this->entity['apl']['name'].' '.$this->entity['fop']['alias']
			.' WHERE '.$this->entity['apl']['alias'].'.product '.$product->getId()
			.' AND '.$this->entity['apl']['alias'].'.language '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}

	/**
	 * @name            removeProductsFromCategory()
	 *
	 * @since           1.2.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @param           array 			$products
	 * @param           mixed			$category
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeProductsFromCategory($products, $category){
		$timeStamp = time();
		$response = $this->getProductCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$idsToRemove = array();
		foreach ($products as $product) {
			$response = $this->getProductCategory($category);
			if($response->error->exist){
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM '.$this->entity['apcl']['name'].' '.$this->entity['apl']['alias']
			.' WHERE '.$this->entity['apcl']['alias'].'.category '.$category->getId()
			.' AND '.$this->entity['apcl']['alias'].'.language '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}

    /**
     * @name            removeProductCategoriesFromLocale ()
     *
     * @since           1.2.3
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->doesProductExist()
     *
     * @param           array 			$categories
     * @param           mixed 			$locale
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function removeProductCategoriesFromLocale($categories, $locale){
        $timeStamp = time();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if($response->error->exist){
			return $response;
		}
		$locale = $response->result->set;
		$toRemove = array();
        $count = 0;
        /** Start persisting files */

        foreach ($categories as $category) {
            $response = $this->getProductCategory($category);
			if($response->error->exist){
				continue;
			}
			$toRemove[] = $response->result->set->getId();
            $count++;
        }
        if ($count > 0) {
            $ids = implode(',', $toRemove);
            $table = $this->entity['apcl']['name'] . ' ' . $this->entity['apcl']['alias'];
            $qStr = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['apcl']['alias'] . '.locale = ' . $locale->getId()
                . ' AND ' . $this->entity['apcl']['alias'] . '.category IN(' . $ids . ')';

            $query = $this->em->createQuery($qStr);
            $query->getResult();
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}

	/**
	 * @name            removeProductsFromLocale ()
	 *
	 * @since           1.2.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @param           array 			$products
	 * @param           mixed 			$locale
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeProductsFromLocale($products, $locale){
		$timeStamp = time();
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if($response->error->exist){
			return $response;
		}
		$locale = $response->result->set;
		$toRemove = array();
		$count = 0;
		foreach ($products as $product) {
			$response = $this->getProduct($product);
			if($response->error->exist){
				continue;
			}
			$toRemove[] = $response->result->set->getId();
			$count++;
		}
		if ($count > 0) {
			$ids = implode(',', $toRemove);
			$table = $this->entity['apl']['name'] . ' ' . $this->entity['apl']['alias'];
			$qStr = 'DELETE FROM ' . $table
				. ' WHERE ' . $this->entity['apl']['alias'] . '.locale = ' . $locale->getId()
				. ' AND ' . $this->entity['apl']['alias'] . '.category IN(' . $ids . ')';

			$query = $this->em->createQuery($qStr);
			$query->getResult();
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}
	/**
	 * @name            unmarkCategoriesAsFeatured()
	 *
	 * @since           1.3.1
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$categories
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function unmarkCategoriesAsFeatured($categories) {
		$timeStamp = time();
		$catIds = array();
		foreach ($categories as $category) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
				return $response;
			}
			$catIds[] = $response->result->set->getId();
		}
		$catIds = implode(',', $catIds);
		if (count($catIds) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		$qStr = 'UPDATE ' . $this->entity['pc']['name'] . ' ' . $this->entity['pc']['alias']
			. ' SET ' . $this->entity['pc']['alias'] . '.is_featured = \'n\''
			. ' WHERE ' . $this->entity['pc']['alias'] . '.id IN(' . $catIds . ')';

		$query = $this->em->createQuery($qStr);
		$result = $query->getResult();

		return new ModelResponse($result, count($catIds), 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
	}
    /**
     * @name            unrelateProductsFromProduct ()
     *
     * @since           1.3.4
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @param           array 		$collection
     * @param           mixed 		$product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function unrelateProductsFromProduct($collection, $product){
        $timeStamp = time();
        $response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
        $countUnrelated = 0;
        $unrelatedProductIds = array();
        foreach ($collection as $item) {
			$response = $this->getProduct($item);
			if($response->error->exist){
				continue;
			}
            $unrelatedProductIds[] = $response->result->set->getId();

            $countUnrelated++;
        }
        $inContent = implode(',', $unrelatedProductIds);
        $qStr = 'DELETE FROM ' . $this->entity['rp']['name'] . ' ' . $this->entity['rp']['alias']
            . ' WHERE ' . $this->entity['rp']['alias'] . '.product = ' . $product->getId()
            . ' AND ' . $this->entity['rp']['alias'] . '.related_product IN(' . $inContent . ')';

        $query = $this->em->createQuery($qStr);

        $query->getResult();

		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
    }
	/**
	 * @name            updateBrand ()
	 *
	 * @since           1.0.6
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->updateBrands()
	 *
	 * @param           mixed 			$brand
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateBrand($brand){
		return $this->updateBrands(array($brand));
	}

	/**
	 * @name            updateBrands()
	 *
	 * @since           1.0.6
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateBrands($collection){
		$timeStamp = time();
		$countUpdates = 0;
		$updatedItems = array();
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
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
					$data->date_updated = $now;
				}
				if (property_exists($data, 'date_added')) {
					unset($data->date_added);
				}
				$response = $this->getBrand($data->id);
				if ($response->error->exist) {
					return $this->createException('EntityDoesNotExist', 'Brand with id ' . $data->id, 'err.invalid.entity');
				}
				$oldEntity = $response->result->set;
				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
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
			}
		}
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}
    /**
     * @name            updateProduct ()
     *
     * @since           1.0.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->updateProducts()
     *
     * @param           mixed 			$product
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateProduct($product){
        return $this->updateProducts(array($product));
    }

    /**
     * @name            updateProductAttribute ()
     *
     * @since           1.0.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->updateProductAttributes()
     *
     * @param           mixed 			$attribute
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateProductAttribute($attribute){
        return $this->updateProductAttributes(array($attribute));
    }

    /**
     * @name            updateProductAttributes()
     *
     * @since           1.0.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateProductAttributes($collection){
        $timeStamp = time();
        $countUpdates = 0;
        $updatedItems = array();
        $localizations = array();
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
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
                    $data->date_updated = $now;
                }
                if (property_exists($data, 'date_added')) {
                    unset($data->date_added);
                }
                if (!property_exists($data, 'site')) {
                    $data->site = 1;
                }
                $response = $this->getProductAttribute($data->id);
                if ($response->error->exist) {
                    return $this->createException('EntityDoesNotExist', 'ProductAttribute with id ' . $data->id, 'err.invalid.entity');
                }
                $oldEntity = $response->result->set;
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
                                    $response = $mlsModel->getLanguage($langCode);
                                    $localization->setLanguage($response->result->set);
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
                            $response = $sModel->getSite($value);
                            if (!$response->error->exist) {
                                $oldEntity->$set($response->result->set);
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
            }
        }
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}

    /**
     * @name            updateProductAttributeValue ()
     *
     * @since           1.1.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->updateProductAttributeValues()
     *
     * @param           mixed 			$data
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateProductAttributeValue($data) {
        return $this->updateProductAttributeValues(array($data));
    }

    /**
     * @name            updateProductAttributeValues()
     *
     * @since           1.1.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateProductAttributeValues($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
        $countUpdates = 0;
        $updatedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\ProductAttributeValues) {
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            }
			else if (is_object($data)) {
				if(!property_exists($data, 'id') || !is_numeric($data->id)){
					return $this->createException('InvalidParameterException', 'Parameter must be an object with the "id" property and id property ​must have an integer value.', 'E:S:003');
				}
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (property_exists($data, 'date_added')) {
                    unset($data->date_added);
                }
                $response = $this->getProductAttributeValue($data->id);
				if ($response->error->exist) {
					return $this->createException('EntityDoesNotExist', 'Attribute value '.$data->id.' does not exist in database.', 'E:D:002');
				}
                $oldEntity = $response->result->set;

                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'attribute':
                            $response = $this->getProductAttribute($value);
                            if ($response->error->exist) {
                                return $response;
                            }
							$oldEntity->$set($response->result->set);
                            unset($response);
                            break;
                        case 'language':
                            $lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                            $response = $lModel->getLanguage($value);
                            if ($response->error->exist) {
                                return $response;
                            }
							$oldEntity->$set($response->result->set);
                            unset($response, $lModel);
                            break;
                        case 'product':
                            $response = $this->getProduct($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
                            unset($response);
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
            }
        }
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}

    /**
     * @name            updateProductCategory()
     *
     * @since           1.0.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->updateProductCategories()
     *
     * @param           mixed 			$category
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateProductCategory($category){
        return $this->updateProductCategories(array($category));
    }

    /**
     * @name            updateProductCategories ()
     *
     * @since           1.0.6
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateProductCategories($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
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
            }
			else if (is_object($data)) {
				if(!property_exists($data, 'id') || !is_numeric($data->id)){
					return $this->createException('InvalidParameterException', 'Parameter must be an object with the "id" property and id property ​must have an integer value.', 'E:S:003');
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
                $response = $this->getProductCategory($data->id);
				if ($response->error->exist) {
					return $this->createException('EntityDoesNotExist', 'Category with id / url_key '.$data->id.' does not exist in database.', 'E:D:002');
				}
				$oldEntity = $response->result->set;
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'local':
							foreach ($value as $langCode => $translation) {
								$localization = $oldEntity->getLocalization($langCode, true);
								$newLocalization = false;
								if (!$localization) {
									$newLocalization = true;
									$localization = new BundleEntity\ProductCategoryLocalization();
									$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
									$response = $mlsModel->getLanguage($langCode);
									$localization->setLanguage($response->result->set);
									$localization->setCategory($oldEntity);
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
                                $response = $this->getProductCategory($value);
                                if ($response->error->exist) {
                                   return $response;
                                }
								$oldEntity->$set($response->result->set);
                            } else {
                                $oldEntity->$set(null);
                            }
                            unset($response);
                            break;
                        case 'preview_image':
                            if (!is_null($value)) {
                                $fModel = $this->kernel->getContainer()->get('filemanagement.model');
                                $response = $fModel->getFile($value);
								if ($response->error->exist) {
									return $response;
								}
								$oldEntity->$set($response->result->set);
                                unset($response, $fModel);
                            }
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
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
            }
        }
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}

    /**
     * @name            updateProducts()
     *
     * @since           1.0.1
     * @version         1.5.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array 			$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateProducts($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
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
				if(!property_exists($data, 'id') || !is_numeric($data->id)){
					return $this->createException('InvalidParameterException', 'Parameter must be an object with the "id" property and id property ​must have an integer value.', 'E:S:003');
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
				$response = $this->getProduct($data->id);
				if ($response->error->exist) {
					return $this->createException('EntityDoesNotExist', 'Product with id / url_key / sku  '.$data->id.' does not exist in database.', 'E:D:002');
				}
				$oldEntity = $response->result->set;
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
                                    $response = $mlsModel->getLanguage($langCode);
                                    $localization->setLanguage($response->result->set);
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
                            $response = $sModel->getSite($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
                            unset($response, $sModel);
                            break;
                        case 'preview_file':
                            $fModel = $this->kernel->getContainer()->get('filemanagement.model');
                            $response = $fModel->getFile($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
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
            }
        }
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}

    /**
     * @name            updateVolumePricing ()
     *
     * @since           1.3.2
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->updateProductAttributeValues()
     *
     * @param           mixed 			$volumePricing
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateVolumePricing($volumePricing){
        return $this->updateVolumePricings(array($volumePricing));
    }

    /**
     * @name            updateVolumePricings ()
     *
     * @since           1.3.2
     * @version         1.5.3
	 *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array 		$collection
     *
     * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
     */
    public function updateVolumePricings($collection) {
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = array();
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\VolumePricing) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			}
			else if (is_object($data)) {
				if (!property_exists($data, 'id') || !is_numeric($data->id)) {
					return $this->createException('InvalidParameterException', 'Parameter must be an object with the "id" property and id property ​must have an integer value.', 'E:S:003');
				}
				if (!property_exists($data, 'date_updated')) {
					$data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
				}
				if (property_exists($data, 'date_added')) {
					unset($data->date_added);
				}
				$response = $this->getVolumePricing($data->id);
				if ($response->error->exist) {
					return $this->createException('EntityDoesNotExist', 'Attribute value ' . $data->id . ' does not exist in database.', 'E:D:002');
				}
				$oldEntity = $response->result->set;

				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'product':
							$response = $this->getProduct($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
							unset($response);
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
			}
		}
		if ($countUpdates > 0) {
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}

		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}
}

/**
 * Change Log
 * **************************************
 * v1.5.4                      25.05.2015
 * Can Berkol
 * **************************************
 * BF :: db_connection is replaced with dbConnection
 *
 * **************************************
 * v1.5.3                      12.05.2015
 * Can Berkol
 * **************************************
 * CR :: Made compatible with Core 3.3.
 *
 * **************************************
 * v1.5.2                      Can Berkol
 * 18.03.2015
 * **************************************
 * A listActiveProductsOfCategory()
 *
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
 */