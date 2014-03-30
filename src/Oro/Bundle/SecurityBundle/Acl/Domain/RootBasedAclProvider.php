<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

/**
 * Extends the default Symfony ACL provider with support of a root ACL.
 * It means that the special ACL named "root" will be used in case when more sufficient ACL was not found.
 */
class RootBasedAclProvider implements AclProviderInterface
{
    /**
     * @var AclProviderInterface
     */
    protected $baseAclProvider;

    /**
     * @var ObjectIdentityFactory
     */
    protected $objectIdentityFactory = null;

    /**
     * Constructor
     *
     * @param ObjectIdentityFactory $objectIdentityFactory
     */
    public function __construct(ObjectIdentityFactory $objectIdentityFactory)
    {
        $this->objectIdentityFactory = $objectIdentityFactory;
    }

    /**
     * Sets the base ACL provider
     *
     * @param AclProviderInterface $provider
     */
    public function setBaseAclProvider(AclProviderInterface $provider)
    {
        $this->baseAclProvider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function findChildren(ObjectIdentityInterface $parentOid, $directChildrenOnly = false)
    {
        return $this->baseAclProvider->findChildren($parentOid, $directChildrenOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function findAcl(ObjectIdentityInterface $oid, array $sids = array())
    {
        $rootOid = $this->objectIdentityFactory->root($oid);
        try {
            $acl = $this->getAcl($oid, $sids, $rootOid);
        } catch (AclNotFoundException $noAcl) {
            try {
                // Try to get ACL for underlying object
                $underlyingOid = $this->objectIdentityFactory->underlying($oid);
                $acl = $this->getAcl($underlyingOid, $sids, $rootOid);
                // Acl cache must be refactoring due task https://magecore.atlassian.net/browse/BAP-3649
                //$this->baseAclProvider->cacheWithUnderlyingAcl($oid);
            } catch (\Exception $noUnderlyingAcl) {
                // Try to get ACL for root object
                try {
                    $this->baseAclProvider->cacheEmptyAcl($oid);

                    return $this->baseAclProvider->findAcl($rootOid, $sids);
                } catch (AclNotFoundException $noRootAcl) {
                    throw new AclNotFoundException(
                        sprintf('There is no ACL for %s. The root ACL %s was not found as well.', $oid, $rootOid),
                        0,
                        $noAcl
                    );
                }
            }
        }

        return $acl;
    }

    /**
     * {@inheritdoc}
     */
    public function findAcls(array $oids, array $sids = array())
    {
        return $this->baseAclProvider->findAcls($oids, $sids);
    }

    /**
     * Get Acl based on given OID and Parent OID
     *
     * @param ObjectIdentityInterface $oid
     * @param array $sids
     * @param ObjectIdentityInterface $rootOid
     * @return RootBasedAclWrapper|\Symfony\Component\Security\Acl\Model\AclInterface
     */
    protected function getAcl(ObjectIdentityInterface $oid, array $sids, ObjectIdentityInterface $rootOid)
    {
        $acl = $this->baseAclProvider->findAcl($oid, $sids);
        if ($this->baseAclProvider->isReplaceWithUnderlyingAcl($acl)) {
            $underlyingOid = $this->objectIdentityFactory->underlying($oid);
            return $this->getAcl($underlyingOid, $sids, $rootOid);
        }

        try {
            $rootAcl = $this->baseAclProvider->findAcl($rootOid, $sids);
            if ($this->baseAclProvider->isEmptyAcl($acl)) {
                return $rootAcl;
            } else {
                return new RootBasedAclWrapper($acl, $rootAcl);
            }
        } catch (AclNotFoundException $noRootAcl) {
            return $acl;
        }
    }
}
