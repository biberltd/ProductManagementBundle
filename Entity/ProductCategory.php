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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreLocalizableEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="product_category",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idxNProductCategoryDateAdded", columns={"date_added"}),
 *         @ORM\Index(name="idxNProductCategoryDateUpdated", columns={"date_updated"}),
 *         @ORM\Index(name="idxNProductCategoryDateRemoved", columns={"date_removed"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUProductCategoryId", columns={"id"})}
 * )
 */
class ProductCategory extends CoreLocalizableEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"t"})
     * @var string
     */
    private $level;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_children;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $date_updated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $date_removed;

    /**
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"n"})
     * @var string
     */
    private $is_featured;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $google_cat;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default":1})
     * @var int
     */
    private $sort_order;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File", cascade={"persist"})
     * @ORM\JoinColumn(name="preview_image", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    private $preview_image;

    /**
     * @ORM\OneToMany(targetEntity="ProductCategory", mappedBy="parent")
     * @var array
     */
    private $product_categories;

    /**
     * @ORM\OneToMany(targetEntity="ProductCategoryLocalization", mappedBy="category")
     * @var array
     */
    protected $localizations;

    /**
     * @ORM\ManyToOne(targetEntity="ProductCategory", inversedBy="product_categories")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id", onDelete="RESTRICT")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory
     */
    private $parent;


    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\SiteManagementBundle\Entity\Site
     */
    private $site;

    /**
     * @ORM\OneToMany(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\CategoriesOfProduct", mappedBy="category", cascade={"persist", "remove"}, orphanRemoval=true))
     * @var ArrayCollection
     */
    private $products;

    /**
     * Product constructor.
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @param int $count_children
     *
     * @return $this
     */
    public function setCountChildren(int $count_children) {
        if(!$this->setModified('count_children', $count_children)->isModified()) {
            return $this;
        }
        $this->count_children = $count_children;
        return $this;
    }

    /**
     * @return int
     */
    public function getCountChildren() {
        return $this->count_children;
    }

    /**
     * @param string $level
     *
     * @return $this
     */
    public function setLevel(string $level) {
        if(!$this->setModified('level', $level)->isModified()) {
            return $this;
        }
        $this->level = $level;
        return $this;
    }

    /**
     * @return string
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @param array $product_categories
     *
     * @return $this
     */
    public function setProductCategories(array $product_categories) {
        if(!$this->setModified('product_categories', $product_categories)->isModified()) {
            return $this;
        }
        $this->product_categories = $product_categories;
        return $this;
    }

    /**
     * @return array
     */
    public function getProductCategories() {
        return $this->product_categories;
    }

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory $product_category
     *
     * @return $this
     */
    public function setParent(\BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory $product_category) {
        if(!$this->setModified('parent', $product_category)->isModified()) {
            return $this;
        }
        $this->parent = $product_category;
        return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductCategory
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * @param \BiberLtd\Bundle\SiteManagementBundle\Entity\Site $site
     *
     * @return $this
     */
    public function setSite(\BiberLtd\Bundle\SiteManagementBundle\Entity\Site $site) {
        if(!$this->setModified('site', $site)->isModified()) {
            return $this;
        }
        $this->site = $site;
        return $this;
    }

    /**
     * @return \BiberLtd\Bundle\SiteManagementBundle\Entity\Site
     */
    public function getSite() {
        return $this->site;
    }

    /**
     * @param string $is_featured
     *
     * @return $this
     */
    public function setIsFeatured(string $is_featured) {
        if($this->setModified('is_featured', $is_featured)->isModified()) {
            $this->is_featured = $is_featured;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getIsFeatured() {
        return $this->is_featured;
    }

    /**
     * @param int $sort_order
     *
     * @return $this
     */
    public function setSortOrder(int $sort_order) {
        if($this->setModified('sort_order', $sort_order)->isModified()) {
            $this->sort_order = $sort_order;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder() {
        return $this->sort_order;
    }

    /**
     * @param int $preview_image
     *
     * @return $this
     */
    public function setPreviewImage(int $preview_image) {
        if($this->setModified('preview_image', $preview_image)->isModified()) {
            $this->preview_image = $preview_image;
        }

        return $this;
    }

    /**
     * @return \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    public function getPreviewImage() {
        return $this->preview_image;
    }

    /**
     * @param CategoriesOfProduct $copEntry
     */
    public function addProduct(CategoriesOfProduct $copEntry){
        if(!$this->products instanceof ArrayCollection){
            $this->products = new ArrayCollection();
        }
        if (!$this->products->contains($copEntry)) {
            $this->products->add($copEntry);
            $copEntry->setCategory($this);
        }
    }

    /**
     * @param CategoriesOfProduct $copEntry
     * @return $this
     */
    public function removeProduct(CategoriesOfProduct $copEntry)
    {
        if(!$this->products instanceof ArrayCollection){
            $this->products = new ArrayCollection();
        }
        if ($this->products->contains($copEntry)) {
            $this->products->removeElement($copEntry);
            $copEntry->setCategory(null);
        }

        return $this;
    }

    /**
     * @param bool $salt
     * @return array|ArrayCollection
     */
    public function getProducts(bool $salt = false){
        if(!$this->products instanceof ArrayCollection){
            $this->products = new ArrayCollection();
        }
        if(!$salt){
            return $this->products;
        }
        $products = [];
        /**
         * @var CategoriesOfProduct $copEntry
         */
        foreach ($this->products as $copEntry){
            $products[] = $copEntry->getProduct();
        }
        return $products;
    }

    /**
     * @return string
     */
    public function getGoogleCat()
    {
        return $this->google_cat;
    }

    /**
     * @param string $google_cat
     */
    public function setGoogleCat($google_cat)
    {
        $this->google_cat = $google_cat;
    }
}
