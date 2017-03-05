<?php
/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        05.03.2017
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="files_of_product",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idxUFilesOfProductDateAdded", columns={"date_added"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUFilesOfProduct", columns={"file","product"})}
 * )
 */
class TagsOfProduct extends CoreEntity
{
    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     * @var int
     */
    private $sort_order;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Tag", cascade={"persist"})
     * @ORM\JoinColumn(name="tag", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Tag
     */
    private $tag;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product", cascade={"persist"})
     * @ORM\JoinColumn(name="product", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    private $product;

    /**
     * @param Tag $tag
     * @return $this
     */
    public function setTag(\BiberLtd\Bundle\ProductManagementBundle\Entity\Tag $tag) {
        if(!$this->setModified('tag', $tag)->isModified()) {
            return $this;
        }
		$this->tag = $tag;
		return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\Tag
     */
    public function getTag() {
        return $this->tag;
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