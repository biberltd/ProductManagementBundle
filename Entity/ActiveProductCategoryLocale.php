<?php
/**
 * @author		Can Berkol
 * @author		Said İmamoğlu
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
 *     name="active_product_category_locale",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUActiveLocaleOfProductCategory", columns={"category","locale"})}
 * )
 */
class ActiveProductCategoryLocale extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ProductCategory")
     * @ORM\JoinColumn(name="category", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory
     */
    private $category;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="locale", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    private $locale;

	/**
	 * @param \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $locale
	 *
	 * @return $this
	 */
    public function setLocale(\BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $locale) {
        if($this->setModified('locale', $locale)->isModified()) {
            $this->locale = $locale;
        }

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
	 */
    public function getLocale() {
        return $this->locale;
    }

	/**
	 * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory $category
	 *
	 * @return $this
	 */
	public function setCategory(\BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory $category) {
		if($this->setModified('category', $category)->isModified()) {
			$this->category = $category;
		}

		return $this;
	}

	/**
	 * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory
	 */
	public function getCategory() {
		return $this->category;
	}
}