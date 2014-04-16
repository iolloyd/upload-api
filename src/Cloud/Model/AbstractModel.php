<?php

namespace Cloud\Model;

abstract class AbstractModel 
{
    public $createdAt;

    protected $name;

    protected $id   = null;
    protected $bean = null;
    protected $data = [];

    /**
     * Returns an instance of a subclass
     *
     * @param int $id The object id 
     * @return Cloud\Model\AbstractModel $class subclass
     */
    public static function find($id)
    {
        $name = self::getName();
        $bean = \R::load($name, $id);
        $class = self::makeObjectFromBean($bean);

        return $class;
    }

    /**
     * Returns a list of subclasses
     *
     * @return array $output subclass 
     */
    public static function findAll()
    {
        $name = self::getName();
        $beans = \R::findAll($name);
        $output = [];
        foreach ($beans as $bean) {
            $output[] = self::makeObjectFromBean($bean);
        }

        return $output;
    }

    /**
     * Stores object details
     *
     * @return void
     */
    public function save()
    {
        if ($this->bean == null) {
            $this->bean = \R::dispense($this->name);
            $this->bean->created_at = time(); 
        }

        foreach ($this->getColumnNames() as $key) {
            $this->bean->{$key} = $this->{$key};
        }

        $this->bean->updated_at = time();
        \R::store($this->bean);
    }

    public function serialize()
    {
        $output = [];
        foreach ($this->getColumnNames() as $key) {
            $output[$key] = $this->{$key};
        }

        return $output;
    }

    protected static function getName()
    {
        $name = get_class_vars(get_called_class())['name'];
        return $name;
    }

    protected static function makeObjectFromBean($bean)
    {
        $class = get_called_class();
        $class = new $class();
        $class->populate($bean);
        foreach ($class->export() as $key => $value) {
            $class->{$key} = $value;
        }; 

        return $class;
    }

    protected function populate($bean)
    {
        $this->bean = $bean;
    }

    protected function export()
    {
        return $this->bean->export();
    }

    protected function getColumnNames()
    {
        $properties = $this->getPublicProperties();
        $columns = array_map(function($x) {return $x->name;}, $properties);
        return $columns;
    }

    protected function getPublicProperties()
    {
        $reflector = new \ReflectionClass(get_called_class());
        $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
        return $properties;
    }


}

