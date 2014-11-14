<?php namespace App;

abstract class ApplicationModel
{
    /* Abstract methods */

    abstract protected function setProperties($hash);
    abstract protected function onUpdate();

    /* Inherited methods */

    public function set($propertyName, $propertyValue)
    {
        if (property_exists($this, $propertyName))
        {
            $this->$propertyName = $propertyValue;
            $this->onUpdate();
        }
    }
    public function get($propertyName)
    {
        if (property_exists($this, $propertyName))
        {
            return $this->$propertyName;
        }
        return null;
    }
    public function all($excludes = null)
    {
        $properties = get_object_vars($this);

        if (is_array($excludes))
        {
            $output = [];
            foreach ($properties as $name => $value)
            {
                if (!in_array($name, $excludes))
                {
                    $output[$name] = $value;
                }
            }
        }
        else
        {
            $output = $properties;
        }
        return $output;
    }
}
