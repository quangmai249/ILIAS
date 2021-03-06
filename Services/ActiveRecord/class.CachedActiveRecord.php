<?php

/**
 * Class CachedActiveRecord
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 */
abstract class CachedActiveRecord extends ActiveRecord implements arStorageInterface
{
    /**
     * @var string
     */
    private $_hash = '';

    /**
     * @return ilGlobalCache
     */
    abstract public function getCache() : ilGlobalCache;

    /**
     * @return string
     */
    final public function getCacheIdentifier() : string
    {
        if ($this->getArFieldList()->getPrimaryField()) {
            return ($this->getConnectorContainerName() . "_" . $this->getPrimaryFieldValue());
        }

        return "";
    }

    public function getTTL() : int
    {
        return 60;
    }

    /**
     * @inheritDoc
     */
    public function __construct($primary_key = 0, arConnector $connector = null)
    {
        if (is_null($connector)) {
            $connector = new arConnectorDB();
        }

        $connector = new arConnectorCache($connector);
        arConnectorMap::register($this, $connector);
        parent::__construct($primary_key, $connector);
    }

    public function afterObjectLoad()
    {
        parent::afterObjectLoad();
        $this->_hash = $this->buildHash();
    }

    private function buildHash() : string
    {
        $hashing = [];
        foreach ($this->getArFieldList()->getFields() as $field) {
            $name           = $field->getName();
            $hashing[$name] = $this->{$name};
        }
        return md5(serialize($hashing));
    }

    public function storeObjectToCache()
    {
        parent::storeObjectToCache();
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $array)
    {
        return parent::buildFromArray($array);
    }

    public function store()
    {
        parent::store();
    }

    public function save()
    {
        parent::save();
    }

    public function create()
    {
        $this->getCache()->flush();
        parent::create();
    }

    /**
     * @inheritDoc
     */
    public function copy($new_id = 0)
    {
        $this->getCache()->flush();
        return parent::copy($new_id);
    }

    public function read()
    {
        parent::read();
        $this->_hash = $this->buildHash();
    }

    public function update()
    {
        if ($this->buildHash() !== $this->_hash) {
            $this->getCache()->flush();
            parent::update();
        }
    }

    public function delete()
    {
        $this->getCache()->flush();
        parent::delete(); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritDoc
     */
    public static function find($primary_key, array $add_constructor_args = array())
    {
        return parent::find($primary_key, $add_constructor_args);
    }

    /**
     * @inheritDoc
     */
    public static function connector(arConnector $connector)
    {
        return parent::connector($connector); // TODO: Change the autogenerated stub
    }
}
