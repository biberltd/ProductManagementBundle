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
use BiberLtd\Bundle\CoreBundle\CoreEntity;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="related_product",
 *     options={"charset":"utf8","engine":"innodb","collate":"utf8_turkish_ci"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxURelatedProduct", columns={"product","related_product"})}
 * )
 */
class RelatedProduct extends CoreEntity
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    private $product;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="related_product", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    private $related_product;

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product
     *
     * @return $this
     */
    public function setProduct(\BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product) {
        if($this->setModified('product', $product)->isModified()) {
            $this->product = $product;
        }

        return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\Product $related_product
     *
     * @return $this
     */
    public function setRelatedProduct(\BiberLtd\Bundle\ProductManagementBundle\Entity\Product $related_product) {
        if($this->setModified('related_product', $related_product)->isModified()) {
            $this->related_product = $related_product;
        }

        return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    public function getRelatedProduct() {
        return $this->related_product;
    }
}
