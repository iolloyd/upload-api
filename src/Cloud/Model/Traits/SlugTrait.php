<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */


namespace Cloud\Model\Traits;

/**
 * Trait for `$slug` title slug field
 *
 * Entity must declare `@HasLifecycleCallbacks` for this trait to work
 * correctly.
 */
trait SlugTrait
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @JMS\Groups({"list", "details"})
     * @JMS\ReadOnly
     */
    protected $slug;

    /**
     * Get the slug value
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set the slug value
     */
    public function setSlug($slug)
    {
      $this->slug = $slug;
    }

    /**
     * Get the fields used to generate the slug. Default: `['title']`
     *
     * @return array
     */
    protected function getSlugFields()
    {
        return ['title'];
    }

    /**
     * Returns the slug's delimiter. Default: `-`
     *
     * @return string
     */
    protected function getSlugDelimiter()
    {
        return '-';
    }

    /**
     * Returns whether or not the slug gets regenerated on update.
     * Default: `true`
     *
     * @return bool
     */
    protected function shouldRegenerateSlugOnUpdate()
    {
        return true;
    }

    /**
     * Prepare the entity's slug value on persist and update
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function prePersistPrepareSlug()
    {
        $slug = $this->getSlug();

        if (!empty($slug) && !$this->shouldRegenerateSlugOnUpdate()) {
            return;
        }

        $fields = $this->getSlugFields();
        $values = [];

        foreach ($fields as $field) {
          if (!empty($this->{$field})) {
            $values[] = $this->{$field};
          }
        }

        if (count($values) < 1) {
            // TODO FIXME
            $this->slug = uniqid();
            return;

            throw new \UnexpectedValueException(
                'Sluggable expects to have at least one usable (non-empty) field from the following: [ '
                    . implode($fields, ',') . ' ]'
            );
        }

        $slug = implode($values, ' ');
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        $slug = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $slug);
        $slug = strtolower(trim($slug, $this->getSlugDelimiter()));
        $slug = preg_replace("/[\/_|+ -]+/", $this->getSlugDelimiter(), $slug);

        $this->slug = $slug;
    }
}
