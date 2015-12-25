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
 *     name="attributes_of_product_category",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idxNAttributesOfProductCategoryDateAdded", columns={"date_added"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUAttributesOfProductCategory", columns={"attribute","category"})}
 * )
 */
class AttributesOfProductCategory extends CoreEntity
{
    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     * @var int
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ProductAttribute")
     * @ORM\JoinColumn(name="attribute", referencedColumnName="id", onDelete="CASCADE")
     * @var \DateTime
     */
    private $attribute;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ProductCategory")
     * @ORM\JoinColumn(name="category", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory
     */
    private $category;

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
	 * @return \DateTime
	 */
	public function getAttribute() {
		return $this->attribute;
	}

	/**
	 * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory $product_category
	 *
	 * @return $this
	 */
	public function setCategory(\BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory $product_category) {
		if(!$this->setModified('category', $product_category)->isModified()) {
			return $this;
		}
		$this->category = $product_category;
		return $this;
	}

	/**
	 * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @param int $sort_order
	 *
	 * @return $this
	 */
    public function setSortOrder(\integer $sort_order) {
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