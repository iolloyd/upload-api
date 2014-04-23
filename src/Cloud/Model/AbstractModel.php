<?php

namespace Cloud\Model;

abstract class AbstractModel 
{
    protected $id   = null;
    protected $bean = null;
    protected $data = [];

    protected $oneToMany = [];
    protected $manyToMany = [];

    protected function getBean()
    {
        return $this->bean;
    }

    /**
     * Returns an instance of a subclass
     *
     * @param int $id The object id 
     *
     * @return Cloud\Model\AbstractModel $class subclass
     */
    public static function find($id)
    {
        $tableName = self::getTableName();
        $bean = \R::load($tableName, $id);
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
        $tableName = self::getTableName();
        $beans = \R::findAll($tableName);
        $output = [];
        foreach ($beans as $bean) {
            $output[] = self::makeObjectFromBean($bean);
        }

        return $output;
    }

    /**
     * Adds the object as a related item.
     * This can be 1-M or M-M.
     *
     * @param AbstractModel $object
     * @return void
     */
    public function add($object)
    {
        $tableName    = $object->getTableName();
        $isOneToMany  = in_array($tableName, $this->oneToMany);
        $isManyToMany = in_array($tableName, $this->manyToMany);

        if (!($isOneToMany || $isManyToMany)) {
            throw new \Exception( "Tried to add a non-existent relationship: $tableName");
        }
        if ($isOneToMany) {
            $list = 'own' . ucfirst($tableName) . 'List';
        } 
        if ($isManyToMany) {
            $list = 'shared' . ucfirst($tableName) . 'List';
        }
        $this->bean->{$list}[] = $object->getBean();
    }

    /**
     * Returns available column names that relate to 
     * table field names.
     *
     * @return array $columns column names
     */
    public function getColumnNames()
    {
        $properties = $this->getPublicProperties();
        $columns = array_map(function($x) {return $x->name;}, $properties);

        return $columns;
    }

    /**
     * Relates an object via M:M
     *
     * @param AbstractModel $model
     * @return void
     */
    public function manyToMany($model)
    {
        $this->manyToMany[] = $model;
    }

    /**
     * Relates an object via 1:M
     *
     * @param AbstractModel $model
     * @return void
     */
    public function oneToMany($model)
    {
        $this->oneToMany[] = $model;
    }

    /**
     * Stores object details
     *
     * @return void
     */
    public function save()
    {
        if ($this->bean == null) {
            $this->bean = \R::dispense($this->getTableName());
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
        $objectAsArray = [];
        foreach ($this->getColumnNames() as $key) {
            $output[$key] = $this->{$key};
        }

        return $objectAsArray;
    }

    protected static function getTableName()
    {
        $class = get_called_class();
        $parts = explode('\\', $class);
        return strtolower(end($parts));
    }

    protected static function makeObjectFromBean($bean)
    {
        $class = get_called_class();
        $class = new $class();
        $class->populate($bean);

        foreach ($class->export() as $property => $value) {
            $class->{$property} = $value;
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

    protected function getPublicProperties()
    {
        $reflector = new \ReflectionClass(get_called_class());
        $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
        return $properties;
    }

}

