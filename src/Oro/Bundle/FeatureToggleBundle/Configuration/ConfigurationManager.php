<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

class ConfigurationManager
{
    /**
     * @var ConfigurationProvider
     */
    protected $configurationProvider;

    /**
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param string $resource
     * @param string $resourceType
     * @return array
     */
    public function getResourceFeatures($resource, $resourceType)
    {
        $configuration = $this->configurationProvider->getConfiguration();

        $features = [];
        foreach ($configuration as $featureName => $config) {
            if (array_key_exists($resourceType, $config) && in_array($resource, $config[$resourceType])) {
                $features[] = $featureName;
            }
        }

        return $features;
    }

    /**
     * @param string $featureName
     * @return array
     */
    public function getDependOnFeatures($featureName)
    {
        $dependOnFeatures = $this->get($featureName, 'dependency');

        $features = [];
        foreach ($dependOnFeatures as $dependOnFeature) {
            $features[] = $this->getDependOnFeatures($dependOnFeature);
        }
    
        return $features;
    }

    /**
     * @param string $feature
     * @param string $node
     * @param null|mixed $default
     * @return mixed
     */
    public function get($feature, $node, $default = null)
    {
        $configuration = $this->configurationProvider->getFeaturesConfiguration();
        if (array_key_exists($feature, $configuration) && array_key_exists($node, $configuration[$feature])) {
            return $configuration[$feature][$node];
        }

        return $default;
    }

    /**
     * @param string $resourceType
     * @param string $resource
     * @return array
     */
    public function getFeaturesByResource($resourceType, $resource)
    {
        $configuration = $this->configurationProvider->getResourcesConfiguration();
        if (array_key_exists($resourceType, $configuration)
            && array_key_exists($resource, $configuration[$resourceType])
        ) {
            return $configuration[$resourceType][$resource];
        }

        return [];
    }

    /**
     * @param string $feature
     * @return array
     */
    public function getFeatureDependencies($feature)
    {
        $configuration = $this->configurationProvider->getDependenciesConfiguration();
        if (array_key_exists($feature, $configuration)) {
            return $configuration[$feature];
        }

        return [];
    }
}
