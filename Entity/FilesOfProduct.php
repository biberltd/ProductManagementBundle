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
 *     name="files_of_product",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idxUFilesOfProductDateAdded", columns={"date_added"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUFilesOfProduct", columns={"file","product"})}
 * )
 */
class FilesOfProduct extends CoreEntity
{
    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     * @var int
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /** 
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"i"})
     * @var string
     */
    private $type;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File", cascade={"persist"})
     * @ORM\JoinColumn(name="file", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    private $file;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product", cascade={"persist"})
     * @ORM\JoinColumn(name="product", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    private $product;

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\File $file
     *
     * @return $this
     */
    public function setFile(\BiberLtd\Bundle\ProductManagementBundle\Entity\File $file) {
        if(!$this->setModified('file', $file)->isModified()) {
            return $this;
        }
		$this->file = $file;
		return $this;
    }

    /**
     * @return \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product
     *
     * @return $this
     */
    public function setProduct(\BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product) {
        if(!$this->setModified('product', $product)->isModified()) {
            return $this;
        }
		$this->product = $product;
		return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @param int $sort_order
     *
     * @return $this
     */
    public function setSortOrder(integer $sort_order) {
        if(!$this->setModified('sort_order', $sort_order)->isModified()) {
            return $this;
        }
		$this->sort_order = $sort_order;
		return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder() {
        return $this->sort_order;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type) {
        if(!$this->setModified('type', $type)->isModified()) {
            return $this;
        }
		$this->type = $type;
		return $this;
    }

    /**
     * @name            getType ()
     *                          Returns the value of type property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->type
     */
    public function getType() {
        return $this->type;
    }
}