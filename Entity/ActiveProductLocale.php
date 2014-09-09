<?php
/**
 * @name        ActiveProductLocale
 * @package		BiberLtd\Bundle\CoreBundle\ProductManagementBundle
 *
 * @author      Can Berkol
 *
 * @version     1.0.0
 * @date        06.03.2014
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description active_product_locale
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;
use BiberLtd\Bundle\CoreBundle\CoreEntity;
use Doctrine\ORM\Mapping AS ORM;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="active_product_locale",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_active_product_locale", columns={"product","locale"})}
 * )
 */
class ActiveProductLocale extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", nullable=false)
     */
    private $product;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="locale", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $locale;

    /**
     * @name            setLocale ()
     *                  Sets the locale property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $locale
     *
     * @return          object                $this
     */
    public function setLocale($locale) {
        if($this->setModified('locale', $locale)->isModified()) {
            $this->locale = $locale;
        }

        return $this;
    }

    /**
     * @name            getLocale ()
     *                  Returns the value of locale property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->locale
     */
    public function getLocale() {
        return $this->locale;
    }

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
}
/**
 * Change Log:
 * **************************************
 * v1.0.0                      Can Berkol
 * 06.03.2014
 * **************************************
 * A product
 * A locale
 * A getLocale()
 * A getProduct()
 * A setLocale()
 * A setProduct()
 */