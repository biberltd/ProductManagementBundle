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
 *     name="brand",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idxNBrandDateAdded", columns={"date_added"}),
 *         @ORM\Index(name="idxNBrandDateUpdated", columns={"date_updated"}),
 *         @ORM\Index(name="idxNBrandDateRemoved", columns={"date_removed"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUBrandId", columns={"id"})}
 * )
 */
class Brand extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /** 
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var string
     */
    private $name;

    /** 
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $logo;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_updated;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $date_removed;

    /** 
     * @ORM\OneToMany(targetEntity="Product", mappedBy="brand")
     * @var array
     */
    private $products;

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string $logo
     *
     * @return $this
     */
    public function setLogo(\string $logo) {
        if($this->setModified('logo', $logo)->isModified()) {
            $this->logo = $logo;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getLogo() {
        return $this->logo;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(\string $name) {
        if($this->setModified('name', $name)->isModified()) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param array $products
     *
     * @return $this
     */
    public function setProducts(array $products) {
        if($this->setModified('products', $products)->isModified()) {
            $this->products = $products;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getProducts() {
        return $this->products;
    }
}