<?php
/**
 * @name        ProductCategoryLocalization
 * @package		BiberLtd\Core\ProductManagementBundle
 *
 * @author		Murat Ünal
 *
 * @version     1.0.1
 * @date        29.09.2013
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Core\CoreEntity;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="product_category_localization",
 *     options={"charset":"utf8","collate":"utf8-turkish-ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idx_u_product_category_localization_url_key", columns={"language","url_key"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_product_category_localization", columns={"language"})}
 * )
 */
class ProductCategoryLocalization extends CoreEntity
{
    /** 
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $name;

    /** 
     * @ORM\Column(type="string", length=155, nullable=false)
     */
    private $url_key;

    /** 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /** 
     * @ORM\Column(type="string", length=155, nullable=true)
     */
    private $meta_keywords;

    /** 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $meta_description;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(
     *     targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory",
     *     inversedBy="localizations"
     * )
     * @ORM\JoinColumn(name="category", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $category;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ORM\Id
     */
    private $language;

    /**
     * @name                  setCategory ()
     *                                    Sets the category property.
     *                                    Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $category
     *
     * @return          object                $this
     */
    public function setCategory($category) {
        if(!$this->setModified('category', $category)->isModified()) {
            return $this;
        }
		$this->category = $category;
		return $this;
    }

    /**
     * @name            getCategory ()
     *                              Returns the value of category property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->category
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @name                  setDescription ()
     *                                       Sets the description property.
     *                                       Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $description
     *
     * @return          object                $this
     */
    public function setDescription($description) {
        if(!$this->setModified('description', $description)->isModified()) {
            return $this;
        }
		$this->description = $description;
		return $this;
    }

    /**
     * @name            getDescription ()
     *                                 Returns the value of description property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->description
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @name                  setLanguage ()
     *                                    Sets the language property.
     *                                    Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $language
     *
     * @return          object                $this
     */
    public function setLanguage($language) {
        if(!$this->setModified('language', $language)->isModified()) {
            return $this;
        }
		$this->language = $language;
		return $this;
    }

    /**
     * @name            getLanguage ()
     *                              Returns the value of language property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->language
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @name                  setMetaDescription ()
     *                                           Sets the meta_description property.
     *                                           Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $meta_description
     *
     * @return          object                $this
     */
    public function setMetaDescription($meta_description) {
        if(!$this->setModified('meta_description', $meta_description)->isModified()) {
            return $this;
        }
		$this->meta_description = $meta_description;
		return $this;
    }

    /**
     * @name            getMetaDescription ()
     *                                     Returns the value of meta_description property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->meta_description
     */
    public function getMetaDescription() {
        return $this->meta_description;
    }

    /**
     * @name                  setMetaKeywords ()
     *                                        Sets the meta_keywords property.
     *                                        Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $meta_keywords
     *
     * @return          object                $this
     */
    public function setMetaKeywords($meta_keywords) {
        if(!$this->setModified('meta_keywords', $meta_keywords)->isModified()) {
            return $this;
        }
		$this->meta_keywords = $meta_keywords;
		return $this;
    }

    /**
     * @name            getMetaKeywords ()
     *                                  Returns the value of meta_keywords property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->meta_keywords
     */
    public function getMetaKeywords() {
        return $this->meta_keywords;
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
        if(!$this->setModified('name', $name)->isModified()) {
            return $this;
        }
		$this->name = $name;
		return $this;
    }

    /**
     * @name            getName ()
     *                          Returns the value of name property.
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
     * @name                  setUrlKey ()
     *                                  Sets the url_key property.
     *                                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $url_key
     *
     * @return          object                $this
     */
    public function setUrlKey($url_key) {
        if(!$this->setModified('url_key', $url_key)->isModified()) {
            return $this;
        }
		$this->url_key = $url_key;
		return $this;
    }

    /**
     * @name            getUrlKey ()
     *                            Returns the value of url_key property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->url_key
     */
    public function getUrlKey() {
        return $this->url_key;
    }
    /******************************************************************
     * PUBLIC SET AND GET FUNCTIONS                                   *
     ******************************************************************/

}
/**
 * Change Log:
 * **************************************
 * v1.0.1                      Can Berkol
 * 29.09.2013
 * **************************************
 * A getCategory()
 * A setCategory()
 * D getProductCategory()
 * D setProductCategory()
 *
 * **************************************
 * v1.0.0                      Murat Ünal
 * 11.09.2013
 * **************************************
 * A getDescription()
 * A getLanguage()
 * A getMetaDescription()
 * A getMetaKeywords()
 * A getName()
 * A getProductCategory()
 * A getUrlKey()
 *
 * A setDescription()
 * A setLanguage()
 * A setMetaDescription()
 * A setMetaKeywords()
 * A setName()
 * A setProductCategory()
 * A setUrlKey()
 *
 */