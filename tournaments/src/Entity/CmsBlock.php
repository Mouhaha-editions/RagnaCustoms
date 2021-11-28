<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Repository\CmsBlockRepository;
use Gedmo\Translatable\Translatable;


/**
 * CmsBlock
 *
 * @ORM\Table(name="block_cms")
 * @ORM\Entity(repositoryClass=CmsBlockRepository::class)
 */
class CmsBlock implements Translatable
{
    const TITLE_ONLY = 0;
    const TEXT_WYSIWYG = 1;
    const BLOCK_BLANC = 2;
    const ILLUSTRATION = 3;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label",type="string", length=50, nullable=false)
     */
    protected $label;

    /**
     * @Gedmo\Slug(fields={"label"})
     * @ORM\Column(length=190, unique=true)
     */
    protected $slug;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="content",type="text", nullable=true)
     */
    protected $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="type",type="integer")
     */
    protected $type;
    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $dateUpdated;
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return CmsBlock
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return CmsBlock
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return CmsBlock
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     *
     * @return CmsBlock
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }



    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return CmsBlock
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateUpdated
     *
     * @param \DateTime $dateUpdated
     *
     * @return CmsBlock
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * Get dateUpdated
     *
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
