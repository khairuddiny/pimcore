<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 22.04.13
 * Time: 23:39
 */

class Deployment_Instance_Adapter_Xml  extends Deployment_Instance_Adapter_Abstract{

    protected $type = 'xml';
    protected $config = null;

    protected $instanceSettings;

    protected $deploymentInstanceWrapperClassName; //default Deployment_Instance

    protected function init(){

        $configFile = PIMCORE_CONFIGURATION_DIRECTORY.'/deployment/deploymentInstances.xml';
        if(!is_readable($configFile)){
            throw new Exception("Config file $configFile not readable or doesn't exist.");
        }else{
            $this->config = new Zend_Config_Xml($configFile);
        }
    }

    public function getInstanceSettings(){
        return $this->instanceSettings;
    }

    public function getInstanceObjectClassName(){
        return $this->instanceObjectClassName;
    }

    public function getInstanceObjectList(){
        return new $this->instanceObjectListClassName();
    }

    protected function getWrapperObject($concreteInstanceObject){
        if($concreteInstanceObject instanceof Object_Concrete){
            $concreteInstance = new $this->deploymentInstanceWrapperClassName();
            $wrappedObject = $concreteInstance->setConcreteDeploymentInstance($concreteInstanceObject);
            return $wrappedObject;
        }
    }

    public function getAllInstances(){
        $list = $this->getInstanceObjectList();
        $instances = array();
        foreach($list as $instanceObject){
            $instances[] = $this->getWrapperObject($instanceObject);
        }

        return $instances;
    }

    public function getInstancesByIdentifiers(array $identifiers){
        $list = $this->getInstanceObjectList();
        $fieldMapping = $this->getFieldMapping('identifier');
        $identifiers = wrapArrayElements($identifiers);
        $list->setCondition($fieldMapping["instanceIdentifier"].' IN(' . implode(',',$identifiers) .') ');
        $instances = array();
        foreach($list as $instanceObject){
            $instances[] = $this->getWrapperObject($instanceObject);
        }
        return $instances;
    }

    public function getInstancesByGroups(array $groups){
        $fieldMapping = $this->getFieldMapping('identifier');
        $dbField = $fieldMapping['instanceGroup'];
        $groups = wrapArrayElements($groups," $dbField LIKE '%,",",%' ");

        $list = $this->getInstanceObjectList();
        $list->setCondition(implode(' OR ', $groups));

        $instances = array();
        foreach($list as $instanceObject){
            $instances[] = $this->getWrapperObject($instanceObject);
        }
        return $instances;
    }

    public function getInstanceByIdentifier($identifier){
        $fieldMapping = $this->getFieldMapping('identifier');
        if($fieldMapping['instanceIdentifier'] == 'id'){
            $dbColumn = 'o_id';
        }else{
            $dbColumn = $fieldMapping['instanceIdentifier'];
        }
        $list = $this->getInstanceObjectList();
        $list->setCondition($dbColumn. ' = ?',array($identifier))->setLimit(1);
        $res = $list->load();
        if($res[0] instanceof Object_Concrete){
            return $this->getWrapperObject($res[0]);
        }
    }
}