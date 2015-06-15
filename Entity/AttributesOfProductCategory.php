<?php
/**
 * @name        AttributesOfProductCategory
 * @package		BiberLtd\Bundle\CoreBundle\ProductManagementBundle
 *
 *  @author		Can Berkol
 * @author		Murat Ünal
 *
 * @version     1.0.1
 * @date        27.04.2015
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="attributes_of_product_category",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idxNAttributesOfProductCategoryDateAdded", columns={"date_added"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUAttributesOfProductCategory", columns={"attribute","category"})}
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
    private $attribute;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory")
     * @ORM\JoinColumn(name="category", referencedColumnName="id", onDelete="CASCADE")
     */
    private $category;

	/**
	 * @name            setAttribute()
	 *
	 * @author          Can Berkol
	 *
	 * @since           1.0.1
	 * @version         1.0.1
	 *
	 * @use             $this->setModified()
	 *
	 * @param           mixed			$attribute
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
	 * @name            getAttribute()
	 *
	 * @author          Can Berkol
	 *
	 * @since           1.0.1
	 * @version         1.0.1
	 *
	 * @return          mixed           $this->product_attribute
	 */
	public function getAttribute() {
		return $this->attribute;
	}

	/**
	 * @name            setCategory()
	 *
	 * @author          Can Berkol
	 *
	 * @since           1.0.1
	 * @version         1.0.1
	 *
	 * @use             $this->setModified()
	 *
	 * @param           mixed $product_category
	 *
	 * @return          object                $this
	 */
	public function setCategory($product_category) {
		if(!$this->setModified('category', $product_category)->isModified()) {
			return $this;
		}
		$this->category = $product_category;
		return $this;
	}

	/**
	 * @name            getCategory()
	 *
	 * @author          Can Berkol
	 *
	 * @since           1.0.1
	 * @version         1.0.1
	 *
	 * @return          mixed           $this->product_category
	 */
	public function getCategory() {
		return $this->category;
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
}
/**
 * Change Log:
 * **************************************
 * v1.0.1                      27.04.2015
 * TW#
 * Can Berkol
 * **************************************
 * Major ORM changes.
 *
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