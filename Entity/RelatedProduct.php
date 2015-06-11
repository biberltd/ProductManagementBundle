<?php
/**
 * @name        RelatedProduct
 * @package		BiberLtd\Bundle\CoreBundle\ProductManagementBundle
 *
 * @author		Can Berkol
 *
 * @version     1.0.0
 * @date        22.05.2014
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;
use BiberLtd\Bundle\CoreBundle\CoreEntity;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="related_product",
 *     options={"charset":"utf8","engine":"innodb","collate":"utf8_turkish_ci"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_related_products", columns={"product","related_product"})}
 * )
 */
class RelatedProduct extends CoreEntity
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $product;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Product")
     * @ORM\JoinColumn(
     *     name="related_product",
     *     referencedColumnName="id",
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    private $related_product;

    /**
     * @name            setProduct ()
     *                  Sets the product property.
     *                  Updates the data only if stored value and value to be set are different.
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
        if($this->setModified('product', $product)->isModified()) {
            $this->product = $product;
        }

        return $this;
    }

    /**
     * @name            getProduct ()
     *                  Returns the value of product property.
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
     * @name            setRelatedProduct ()
     *                  Sets the related_product property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $related_product
     *
     * @return          object                $this
     */
    public function setRelatedProduct($related_product) {
        if($this->setModified('related_product', $related_product)->isModified()) {
            $this->related_product = $related_product;
        }

        return $this;
    }

    /**
     * @name            getRelatedProduct ()
     *                  Returns the value of related_product property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->related_product
     */
    public function getRelatedProduct() {
        return $this->related_product;
    }
}
/**
 * Change Log:
 * **************************************
 * v1.0.0                      Can Berkol
 * 22.05.2014
 * **************************************
 * File Created
 */