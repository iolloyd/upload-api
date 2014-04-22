<?php

namespace Cloud\Model;

abstract class AbstractModel 
{
    protected $createdAt;
    protected $tableName;

    protected $id   = null;
    protected $bean = null;
    protected $data = [];

    protected $oneToMany = [];
    protected $manyToMany = [];

    protected $relatedOneToMany = [];
    protected $relatedManyToMany = [];

    /**
     * Returns an instance of a subclass
     *
     * @param int $id The object id 
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
     * Stores object details
     *
     * @return void
     */
    public function save()
    {
        if ($this->bean == null) {
            $this->bean = \R::dispense($this->tableName);
            $this->bean->created_at = time(); 
        }

        foreach ($this->getColumnNames() as $key) {
            $this->bean->{$key} = $this->{$key};
        }

        $this->saveRelations();
        $this->bean->updated_at = time();
        \R::store($this->bean);
    }

    public function add($collection, $object)
    {
        if (isset($this->oneToMany)
            && in_array($collection, $this->oneToMany)
        ) {
            if (!isset($this->relatedOneToMany[$collection])) {
                $this->relatedOneToMany[$collection] = [];
            }

           $this->relatedOneToMany[$collection][] = $object;

        } elseif (isset($this->manyToMany) 
            && in_array($collection, $this->manyToMany)
        ) {
            if (!isset($this->relatedManyToMany[$collection])) {
                $this->relatedManyToMany[$collection] = [];
            }
            $this->relatedManyToMany[$collection][] = $object;

        } else {
            throw new \Exception( "Tried to add a non-existent relationship: $collection");
        }
    }

    protected function saveRelations()
    {
        $list = $this->relatedManyToMany;
        foreach ($list as $tableName => $objects) {
            $method = "shared" . ucfirst(strtolower($tableName)) . "List";
            foreach ($objects as $object) {
                $object->save();
                $this->bean->{$method}[] = $object->bean;
            }
        }

    }

    public function serialize()
    {
        $output = [];
        foreach ($this->getColumnNames() as $key) {
            $output[$key] = $this->{$key};
        }

        return $output;
    }

    public function oneToMany($model)
    {
        $this->oneToMany[] = $model;
    }

    public function manyToMany($model)
    {
        $this->manyToMany[] = $model;
    }

    protected static function getTableName()
    {
        $class = get_class_vars(get_called_class());
        return $class['tableName'];
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

    public function getColumnNames()
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

