<?php
/**
 * @name        AttributesOfProductCategory
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
 *     name="attributes_of_product_category",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idx_n_attributes_of_product_category_date_added", columns={"date_added"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_attributes_of_product_category", columns={"attribute","category"})}
 * )
 */
class AttributesOfProductCategory extends CoreEntity
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
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute")
     * @ORM\JoinColumn(name="attribute", referencedColumnName="id", onDelete="CASCADE")
     */
    private $product_attribute;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory")
     * @ORM\JoinColumn(name="category", referencedColumnName="id", onDelete="CASCADE")
     */
    private $product_category;

    /**
     * @name                  setProductAttribute ()
     *                                            Sets the product_attribute property.
     *                                            Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $product_attribute
     *
     * @return          object                $this
     */
    public function setProductAttribute($product_attribute) {
        if(!$this->setModified('product_attribute', $product_attribute)->isModified()) {
            return $this;
        }
		$this->product_attribute = $product_attribute;
		return $this;
    }

    /**
     * @name            getProductAttribute ()
     *                                      Returns the value of product_attribute property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->product_attribute
     */
    public function getProductAttribute() {
        return $this->product_attribute;
    }

    /**
     * @name                  setProductCategory ()
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
    public function setProductCategory($product_category) {
        if(!$this->setModified('product_category', $product_category)->isModified()) {
            return $this;
        }
		$this->product_category = $product_category;
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
    public function getProductCategory() {
        return $this->product_category;
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
 * v1.0.0                      Murat Ünal
 * 11.09.2013
 * **************************************
 * A getDateAdded()
 * A getProductAttribute()
 * A getProductCategory()
 * A getSortOrder()
 *
 * A setDateAdded()
 * A setProductAttribute()
 * A setProductCategory()
 * A setSortOrder()
 *
 */