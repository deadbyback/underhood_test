<?php
namespace App\Models;

class Trademark implements Model
{
    public int $id;
    public string $number;
    public string $name;
    public string $logoUrl;
    public string $class;
    public string $status;
    public string $detailsPageUrl;

    private const JSON_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    /**
     * Initialize object properties with values from the given associative array.
     *
     * @param array $data Associative array of properties to initialize.
     * @return self
     */
    public function initializeProperties(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->assignValueIfPropertyExists($key, $value);
        }
        return $this;
    }

    /**
     * Assign a value to the property if it exists in the class.
     *
     * @param string $key Property name.
     * @param mixed $value Value to assign to the property.
     * @return void
     */
    private function assignValueIfPropertyExists(string $key, $value): void
    {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        }
    }

    /**
     * Convert the object to an associative array.
     *
     * @return array Associative array representation of the object.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Convert the object to a JSON string.
     *
     * @return string JSON representation of the object.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), self::JSON_OPTIONS);
    }

    /**
     * Get the short name of the class.
     *
     * @return string Short class name.
     */
    public function getShortClassName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}