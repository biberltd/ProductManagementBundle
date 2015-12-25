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
 *     name="product_category_localization",
 *     options={"charset":"utf8","collate":"utf8-turkish-ci","engine":"innodb"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idxUProductCategoryLocalization", columns={"language","category"}),
 *         @ORM\UniqueConstraint(name="idxUProductCategoryUrlKey", columns={"language","url_key","category"})
 *     }
 * )
 */
class ProductCategoryLocalization extends CoreEntity
{
    /** 
     * @ORM\Column(type="string", length=45, nullable=true)
     * @var string
     */
    private $name;

    /** 
     * @ORM\Column(type="string", length=155, nullable=false)
     * @var string
     */
    private $url_key;

    /** 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $description;

    /** 
     * @ORM\Column(type="string", length=155, nullable=true)
     * @var string
     */
    private $meta_keywords;

    /** 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $meta_description;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ProductCategory", inversedBy="localizations", cascade={"persist"})
     * @ORM\JoinColumn(name="category", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory
     */
    private $category;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ORM\Id
     * @var \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    private $language;

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory $category
     *
     * @return $this
     */
    public function setCategory(\BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory $category) {
        if(!$this->setModified('category', $category)->isModified()) {
            return $this;
        }
		$this->category = $category;
		return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(\string $description) {
        if(!$this->setModified('description', $description)->isModified()) {
            return $this;
        }
		$this->description = $description;
		return $this;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language
     *
     * @return $this
     */
    public function setLanguage(\BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language) {
        if(!$this->setModified('language', $language)->isModified()) {
            return $this;
        }
		$this->language = $language;
		return $this;
    }

    /**
     * @return \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param string $meta_description
     *
     * @return $this
     */
    public function setMetaDescription(\string $meta_description) {
        if(!$this->setModified('meta_description', $meta_description)->isModified()) {
            return $this;
        }
		$this->meta_description = $meta_description;
		return $this;
    }

    /**
     * @return string
     */
    public function getMetaDescription() {
        return $this->meta_description;
    }

    /**
     * @param string $meta_keywords
     *
     * @return $this
     */
    public function setMetaKeywords(\string $meta_keywords) {
        if(!$this->setModified('meta_keywords', $meta_keywords)->isModified()) {
            return $this;
        }
		$this->meta_keywords = $meta_keywords;
		return $this;
    }

    /**
     * @return string
     */
    public function getMetaKeywords() {
        return $this->meta_keywords;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(\string $name) {
        if(!$this->setModified('name', $name)->isModified()) {
            return $this;
        }
		$this->name = $name;
		return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $url_key
     *
     * @return $this
     */
    public function setUrlKey(\string $url_key) {
        if(!$this->setModified('url_key', $url_key)->isModified()) {
            return $this;
        }
		$this->url_key = $url_key;
		return $this;
    }

    /**
     * @return string
     */
    public function getUrlKey() {
        return $this->url_key;
    }
}