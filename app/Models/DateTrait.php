<?php
namespace App\Models;

/**
 * trait DateTrait
 */
trait DateTrait
{

    /**
     * override getAttribute method and setup hook for $dates attributes
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (in_array($key, $this->getDates(), true)) {
            return $this->getDateAttr($key);
        }
        return parent::getAttribute($key);
    }

    /**
     * override setAttribute method and setup hook for $dates attributes
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->getDates(), true)) {
            return $this->setDateAttr($key, $value);
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * get value in date attribute $attrName and converting it to ISO8601 format
     * @param string $attrName
     * @return string|null
     */
    public function getDateAttr($attrName): ?string
    {
        $attr = $this->attributes[$attrName];
        return static::toISO8601($attr);
    }

    /**
     * convert date $value ISO8601 format -> MySQL datetime format and save into $attrName attribute
     * @param string $attrName
     * @param string $value
     * @return mixed
     */
    public function setDateAttr($attrName, $value)
    {
        $this->attributes[$attrName] = static::toStandardTime($value);
        return $this;
    }

    /**
     * convert string MySQL datetime format -> ISO8601
     * @param string $standardDatetime
     * @return string|null
     */
    public static function toISO8601($standardDatetime): ?string
    {
        return $standardDatetime ? date('c', strtotime($standardDatetime)) : null;
    }

    /**
     * convert string ISO8601 format -> MySQL
     * @param string $iso8601
     * @return string|null
     */
    public static function toStandardTime($iso8601): ?string
    {
        return $iso8601 ? date('Y-m-d H:i:s', strtotime($iso8601)) : null;
    }


    /**
     * override method for conversion model to array and auto convert dates format (MySQL datetime  -> ISO8601)
     * @param array $attributes
     * @return array
     */
    protected function addDateAttributesToArray(array $attributes): array
    {
        foreach ($this->getDates() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = static::toISO8601($attributes[$key]);
        }

        return $attributes;
    }
}
