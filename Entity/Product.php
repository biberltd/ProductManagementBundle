<?php
/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        23.12.2015
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
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="integer", length=5, nullable=false, options={"default":0})
     * @var integer
     */
    private $quantity;

    /**
     * @ORM\Column(type="decimal", length=6, nullable=false, options={"default":"0.00"})
     * @var float
     */
    private $price;

    /** 
     * @ORM\Column(type="decimal", length=6, nullable=true)
     * @var float
     */
    private $discount_price;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_view;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_like;

    /**
     * @ORM\Column(type="string", length=155, nullable=false)
     * @var string
     */
    private $sku;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default":0})
     * @var int
     */
    private $sort_order;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_updated;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $date_removed;

    /**
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"a"})
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"t"})
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"n"})
     * @var string
     */
    private $is_customizable;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $extra_info;

    /**
     * @ORM\OneToMany(targetEntity="ProductLocalization", mappedBy="product", cascade={"persist"})
     * @var array
     */
    protected $localizations;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\SiteManagementBundle\Entity\Site
     */
    private $site;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File")
	 * @ORM\JoinColumn(name="preview_file", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    private $preview_file;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\StockManagementBundle\Entity\Supplier")
     * @ORM\JoinColumn(name="supplier", referencedColumnName="id")
     * @var \BiberLtd\Bundle\StockManagementBundle\Entity\Supplier
     */

    private $supplier;

    /**
     * @ORM\ManyToOne(targetEntity="Brand", inversedBy="products")
     * @ORM\JoinColumn(name="brand", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Brand
     */
    private $brand;

    /**
     * @return mixed
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @param int $count_like
     *
     * @return $this
     */
    public function setCountLike(int $count_like) {
        if(!$this->setModified('count_like', $count_like)->isModified()) {
            return $this;
        }
		$this->count_like = $count_like;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getCountLike() {
        return $this->count_like;
    }

	/**
	 * @param int $count_view
	 *
	 * @return $this
	 */
    public function setCountView(int $count_view) {
        if(!$this->setModified('count_view', $count_view)->isModified()) {
            return $this;
        }
		$this->count_view = $count_view;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getCountView() {
        return $this->count_view;
    }

	/**
	 * @param string $is_customizable
	 *
	 * @return $this
	 */
    public function setIsCustomizable(string $is_customizable) {
        if(!$this->setModified('is_customizable', $is_customizable)->isModified()) {
            return $this;
        }
		$this->is_customizable = $is_customizable;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getIsCustomizable() {
        return $this->is_customizable;
    }

	/**
	 * @param \BiberLtd\Bundle\FileManagementBundle\Entity\File $preview_file
	 *
	 * @return $this
	 */
    public function setPreviewFile(\BiberLtd\Bundle\FileManagementBundle\Entity\File $preview_file) {
        if(!$this->setModified('preview_file', $preview_file)->isModified()) {
            return $this;
        }
		$this->preview_file = $preview_file;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\FileManagementBundle\Entity\File
	 */
    public function getPreviewFile() {
        return $this->preview_file;
    }

	/**
	 * @param float $price
	 *
	 * @return $this
	 */
    public function setPrice(float $price) {
        if(!$this->setModified('price', $price)->isModified()) {
            return $this;
        }
		$this->price = $price;
		return $this;
    }

	/**
	 * @return float
	 */
    public function getPrice() {
        return $this->price;
    }

	/**
	 * @param int $quantity
	 *
	 * @return $this
	 */
    public function setQuantity(int $quantity) {
        if(!$this->setModified('quantity', $quantity)->isModified()) {
            return $this;
        }
		$this->quantity = $quantity;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getQuantity() {
        return $this->quantity;
    }

	/**
	 * @param \BiberLtd\Bundle\SiteManagementBundle\Entity\Site $site
	 *
	 * @return $this
	 */
    public function setSite(\BiberLtd\Bundle\SiteManagementBundle\Entity\Site $site) {
        if(!$this->setModified('site', $site)->isModified()) {
            return $this;
        }
		$this->site = $site;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\SiteManagementBundle\Entity\Site
	 */
    public function getSite() {
        return $this->site;
    }

	/**
	 * @param string $sku
	 *
	 * @return $this
	 */
    public function setSku(string $sku) {
        if(!$this->setModified('sku', $sku)->isModified()) {
            return $this;
        }
		$this->sku = $sku;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getSku() {
        return $this->sku;
    }

	/**
	 * @param int $sort_order
	 *
	 * @return $this
	 */
    public function setSortOrder(int $sort_order) {
        if(!$this->setModified('sort_order', $sort_order)->isModified()) {
            return $this;
        }
		$this->sort_order = $sort_order;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getSortOrder() {
        return $this->sort_order;
    }

	/**
	 * @param string $status
	 *
	 * @return $this
	 */
    public function setStatus(string $status) {
        if(!$this->setModified('status', $status)->isModified()) {
            return $this;
        }
		$this->status = $status;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getStatus() {
        return $this->status;
    }

	/**
	 * @param string $type
	 *
	 * @return $this
	 */
    public function setType(string $type) {
        if(!$this->setModified('type', $type)->isModified()) {
            return $this;
        }
		$this->type = $type;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getType() {
        return $this->type;
    }

	/**
	 * @param \BiberLtd\Bundle\StockManagementBundle\Entity\Supplier $supplier
	 *
	 * @return $this
	 */
    public function setSupplier(\BiberLtd\Bundle\StockManagementBundle\Entity\Supplier $supplier) {
        if($this->setModified('supplier', $supplier)->isModified()) {
            $this->supplier = $supplier;
        }

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\StockManagementBundle\Entity\Supplier
	 */
    public function getSupplier() {
        return $this->supplier;
    }

	/**
	 * @param float $discount_price
	 *
	 * @return $this
	 */
    public function setDiscountPrice(float $discount_price) {
        if($this->setModified('discount_price', $discount_price)->isModified()) {
            $this->discount_price = $discount_price;
        }

        return $this;
    }

	/**
	 * @return float
	 */
    public function getDiscountPrice() {
        return $this->discount_price;
    }

	/**
	 * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\Brand $brand
	 *
	 * @return $this
	 */
    public function setBrand(\BiberLtd\Bundle\ProductManagementBundle\Entity\Brand $brand) {
        if($this->setModified('brand', $brand)->isModified()) {
            $this->brand = $brand;
        }

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\Brand
	 */
    public function getBrand() {
        return $this->brand;
    }

	/**
	 * @param string $extra_info
	 *
	 * @return $this
	 */
    public function setExtraInfo(string $extra_info) {
        if($this->setModified('extra_info', $extra_info)->isModified()) {
            $this->extra_info = $extra_info;
        }

        return $this;
    }

	/**
	 * @return string
	 */
    public function getExtraInfo() {
        return $this->extra_info;
    }
}