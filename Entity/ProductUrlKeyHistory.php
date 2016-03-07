<?php
/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        25.12.2015
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;

use Doctrine\ORM\Mapping AS ORM;
use \BiberLtd\Bundle\CoreBundle\CoreEntity;
/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="product_url_key_history",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idxUProductUrlKey", columns={"url_key","date_added","product"})}
 * )
 */
class ProductUrlKeyHistory extends CoreEntity
{
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var string
     */
    private $url_key;

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
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    private $product;

    /**
     * @return string
     */
    public function getUrlKey(){
        return $this->url_key;
    }

    /**
     * @param string $url_key
     *
     * @return $this
     */
    public function setUrlKey(string $url_key){
        if(!$this->setModified('url_key', $url_key)->isModified()){
            return $this;
        }
        $this->url_key = $url_key;

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
	 */
    public function getProduct(){
        return $this->product;
    }

	/**
	 * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product
	 *
	 * @return $this
	 */
    public function setProduct(\BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product){
        if(!$this->setModified('product', $product)->isModified()){
            return $this;
        }
        $this->product = $product;

        return $this;
    }
}