<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;

class OrmTranslationLoader implements LoaderInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var bool|null */
    protected $dbCheck;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        /** @var MessageCatalogue $catalogue */
        $catalogue = new MessageCatalogue($locale);

        if ($this->checkDatabase()) {
            $messages = [];
            $translations = $this->getTranslationRepository()->findAllByLanguageAndDomain($locale, $domain);
            foreach ($translations as $translation) {
                $messages[$translation['key']] = $translation['value'];
            }

            $catalogue->add($messages, $domain);
        }

        return $catalogue;
    }

    /**
     * Checks whether the translations table exists in the database
     *
     * @return bool
     */
    protected function checkDatabase()
    {
        if (null === $this->dbCheck) {
            $this->dbCheck = SafeDatabaseChecker::tablesExist(
                $this->getEntityManager()->getConnection(),
                SafeDatabaseChecker::getTableName($this->doctrine, Translation::class)
            );
        }

        return $this->dbCheck;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManagerForClass(Translation::class);
    }

    /**
     * @return TranslationRepository
     */
    protected function getTranslationRepository()
    {
        return $this->getEntityManager()->getRepository(Translation::class);
    }
}
