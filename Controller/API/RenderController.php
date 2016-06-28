<?php
namespace ITF\AdminBundle\Controller\API;

use FOS\RestBundle\Controller\Annotations\Route;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RenderController extends Controller
{
    /**
     * @Route("/{bundle}/{entity}/index", name="api_render_index_elements")
     * @param Request $request
     * @param $bundle
     * @param $entity
     *
     * @return JsonResponse
     */
    public function renderIndexElementsAction(Request $request, $bundle, $entity)
    {
        $ah = $this->get('itf.admin_helper');
        $config = $this->get('itf.admin.config');
        $ah->setBundle($bundle);
        $ah->setEntity($entity);
        $html = '';

        // TODO: refresh config (workaround)
        $this->get('itf.admin.config')->refreshConfig();

        // get data
        $data = $request->request->get('data', array());

        if ($data !== NULL && is_array($data)) {
            $indexService = $config->getIndexService();

            foreach($data as $id) {
                $html .= $indexService->renderItem($id);
            }
        }

        return new JsonResponse(array(
            'html' => $html
        ));
    }
}