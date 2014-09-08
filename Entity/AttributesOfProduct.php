<?php
/**
 * @name        AttributesOfProduct
 * @package		BiberLtd\Core\ProductManagementBundle
 *
 * @author      Can Berkol
 * @author		Murat Ünal
 *
 * @version     1.0.1
 * @date        30.11.2013
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Core\CoreEntity;

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
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /** 
     * @ORM\Column(type="decimal", length=5, nullable=true)
     */
    private $price_factor;

    /** 
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $price_factor_type;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute")
     * @ORM\JoinColumn(name="attribute", referencedColumnName="id", onDelete="CASCADE")
     */
    private $attribute;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", onDelete="CASCADE")
     */
    private $product;

    /**
     * @name                  setAttribute ()
     *                                     Sets the attribute property.
     *                                     Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $attribute
     *
     * @return          object                $this
     */
    public function setAttribute($attribute) {
        if(!$this->setModified('attribute', $attribute)->isModified()) {
            return $this;
        }
		$this->attribute = $attribute;
		return $this;
    }

    /**
     * @name            getAttribute ()
     *                               Returns the value of attribute property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->attribute
     */
    public function getAttribute() {
        return $this->attribute;
    }

    /**
     * @name                  setPriceFactor ()
     *                                       Sets the price_factor property.
     *                                       Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $price_factor
     *
     * @return          object                $this
     */
    public function setPriceFactor($price_factor) {
        if(!$this->setModified('price_factor', $price_factor)->isModified()) {
            return $this;
        }
		$this->price_factor = $price_factor;
		return $this;
    }

    /**
     * @name            getPriceFactor ()
     *                                 Returns the value of price_factor property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->price_factor
     */
    public function getPriceFactor() {
        return $this->price_factor;
    }

    /**
     * @name                  setPriceFactorType ()
     *                                           Sets the price_factor_type property.
     *                                           Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $price_factor_type
     *
     * @return          object                $this
     */
    public function setPriceFactorType($price_factor_type) {
        if(!$this->setModified('price_factor_type', $price_factor_type)->isModified()) {
            return $this;
        }
		$this->price_factor_type = $price_factor_type;
		return $this;
    }

    /**
     * @name            getPriceFactorType ()
     *                                     Returns the value of price_factor_type property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->price_factor_type
     */
    public function getPriceFactorType() {
        return $this->price_factor_type;
    }

    /**
     * @name                  setProduct ()
     *                                   Sets the product property.
     *                                   Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $product
     *
     * @return          object                $this
     */
    public function setProduct($product) {
        if(!$this->setModified('product', $product)->isModified()) {
            return $this;
        }
		$this->product = $product;
		return $this;
    }

    /**
     * @name            getProduct ()
     *                             Returns the value of product property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->product
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @name                  setSortOrder ()
     *                                     Sets the sort_order property.
     *                                     Updates the data only if stored value and value to be set are different.
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
     *                               Returns the value of sort_order property.
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
    /******************************************************************
     * PUBLIC SET AND GET FUNCTIONS                                   *
     ******************************************************************/

}
/**
 * Change Log:
 * **************************************
 * v1.0.1                      Can Berkol
 * 30.11.2013
 * **************************************
 * A getAttribute()
 * A setAttribute
 * D getProductAttribute()
 * D setProductAttribute()
 *
 * **************************************
 * v1.0.0                      Murat Ünal
 * 11.09.2013
 * **************************************
 * A getDateAdded()
 * A getPriceFactor()
 * A getPriceFactorType()
 * A getProduct()
 * A getProductAttribute()
 * A getSortOrder()
 *
 * A setDateAdded()
 * A setPriceFactor()
 * A setPriceFactorType()
 * A setProduct()
 * A setProduct()
 * A setProductAttribute()
 * A setSortOrder()
 *
 */