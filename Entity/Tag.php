<?php
/**
 * @author        Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        05.03.2017
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;
/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="tag",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"}
 * )
 */
class Tag extends CoreEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=155, nullable=false)
     * @var string
     */
    private $name;
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
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name) {
        if($this->setModified('name', $name)->isModified()) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $urlKey
     *
     * @return $this
     */
    public function setUrlKey(string $urlKey) {
        if($this->setModified('name', $urlKey)->isModified()) {
            $this->url_key = $urlKey;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUrlKey() {
        return $this->url_key;
    }
}