<?php
/**
 * Created by PhpStorm.
 * User: thomastourlourat
 * Date: 17/06/2016
 * Time: 10:00
 */

namespace Armetiz\AirtableSDK;


class Record
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $fields;

    /**
     * Record constructor.
     *
     * @param string $id
     * @param array  $fields
     */
    public function __construct($id, array $fields)
    {
        $this->id     = $id;
        $this->fields = $fields;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}