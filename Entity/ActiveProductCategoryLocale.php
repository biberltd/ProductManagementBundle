<?php
/**
 * @name        ActiveProductCategoryLocale
 * @package		BiberLtd\Core\ProductManagementBundle
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
namespace BiberLtd\Core\Bundles\ProductManagementBundle\Entity;
use BiberLtd\Core\CoreEntity;
use Doctrine\ORM\Mapping AS ORM;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="active_product_category_locale",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_active_product_category_locale_id", columns={"product_category","locale"})}
 * )
 */
class ActiveProductCategoryLocale extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Core\Bundles\ProductManagementBundle\Entity\ProductCategory")
     * @ORM\JoinColumn(name="product_category", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $product_category;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Core\Bundles\MultiLanguageSupportBundle\Entity\Language")
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
     * @name            setProductCategory ()
     *                  Sets the product_category property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $product_category
     *
     * @return          object                $this
     */
    public function setProductCategory($product_category) {
        if($this->setModified('product_category', $product_category)->isModified()) {
            $this->product_category = $product_category;
        }

        return $this;
    }

    /**
     * @name            getProductCategory ()
     *                  Returns the value of product_category property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->product_category
     */
    public function getProductCategory() {
        return $this->product_category;
    }
}
/**
 * Change Log:
 * **************************************
 * v1.0.0                      Can Berkol
 * 06.03.2014
 * **************************************
 * A product_category
 * A locale
 * A getLocale()
 * A getProductCategory()
 * A setLocale()
 * A setProductCategory()
 */