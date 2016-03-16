<?php
/**
 * @author		Can Berkol
 * @author		Said İmamoğlu
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        23.12.2015
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Services;


use BiberLtd\Bundle\CoreBundle\CoreModel;
use BiberLtd\Bundle\CoreBundle\Responses\ModelResponse;
use BiberLtd\Bundle\ProductManagementBundle\Entity as BundleEntity;
use BiberLtd\Bundle\FileManagementBundle\Entity as FileBundleEntity;
use BiberLtd\Bundle\MultiLanguageSupportBundle\Entity as MLSEntity;
use BiberLtd\Bundle\SiteManagementBundle\Entity as SiteManagementEntity;
use BiberLtd\Bundle\FileManagementBundle\Services as FMMService;
use BiberLtd\Bundle\MultiLanguageSupportBundle\Services as MLSService;
use BiberLtd\Bundle\SiteManagementBundle\Services as SMMService;
use BiberLtd\Bundle\CoreBundle\Services as CoreServices;
use BiberLtd\Bundle\CoreBundle\Exceptions as CoreExceptions;
use BiberLtd\Bundle\StockManagementBundle\Services\StockManagementModel;

class ProductManagementModel extends CoreModel
{
	/**
	 * ProductManagementModel constructor.
	 *
	 * @param object $kernel
	 * @param string $dbConnection
	 * @param string $orm
	 */
	public function __construct($kernel, string $dbConnection = 'default', string $orm = 'doctrine')
	{
		parent::__construct($kernel, $dbConnection, $orm);

		$this->entity = array(
			'apl' => array('name' => 'ProductManagementBundle:ActiveProductLocale', 'alias' => 'apl'),
			'apcl' => array('name' => 'ProductManagementBundle:ActiveProductCategoryLocale', 'alias' => 'apcl'),
			'aop' => array('name' => 'ProductManagementBundle:AttributesOfProduct', 'alias' => 'aop'),
			'aopc' => array('name' => 'ProductManagementBundle:AttributesOfProductCategory', 'alias' => 'aopc'),
			'b' => array('name' => 'ProductManagementBundle:Brand', 'alias' => 'b'),
			'cop' => array('name' => 'ProductManagementBundle:CategoriesOfProduct', 'alias' => 'cop'),
			'f' => array('name' => 'FileManagementBundle:File', 'alias' => 'f'),
			'fop' => array('name' => 'ProductManagementBundle:FilesOfProduct', 'alias' => 'fop'),
			'l' => array('name' => 'MultiLanguageSupportBundle:Language', 'alias' => 'l'),
			'p' => array('name' => 'ProductManagementBundle:Product', 'alias' => 'p'),
			'pa' => array('name' => 'ProductManagementBundle:ProductAttribute', 'alias' => 'pa'),
			'pal' => array('name' => 'ProductManagementBundle:ProductAttributeLocalization', 'alias' => 'pal'),
			'pav' => array('name' => 'ProductManagementBundle:ProductAttributeValues', 'alias' => 'pav'),
			'pc' => array('name' => 'ProductManagementBundle:ProductCategory', 'alias' => 'pc'),
			'pcl' => array('name' => 'ProductManagementBundle:ProductCategoryLocalization', 'alias' => 'pcl'),
			'pl' => array('name' => 'ProductManagementBundle:ProductLocalization', 'alias' => 'pl'),
			'pos' => array('name' => 'ProductManagementBundle:ProductsOfSite', 'alias' => 'pos'),
			'pukh' => array('name' => 'ProductManagementBundle:ProductUrlKeyHistory', 'alias' => 'pukh'),
			'rp' => array('name' => 'ProductManagementBundle:RelatedProduct', 'alias' => 'rp'),
			'vp' => array('name' => 'ProductManagementBundle:VolumePricing', 'alias' => 'vp'),
		);
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		foreach ($this as $property => $value) {
			$this->$property = null;
		}
	}

	/**
	 * @param array $collection
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addAttributesToProduct(array $collection, $product)
	{
		$timeStamp = microtime(true);

		$validAttributes = [];
		foreach ($collection as $attr) {
			$response = $this->getProductAttribute($attr['attribute']);
			if (!$response->error->exist) {
				$validAttributes[$response->result->set->getId()]['attr'] = $response->result->set;
				$validAttributes[$response->result->set->getId()]['sort_order'] = $attr['sort_order'];
			}
		}
		unset($collection);
		/** issue an error only if there is no valid file entries */
		if (count($validAttributes) < 1) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. $collection parameter must be an array collection', 'E:S:001');
		}
		unset($count);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		$aopCcollection = [];
		$count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($validAttributes as $item) {
			/** If no entity is provided as product we need to check if it does exist */
			$aopCollection = [];
			/** Check if association exists */
			if (!$this->isAttributeAssociatedWithProduct($item['attr'], $product, true)) {
				$aop = new BundleEntity\AttributesOfProduct();
				$aop->setAttribute($item['attr'])->setProduct($product)->setDateAdded($now)->setPriceFactorType('a');
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
		if ($count > 0) {
			$this->em->flush();
			return new ModelResponse($aopCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addFilesToProduct(array $collection, $product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$fileModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);

		$fopCollection = [];
		$count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $file) {
			$response = $fileModel->getFile($file['file']);
			if ($response->error->exist) {
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

		if ($count > 0) {
			$this->em->flush();
			return new ModelResponse($fopCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $locales
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addLocalesToProduct(array $locales, $product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		if (count($locales) < 1) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. $locales parameter must be an array collection', 'E:S:001');
		}

		$aplCollection = [];
		$count = 0;
		/** Start persisting locales */
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if ($response->error->exist) {
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
		if ($count > 0) {
			$this->em->flush();
			return new ModelResponse($aplCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $locales
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addLocalesToProductCategory(array $locales, $category)
	{
		$timeStamp = microtime(true);
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;

		if (count($locales) < 1) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. $locales parameter must be an array collection', 'E:S:001');
		}

		$aplCollection = [];
		$count = 0;
		/** Start persisting locales */
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if ($response->error->exist) {
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
		if ($count > 0) {
			$this->em->flush();
			return new ModelResponse($aplCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addProductsToCategory(array $collection, $category)
	{
		$timeStamp = microtime(true);
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;
		$copCollection = [];
		$count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $product) {
			$response = $this->getProduct($product['product']);
			if ($response->error->exist) {
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
		if ($count > 0) {
			$this->em->flush();
			return new ModelResponse($copCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addProductToCategories($product, array $collection){
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$productCollection = [];
		$count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $category) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
				continue;
			}
			$category = $response->result->set;
			if ($this->isProductAssociatedWithCategory($product, $category, true)) {
				continue;
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
		if ($count > 0) {
			$this->em->flush();
			return new ModelResponse($productCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $categories
	 * @param mixed $locale
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addProductCategoriesToLocale(array $categories, $locale)
	{
		$timeStamp = microtime(true);
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if ($response->error->exist) {
			return $response;
		}
		$locale = $response->result->set;
		$aplCollection = [];
		$count = 0;
		/** Start persisting locales */
		foreach ($categories as $category) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
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
		if ($count > 0) {
			$this->em->flush();
			return new ModelResponse($aplCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $products
	 * @param mixed $locale
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addProductsToLocale(array $products, $locale)
	{
		$timeStamp = microtime(true);
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if ($response->error->exist) {
			return $response;
		}
		$locale = $response->result->set;
		$aplCollection = [];
		$count = 0;
		/** Start persisting locales */
		foreach ($products as $product) {
			$response = $this->getProduct($product);
			if ($response->error->exist) {
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
		if ($count > 0) {
			$this->em->flush();
			return new ModelResponse($aplCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $filter
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function countProducts(array $filter = null)
	{
		$timeStamp = microtime(true);
		$wStr = '';
		$qStr = 'SELECT COUNT(' . $this->entity['p']['alias'] . ')'
			. ' FROM ' . $this->entity['p']['name'] . ' ' . $this->entity['p']['alias'];

		if ($filter != null) {
			$filterStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $filterStr;
		}

		$qStr .= $wStr;
		$q = $this->em->createQuery($qStr);
		$result = $q->getSingleScalarResult();
		$count = 0;
		if (!$result) {
			$count = $result;
		}
		return new ModelResponse($count, 1, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 * @param array|null $filter
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function countProductsOfCategory($category, array $filter = null){
		$timeStamp = microtime(true);

		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;
		$wStr = '';

		$qStr = 'SELECT COUNT(' . $this->entity['cop']['alias'] . '.product)'
			. ' FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias'];

		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['cop']['alias'] . '.category', 'comparison' => '=', 'value' => $category->getId()),
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
		if (!$result) {
			$count = $result;
		}
		return new ModelResponse($count, 1, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $brand
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteBrand($brand)
	{
		return $this->deleteBrands(array($brand));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteBrands(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach ($collection as $entry) {
			if ($entry instanceof BundleEntity\Brand) {
				$this->em->remove($entry);
				$countDeleted++;
			} else {
				$response = $this->getBrand($entry);
				if (!$response->error->exist) {
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if ($countDeleted < 0) {
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $brand
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesBrandExist($brand, bool $bypass = false)
	{
		$response = $this->getBrand($brand);
		$exist = true;
		if ($response->error->exist) {
			$exist = false;
			$response->result->set = false;
		}
		if ($bypass) {
			return $exist;
		}
		return $response;
	}

	/**
	 * @param mixed $attribute
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProductAttribute($attribute)
	{
		return $this->deleteProductAttributes(array($attribute));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProductAttributes(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach ($collection as $entry) {
			if ($entry instanceof BundleEntity\ProductAttribute) {
				$this->em->remove($entry);
				$countDeleted++;
			} else {
				$response = $this->getProductAttribute($entry);
				if (!$response->error->exist) {
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if ($countDeleted < 0) {
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $attribute
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteAllAttributeValuesOfProductByAttribute($attribute, $product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProductAttribute($attribute);
		if ($response->error->exist) {
			return $response;
		}
		$attribute = $response->result->set;
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$qStr = 'DELETE FROM ' . $this->entity['pav']['name'] . ' ' . $this->entity['pav']['alias']
			. ' WHERE ' . $this->entity['pav']['alias'] . '.attribute = ' . $attribute->getId()
			. ' AND ' . $this->entity['pav']['alias'] . '.product = ' . $product->getId();
		$query = $this->em->createQuery($qStr);
		$query->getResult();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProductCategory($category)
	{
		return $this->deleteProductCategories(array($category));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProductCategories(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach ($collection as $entry) {
			if ($entry instanceof BundleEntity\ProductCategory) {
				$this->em->remove($entry);
				$countDeleted++;
			} else {
				$response = $this->getProductCategory($entry);
				if (!$response->error->exist) {
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if ($countDeleted < 0) {
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @name            deleteProduct ()
	 * @since           1.0.2
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @use                $this->deleteProductCategories()
	 *
	 * @param           mixed $product
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProduct($product)
	{
		return $this->deleteProducts(array($product));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteProducts(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach ($collection as $entry) {
			if ($entry instanceof BundleEntity\Product) {
				$this->em->remove($entry);
				$countDeleted++;
			} else {
				$response = $this->getProduct($entry);
				if (!$response->error->exist) {
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if ($countDeleted < 0) {
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\VolumePricing $pricing
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteVolumePricing(\BiberLtd\Bundle\ProductManagementBundle\Entity\VolumePricing $pricing)
	{
		return $this->deleteVolumePricings(array($pricing));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteVolumePricings(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach ($collection as $entry) {
			if ($entry instanceof BundleEntity\VolumePricing) {
				$this->em->remove($entry);
				$countDeleted++;
			} else {
				$response = $this->getVolumePricing($entry);
				if (!$response->error->exist) {
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if ($countDeleted < 0) {
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $attribute
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesProductAttributeExist($attribute, bool $bypass = false)
	{
		$response = $this->getProductAttribute($attribute);
		$exist = true;
		if ($response->error->exist) {
			$exist = false;
			$response->result->set = false;
		}
		if ($bypass) {
			return $exist;
		}
		return $response;
	}

	/**
	 * @param mixed $attribute
	 * @param mixed $product
	 * @param mixed $language
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesProductAttributeValueExist($attribute, $product, $language, bool $bypass = false)
	{
		$exist = false;
		$response = $this->getAttributeValueOfProduct($attribute, $product, $language);
		if (!$response->error->exist) {
			$exist = true;
		}
		if ($bypass) {
			return $exist;
		}
		$response->result->set = $exist;
		return $response;
	}

	/**
	 * @param mixed $product
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesProductExist($product, bool $bypass = false)
	{
		$response = $this->getProduct($product);
		$exist = true;
		if ($response->error->exist) {
			$exist = false;
			$response->result->set = false;
		}
		if ($bypass) {
			return $exist;
		}
		return $response;
	}

	/**
	 * @param mixed $category
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesProductCategoryExist($category, bool $bypass = false)
	{
		$response = $this->getProductCategory($category);
		$exist = true;
		if ($response->error->exist) {
			$exist = false;
			$response->result->set = false;
		}
		if ($bypass) {
			return $exist;
		}
		return $response;
	}

	/**
	 * @param mixed $attribute
	 * @param mixed $product
	 * @param mixed $language
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getAttributeValueOfProduct($attribute, $product, $language)
	{
		$timeStamp = microtime(true);
		$response = $this->getProductAttribute($attribute);
		if ($response->error->exist) {
			return $response;
		}
		$attribute = $response->result->set;

		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$resposne = $mlsModel->getLanguage($language);

		if ($response->error->exist) {
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
		if (is_null($result)) {
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $brand
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getBrand($brand)
	{
		$timeStamp = microtime(true);
		if ($brand instanceof BundleEntity\Brand) {
			return new ModelResponse($brand, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
		}
		$result = null;
		switch ($brand) {
			case is_numeric($brand):
				$result = $this->em->getRepository($this->entity['b']['name'])->findOneBy(array('id' => $brand));
				break;
			case is_string($brand):
				$result = $this->em->getRepository($this->entity['b']['name'])->findOneBy(array('name' => $brand));
				break;
		}
		if (is_null($result)) {
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 * @param string $urlKey
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductUrlKeyHistory($product, string $urlKey)
	{
		$timeStamp = microtime(true);
		if ($product instanceof BundleEntity\ProductUrlKeyHistory) {
			return new ModelResponse($product, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
		}
		$result = null;
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;

		$result = $this->em->getRepository($this->entity['pukh']['name'])->findOneBy(array('product' => $product->getId(), 'url_key' => $urlKey));

		if (is_null($result)) {
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}
	/**
	 * @param mixed $product
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getMaxSortOrderOfAttributeInProduct($product, bool $bypass = false)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
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
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getMaxSortOrderOfProductFile($product, bool $bypass = false)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		$qStr = 'SELECT MAX(' . $this->entity['fop']['alias'] . '.sort_order) FROM '
			. $this->entity['fop']['name'] . ' ' . $this->entity['fop']['alias']
			. ' WHERE ' . $this->entity['fop']['alias'] . '.product = ' . $product->getId();

		$query = $this->em->createQuery($qStr);
		$result = $query->getSingleScalarResult();

		if ($bypass) {
			return $result;
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getMaxSortOrderOfProductInCategory($category,  bool $bypass = false)
	{
		$timeStamp = microtime(true);
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;
		$qStr = 'SELECT MAX(' . $this->entity['cop']['alias'] . ') FROM ' . $this->entity['cop']['name']
			. ' WHERE ' . $this->entity['cop']['alias'] . '.category = ' . $category->getId();

		$query = $this->em->createQuery($qStr);
		$result = $query->getSingleScalarResult();

		if ($bypass) {
			return $result;
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getMostRecentFileOfProduct($product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		$response = $this->listFilesOfProduct($product, null, array('date_added' => 'desc'), array('start' => 0, 'count' => 1));
		if ($response->error->exist) {
			return $response;
		}
		return new ModelResponse($response->result->set, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getParentOfProductCategory($category)
	{
		$response = $this->getProductCategory($category);

		if (!$response->error->exist) {
			$response->result->set = $response->result->set->getParent();
			if (is_null($response->result->set)) {
				$response->error->exist = true;
			}
		}
		return $response;
	}

	/**
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProduct($product)
	{
		$timeStamp = microtime(true);
		if ($product instanceof BundleEntity\Product) {
			return new ModelResponse($product, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
		}
		$result = null;
		switch ($product) {
			case is_numeric($product):
				$result = $this->em->getRepository($this->entity['p']['name'])->findOneBy(array('id' => $product));
				break;
			case is_string($product):
				$result = $this->em->getRepository($this->entity['p']['name'])->findOneBy(array('sku' => $product));
				if (is_null($result)) {
					$response = $this->getProductByUrlKey($product);
					if (!$response->error->exist) {
						$result = $response->result->set;
					}
				}
				unset($response);
				break;
		}
		if (is_null($result)) {
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param string $urlKey
	 * @param null $language
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductByUrlKey(string $urlKey, $language = null)
	{
		$timeStamp = microtime(true);
		if (!is_string($urlKey)) {
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pl']['alias'] . '.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if (!is_null($language)) {
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if (!$response->error->exist) {
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['pl']['alias'] . '.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listProducts($filter, null, array('start' => 0, 'count' => 1));
		if ($response->error->exist) {
			return $response;
		}
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = microtime(true);
		$response->result->set = $response->result->set[0];

		return $response;
	}

	/**
	 * @param $attr
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductAttribute(mixed $attr)
	{
		$timeStamp = microtime(true);
		if ($attr instanceof BundleEntity\ProductAttribute) {
			return new ModelResponse($attr, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
		}
		$result = null;
		switch ($attr) {
			case is_numeric($attr):
				$result = $this->em->getRepository($this->entity['pa']['name'])->findOneBy(array('id' => $attr));
				break;
			case is_string($attr):
				$response = $this->getProductAttributeByUrlKey($attr);
				if (!$response->error->exist) {
					$result = $response->result->set;
				}
				unset($response);
				break;
		}
		if (is_null($result)) {
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param int $id
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductAttributeValue(int $id)
	{
		$timeStamp = microtime(true);
		$result = $this->em->getRepository($this->entity['pav']['name'])
		                   ->findOneBy(array('id' => $id));

		if (is_null($result)) {
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param string $urlKey
	 * @param null   $language
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductAttributeByUrlKey(string $urlKey, $language = null)
	{
		$timeStamp = microtime(true);
		if (!is_string($urlKey)) {
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pal']['alias'] . '.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if (!is_null($language)) {
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if (!$response->error->exist) {
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['pal']['alias'] . '.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listProductAtrributes($filter, null, array('start' => 0, 'count' => 1));

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = microtime(true);
		$response->result->set  = $response->result->set[0];

		return $response;
	}

	/**
	 * @param string $sku
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductBySku(string $sku)
	{
		return $this->getProduct($sku);
	}

	/**
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductCategory($category){
		$timeStamp = microtime(true);
		if ($category instanceof BundleEntity\ProductCategory) {
			return new ModelResponse($category, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
		}
		$result = null;
		switch ($category) {
			case is_numeric($category):
				$result = $this->em->getRepository($this->entity['pc']['name'])->findOneBy(array('id' => $category));
				break;
			case is_string($category):
				$response = $this->getProductCategoryByUrlKey($category);
				if (!$response->error->exist) {
					$result = $response->result->set;
				}

				unset($response);
				break;
		}
		if (is_null($result)) {
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param string $urlKey
	 * @param mixed|null   $language
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getProductCategoryByUrlKey(string $urlKey, $language = null){
		$timeStamp = microtime(true);
		if (!is_string($urlKey)) {
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['pcl']['alias'] . '.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if (!is_null($language)) {
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if (!$response->error->exist) {
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['pcl']['alias'] . '.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listProductCategories($filter, null, array('start' => 0, 'count' => 1));

		$response->result->set = $response->result->set[0];
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = microtime(true);

		return $response;
	}

	/**
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getRandomCategoryOfProduct($product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
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
		return new ModelResponse($category, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $pricing
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getVolumePricing($pricing)
	{
		$timeStamp = microtime(true);

		$result = $this->em->getRepository($this->entity['vp']['name'])
		                   ->findOneBy(array('id' => $pricing));

		if (is_null($result)) {
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getVolumePricingOfProductWithMaximumQuantity($product)
	{
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		return $this->listVolumePricingsOfProduct($product, [], array('quantity_limit' => 'desc'), array('start' => 0, 'count' => 1));
	}

	/**
	 * @param     $product
	 * @param int $count
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function incrementCountViewOfProduct($product, int $count)
	{
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$product->setCountView($product->getCountView() + $count);
		return $this->updateProduct($product);
	}

	/**
	 * @param mixed $pUKey
	 *
	 * @return mixed
	 */
	public function insertProductUrlKeyHistory($pUKey)
	{
		return $this->insertProductUrlKeyHistories(array($pUKey));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductUrlKeyHistories(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\ProductUrlKeyHistory) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			} else if (is_object($data)) {
				$entity = new BundleEntity\ProductUrlKeyHistory();
				if (!property_exists($data, 'date_added')) {
					$data->date_added = $now;
				}
				if (!property_exists($data, 'date_updated')) {
					$data->date_updated = $now;
				}
				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'product':
							$response =$this->getProduct($value);
							if($response->error->exist){
								return $response;
							}
							$entity->setProduct($response->result->set);
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
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $brand
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertBrand($brand)
	{
		return $this->insertBrands(array($brand));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertBrands(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
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
					$data->date_updated = $now;
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
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;

				$countInserts++;
			}
		}
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProduct($product)
	{
		return $this->insertProducts(array($product));
	}

	/**
	 * @param mixed $attribute
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductAttribute($attribute)
	{
		return $this->insertProductAttributes(array($attribute));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductAttributeLocalizations(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\ProductAttributeLocalization) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			} else {
				$attribute = $data['entity'];
				foreach ($data['localizations'] as $locale => $translation) {
					$entity = new BundleEntity\ProductAttributeLocalization();
					$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
					$response = $lModel->getLanguage($locale);
					if ($response->error->exist) {
						return $response;
					}
					$entity->setLanguage($response->result->set);
					unset($response);
					$entity->setAttribute($attribute);
					foreach ($translation as $column => $value) {
						$set = 'set' . $this->translateColumnName($column);
						switch ($column) {
							default:
								if (is_object($value) || is_array($value)) {
									$value = json_encode($value);
								}
								$entity->$set($value);
								break;
						}
					}
					$this->em->persist($entity);
					$insertedItems[] = $entity;
					$countInserts++;
				}
			}
		}
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $value
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductAttributeValue($value)
	{
		return $this->insertProductAttributeValues(array($value));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductAttributeValues(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
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
							$response = $lModel->getLanguage($value);
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
							unset($response, $lModel);
							break;
						case 'attribute':
							$response = $this->getProductAttribute($value);
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
							break;
						case 'product':
							$response = $this->getProduct($value);
							if ($response->error->exist) {
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
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductAttributes(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$insertedItems = [];
		$localizations = [];
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
							/**
							 * @var \BiberLtd\Bundle\SiteManagementBundle\Services\SiteManagementModel $sModel
							 */
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value);
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
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductCategory($category)
	{
		return $this->insertProductCategories(array($category));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductCategories(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$insertedItems = [];
		$localizations = [];
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
					$data->date_updated = $now;
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
							if ($response->error->exist) {
								break;
							}
							$entity->$set($response->result->set);
							unset($response, $fModel);
							break;
						case 'parent':
							$response = $this->getProductCategory($value);
							if ($response->error->exist) {
								break;
							}
							$entity->$set($response->result->set);
							unset($response, $fModel);
							break;
						case 'site':
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value);
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
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductCategoryLocalizations(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\ProductCategoryLocalization) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			} else {
				$cat = $data['entity'];
				foreach ($data['localizations'] as $locale => $translation) {
					$entity = new BundleEntity\ProductCategoryLocalization();
					$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
					$response = $lModel->getLanguage($locale);
					if ($response->error->exist) {
						return $response;
					}
					$entity->setLanguage($response->result->set);
					unset($response);
					$entity->setCategory($cat);
					foreach ($translation as $column => $value) {
						$set = 'set' . $this->translateColumnName($column);
						switch ($column) {
							default:
								if (is_object($value) || is_array($value)) {
									$value = json_encode($value);
								}
								$entity->$set($value);
								break;
						}
					}
					$this->em->persist($entity);
					$insertedItems[] = $entity;
					$countInserts++;
				}
			}
		}
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProductLocalizations(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\ProductLocalization) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			} else {
				$product = $data['entity'];
				foreach ($data['localizations'] as $locale => $translation) {
					$entity = new BundleEntity\ProductLocalization();
					$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
					$response = $lModel->getLanguage($locale);
					if ($response->error->exist) {
						return $response;
					}
					$entity->setLanguage($response->result->set);
					unset($response);
					$entity->setProduct($product);
					foreach ($translation as $column => $value) {
						$set = 'set' . $this->translateColumnName($column);
						switch ($column) {
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
		}
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertProducts(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$insertedItems = [];
		$localizations = [];
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\Product) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			} else if (is_object($data)) {
				unset($data->id);
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
							if ($response->error->exist) {
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
							if ($response->error->exist) {
								break;
							}
							$entity->$set($response->result->set);
							unset($response, $fModel);
							break;
						case 'site':
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value);
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
							unset($response, $sModel);
							break;
						case 'supplier':
							$sModel = $this->kernel->getContainer()->get('stockmanagement.model');
							$response = $sModel->getSupplier($value);
							if ($response->error->exist) {
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
			$this->em->flush();
			$this->insertProductLocalizations($localizations);
		}
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $volumePricing
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertVolumePricing($volumePricing)
	{
		return $this->insertVolumePricings(array($volumePricing));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertVolumePricings(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
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
		if ($countInserts > 0) {
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $attribute
	 * @param mixed $product
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isAttributeAssociatedWithProduct($attribute, $product, bool $bypass = false)
	{
		$timeStamp = microtime(true);
		$response = $this->getProductAttribute($attribute);
		if ($response->error->exist) {
			return $response;
		}
		$attribute = $response->result->set;

		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['aop']['alias'] . '.attribute)'
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));

	}

	/**
	 * @param mixed $file
	 * @param mixed $product
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isFileAssociatedWithProduct($file, $product, bool $bypass = false){
		$timeStamp = microtime(true);
		$fModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);

		$response = $fModel->getFile($file);
		if ($response->error->exist) {
			return $response;
		}

		$response = $this->getProduct($product);

		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['fop']['alias'] . '.file' . ')'
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $locale
	 * @param mixed $product
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isLocaleAssociatedWithProduct($locale, $product, bool $bypass = false){
		$timeStamp = microtime(true);
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');

		$response = $mlsModel->getLanguage($locale);
		if ($response->error->exist) {
			return $response;
		}
		$locale = $response->result->set;

		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['apl']['alias'] . '.product' . ')'
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $locale
	 * @param mixed $category
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isLocaleAssociatedWithProductCategory($locale, $category, bool $bypass = false)
	{
		$timeStamp = microtime(true);
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');

		$response = $mlsModel->getLanguage($locale);
		if ($response->error->exist) {
			return $response;
		}
		$locale = $response->result->set;

		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['apcl']['alias'] . '.category)'
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 * @param mixed $category
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isProductAssociatedWithCategory($product, $category, bool $bypass = false){
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['cop']['alias'] . '.product)'
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveProducts(array $filter = null, array $sortOrder = null, array $limit= null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['p']['alias'].'.status', 'comparison' => '=', 'value' =>'a' ),
		);
		return $this->listProducts($filter,$sortOrder,$limit);
	}

	/**
	 * @param mixed $site
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveProductsOfSite($site, array $filter = null, array $sortOrder = null, array $limit = null){
		$SMMModel = new SMMService\SiteManagementModel($this->kernel);
		$response = $SMMModel->getSite($site);
		if ($response->error->exist) {
			return $response;
		}
		$site = $response->result->set->getId();
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['p']['alias'].'.site', 'comparison' => '=', 'value' => $site ),
		);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['p']['alias'].'.status', 'comparison' => '=', 'value' =>'a' ),
		);
		return $this->listProducts($filter,$sortOrder,$limit);
	}

	/**
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveLocalesOfProduct($product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$qStr = 'SELECT ' . $this->entity['apl']['alias']
			. ' FROM ' . $this->entity['apl']['name'] . ' ' . $this->entity['apl']['alias']
			. ' WHERE ' . $this->entity['apl']['alias'] . '.product = ' . $product->getId();
		$query = $this->em->createQuery($qStr);
		$result = $query->getResult();
		$locales = [];
		$unique = [];
		foreach ($result as $entry) {
			$id = $entry->getLocale()->getId();
			if (!isset($unique[$id])) {
				$locales[] = $entry->getLocale();
				$unique[$id] = '';
			}
		}
		unset($unique);
		$totalRows = count($locales);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($locales, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveLocalesOfProductCategory($category)
	{
		$timeStamp = microtime(true);
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;
		$qStr = 'SELECT ' . $this->entity['apcl']['alias']
			. ' FROM ' . $this->entity['apcl']['name'] . ' ' . $this->entity['apcl']['alias']
			. ' WHERE ' . $this->entity['apcl']['alias'] . '.category = ' . $category->getId();
		$query = $this->em->createQuery($qStr);
		$result = $query->getResult();
		$locales = [];
		$unique = [];
		foreach ($result as $entry) {
			$id = $entry->getLocale()->getId();
			if (!isset($unique[$id])) {
				$locales[] = $entry->getLocale();
				$unique[$id] = '';
			}
		}
		unset($unique);
		$totalRows = count($locales);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($locales, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveProductsOfCategory($category, array  $sortOrder = null, array  $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
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
			if ($sorting) {
				$oStr = rtrim($oStr, ', ');
				$oStr = ' ORDER BY ' . $oStr . ' ';
			}
		}
		$qStr .= $oStr;
		$query = $this->em->createQuery($qStr);
		$result = $query->getResult();
		if (count($result) < 1) {

		}
		$collection = [];
		foreach ($result as $item) {
			$collection[] = $item->getProduct()->getId();
		}
		unset($result);
		$filter = [];
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
	 * @param mixed $product
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listAttributesOfProduct($product, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
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
		$entities = [];
		foreach ($result as $entity) {
			$id = $entity->getAttribute()->getId();
			if (!isset($unique[$id])) {
				$unique[$id] = '';
				$entities[] = $entity->getAttribute();
			}
		}
		$totalRows = count($entities);
		unset($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listAttributesOfProductCategory($category, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listAllAttributeValuesOfProduct($product, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listAllChildProductCategories(array $sortOrder = null, array  $limit = null)
	{
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

	/***
	 * @param mixed $product
	 * @param mixed $attribute
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listAttributeValuesOfProduct($product, $attribute, array $sortOrder = null, array $limit = null)
	{
		$this->resetResponse();
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		$response = $this->getProductAttribute($attribute);
		if ($response->error->exist) {
			return $response;
		}
		$attribute = $response->result->set;
		unset($response);

		$qStr = 'SELECT ' . $this->entity['pav']['alias'] . ', ' . $this->entity['pa']['alias']
			. ' FROM ' . $this->entity['pav']['name'] . ' ' . $this->entity['pav']['alias']
			. ' JOIN ' . $this->entity['pav']['alias'] . '.attribute ' . $this->entity['pa']['alias']
			. ' WHERE ' . $this->entity['pav']['alias'] . '.product = ' . $product->getId() . ' AND ' . $this->entity['pav']['alias'] . '.attribute = ' . $attribute->getId();

		$oStr = '';
		if ($sortOrder != null){
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listBrands(array $filter = null, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['b']['alias']
			. ' FROM ' . $this->entity['b']['name'] . ' ' . $this->entity['b']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'id':
					case 'name':
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

		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
		}

		$qStr .= $wStr . $gStr . $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}
	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductUrlKeyHistories(array $filter = null, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['pukh']['alias']
			. ' FROM ' . $this->entity['pukh']['name'] . ' ' . $this->entity['pukh']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'url_key':
					case 'date_added':
					case 'date_updated':
					case 'date_removed':
						$column = $this->entity['pukh']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}

		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
		}

		$qStr .= $wStr . $gStr . $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductUrlKeyHistoriesOfProduct($product, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				'column' => $this->entity['pukh']['alias'].'.product',
				'comparison' => '=',
				'value' => $product->getId()
			),
		);

		return $this->listProductUrlKeyHistories($filter, $sortOrder, $limit);
	}
	/**
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getLastUrlKeyHistoryOfProuct($product)
	{
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				'column' => $this->entity['pukh']['alias'].'.product',
				'comparison' => '=',
				'value' => $product->getId()
			),
		);

		$response = $this->listProductUrlKeyHistories($filter, array('date_added' => 'desc'), array('start' => 0, 'count' => 1));
		$response->result->set = $response->result->set[0];
		$response->result->count->set = 1;
		return $response;
	}
	/**
	 * @param mixed $product
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listCategoriesOfProduct($product, array $filter = null, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$qStr = 'SELECT ' . $this->entity['cop']['alias']
			. ' FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias']
			. ' WHERE ' . $this->entity['cop']['alias'] . '.product = ' . $product->getId();
		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$catsOfProduct = [];
		if (count($result) > 0) {
			foreach ($result as $cop) {
				$catsOfProduct[] = $cop->getCategory()->getId();
			}
		}
		if (count($catsOfProduct) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
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
	 * @param mixed $category
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listChildCategoriesOfProductCategory($category, array $sortOrder = null, array $limit = null)
	{
		if ($category instanceof BundleEntity\ProductCategory) {
			$category = $category->getId();
		}
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
	 * @param mixed $category
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listChildCategoriesOfProductCategoryWithPreviewImage($category, array $sortOrder = null, array $limit = null){
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;

		$column = $this->entity['pc']['alias'] . '.parent';
		$condition = array('column' => $column, 'comparison' => 'eq', 'value' => $category->getId());
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
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listCustomizableProducts(array $sortOrder = null, array $limit = null)
	{
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
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listFeaturedParentProductCategories(array $filter = null, array $sortOrder = null, array $limit = null)
	{
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
	 * @param mixed $product
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listFilesOfProduct($product, array $filter = null, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}

		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$oStr = $wStr = $gStr = '';

		$qStr = 'SELECT ' . $this->entity['fop']['alias']
			. ' FROM ' . $this->entity['fop']['name'] . ' ' . $this->entity['fop']['alias'];
		/**
		 * Prepare ORDER BY part of query.
		 */
		if ($sortOrder != null) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					default:
						$oStr .= ' ' . $this->entity['fop']['alias'] . '.' . $column . ' ' . $direction . ', ';
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
					'condition' => array('column' => $this->entity['fop']['alias'] . '.product', 'comparison' => '=', 'value' => $product->getId()),
				)
			)
		);
		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
		}

		$qStr .= $wStr . $gStr . $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listInactiveProducts(array $filter = null, array $sortOrder = null, array $limit= null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['p']['alias'].'.status', 'comparison' => '=', 'value' =>'i' ),
		);
		return $this->listProducts($filter, $sortOrder, $limit);
	}

	/**
	 * @param mixed $product
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listLocalizationsOfProduct($product, array $sortOrder = null, array $limit = null){
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$response = $this->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['pl']['alias']
			. ' FROM ' . $this->entity['pl']['name'] . ' ' . $this->entity['pl']['alias']
			. ' JOIN ' . $this->entity['pl']['alias'] . '.product ' . $this->entity['p']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'name':
					case 'description':
					case 'meta_keywords':
					case 'meta_description':
						$column = $this->entity['pl']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			if(!empty($oStr)){
				$oStr = ' ORDER BY ' . $oStr . ' ';
			}
		}

		$wStr .= ' WHERE '.$this->entity['pl']['alias'].'.product = '.$product->getId();

		$qStr .= $wStr . $gStr . $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);
		$result = $q->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNotCustomizableProducts(array $sortOrder = null, array $limit = null)
	{
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
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listOutOfStockProducts(array $sortOrder = null, array $limit = null)
	{
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
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listParentOnlyProductCategories(array $sortOrder = null, array $limit = null)
	{
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
	 * @param int        $level
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listParentOnlyProductCategoriesOfLevel(int $level = 1, array $filter = null, array $sortOrder = null, array $limit = null)
	{
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
	 * @param array      $amounts
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listProductsPricedBetween(array $amounts, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsPriced($amounts, 'between', $sortOrder, $limit);
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductAttributes(array $filter = null, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['pa']['alias'] . ', ' . $this->entity['pal']['alias']
			. ' FROM ' . $this->entity['pal']['name'] . ' ' . $this->entity['pal']['alias']
			. ' JOIN ' . $this->entity['pal']['alias'] . '.attribute ' . $this->entity['pa']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'id':
					case 'sort_order':
					case 'date_added':
					case 'date_updated':
					case 'date_removed':
					case 'site':
						$column = $this->entity['pa']['alias'] . '.' . $column;
						break;
					case 'name':
					case 'url_key':
						$column = $this->entity['pal']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}

		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
		}

		$qStr .= $wStr . $gStr . $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = [];
		foreach ($result as $entry) {
			$id = $entry->getAttribute()->getId();
			if (!isset($unique[$id])) {
				$unique[$id] = '';
				$entities[] = $entry->getAttribute();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductAttributeValues(array $filter = null, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
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
		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductCategories(array $filter = null, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '. $this->entity['pcl']['alias']
			. ' FROM ' . $this->entity['pcl']['name'] . ' ' . $this->entity['pcl']['alias']
			. ' JOIN ' . $this->entity['pcl']['alias'] . '.category ' . $this->entity['pc']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'id':
					case 'sort_order':
					case 'date_added':
					case 'date_updated':
					case 'date_removed':
					case 'site':
					case 'parent':
					case 'count_children':
						$column = $this->entity['pc']['alias'] . '.' . $column;
						break;
					case 'name':
					case 'url_key':
						$column = $this->entity['pcl']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}

		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
		}

		$qStr .= $wStr . $gStr . $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);
		$result = $q->getResult();
		$entities = [];
		$unique = [];
		foreach ($result as $entry) {
			$id = $entry->getCategory()->getId();
			if (!isset($unique[$id])) {
				$entities[] = $entry->getCategory();
				$unique[$id] = $entry->getCategory();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 * @param int        $level
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductCategoriesOfParentHavingLevel($category, int $level, array $filter = null, array $sortOrder = null, array $limit = null)
	{
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
	 * @param mixed $product
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductAttributeValuesOfProduct($product, array $filter = null, array  $sortOrder = null, array $limit = null)
	{
		$response = $this->getProduct($product);
		if ($response->error->exist) {
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
	 * @param int        $level
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductCategoriesOfLevel(int $level = 1, array $sortOrder = null, array $limit = null)
	{
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
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProducts(array  $filter = null, array $sortOrder = null,array  $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['p']['alias'] . ', ' . $this->entity['pl']['alias']
			. ' FROM ' . $this->entity['pl']['name'] . ' ' . $this->entity['pl']['alias']
			. ' JOIN ' . $this->entity['pl']['alias'] . '.product ' . $this->entity['p']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
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
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}

		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
		}

		$qStr .= $wStr . $gStr . $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = [];
		foreach ($result as $entry) {
			$id = $entry->getProduct()->getId();
			if (!isset($unique[$id])) {
				$unique[$id] = '';
				$entities[] = $entry->getProduct();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param \DateTime  $date
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsAdded(\DateTime $date, string $eq, array $sortOrder = null, array $limit = null)
	{
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
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsAddedAfter(\DateTime $date, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsAdded($date, 'after', $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsAddedBefore(\DateTime$date, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsAdded($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @param array      $dates
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsAddedBetween(array $dates, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsAdded($dates, 'between', $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsAddedOn(\DateTime $date, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsAdded($date, 'on', $sortOrder, $limit);
	}

	/**
	 * @param array      $categories
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsInCategory(array $categories, array $sortOrder = null, array $limit = null){
		$timeStamp = microtime(true);
		$catIds = [];
		foreach ($categories as $category) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
				continue;
			}
			$category = $response->result->set;
			$catIds[] = $category->getId();
		}
		if (empty($catIds)) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		$catIds = implode(',', $catIds);

		$qStr = 'SELECT ' . $this->entity['cop']['alias']
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

		$totalRows = 0;
		$collection = [];
		$unique = [];
		if(count($result)){
			foreach($result as $item){
				$id = $item->getProduct()->getId();
				if(!isset($unique[$id])){
					$unique[$id] = '';
					$collection[] = $item->getProduct();
					$totalRows++;
				}
			}
		}
		unset($unique);
		unset($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($collection, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array      $locales
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsInLocales(array $locales, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		$langIds = [];
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if ($response->error->exist) {
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
		$products = [];
		foreach ($result as $cop) {
			$products[] = $cop->getProduct();
		}

		$totalRows = count($products);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($products, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array      $locales
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductCategoriesInLocales(array $locales, array $sortOrder = null, array $limit = null){
		$timeStamp = microtime(true);
		$langIds = [];
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if ($response->error->exist) {
				break;
			}
			$locale = $response->result->set;
			$langIds[] = $locale->getId();
		}
		$langIds = implode(',', $langIds);

		$qStr = 'SELECT '.$this->entity['apcl']['alias']
			. ' FROM '.$this->entity['apcl']['name'].' '.$this->entity['apcl']['alias']
			. ' JOIN '.$this->entity['apcl']['alias'].'.category '.$this->entity['p']['alias']
			. ' WHERE '.$this->entity['apcl']['alias'].'.locale IN ('.$langIds.')';

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

		$categories = [];
		foreach ($result as $cop) {
			$categories[] = $cop->getCategory();
		}

		$totalRows = count($categories);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($categories, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param int        $likes
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsLiked(int  $likes, string $eq, array $sortOrder = null, array $limit = null){
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
	 * @param int        $likes
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsLikedBetween(int $likes, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsLiked($likes, 'between', $sortOrder, $limit);
	}

	/**
	 * @param int        $likes
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsLikedLessThan(int $likes, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsLiked($likes, 'less', $sortOrder, $limit);
	}

	/**
	 * @param int        $likes
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsLikedMoreThan(int $likes, array $sortOrder = null, array $limit = null){
		return $this->listProductsLiked($likes, 'more', $sortOrder, $limit);
	}

	/**
	 * @param mixed $brand
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsOfBrand($brand, array $sortOrder = null, array $limit = null){
		$response = $this->getBrand($brand);
		if ($response->error->exist) {
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
	 * @param mixed $category
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsOfCategory($category, array $sortOrder = null, array $limit = null){
		$timeStamp = microtime(true);
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
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

		$collection = [];
		foreach ($result as $item) {
			$collection[] = $item->getProduct()->getId();
		}
		unset($result);
		if(count($collection) < 1){
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['p']['alias'] . '.id', 'comparison' => 'in', 'value' => $collection),
				)
			)
		);
		return $this->listProducts($filter, $sortOrder, $limit);
	}

	/**
	 * @param mixed $category
	 * @param array      $locales
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsOfCategoryInLocales($category, array $locales, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		$langIds = [];
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if ($response->error->exist) {
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
		$productIdCollection = [];
		$productCollection = [];
		foreach ($products as $productEntity) {
			$productIdCollection[] = $productEntity->getId();
			$productCollection[$productEntity->getId()] = $productEntity;
		}
		$productIdCollection = implode(',', $productIdCollection);

		$qStr = 'SELECT ' . $this->entity['apl']['alias']
			. ' FROM ' . $this->entity['apl']['name'] . ' ' . $this->entity['apl']['alias']
			. ' JOIN ' . $this->entity['apl']['alias'] . '.product ' . $this->entity['p']['alias']
			. ' WHERE ' . $this->entity['apl']['alias'] . '.locale IN (' . $langIds . ')'
			. ' AND ' . $this->entity['apl']['alias'] . '.product IN (' . $productIdCollection . ')';

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
		if (!is_null($result)) {
			$products = [];
			foreach ($result as $cop) {
				$products[] = $cop->getProduct();
			}
			$totalRows = count($products);
		}

		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($products, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));


	}

	/**
	 * @param mixed $site
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsOfSite($site, array $sortOrder = null, array $limit = null)
	{
		$SMMModel = new SMMService\SiteManagementModel($this->kernel);
		$response = $SMMModel->getSite($site);
		if ($response->error->exist) {
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
	 * @param \DateTime  $date
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsUpdated(\DateTime $date, string $eq, array $sortOrder = null, array $limit = null)
	{
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
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsUpdatedAfter(\DateTime $date, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsUpdated($date, 'after', $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsUpdatedBefore(\DateTime $date, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsUpdated($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @name            listProductsUpdatedBetween ()
	 *
	 * @since           1.0.3
	 * @version         1.5.3
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProductsUpdated()
	 *
	 * @param           array $date
	 * @param           array $sortOrder
	 * @param           array $limit
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsUpdatedBetween($date, $sortOrder = null, $limit = null)
	{
		return $this->listProductsUpdated($date, 'between', $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsUpdatedOn(\DateTime $date, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsUpdated($date, 'on', $sortOrder, $limit);
	}

	/**
	 * @param float      $price
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsWithPrice(float $price, string $eq, array $sortOrder = null, array $limit = null)
	{
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
	 * @param int        $quantity
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsWithQuantities(int $quantity, string $eq, array $sortOrder = null, array $limit = null)
	{
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
	 * @param array      $quantities
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listProductsWithQuantityBetween(array $quantities, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsWithQuantity($quantities, 'between', $sortOrder, $limit);
	}

	/**
	 * @param int        $quantity
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsWithQuantityLessThan(int $quantity, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsWithQuantities($quantity, 'less', $sortOrder, $limit);
	}

	/**
	 * @param int        $quantity
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsWithQuantitiesMoreThan(int $quantity, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsWithQuantities($quantity, 'more', $sortOrder, $limit);
	}

	/**
	 * @param float      $amount
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listProductsWithPriceLessThan(float $amount, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsPriced($amount, 'less', $sortOrder, $limit);
	}

	/**
	 * @param float      $amount
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listProductsWithPriceMoreThan(float $amount, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsPriced($amount, 'more', $sortOrder, $limit);
	}

	/**
	 * @param mixed $views
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsViewed($views, string $eq, array $sortOrder = null, array $limit = null)
	{
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
	 * @param array      $views
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsViewedBetween(array $views, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsViewed($views, 'between', $sortOrder, $limit);
	}

	/**
	 * @param array      $views
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsViewedLessThan(array $views, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsViewed($views, 'less', $sortOrder, $limit);
	}

	/**
	 * @param array      $views
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsViewedMoreThan(array $views, array $sortOrder = null, array $limit = null)
	{
		return $this->listProductsViewed($views, 'more', $sortOrder, $limit);
	}

	/**
	 * @param mixed $product
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listRelatedProductsOfProduct($product, array $sortOrder = null, array $limit = null)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		$relatedProducts = [];
		$relatedProductIds = [];
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
	 * @param mixed $product
	 * @param mixed $attribute
	 * @param mixed $language
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listValuesOfProductAttributes($product, $attribute, $language)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;

		$response = $this->getProductAttribute($attribute);
		if ($response->error->exist) {
			return $response;
		}
		$ættribute = $response->result->set;

		$MLSModel = new MLSService\MultiLanguageSupportModel($this->kernel, $this->dbConnection, $this->orm);

		$response = $MLSModel->getLanguage($language);
		if ($response->error->exist) {
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricings(array $filter = null, array $sortOrder = null, array  $limit = null)
	{
		$timeStamp = microtime(true);
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['vp']['alias'] . ', ' . $this->entity['vp']['alias']
			. ' FROM ' . $this->entity['vp']['name'] . ' ' . $this->entity['vp']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
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
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}

		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
		}

		$qStr .= $wStr . $gStr . $oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = [];
		foreach ($result as $entry) {
			/**
			 * @var BundleEntity\VolumePricing $entry
			 */
			$id = $entry->getId();
			if (!isset($unique[$id])) {
				$unique[$id] = '';
				$entities[] = $entry;
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 * @param array      $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricingsOfProduct($product, array $filter = [], array  $sortOrder = null, array $limit = null)
	{
		$response = $this->getProduct($product);
		if ($response->error->exist) {
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
	 * @param mixed $product
	 * @param int $quantity
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricingsOfProductWithClosestQuantity($product, int $quantity)
	{
		return $this->listVolumePricingsOfProductWithQuantityLowerThan($product, $quantity, null, array('quantity_limit' => 'desc'), array('start' => 0, 'count' => 1));
	}

	/**
	 * @param mixed $product
	 * @param int        $quantity
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricingsOfProductWithQuantityGreaterThan($product, int $quantity, array $sortOrder = null, array $limit = null)
	{
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
	 * @param mixed $product
	 * @param int        $quantity
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVolumePricingsOfProductWithQuantityLowerThan($product, int $quantity, array $sortOrder = null, array $limit = null)
	{
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
	 * @param array $categories
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function markCategoriesAsFeatured(array $categories)
	{
		$timeStamp = microtime(true);
		$catIds = [];
		foreach ($categories as $category) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
				continue;
			}
			$catIds[] = $response->result->set->getId();
		}
		$catIds = implode(',', $catIds);
		if (count($catIds) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		$qStr = 'UPDATE ' . $this->entity['pc']['name'] . ' ' . $this->entity['pc']['alias']
			. ' SET ' . $this->entity['pc']['alias'] . '.is_featured = \'y\''
			. ' WHERE ' . $this->entity['pc']['alias'] . '.id IN(' . $catIds . ')';

		$query = $this->em->createQuery($qStr);
		$result = $query->getResult();

		return new ModelResponse($result, count($catIds), 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function relateProductsWithProduct(array $collection, $product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$countRelated = 0;
		$relatedProducts = [];
		foreach ($collection as $item) {
			$response = $this->getProduct($item);
			if ($response->error->exist) {
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
		return new ModelResponse($relatedProducts, $countRelated, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array                                                   $categories
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeCategoriesFromProduct(array $categories, $product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$idsToRemove = [];
		foreach ($categories as $category) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
				return $response;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias']
			. ' WHERE ' . $this->entity['cop']['alias'] . '.product = ' . $product->getId()
			. ' AND ' . $this->entity['cop']['alias'] . '.category ' . $in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $files
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeFilesFromProduct(array $files, $product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$idsToRemove = [];
		$fmModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);
		foreach ($files as $file) {
			$response = $fmModel->getFile($file);
			if ($response->error->exist) {
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM ' . $this->entity['fop']['name'] . ' ' . $this->entity['fop']['alias']
			. ' WHERE ' . $this->entity['fop']['alias'] . '.product ' . $product->getId()
			. ' AND ' . $this->entity['fop']['alias'] . '.file ' . $in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $locales
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeLocalesFromProduct(array $locales, $product){
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$idsToRemove = [];
		$mModel = new MLSService\MultiLanguageSupportModel($this->kernel, $this->dbConnection, $this->orm);
		foreach ($locales as $locale) {
			$response = $mModel->getLanguage($locale);
			if ($response->error->exist) {
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM ' . $this->entity['apl']['name'] . ' ' . $this->entity['apl']['alias']
			. ' WHERE ' . $this->entity['apl']['alias'] . '.product = ' . $product->getId()
			. ' AND ' . $this->entity['apl']['alias'] . '.locale ' . $in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $locales
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeLocalesFromProductCategory(array $locales, $category){
		$timeStamp = microtime(true);
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;
		$idsToRemove = [];
		$mModel = new MLSService\MultiLanguageSupportModel($this->kernel, $this->dbConnection, $this->orm);
		foreach ($locales as $locale) {
			$response = $mModel->getLanguage($locale);
			if ($response->error->exist) {
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM ' . $this->entity['apcl']['name'] . ' ' . $this->entity['apcl']['alias']
			. ' WHERE ' . $this->entity['apcl']['alias'] . '.category = ' . $category->getId()
			. ' AND ' . $this->entity['apcl']['alias'] . '.locale ' . $in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $products
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeProductsFromCategory(array $products, $category){
		$timeStamp = microtime(true);
		$response = $this->getProductCategory($category);
		if ($response->error->exist) {
			return $response;
		}
		$category = $response->result->set;
		$idsToRemove = [];
		foreach ($products as $product) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM ' . $this->entity['cop']['name'] . ' ' . $this->entity['cop']['alias']
			. ' WHERE ' . $this->entity['cop']['alias'] . '.category ' . $category->getId()
			. ' AND ' . $this->entity['cop']['alias'] . '.product ' . $in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $categories
	 * @param       $locale
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeProductCategoriesFromLocale(array $categories, mixed $locale)
	{
		$timeStamp = microtime(true);
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if ($response->error->exist) {
			return $response;
		}
		$locale = $response->result->set;
		$toRemove = [];
		$count = 0;
		/** Start persisting files */

		foreach ($categories as $category) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
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
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $products
	 * @param mixed $locale
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeProductsFromLocale(array $products, $locale)
	{
		$timeStamp = microtime(true);
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if ($response->error->exist) {
			return $response;
		}
		$locale = $response->result->set;
		$toRemove = [];
		$count = 0;
		foreach ($products as $product) {
			$response = $this->getProduct($product);
			if ($response->error->exist) {
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
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $categories
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function unmarkCategoriesAsFeatured(array $categories)
	{
		$timeStamp = microtime(true);
		$catIds = [];
		foreach ($categories as $category) {
			$response = $this->getProductCategory($category);
			if ($response->error->exist) {
				return $response;
			}
			$catIds[] = $response->result->set->getId();
		}
		$catIds = implode(',', $catIds);
		if (count($catIds) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		$qStr = 'UPDATE ' . $this->entity['pc']['name'] . ' ' . $this->entity['pc']['alias']
			. ' SET ' . $this->entity['pc']['alias'] . '.is_featured = \'n\''
			. ' WHERE ' . $this->entity['pc']['alias'] . '.id IN(' . $catIds . ')';

		$query = $this->em->createQuery($qStr);
		$result = $query->getResult();

		return new ModelResponse($result, count($catIds), 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function unrelateProductsFromProduct(array $collection, $product)
	{
		$timeStamp = microtime(true);
		$response = $this->getProduct($product);
		if ($response->error->exist) {
			return $response;
		}
		$product = $response->result->set;
		$countUnrelated = 0;
		$unrelatedProductIds = [];
		foreach ($collection as $item) {
			$response = $this->getProduct($item);
			if ($response->error->exist) {
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

		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $brand
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateBrand($brand)
	{
		return $this->updateBrands(array($brand));
	}

	/**
	 * @param mixed $urlKey
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updadeProductUrlKeyHistory($urlKey)
	{
		return $this->updadeProductUrlKeyHistories(array($urlKey));
	}
	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updadeProductUrlKeyHistories(array $collection)
	{
		$timeStamp = microtime(true);
		$countUpdates = 0;
		$updatedItems = [];
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\ProductUrlKeyHistory) {
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
				$response = $this->getProductUrlHistory($data->product, $data->url_key);
				if ($response->error->exist) {
					return $this->createException('EntityDoesNotExist', 'Brand with id ' . $data->id, 'err.invalid.entity');
				}
				$oldEntity = $response->result->set;
				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'id':
							break;
						case 'product':
							$response = $this->getProduct($value);
							if($response->error->exist){
								return $response;
							}
							$oldEntity->setProduct($value);
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
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}
	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateBrands(array $collection)
	{
		$timeStamp = microtime(true);
		$countUpdates = 0;
		$updatedItems = [];
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\Brand) {
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
		if ($countUpdates > 0) {
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $product
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateProduct($product)
	{
		return $this->updateProducts(array($product));
	}

	/**
	 * @param mixed $attribute
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateProductAttribute($attribute)
	{
		return $this->updateProductAttributes(array($attribute));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateProductAttributes(array $collection)
	{
		$timeStamp = microtime(true);
		$countUpdates = 0;
		$updatedItems = [];
		$localizations = [];
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
		if ($countUpdates > 0) {
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $data
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateProductAttributeValue($data)
	{
		return $this->updateProductAttributeValues(array($data));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateProductAttributeValues(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\ProductAttributeValues) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			} else if (is_object($data)) {
				if (!property_exists($data, 'id') || !is_numeric($data->id)) {
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
					return $this->createException('EntityDoesNotExist', 'Attribute value ' . $data->id . ' does not exist in database.', 'E:D:002');
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
		if ($countUpdates > 0) {
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateProductCategory($category)
	{
		return $this->updateProductCategories(array($category));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateProductCategories(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = [];
		$localizations = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\ProductCategory) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			} else if (is_object($data)) {
				if (!property_exists($data, 'id') || !is_numeric($data->id)) {
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
					return $this->createException('EntityDoesNotExist', 'Category with id / url_key ' . $data->id . ' does not exist in database.', 'E:D:002');
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
		if ($countUpdates > 0) {
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateProducts(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = [];
		$localizations = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\Product) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			} else if (is_object($data)) {
				if (!property_exists($data, 'id') || !is_numeric($data->id)) {
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
					return $this->createException('EntityDoesNotExist', 'Product with id / url_key / sku  ' . $data->id . ' does not exist in database.', 'E:D:002');
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
		if ($countUpdates > 0) {
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $volumePricing
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateVolumePricing($volumePricing)
	{
		return $this->updateVolumePricings(array($volumePricing));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateVolumePricings(array $collection)
	{
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\VolumePricing) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			} else if (is_object($data)) {
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
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}

		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param string     $keyword
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsWitkKeywordMatchingInMeta(string $keyword, array $sortOrder = null, array $limit = null){
		$filter[] = array(
			'glue' => 'or',
			'condition' => array('column' => $this->entity['p']['alias'].'.sku', 'comparison' => 'contains', 'value' => $keyword),
		);
		$filter[] = array(
			'glue' => 'or',
			'condition' => array('column' => $this->entity['p']['alias'].'.extra_info', 'comparison' => 'contains', 'value' => $keyword),
		);
		$filter[] = array(
			'glue' => 'or',
			'condition' => array('column' => $this->entity['pl']['alias'].'.name', 'comparison' => 'contains', 'value' => $keyword),
		);
		$filter[] = array(
			'glue' => 'or',
			'condition' => array('column' => $this->entity['pl']['alias'].'.description', 'comparison' => 'contains', 'value' => $keyword),
		);
		$filter[] = array(
			'glue' => 'or',
			'condition' => array('column' => $this->entity['pl']['alias'].'.meta_keywords', 'comparison' => 'contains', 'value' => $keyword),
		);
		$filter[] = array(
			'glue' => 'or',
			'condition' => array('column' => $this->entity['pl']['alias'].'.meta_description', 'comparison' => 'contains', 'value' => $keyword),
		);
		$filter[] = array(
			'glue' => 'or',
			'condition' => array('column' => $this->entity['pl']['alias'].'.url_key', 'comparison' => 'contains', 'value' => $keyword),
		);
		return $this->listProducts($filter,$sortOrder,$limit);
	}

	/**
	 * @param mixed $supplier
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsOfSupplier($supplier, array $sortOrder = null, array $limit = null){
		/**
		 * @var StockManagementModel $sModel
		 */
		$sModel = $this->kernel->getContainer()->get('stockmanagement.model');
		$response = $sModel->getSupplier($supplier);
		if ($response->error->exist) {
			return $response;
		}
		$supplier = $response->result->set;
		unset($response);
		$column = $this->entity['p']['alias'] . '.supplier';
		$condition = array('column' => $column, 'comparison' => '=', 'value' => $supplier->getId());
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
	 * @param string     $status
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listProductsWithStatus(string $status, array $sortOrder = null, array $limit= null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['p']['alias'].'.status', 'comparison' => '=', 'value' => $status),
		);
		return $this->listProducts($filter,$sortOrder,$limit);
	}
}