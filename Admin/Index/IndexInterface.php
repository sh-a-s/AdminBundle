<?php
namespace ITF\AdminBundle\Admin\Index;

use ITF\AdminBundle\Admin\Controller\ControllerResponse;

interface IndexInterface
{
    /**
     * @param ControllerResponse $response
     * @return IndexInterface
     */
    public function setResponse(ControllerResponse $response);

    /**
     * @return ControllerResponse
     */
    public function getResponse();

    /**
     * @return IndexInterface
     */
    public function returnPrepared();
    
    /**
     * @param $item
     *
     * @return mixed
     */
    public function renderItem($item);
}