<?php
/**
 * @name        ProductAttributeValues
 * @package		BiberLtd\Core\ProductManagementBundle
 *
 * @author		Murat Ünal
 *
 * @version     1.0.0
 * @date        11.09.2013
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
 *     name="product_attribute_values",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idx_u_product_attribute_values", columns={"attribute","language","product"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_product_attribute_values_id", columns={"id"})}
 * )
 */
class ProductAttributeValues extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="text", nullable=false)
     */
    private $value;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", onDelete="CASCADE")
     */
    private $product;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute")
     * @ORM\JoinColumn(name="attribute", referencedColumnName="id", onDelete="CASCADE")
     */
    private $attribute;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", onDelete="CASCADE")
     */
    private $language;
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
     * @name                  setLanguage ()
     *                                    Sets the language property.
     *                                    Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $language
     *
     * @return          object                $this
     */
    public function setLanguage($language) {
        if(!$this->setModified('language', $language)->isModified()) {
            return $this;
        }
		$this->language = $language;
		return $this;
    }

    /**
     * @name            getLanguage ()
     *                              Returns the value of language property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->language
     */
    public function getLanguage() {
        return $this->language;
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

    /**
     * @name                  setValue ()
     *                                 Sets the value property.
     *                                 Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $value
     *
     * @return          object                $this
     */
    public function setValue($value) {
        if(!$this->setModified('value', $value)->isModified()) {
            return $this;
        }
		$this->value = $value;
		return $this;
    }

    /**
     * @name            getValue ()
     *                           Returns the value of value property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->value
     */
    public function getValue() {
        return $this->value;
    }
}
/**
 * Change Log:
 * **************************************
 * v1.0.0                      Murat Ünal
 * 11.09.2013
 * **************************************
 * A getId()
 * A getLanguage()
 * A getProduct()
 * A getProductAttribute()
 * A getSortOrder()
 * A getValue()
 *
 * A setLanguage()
 * A setProduct()
 * A setProductAttribute()
 * A setSortOrder()
 * A setValue()
 *
 */