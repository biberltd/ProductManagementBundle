<?php
/**
 * @name        ActiveProductCategoryLocale
 * @package		BiberLtd\Bundle\CoreBundle\ProductManagementBundle
 *
 * @author      Can Berkol
 *
 * @version     1.0.1
 * @date        27.04.2015
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
 *     name="active_product_category_locale",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_active_product_category_locale_id", columns={"product_category","locale"})}
 * )
 */
class ActiveProductCategoryLocale extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory")
     * @ORM\JoinColumn(name="category", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $category;

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
	 * @name            setCategory()
	 *
	 * @author          Can Berkol
	 *
	 * @since           1.0.1
	 * @version         1.0.1
	 *
	 * @use             $this->setModified()
	 *
	 * @param           mixed				$category
	 *
	 * @return          object                $this
	 */
	public function setCategory($category) {
		if($this->setModified('category', $category)->isModified()) {
			$this->category = $category;
		}

		return $this;
	}

	/**
	 * @name            getCategory ()
	 *
	 * @author          Can Berkol
	 *
	 * @since           1.0.1
	 * @version         1.0.1
	 *
	 * @return          mixed           $this->product_category
	 */
	public function getCategory() {
		return $this->category;
	}
}
/**
 * Change Log:
 * /**
 * Change Log:
 * **************************************
 * v1.0.1                      27.04.2015
 * TW#
 * Can Berkol
 * **************************************
 * A product_category
 *
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