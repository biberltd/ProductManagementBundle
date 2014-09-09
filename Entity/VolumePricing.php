<?php
/**
 * @name        VolumePricing
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
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;
/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="volume_pricing",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idx_n_volume_pricing", columns={"product"}),
 *         @ORM\Index(name="idx_n_volume_pricing_date_added", columns={"date_added"}),
 *         @ORM\Index(name="idx_n_volume_pricing_updated", columns={"date_updated"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_volume_pricing_id", columns={"id"})}
 * )
 */
class VolumePricing extends CoreEntity
{
    /**
     * @ORM\Column(type="integer", length=15)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     */
    private $id;
    /** 
     * @ORM\Column(type="integer", length=5, nullable=false)
     */
    private $quantity_limit;

    /** 
     * @ORM\Column(type="string", length=2, nullable=false)
     */
    private $limit_direction;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_updated;

    /** 
     * @ORM\Column(type="decimal", length=8, nullable=false)
     */
    private $price;

    /** 
     * @ORM\Column(type="decimal", length=8, nullable=true)
     */
    private $discounted_price;

    /** 
     * 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", nullable=false)
     */
    private $product;


    /**
     * @name                  setDiscountedPrice ()
     *                                           Sets the discounted_price property.
     *                                           Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $discounted_price
     *
     * @return          object                $this
     */
    public function setDiscountedPrice($discounted_price) {
        if($this->setModified('discounted_price', $discounted_price)->isModified()) {
            $this->discounted_price = $discounted_price;
        }

        return $this;
    }

    /**
     * @name            getDiscountedPrice ()
     *                                     Returns the value of discounted_price property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->discounted_price
     */
    public function getDiscountedPrice() {
        return $this->discounted_price;
    }

    /**
     * @name                  setLimitDirection ()
     *                                          Sets the limit_direction property.
     *                                          Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $limit_direction
     *
     * @return          object                $this
     */
    public function setLimitDirection($limit_direction) {
        if($this->setModified('limit_direction', $limit_direction)->isModified()) {
            $this->limit_direction = $limit_direction;
        }

        return $this;
    }

    /**
     * @name            getLimitDirection ()
     *                                    Returns the value of limit_direction property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->limit_direction
     */
    public function getLimitDirection() {
        return $this->limit_direction;
    }

    /**
     * @name                  setPrice ()
     *                                 Sets the price property.
     *                                 Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $price
     *
     * @return          object                $this
     */
    public function setPrice($price) {
        if($this->setModified('price', $price)->isModified()) {
            $this->price = $price;
        }

        return $this;
    }

    /**
     * @name            getPrice ()
     *                           Returns the value of price property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->price
     */
    public function getPrice() {
        return $this->price;
    }

    /**
     * @name            getId ()
     *                  Returns the value of id property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->pricing
     */
    public function getId() {
        return $this->id;
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
        if($this->setModified('product', $product)->isModified()) {
            $this->product = $product;
        }

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
     * @name                  setQuantityLimit ()
     *                                         Sets the quantity_limit property.
     *                                         Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $quantity_limit
     *
     * @return          object                $this
     */
    public function setQuantityLimit($quantity_limit) {
        if($this->setModified('quantity_limit', $quantity_limit)->isModified()) {
            $this->quantity_limit = $quantity_limit;
        }

        return $this;
    }

    /**
     * @name            getQuantityLimit ()
     *                                   Returns the value of quantity_limit property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->quantity_limit
     */
    public function getQuantityLimit() {
        return $this->quantity_limit;
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