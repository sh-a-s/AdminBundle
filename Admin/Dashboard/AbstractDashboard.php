<?php
namespace ITF\AdminBundle\Admin\Dashboard;

use ITF\AdminBundle\Admin\Service\AbstractServiceSetter;

abstract class AbstractDashboard extends AbstractServiceSetter
{
    protected function executeQuery($query, $params = array())
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($query);
        
        if (count($params) > 0) {
            foreach($params as $attr => $value) {
                $stmt->bindValue($attr, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    protected function getExecutedQueryCount($query, $params = array())
    {
        return count($this->executeQuery($query, $params));
    }
    
    protected function getRelativeDateByDays($days, $format = false)
    {
        $date = strtotime('-' . $days . ' days');
        
        if ($format !== false) {
            if (!is_string($format)) $format = 'Y-m-d';
            
            return date($format, $date);
        }
        
        return $date;
    }
}