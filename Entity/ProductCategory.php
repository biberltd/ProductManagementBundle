<?php
/**
 * @name        ProductCategory
 * @package		BiberLtd\Bundle\CoreBundle\ProductManagementBundle
 *
 * @author      Can Berkol
 * @author		Murat Ünal
 *
 * @version     1.0.3
 * @date        12.07.2015
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreLocalizableEntity;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="product_category",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idxNProductCategoryDateAdded", columns={"date_added"}),
 *         @ORM\Index(name="idxNProductCategoryDateUpdated", columns={"date_updated"}),
 *         @ORM\Index(name="idxNProductCategoryDateRemoved", columns={"date_removed"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUProductCategoryId", columns={"id"})}
 * )
 */
class ProductCategory extends CoreLocalizableEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** 
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    private $level;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $count_children;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $date_updated;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	public $date_removed;

    /** 
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    private $is_featured;

    /** 
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sort_order;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File", cascade={"persist"})
	 * @ORM\JoinColumn(name="preview_image", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $preview_image;

    /** 
     * @ORM\OneToMany(
     *     targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory",
     *     mappedBy="parent"
     * )
     */
    private $product_categories;

    /** 
     * @ORM\OneToMany(
     *     targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategoryLocalization",
     *     mappedBy="category"
     * )
     */
    protected $localizations;

    /** 
     * @ORM\ManyToOne(
     *     targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory",
     *     inversedBy="product_categories"
     * )
     * @ORM\JoinColumn(name="parent", referencedColumnName="id", onDelete="RESTRICT")
     */
    private $parent;


    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     */
    private $site;
    /******************************************************************
     * PUBLIC SET AND GET FUNCTIONS                                   *
     ******************************************************************/

    /**
     * @name            getId()
     *                  Gets $id property.
     * .
     * @author          Murat Ünal
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          integer          $this->id
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @name                  setCountChildren ()
     *                                         Sets the count_children property.
     *                                         Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_children
     *
     * @return          object                $this
     */
    public function setCountChildren($count_children) {
        if(!$this->setModified('count_children', $count_children)->isModified()) {
            return $this;
        }
		$this->count_children = $count_children;
		return $this;
    }

    /**
     * @name            getCountChildren ()
     *                                   Returns the value of count_children property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_children
     */
    public function getCountChildren() {
        return $this->count_children;
    }

    /**
     * @name                  setLevel ()
     *                                 Sets the level property.
     *                                 Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $level
     *
     * @return          object                $this
     */
    public function setLevel($level) {
        if(!$this->setModified('level', $level)->isModified()) {
            return $this;
        }
		$this->level = $level;
		return $this;
    }

    /**
     * @name            getLevel ()
     *                           Returns the value of level property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->level
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @name                  setProductCategories ()
     *                                             Sets the product_categories property.
     *                                             Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $product_categories
     *
     * @return          object                $this
     */
    public function setProductCategories($product_categories) {
        if(!$this->setModified('product_categories', $product_categories)->isModified()) {
            return $this;
        }
		$this->product_categories = $product_categories;
		return $this;
    }

    /**
     * @name            getProductCategories ()
     *                                       Returns the value of product_categories property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->product_categories
     */
    public function getProductCategories() {
        return $this->product_categories;
    }

    /**
     * @name                  setParent ()
     *                                           Sets the product_category property.
     *                                           Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $product_category
     *
     * @return          object                $this
     */
    public function setParent($product_category) {
        if(!$this->setModified('parent', $product_category)->isModified()) {
            return $this;
        }
		$this->parent = $product_category;
		return $this;
    }

    /**
     * @name            getProductCategory ()
     *                                     Returns the value of product_category property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->product_category
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * @name                  setSite ()
     *                                Sets the site property.
     *                                Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $site
     *
     * @return          object                $this
     */
    public function setSite($site) {
        if(!$this->setModified('site', $site)->isModified()) {
            return $this;
        }
		$this->site = $site;
		return $this;
    }

    /**
     * @name            getSite()
     *                          Returns the value of site property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->site
     */
    public function getSite() {
        return $this->site;
    }

    /**
     * @name            setIsFeatured()
     *                  Sets the is_featured property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.2
     * @version         1.0.2
     *
     * @use             $this->setModified()
     *
     * @param           mixed $is_featured
     *
     * @return          object                $this
     */
    public function setIsFeatured($is_featured) {
        if($this->setModified('is_featured', $is_featured)->isModified()) {
            $this->is_featured = $is_featured;
        }

        return $this;
    }

    /**
     * @name            isFeatured()
     *                  Returns the value of is_featured property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.2
     * @version         1.0.2
     *
     * @return          mixed           $this->is_featured
     */
    public function getIsFeatured() {
        return $this->is_featured;
    }

    /**
     * @name            setSortOrder ()
     *                  Sets the sort_order property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.2
     * @version         1.0.2
     *
     * @use             $this->setModified()
     *
     * @param           mixed $sort_order
     *
     * @return          object                $this
     */
    public function setSortOrder($sort_order) {
        if($this->setModified('sort_order', $sort_order)->isModified()) {
            $this->sort_order = $sort_order;
        }

        return $this;
    }

    /**
     * @name            getSortOrder ()
     *                  Returns the value of sort_order property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.2
     * @version         1.0.2
     *
     * @return          mixed           $this->sort_order
     */
    public function getSortOrder() {
        return $this->sort_order;
    }

    /**
     * @name            setPreviewImage()
     *                  Sets the preview_image property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.2
     * @version         1.0.2
     *
     * @use             $this->setModified()
     *
     * @param           mixed $preview_image
     *
     * @return          object                $this
     */
    public function setPreviewImage($preview_image) {
        if($this->setModified('preview_image', $preview_image)->isModified()) {
            $this->preview_image = $preview_image;
        }

        return $this;
    }

    /**
     * @name            getPreviewImage()
     *                  Returns the value of preview_image property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.2
     * @version         1.0.2
     *
     * @return          mixed           $this->preview_image
     */
    public function getPreviewImage() {
        return $this->preview_image;
    }

}
/**
 * Change Log:
 * **************************************
 * v1.0.2                      Can Berkol
 * 12.01.2014
 * **************************************
 * A isFeatured()
 * A getPreviewImage()
 * A getSortOrder()
 * A setIsFeatured()
 * A setPreviewImage()
 * A setSortOrder()
 *
 * **************************************
 * v1.0.1                      Murat Ünal
 * 11.10.2013
 * **************************************
 * D get_categories_of_products()
 * D set_categories_of_products()
 * D get_attributes_of_product_categories()
 * D set_attributes_of_product_categories()
 * D getCoupons()
 * D setCoupons()
 * D getShipmentRates()
 * D setShipmentRates()
 * D getTaxRates()
 * D setTaxRates()
 *
 * **************************************
 * v1.0.0                      Murat Ünal
 * 11.09.2013
 * **************************************
 * A get_attributes_of_product_categories()
 * A get_categories_of_products()
 * A getCountChildren()
 * A getCoupons()
 * A getDateAdded()
 * A getDateUpdated()
 * A getId()
 * A getLevel()
 * A getLocalizations()
 * A getProductCategories()
 * A getProductCategory()
 * A getShipmentRates()
 * A getSite()
 * A getTaxRates()
 *
 * A set_attributes_of_product_categories()
 * A set_categories_of_products()
 * A setCountChildren()
 * A setCoupons()
 * A setDateAdded()
 * A setDateUpdated()
 * A setLevel()
 * A setLocalizations()
 * A setProductCategories()
 * A setProductCategory()
 * A setSite()
 * A setTaxRates()
 *
 */