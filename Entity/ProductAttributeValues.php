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
use BiberLtd\Bundle\MultiLanguageSupportBundle\Services\MultiLanguageSupportModel;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="product_attribute_values",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idxUProductAttributeValuesId", columns={"id"}),
 *         @ORM\UniqueConstraint(name="idxUProductAttributeValues", columns={"attribute","language","product"})
 *     }
 * )
 */
class ProductAttributeValues extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="text", nullable=false)
     * @var string
     */
    private $value;

    /** 
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    private $product;

    /** 
     * @ORM\ManyToOne(targetEntity="ProductAttribute")
     * @ORM\JoinColumn(name="attribute", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute
     */
    private $attribute;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    private $language;

    /**
     * @return mixed
     */
    public function getId(){
        return $this->id;
    }

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
     * @param \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language
     *
     * @return $this
     */
    public function setLanguage(\BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language) {
        if(!$this->setModified('language', $language)->isModified()) {
            return $this;
        }
		$this->language = $language;
		return $this;
    }

    /**
     * @return \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    public function getLanguage() {
        return $this->language;
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
    public function setSortOrder(integer $sort_order) {
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
     * @param string $value
     *
     * @return $this
     */
    public function setValue(string $value) {
        if(!$this->setModified('value', $value)->isModified()) {
            return $this;
        }
		$this->value = $value;
		return $this;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }
}