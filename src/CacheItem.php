<?php

/*
 * This file is part of php-cache\adapter-common package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\Adapter\Common;

use Cache\Taggable\TaggableItemInterface;
use Cache\Taggable\TaggableItemTrait;
use Psr\Cache\CacheItemInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CacheItem implements HasExpirationDateInterface, CacheItemInterface, TaggableItemInterface
{
    use TaggableItemTrait;

    /**
     * @type string
     */
    private $key;

    /**
     * @type mixed
     */
    private $value;

    /**
     * @type \DateTimeInterface|null
     */
    private $expirationDate = null;

    /**
     * @type bool
     */
    private $expirationHasChanged = false;

    /**
     * @type bool|Callable
     */
    private $hasValue = false;

    /**
     * @param string                  $key
     * @param bool|Callable           $hasValue
     * @param mixed                   $value
     * @param \DateTimeInterface|null $expirationDate
     */
    public function __construct($key, $hasValue = false, $value = null, \DateTimeInterface $expirationDate = null)
    {
        $this->taggedKey = $key;
        $this->key       = $this->getKeyFromTaggedKey($key);
        $this->load($hasValue, $value, $expirationDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value)
    {
        $this->value    = $value;
        $this->hasValue = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if (!$this->isHit()) {
            return;
        }

        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        $this->initialize();

        if (true !== $this->hasValue) {
            return false;
        }

        if ($this->expirationDate === null) {
            return true;
        }

        return (new \DateTime()) <= $this->expirationDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getExpirationDate()
    {
        $this->initialize();

        return $this->expirationDate;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration)
    {
        $this->expirationHasChanged = true;
        $this->expirationDate = $expiration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time)
    {
        $this->expirationHasChanged = true;
        if ($time === null) {
            $this->expirationDate = null;
        }

        if ($time instanceof \DateInterval) {
            $this->expirationDate = new \DateTime();
            $this->expirationDate->add($time);
        }

        if (is_int($time)) {
            $this->expirationDate = new \DateTime(sprintf('+%sseconds', $time));
        }

        return $this;
    }

    /**
     * @param bool $hasValue
     * @param mixed $value
     * @param \DateTimeInterface $expirationDate
     */
    private function load($hasValue, $value, \DateTimeInterface $expirationDate = null)
    {
        $this->hasValue = $hasValue;
        $this->expirationDate = $expirationDate;

        if ($hasValue === true) {
            $this->value = $value;
        }
    }

    /**
     * Initialize the CacheItem with actual data
     */
    private function initialize()
    {
        if (is_callable($this->hasValue)) {
            $func = $this->hasValue;
            list($hasValue, $value, $expirationDate) = $func();
            $this->load($hasValue, $value, $expirationDate);
        }
    }

    /**
     * @return bool
     */
    public function getExpirationHasChanged()
    {
        return $this->expirationHasChanged;
    }
}
