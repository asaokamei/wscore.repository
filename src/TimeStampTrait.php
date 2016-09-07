<?php

trait TimeStampTrait
{
    /**
     * @return array
     */
    abstract protected function getTimeStampColumns();

    /**
     * @return string
     */
    abstract protected function getTimeStampFormat();

    /**
     * @param array  $data
     * @param string $type
     * @return array
     */
    protected function _addTimeStamps(array $data, $type)
    {
        if (!$timeStamps = $this->getTimeStampColumns()) {
            return $data;
        }
        if (!isset($timeStamps[$type])) {
            return $data;
        }
        $column = $timeStamps[$type];
        if (isset($data[$column])) {
            return $data;
        }
        $data[$column] = (new DateTime('now'))->format($this->getTimeStampFormat());

        return $data;
    }

}