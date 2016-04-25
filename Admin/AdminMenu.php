<?php
namespace ITF\AdminBundle\Admin;

use ITF\AdminBundle\Admin\Service\AbstractServiceSetter;

class AdminMenu extends AbstractServiceSetter
{
    private $current_entity;
    
    /**
     * Create Admin Menu
     * 
     * @param null $current_entity
     * @return array
     */
    public function createAdminMenu($current_entity = NULL)
    {
        if ($current_entity !== NULL) $this->setCurrentEntity($current_entity);
        $entities = $this->getHelper()->getEntities();

        $menu = array();
        foreach($entities as $entity) {
            $entity_short = $this->getHelper()->getEntityName($entity, 'strtolower');

            $menu[] = array(
                'title' => $this->getTitle($entity),
                'entitiy_class' => $entity,
                'active' => ($this->getCurrentEntity() == $entity_short),
                'has_error' => !class_exists($this->getHelper()->getEntityFormTypeClass($entity)),
                'url' => $this->container->get('router')->generate('admin_list', array(
                    'entity' => $this->getHelper()->getEntityName($entity, 'strtolower'),
                    'bundle' => $this->getHelper()->getBundleNameShort()
                ))
            );
        }

        return $menu;
    }
    
    /**
     * @return mixed
     */
    public function getCurrentEntity()
    {
        return $this->current_entity;
    }
    
    /**
     * @param mixed $current_entity
     *
     * @return AdminMenu
     */
    public function setCurrentEntity($current_entity)
    {
        $this->current_entity = $current_entity;
        
        return $this;
    }
    
    
    
    /**
     * Get Title
     * 
     * @param $entity
     * @return string
     */
    private function getTitle($entity)
    {
        $entity_short = $this->getHelper()->getEntityName($entity, 'strtolower');
        $title = $this->getConfigTitle($entity_short);

        if ($title === null || !$title) {
            // get title
            $title = $this->getHelper()->getEntityNameFromClass($entity);
        }

        return $title;
    }
    

    /**
     * @return AdminHelper
     */
    private function getHelper()
    {
        return $this->container->get('itf.admin_helper');
    }
    
    
    /**
     * @param null $entity_short
     *
     * @return array|null
     */
    private function getConfig($entity_short = null)
    {
        return @$this->container->get('itf.admin.config')->getEntityConfig($entity_short);
    }
    
    
    /**
     * @param null $entity_short
     * @return null|string
     */
    private function getConfigTitle($entity_short = null)
    {
        return @$this->getConfig($entity_short)['title'];
    }
}