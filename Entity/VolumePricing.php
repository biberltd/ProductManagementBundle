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
 *     name="volume_pricing",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idxNVolumePricing", columns={"product","id"}),
 *         @ORM\Index(name="idxNVolumePricingDateAdded", columns={"date_added"}),
 *         @ORM\Index(name="idxNVolumePricingDateUpdated", columns={"date_updated"}),
 *         @ORM\Index(name="idxNVolumePricingDateRemoved", columns={"date_removed"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUVolumePricingId", columns={"id"})}
 * )
 */
class VolumePricing extends CoreEntity
{
    /**
     * @ORM\Column(type="integer", length=15)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     * @var int
     */
    private $id;
    /** 
     * @ORM\Column(type="integer", length=5, nullable=false)
     * @var int
     */
    private $quantity_limit;

    /** 
     * @ORM\Column(type="string", length=2, nullable=false, options={"default":"xm"})
     * @var string
     */
    private $limit_direction;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /** 
     * @ORM\Column(type="datetime", nullable=false, options={"default":"xl"})
     * @var \DateTime
     */
    public $date_updated;

    /** 
     * @ORM\Column(type="decimal", length=8, nullable=false, options={"default":"0.00"})
     * @var float
     */
    private $price;

    /** 
     * @ORM\Column(type="decimal", length=8, nullable=true)
     * @var float
     */
    private $discounted_price;
	/**
	 * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
	 */
	public $date_removed;

	/**
	 *
	 * @ORM\ManyToOne(targetEntity="Product")
	 * @ORM\JoinColumn(name="product", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
	 */
	private $product;

    /**
     * @param float $discounted_price
     *
     * @return $this
     */
    public function setDiscountedPrice(\float $discounted_price) {
        if($this->setModified('discounted_price', $discounted_price)->isModified()) {
            $this->discounted_price = $discounted_price;
        }

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountedPrice() {
        return $this->discounted_price;
    }

    /**
     * @param string $limit_direction
     *
     * @return $this
     */
    public function setLimitDirection(\string $limit_direction) {
        if($this->setModified('limit_direction', $limit_direction)->isModified()) {
            $this->limit_direction = $limit_direction;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getLimitDirection() {
        return $this->limit_direction;
    }

    /**
     * @param float $price
     *
     * @return $this
     */
    public function setPrice(\float $price) {
        if($this->setModified('price', $price)->isModified()) {
            $this->price = $price;
        }

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice() {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

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
     * @param int $quantity_limit
     *
     * @return $this
     */
    public function setQuantityLimit(\integer $quantity_limit) {
        if($this->setModified('quantity_limit', $quantity_limit)->isModified()) {
            $this->quantity_limit = $quantity_limit;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getQuantityLimit() {
        return $this->quantity_limit;
    }
}