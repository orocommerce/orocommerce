<?php

namespace Oro\Bundle\CatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Oro\Bundle\CatalogBundle\Model\ExtendCategory;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DenormalizedPropertyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Component\Tree\Entity\TreeTrait;

/**
 * Represents product category
 * @ORM\Table(
 *      name="oro_catalog_category",
 *      indexes={
 *              @ORM\Index(name="idx_oro_category_default_title", columns={"title"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository")
 * @Gedmo\Tree(type="nested")
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="slugPrototypes",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_catalog_cat_slug_prototype",
 *              joinColumns={
 *                  @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(
 *                      name="localized_value_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE",
 *                      unique=true
 *                  )
 *              }
 *          )
 *      ),
 *     @ORM\AssociationOverride(
 *          name="slugs",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_catalog_cat_slug",
 *              joinColumns={
 *                  @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(name="slug_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
 *              }
 *          )
 *      )
 * })
 * @Config(
 *      routeName="oro_catalog_category_index",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-folder"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="catalog"
 *          },
 *          "activity"={
 *              "show_on_page"="\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::UPDATE_PAGE"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "slug"={
 *              "source"="titles"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Category extends ExtendCategory implements
    SluggableInterface,
    DatesAwareInterface,
    OrganizationAwareInterface,
    DenormalizedPropertyAwareInterface
{
    use DatesAwareTrait;
    use TreeTrait;
    use SluggableTrait;
    use OrganizationAwareTrait;

    const MATERIALIZED_PATH_DELIMITER = '_';
    const CATEGORY_PATH_DELIMITER = ' / ';
    const FIELD_PARENT_CATEGORY = 'parentCategory';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=false
     *          },
     *          "importexport"={
     *              "identity"=true,
     *              "order"=10
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var Collection|CategoryTitle[]
     *
     * @ORM\OneToMany(targetEntity="CategoryTitle", mappedBy="category", cascade={"ALL"}, orphanRemoval=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=20,
     *              "full"=true,
     *              "fallback_field"="string"
     *          }
     *      }
     * )
     */
    protected $titles;

    /**
     * @var Category
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="childCategories")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=30
     *          }
     *      }
     * )
     */
    protected $parentCategory;

    /**
     * @var Collection|Category[]
     *
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parentCategory", cascade={"persist"})
     * @ORM\OrderBy({"left" = "ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $childCategories;

    /**
     * @var Collection|CategoryShortDescription[]
     *
     * @ORM\OneToMany(targetEntity="CategoryShortDescription", mappedBy="category", cascade={"ALL"}, orphanRemoval=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=50,
     *              "full"=true,
     *              "fallback_field"="text"
     *          }
     *      }
     * )
     */
    protected $shortDescriptions;

    /**
     * @var Collection|CategoryLongDescription[]
     *
     * @ORM\OneToMany(targetEntity="CategoryLongDescription", mappedBy="category", cascade={"ALL"}, orphanRemoval=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=60,
     *              "full"=true,
     *              "fallback_field"="wysiwyg"
     *          },
     *          "attachment"={
     *              "acl_protected"=false
     *          }
     *      }
     * )
     */
    protected $longDescriptions;

    /**
     * @var CategoryDefaultProductOptions
     *
     * @ORM\OneToOne(targetEntity="CategoryDefaultProductOptions", cascade={"persist"})
     * @ORM\JoinColumn(name="default_product_options_id", nullable=true, referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $defaultProductOptions;

    /**
     * @var string
     *
     * @ORM\Column(name="materialized_path", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $materializedPath;

    /**
     * This is a mirror field for performance reasons only.
     * It mirrors getDefaultTitle()->getString()
     *
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      },
     *      mode="hidden"
     * )
     */
    protected $denormalizedDefaultTitle;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @Symfony\Component\Validator\Constraints\All(
     *     constraints = {
     *         @Oro\Bundle\RedirectBundle\Validator\Constraints\UrlSafeSlugPrototype(allowSlashes=true)
     *     }
     * )
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     *
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=40,
     *              "full"=true,
     *              "fallback_field"="string"
     *          }
     *      }
     * )
     */
    protected $slugPrototypes;

    /**
     * @var integer
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="tree_left", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $left;

    /**
     * @var integer
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="tree_level", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $level;

    /**
     * @var integer
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="tree_right", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $right;

    /**
     * @var integer
     *
     * @Gedmo\TreeRoot(identifierMethod="getRoot")
     * @ORM\Column(name="tree_root", type="integer", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $root;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=80
     *          }
     *      }
     * )
     */
    protected $organization;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles = new ArrayCollection();
        $this->childCategories = new ArrayCollection();
        $this->shortDescriptions = new ArrayCollection();
        $this->longDescriptions = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultTitle($value)
    {
        $this->setDefaultFallbackValue($this->titles, $value, CategoryTitle::class);
        $this->getDefaultTitle()->setCategory($this);

        return $this;
    }

    /**
     * @return Collection|CategoryTitle[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param CategoryTitle $title
     *
     * @return $this
     */
    public function addTitle(CategoryTitle $title)
    {
        if (!$this->titles->contains($title)) {
            $title->setCategory($this);
            $this->titles->add($title);
        }

        return $this;
    }

    /**
     * @param CategoryTitle $title
     *
     * @return $this
     */
    public function removeTitle(CategoryTitle $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return Category
     */
    public function getParentCategory()
    {
        return $this->parentCategory;
    }

    /**
     * @param Category|null $parentCategory
     *
     * @return $this
     */
    public function setParentCategory(Category $parentCategory = null)
    {
        $this->parentCategory = $parentCategory;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getChildCategories()
    {
        return $this->childCategories;
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function addChildCategory(Category $category)
    {
        if (!$this->childCategories->contains($category)) {
            $this->childCategories->add($category);
            $category->setParentCategory($this);
        }

        return $this;
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function removeChildCategory(Category $category)
    {
        if ($this->childCategories->contains($category)) {
            $this->childCategories->removeElement($category);
        }

        return $this;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->updateDenormalizedProperties();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->updateDenormalizedProperties();
    }

    public function updateDenormalizedProperties(): void
    {
        if (!$this->getDefaultTitle()) {
            throw new \RuntimeException('Category has to have a default title');
        }
        $this->denormalizedDefaultTitle = $this->getDefaultTitle()->getString();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getDefaultTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultShortDescription($value)
    {
        $this->setDefaultFallbackValue($this->shortDescriptions, $value, CategoryShortDescription::class);
        $this->getDefaultShortDescription()->setCategory($this);

        return $this;
    }

    /**
     * @return Collection|CategoryShortDescription[]
     */
    public function getShortDescriptions()
    {
        return $this->shortDescriptions;
    }

    /**
     * @param CategoryShortDescription $shortDescription
     *
     * @return $this
     */
    public function addShortDescription(CategoryShortDescription $shortDescription)
    {
        if (!$this->shortDescriptions->contains($shortDescription)) {
            $shortDescription->setCategory($this);
            $this->shortDescriptions->add($shortDescription);
        }

        return $this;
    }

    /**
     * @param CategoryShortDescription $shortDescription
     *
     * @return $this
     */
    public function removeShortDescription(CategoryShortDescription $shortDescription)
    {
        if ($this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->removeElement($shortDescription);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultLongDescription($value)
    {
        $this->setDefaultFallbackValue($this->longDescriptions, $value, CategoryLongDescription::class);
        $this->getDefaultShortDescription()->setCategory($this);

        return $this;
    }

    /**
     * @return Collection|CategoryLongDescription[]
     */
    public function getLongDescriptions()
    {
        return $this->longDescriptions;
    }

    /**
     * @param CategoryLongDescription $longDescription
     *
     * @return $this
     */
    public function addLongDescription(CategoryLongDescription $longDescription)
    {
        if (!$this->longDescriptions->contains($longDescription)) {
            $longDescription->setCategory($this);
            $this->longDescriptions->add($longDescription);
        }

        return $this;
    }

    /**
     * @param CategoryLongDescription $longDescription
     *
     * @return $this
     */
    public function removeLongDescription(CategoryLongDescription $longDescription)
    {
        if ($this->longDescriptions->contains($longDescription)) {
            $this->longDescriptions->removeElement($longDescription);
        }

        return $this;
    }

    /**
     * @return CategoryDefaultProductOptions
     */
    public function getDefaultProductOptions()
    {
        return $this->defaultProductOptions;
    }

    /**
     * Set unitPrecision
     *
     * @param CategoryDefaultProductOptions $defaultProductOptions
     *
     * @return Category
     */
    public function setDefaultProductOptions(CategoryDefaultProductOptions $defaultProductOptions = null)
    {
        $this->defaultProductOptions = $defaultProductOptions;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getMaterializedPath()
    {
        return $this->materializedPath;
    }

    /**
     * @param string $materializedPath
     *
     * @return Category
     */
    public function setMaterializedPath($materializedPath)
    {
        $this->materializedPath = $materializedPath;

        return $this;
    }

    /**
     * This field is read-only, updated automatically prior to persisting
     *
     * @return string
     */
    public function getDenormalizedDefaultTitle()
    {
        return $this->denormalizedDefaultTitle;
    }
}
