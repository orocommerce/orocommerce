<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;

/**
 * Provides the ability to create slugs based on entity source.
 */
class SlugifyEntityHelper
{
    /**
     * @var SlugGenerator
     */
    private $slugGenerator;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param SlugGenerator $slugGenerator
     * @param ConfigManager $configManager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        SlugGenerator $slugGenerator,
        ConfigManager $configManager,
        ManagerRegistry $managerRegistry
    ) {
        $this->slugGenerator = $slugGenerator;
        $this->configManager = $configManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param SluggableInterface $entity
     */
    public function fill(SluggableInterface $entity): void
    {
        $localizedSources = $this->getSourceField($entity);
        if ($localizedSources) {
            $localizedSlugs = $entity->getSlugPrototypes();
            $this->fillFromSourceField($localizedSources, $localizedSlugs);
        }
    }

    /**
     * @param Collection|AbstractLocalizedFallbackValue[] $localizedSources
     * @param Collection|AbstractLocalizedFallbackValue[] $localizedSlugs
     */
    public function fillFromSourceField(Collection $localizedSources, Collection $localizedSlugs): void
    {
        foreach ($localizedSources as $localizedSource) {
            if (!$localizedSource->getString()) {
                continue;
            }

            $localizedSlug = $this->getSlugBySource($localizedSlugs, $localizedSource);
            if ($localizedSlug && $this->isSlugEmpty($localizedSlug)) {
                $localizedSlug->setString($this->slugGenerator->slugify($localizedSource->getString()));
                continue;
            }

            if (!$localizedSlug) {
                $localizedSlug = LocalizedFallbackValue::createFromAbstract($localizedSource);
                $localizedSlug->setString($this->slugGenerator->slugify($localizedSource->getString()));
                $localizedSlugs->add($localizedSlug);
            }
        }
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    public function getSourceFieldName(string $className): ?string
    {
        $provider = $this->configManager->getProvider('slug');
        $config = $provider->getConfig($className);

        return $config->get('source');
    }

    /**
     * @param SluggableInterface $sluggableEntity
     *
     * @return Collection
     */
    private function getSourceField(SluggableInterface $sluggableEntity): ?Collection
    {
        $className = ClassUtils::getClass($sluggableEntity);
        $sourceField = $this->getSourceFieldName($className);
        if (!$sourceField) {
            return null;
        }
        $entityManager = $this->managerRegistry->getManagerForClass($className);
        $classMetadata = $entityManager->getClassMetadata($className);

        return $classMetadata->reflFields[$sourceField]->getValue($sluggableEntity);
    }

    /**
     * Slug is considered empty if there is no text and not have parent or system slug.
     *
     * @param AbstractLocalizedFallbackValue $localizedSlug
     *
     * @return bool
     */
    private function isSlugEmpty(AbstractLocalizedFallbackValue $localizedSlug): bool
    {
        return !$localizedSlug->getString() && FallbackType::NONE === $localizedSlug->getFallback();
    }

    /**
     * @param Collection|AbstractLocalizedFallbackValue[] $localizedSlugs
     * @param AbstractLocalizedFallbackValue $localizedSource
     *
     * @return null|AbstractLocalizedFallbackValue
     */
    private function getSlugBySource(
        Collection $localizedSlugs,
        AbstractLocalizedFallbackValue $localizedSource
    ): ?AbstractLocalizedFallbackValue {
        foreach ($localizedSlugs as $localizedSlug) {
            if (null === $localizedSource->getLocalization() &&
                null === $localizedSlug->getLocalization()) {
                return $localizedSlug;
            }

            if ($localizedSource->getLocalization() &&
                $localizedSlug->getLocalization() &&
                $localizedSource->getLocalization()->getName() === $localizedSlug->getLocalization()->getName()
            ) {
                return $localizedSlug;
            }
        }

        return null;
    }
}