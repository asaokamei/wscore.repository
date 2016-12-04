<?php
namespace WScore\Repository\Helpers;

use Interop\Container\Exception\ContainerException;

trait ContainerTrait
{
    /**
     * @var array
     */
    private $container = [];

    /**
     * @var callable[]
     */
    private $factories = [];

    /**
     * sets a factory for identifier.
     * factory should be a callable.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->factories[$key] = $value;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws ContainerNotFoundException  No entry was found for this identifier.
     * @throws ContainerErrorException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->container)) {
            return $this->container[$id];
        }
        if (array_key_exists($id, $this->factories)) {
            $factory = $this->factories[$id];
            if (is_callable($factory)) {
                $factory = $factory($this);
            }
            return $this->container[$id] = $factory;
        }
        throw new ContainerNotFoundException('cannot find id: '.(string) $id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        if (array_key_exists($id, $this->container)) {
            return true;
        }
        return array_key_exists($id, $this->factories);
    }
}