<?php
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
     */
    private $id;

    /** 
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $name;

    /** 
     * @ORM\Column(type="text", nullable=true)
     */
    private $logo;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_updated;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $date_removed;

    /** 
     * @ORM\OneToMany(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Product", mappedBy="brand")
     */
    private $products;

    /**
     * @name            getId()
     *                      Returns the value of id property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @name                  setLogo ()
     *                                Sets the logo property.
     *                                Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $logo
     *
     * @return          object                $this
     */
    public function setLogo($logo) {
        if($this->setModified('logo', $logo)->isModified()) {
            $this->logo = $logo;
        }

        return $this;
    }

    /**
     * @name            getLogo ()
     *                          Returns the value of logo property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->logo
     */
    public function getLogo() {
        return $this->logo;
    }

    /**
     * @name                  setName ()
     *                                Sets the name property.
     *                                Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $name
     *
     * @return          object                $this
     */
    public function setName($name) {
        if($this->setModified('name', $name)->isModified()) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * @name            getName ()
     *                  Returns the value of name property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @name            setProducts ()
     *                  Sets the products property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $products
     *
     * @return          object                $this
     */
    public function setProducts($products) {
        if($this->setModified('products', $products)->isModified()) {
            $this->products = $products;
        }

        return $this;
    }

    /**
     * @name            getProducts ()
     *                  Returns the value of products property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->products
     */
    public function getProducts() {
        return $this->products;
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
 */