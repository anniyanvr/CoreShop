<?php

namespace CoreShop\Component\Taxation\Model;

use CoreShop\Component\Resource\Model\ResourceInterface;
use CoreShop\Component\Store\Model\StoreInterface;
use Doctrine\Common\Collections\Collection;

interface TaxRuleGroupInterface extends ResourceInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param $name
     *
     * @return static
     */
    public function setName($name);

    /**
     * @return bool
     */
    public function getActive();

    /**
     * @param bool $active
     *
     * @return static
     */
    public function setActive($active);

    /**
     * @return Collection|TaxRuleInterface[]
     */
    public function getTaxRules();

    /**
     * @return bool
     */
    public function hasTaxRules();

    /**
     * @param TaxRuleInterface $taxRule
     */
    public function addTaxRule(TaxRuleInterface $taxRule);

    /**
     * @param TaxRuleInterface $taxRule
     */
    public function removeTaxRule(TaxRuleInterface $taxRule);

    /**
     * @param TaxRuleInterface $taxRule
     *
     * @return bool
     */
    public function hasTaxRule(TaxRuleInterface $taxRule);

    /**
     * @return Collection|StoreInterface[]
     */
    public function getStores();

    /**
     * @return bool
     */
    public function hasStores();

    /**
     * @param StoreInterface $store
     */
    public function addStore(StoreInterface $store);

    /**
     * @param StoreInterface $store
     */
    public function removeStore(StoreInterface $store);

    /**
     * @param StoreInterface $store
     *
     * @return bool
     */
    public function hasStore(StoreInterface $store);
}
