<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */

abstract class Oct8neBaseSearch
{

    /**
     * Devuelve el nombre del motor
     * @return mixed
     */
    public abstract function getEngineName();

    /*
     * Realiza la busqueda
     */
    public abstract function search();

    /**
     * Filtros aplicados
     * @param $param
     * @param $paramLabel
     * @param $value
     * @param $valueLabel
     * @return array
     */
    protected function createAppliedFilter($param, $paramLabel, $value, $valueLabel){

        return array("param" => $param, "paramLabel" => $paramLabel, "value" => $value, "valueLabel" => $valueLabel);

    }

    /**
     * Filtros disponibles
     * @param $param
     * @param $label
     * @param $options
     * @return array
     */
    protected function createAvailableFilters ($param, $label, $options){

        return array("param" => $param, "paramLabel" => $label, "options" => $options);
    }

    /**
     * Opcion para filtro disponible
     * @param $value
     * @param $valueLabel
     * @param $count
     */
    protected function createOption($value, $valueLabel, $count){

        return array("value" => $value, "valueLabel" => $valueLabel, "count" => $count);
    }

}