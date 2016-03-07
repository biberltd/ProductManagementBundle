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
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="attributes_of_product",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_attributes_of_product", columns={"product","attribute"})}
 * )
 */
class AttributesOfProduct extends CoreEntity
{
    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /** 
     * @ORM\Column(type="decimal", length=5, nullable=true)
     * @var float
     */
    private $price_factor;

    /** 
     * @ORM\Column(type="string", length=1, nullable=true, options={"default":"a"})
     * @var string
     */
    private $price_factor_type;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ProductAttribute")
     * @ORM\JoinColumn(name="attribute", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute
     */
    private $attribute;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    private $product;

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute $attribute
     *
     * @return $this
     */
    public function setAttribute(\BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute $attribute) {
        if(!$this->setModified('attribute', $attribute)->isModified()) {
            return $this;
        }
		$this->attribute = $attribute;
		return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute
     */
    public function getAttribute() {
        return $this->attribute;
    }

    /**
     * @param float $price_factor
     *
     * @return $this
     */
    public function setPriceFactor(float $price_factor) {
        if(!$this->setModified('price_factor', $price_factor)->isModified()) {
            return $this;
        }
		$this->price_factor = $price_factor;
		return $this;
    }

    /**
     * @return float
     */
    public function getPriceFactor() {
        return $this->price_factor;
    }

    /**
     * @param float $price_factor_type
     *
     * @return $this
     */
    public function setPriceFactorType(float $price_factor_type) {
        if(!$this->setModified('price_factor_type', $price_factor_type)->isModified()) {
            return $this;
        }
		$this->price_factor_type = $price_factor_type;
		return $this;
    }

    /**
     * @return string
     */
    public function getPriceFactorType() {
        return $this->price_factor_type;
    }

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product
     *
     * @return $this
     */
    public function setProduct(\BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product) {
        if(!$this->setModified('product', $product)->isModified()) {
            return $this;
        }
		$this->product = $product;
		return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    public function getProduct() {
        return $this->product;
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
}