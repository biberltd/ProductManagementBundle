<?php
/**
 * @name        Product
 * @package		BiberLtd\Bundle\CoreBundle\ProductManagementBundle
 *
 * @author      Can Berkol
 * @author		Murat Ünal
 *
 * @version     1.0.5
 * @date        13.07.2014
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreLocalizableEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="product",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idxNProductDateAdded", columns={"date_added"}),
 *         @ORM\Index(name="idxNProductDateUpdated", columns={"date_updated"}),
 *         @ORM\Index(name="idxNProductDateRemoved", columns={"date_removed"}),
 *         @ORM\Index(name="idxNProductsOfBrand", columns={"id","brand"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idxUProductId", columns={"id"}),
 *         @ORM\UniqueConstraint(name="idxUProductSku", columns={"sku","site"})
 *     }
 * )
 */
class Product extends CoreLocalizableEntity{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=15)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", length=5, nullable=false)
     */
    private $quantity;

    /**
     * @ORM\Column(type="decimal", length=6, nullable=false)
     */
    private $price;

    /** 
     * @ORM\Column(type="decimal", length=6, nullable=true)
     */
    private $discount_price;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $count_view;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $count_like;

    /**
     * @ORM\Column(type="string", length=155, nullable=false)
     */
    private $sku;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $sort_order;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_updated;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $date_removed;

    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    private $is_customizable;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $extra_info;

    /**
     * @ORM\OneToMany(
     *     targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductLocalization",
     *     mappedBy="product",
     *     cascade={"persist"}
     * )
     */
    protected $localizations;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     */
    private $site;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File", cascade={"persist"})
	 * @ORM\JoinColumn(name="preview_file", referencedColumnName="id", onDelete="CASCADE")
     */
    private $preview_file;

    private $supplier;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Brand", inversedBy="products")
     * @ORM\JoinColumn(name="brand", referencedColumnName="id", onDelete="CASCADE")
     */
    private $brand;
    /******************************************************************
     * PUBLIC SET AND GET FUNCTIONS                                   *
     ******************************************************************/

    /**
     * @name            getId()
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
     * @name            setCountLike ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_like
     *
     * @return          object                $this
     */
    public function setCountLike($count_like) {
        if(!$this->setModified('count_like', $count_like)->isModified()) {
            return $this;
        }
		$this->count_like = $count_like;
		return $this;
    }

    /**
     * @name            getCountLike ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_like
     */
    public function getCountLike() {
        return $this->count_like;
    }

    /**
     * @name            setCountView ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_view
     *
     * @return          object                $this
     */
    public function setCountView($count_view) {
        if(!$this->setModified('count_view', $count_view)->isModified()) {
            return $this;
        }
		$this->count_view = $count_view;
		return $this;
    }

    /**
     * @name            getCountView ()
     *                               Returns the value of count_view property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_view
     */
    public function getCountView() {
        return $this->count_view;
    }

    /**
     * @name            setIsCustomizable()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $is_customizable
     *
     * @return          object                $this
     */
    public function setIsCustomizable($is_customizable) {
        if(!$this->setModified('is_customizable', $is_customizable)->isModified()) {
            return $this;
        }
		$this->is_customizable = $is_customizable;
		return $this;
    }

    /**
     * @name            getIsCustomizable()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->is_customizable
     */
    public function getIsCustomizable() {
        return $this->is_customizable;
    }

    /**
     * @name            setPreviewFile ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $preview_file
     *
     * @return          object                $this
     */
    public function setPreviewFile($preview_file) {
        if(!$this->setModified('preview_file', $preview_file)->isModified()) {
            return $this;
        }
		$this->preview_file = $preview_file;
		return $this;
    }

    /**
     * @name            getPreviewFile ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->preview_file
     */
    public function getPreviewFile() {
        return $this->preview_file;
    }

    /**
     * @name            setPrice ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $price
     *
     * @return          object                $this
     */
    public function setPrice($price) {
        if(!$this->setModified('price', $price)->isModified()) {
            return $this;
        }
		$this->price = $price;
		return $this;
    }

    /**
     * @name            getPrice ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->price
     */
    public function getPrice() {
        return $this->price;
    }

    /**
     * @name            setQuantity ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $quantity
     *
     * @return          object                $this
     */
    public function setQuantity($quantity) {
        if(!$this->setModified('quantity', $quantity)->isModified()) {
            return $this;
        }
		$this->quantity = $quantity;
		return $this;
    }

    /**
     * @name            getQuantity ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->quantity
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * @name            setSite ()
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
     * @name            getSite ()
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
     * @name            setSku ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $sku
     *
     * @return          object                $this
     */
    public function setSku($sku) {
        if(!$this->setModified('sku', $sku)->isModified()) {
            return $this;
        }
		$this->sku = $sku;
		return $this;
    }

    /**
     * @name            getSku ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->sku
     */
    public function getSku() {
        return $this->sku;
    }

    /**
     * @name            setSortOrder ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $sort_order
     *
     * @return          object                $this
     */
    public function setSortOrder($sort_order) {
        if(!$this->setModified('sort_order', $sort_order)->isModified()) {
            return $this;
        }
		$this->sort_order = $sort_order;
		return $this;
    }

    /**
     * @name            getSortOrder ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->sort_order
     */
    public function getSortOrder() {
        return $this->sort_order;
    }

    /**
     * @name            setStatus ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $status
     *
     * @return          object                $this
     */
    public function setStatus($status) {
        if(!$this->setModified('status', $status)->isModified()) {
            return $this;
        }
		$this->status = $status;
		return $this;
    }

    /**
     * @name            getStatus ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->status
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @name            setType ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $type
     *
     * @return          object                $this
     */
    public function setType($type) {
        if(!$this->setModified('type', $type)->isModified()) {
            return $this;
        }
		$this->type = $type;
		return $this;
    }

    /**
     * @name            getType ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->type
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @name            setSupplier ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $supplier
     *
     * @return          object                $this
     */
    public function setSupplier($supplier) {
        if($this->setModified('supplier', $supplier)->isModified()) {
            $this->supplier = $supplier;
        }

        return $this;
    }

    /**
     * @name            getSupplier ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->supplier
     */
    public function getSupplier() {
        return $this->supplier;
    }

    /**
     * @name            setDiscountPrice ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $discount_price
     *
     * @return          object                $this
     */
    public function setDiscountPrice($discount_price) {
        if($this->setModified('discount_price', $discount_price)->isModified()) {
            $this->discount_price = $discount_price;
        }

        return $this;
    }

    /**
     * @name            getDiscountPrice ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->discount_price
     */
    public function getDiscountPrice() {
        return $this->discount_price;
    }

    /**
     * @name            setBrand ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $brand
     *
     * @return          object                $this
     */
    public function setBrand($brand) {
        if($this->setModified('brand', $brand)->isModified()) {
            $this->brand = $brand;
        }

        return $this;
    }

    /**
     * @name            getBrand ()
     *                  Returns the value of brand property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->brand
     */
    public function getBrand() {
        return $this->brand;
    }

    /**
     * @name            setExtraInfo()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $extra_info
     *
     * @return          object                $this
     */
    public function setExtraInfo($extra_info) {
        if($this->setModified('extra_info', $extra_info)->isModified()) {
            $this->extra_info = $extra_info;
        }

        return $this;
    }

    /**
     * @name            getExtraInfo()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->extra_info
     */
    public function getExtraInfo() {
        return $this->extra_info;
    }
}
/**
 * Change Log:
 * **************************************
 * v1.0.5                      13.07.2015
 * Can Berkol
 * **************************************
 * BF ::  cascade={"persist"} added to preview_file ManyToOne
 *
 * **************************************
 * v1.0.4                     Can Berkol
 * 16.05.2014
 * **************************************
 * A getExtraInfo()
 * A setExtraInfo()
 *
 * **************************************
 * v1.0.2                     Can Berkol
 * 28.11.2013
 * **************************************
 * A getPreviewFile()
 * A getSite()
 * A setPreviewFile()
 * A getPreviewFile()
 *
 * **************************************
 * v1.0.1                      Murat Ünal
 * 11.10.2013
 * **************************************
 * D getProducts_of_sites()
 * D setProducts_of_sites()
 * D get_categories_of_products()
 * D set_categories_of_products()
 * D get_attributes_of_products()
 * D set_attributes_of_products()
 * D getProductAttribute_valueses()
 * D setProduct_attribute_valueses()
 * D get_files_of_products()
 * D set_files_of_products()
 * D getShoppingOrderItems()
 * D setShoppingOrderItems()
 * D getSite()
 * D setSite
 *
 * **************************************
 * v1.0.0                      Murat Ünal
 * 11.09.2013
 * **************************************
 * A get_attributes_of_products()
 * A get_categories_of_products()
 * A getCountLike()
 * A getCountView()
 * A getCoupons()
 * A getDateAdded()
 * A getDateUpdated()
 * A get_files_of_products()
 * A getId()
 * A getIsCustomizable()
 * A getLocalizations()
 * A getPrice()
 * A getProductAttribute_valueses()
 * A getProducts_of_sites()
 * A getQuantity()
 * A getShoppingCartItems()
 * A getShoppingOrderItems()
 * A getSite()
 * A getSku()
 * A getSortOrder()
 * A getStatus()
 * A getType()
 * A set_attributes_of_products()
 * A set_categories_of_products()
 * A setCountLike()
 * A setCountView()
 * A setCoupons()
 * A setDateAdded()
 * A setDateUpdated()
 * A set_files_of_products()
 * A setIsCustomizable()
 * A setLocalizations()
 * A setPrice()
 * A setProductAttribute_valueses()
 * A setProducts_of_sites()
 * A setQuantity()
 * A setShoppingCartItems()
 * A setShoppingOrderItems()
 * A setSite()
 * A setSku()
 * A setSortOrder()
 * A setStatus()
 * A setType()
 *
 */
