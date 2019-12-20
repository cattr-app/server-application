<?php
namespace App\Models;

use Carbon\Carbon;
use DateTime;
use InvalidArgumentException;

/**
 * trait DateTrait
 * @package App\Models
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
        if (in_array($key, $this->getDates())) {
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
        if (in_array($key, $this->getDates())) {
            return $this->setDateAttr($key, $value);
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * get value in date attribute $attrName and converting it to ISO8601 format
     * @param string $attrName
     * @return string|null
     */
    public function getDateAttr($attrName)
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
        $this->attributes[$attrName] = static::toStandartTime($value);
        return $this;
    }

    /**
     * convert string MySQL datetime format -> ISO8601
     * @param string $standartDatetime
     * @return string|null
     */
    public static function toISO8601($standartDatetime)
    {
        return $standartDatetime ? date('c', strtotime($standartDatetime)) : null;
    }

    /**
     * convert string ISO8601 format -> MySQL
     * @param string $iso8601
     * @return string|null
     */
    public static function toStandartTime($iso8601)
    {
        return $iso8601 ? date('Y-m-d H:i:s', strtotime($iso8601)) : null;
    }


    /**
     * override method for convertation model to array and auto convert dates format (MySQL datetime  -> ISO8601)
     * @param array $attributes
     * @return array
     */
    protected function addDateAttributesToArray(array $attributes)
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
