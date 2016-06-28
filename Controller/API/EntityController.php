<?php
namespace ITF\AdminBundle\Controller\API;

use AppBundle\Entity\Post;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use FOS\RestBundle\Controller\Annotations\Route;
use JMS\Serializer\SerializerBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntityController
 * @package ITF\AdminBundle\Controller\API
 *
 * @Security("has_role('ROLE_ADMIN')")
 */
class EntityController extends Controller
{
    private $bundle;
    private $entity;

    const COUNT_ATTRIBUTE = 'count';
    const DEFAULT_LIMIT = 24;

    /**
     * @Route("/{bundle}/{entity}.{_format}", defaults={"_format" = "json"}, name="admin_api_get_entities")
     * @param $bundle
     * @param $entity
     * @param $_format
     *
     * @return JsonResponse
     */
    public function getAction($bundle, $entity, $_format)
    {
        $this->bundle = $bundle;
        $this->entity = $entity;

        $query = $this->getQueryBuilder()->getQuery();

        if ($this->getOption('query', false)) {
            //var_dump($this->getOption('query', false));exit;
        }

        try {
            $entities = $query->getResult();
        } catch (NoResultException $e) {
            $entities = array();
        }

        return $this->serialize($entities, $_format);
    }

    /**
     * @Route("/{bundle}/{entity}/count.{_format}", defaults={"_format" = "json"}, name="admin_api_get_entities_count")
     * @param $bundle
     * @param $entity
     * @param $_format
     *
     * @return JsonResponse
     */
    public function getCountAction($bundle, $entity, $_format)
    {
        $this->bundle = $bundle;
        $this->entity = $entity;

        $em = $this->getDoctrine()->getManager();
        $tableName = $em->getClassMetadata(get_class($this->getInstance($bundle, $entity)))->getTableName();

        // prepare sql
        $sql = "SELECT count(id) as " . self::COUNT_ATTRIBUTE . " FROM " . $tableName;

        // if querystring
        if ($this->getOption('query', false)) {
            $fieldNames = $this->getSearchableFieldnames();
            $queryString = $this->getPrepareQueryString();

            // add where
            $sql .= ' WHERE ';

            // if wildcards had to be replaced and more than one fieldname available
            $operator = $this->replaceQueryWildcards(NULL, true) && count($fieldNames) == 1
                ? '='
                : 'LIKE'
            ;

            for ($i = 0; $i < count($fieldNames); $i++) {
                if ($i > 0) {
                    $sql .= 'OR ';
                }

                $sql .= $fieldNames[$i] . ' ' . $operator . ' "' . $queryString . '" ';
            }
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $conn = $em->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $data = array(
            self::COUNT_ATTRIBUTE => (int) $stmt->fetch()[self::COUNT_ATTRIBUTE]
        );

        return $this->serialize($data, $_format);
    }



    /**
     * @param $bundle
     * @param $entity
     *
     * @return object
     */
    private function getInstance($bundle, $entity)
    {
        $ah = $this->get('itf.admin_helper');
        $ah->setBundle($bundle);
        $ah->setEntity($entity);

        return $ah->getEntityInstance($entity);
    }



    /**
     * @param $bundle
     * @param $entity
     *
     * @return EntityRepository
     */
    private function getRepository($bundle, $entity)
    {
        $instance = $this->getInstance($bundle, $entity);

        return $this->getDoctrine()->getManager()->getRepository(get_class($instance));
    }


    private function getOrderConfig()
    {
        $ah = $this->get('itf.admin_helper');
        $config = $this->get('itf.admin.config');
        $ah->setBundle($this->bundle);
        $ah->setEntity($this->entity);

        // TODO: workaround
        $config->refreshConfig();

        return $config->getEntityListConfig();
    }

    private function getOrderBy()
    {
        $attr = @$this->getOrderConfig()['order_property'];

        return $attr ? $attr : 'id';
    }

    private function getOrderDirection()
    {
        $direction = @$this->getOrderConfig()['order_direction'];

        return $direction ? $direction : Criteria::ASC;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getQueryBuilder()
    {
        $qb = $this->getRepository($this->bundle, $this->entity)->createQueryBuilder('a');
        $query = $qb
            ->setMaxResults($this->getOption('limit', self::DEFAULT_LIMIT))
            ->setFirstResult($this->getOption('offset', 0))
            ->orderBy('a.' . $this->getOrderBy(), $this->getOrderDirection())
        ;

        // if query set
        if ($this->getOption('query', false)) {
            $queryString = $this->getPrepareQueryString();

            // get fielnames
            $fieldNames = $this->getSearchableFieldnames();

            if (count($fieldNames) == 1) {
                if ($this->replaceQueryWildcards(NULL, true)) {
                    $qb->where($qb->expr()->eq('a.' . $fieldNames[0], ':searchQuery'));
                } else {
                    $qb->where($qb->expr()->like('a.' . $fieldNames[0], ':searchQuery'));
                }
            } else {
                for ($i = 0; $i < count($fieldNames); $i++) {
                    if ($i == 0) {
                        $qb->where($qb->expr()->like('a.' . $fieldNames[ $i ], ':searchQuery'));
                    } else {
                        $qb->orWhere($qb->expr()->like('a.' . $fieldNames[ $i ], ':searchQuery'));
                    }
                }
            }

            if (count($fieldNames) > 0) {
                $qb->setParameter('searchQuery', $queryString);
            }
        }

        //$query->select('a.id, a.title');pre($query->getQuery()->getSQL());exit;

        return $query;
    }


    /**
     * @return string
     */
    private function getPrepareQueryString()
    {
        $searchQuery = $this->getOption('query', NULL);

        // if has id=xyz
        if ($m = $this->getQueryStringHasEqual($searchQuery)) {
            $searchQuery = $m[2];
        }

        // if has id="asdf"
        if (preg_match('/^\"(.*?)\"$/', $searchQuery, $m)) {
            $searchQuery = $m[1];
        } else {
            $searchQuery = '%' . $searchQuery . '%';
        }

        // replace wildcards
        return $this->replaceQueryWildcards($searchQuery);
    }

    private function replaceQueryWildcards($queryString = NULL, $check = false)
    {
        if ($queryString === NULL) $queryString = $this->getOption('query', false);

        $searchQueryReplaced = str_replace([' ', '*'], '%', $queryString);

        if ($check) {
            return $queryString === $searchQueryReplaced;
        }

        return $searchQueryReplaced;
    }


    private function getQueryStringHasEqual($queryString = NULL)
    {
        if ($queryString === NULL) $queryString = $this->getOption('query', false);

        preg_match('/^(.*?)\=(.*?)$/', $queryString, $m);
        return $m;
    }


    /**
     * @return array
     */
    private function getSearchableFieldnames()
    {
        $searchQuery = $this->getOption('query', false);
        if ($searchQuery && $m = $this->getQueryStringHasEqual($searchQuery)) {
            return [ $m[1] ]; // return only fieldname
        }

        $instance = $this->getInstance($this->bundle, $this->entity);
        $meta = $this->getDoctrine()->getManager()->getMetadataFactory()->getMetadataFor(get_class($instance));
        $searchable_types = ['string', 'integer'];

        $fieldNames = array();
        foreach ($meta->getFieldNames() as $fieldName) {
            $type = $meta->getTypeOfField($fieldName);

            if (in_array($type, $searchable_types)) {
                $fieldNames[] = $fieldName;
            }
        }

        return $fieldNames;
    }



    /**
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    private function getOption($key, $default = NULL)
    {
        return $this->get('request')->isMethod('GET')
            ? $this->get('request')->query->get($key, $default)
            : $this->get('request')->request->get($key, $default);
    }



    /**
     * @param $data
     * @param string $_format
     *
     * @return JsonResponse
     */
    private function serialize($data, $_format = 'json')
    {
        $serializer = SerializerBuilder::create()->build();

        switch($_format) {
            case 'json_id':
                $data = array_map(function($entry) {
                    return $entry->getId();
                }, $data);

                return new JsonResponse(json_decode($serializer->serialize($data, 'json')));
                break;

            case 'array':
                pre(json_decode($serializer->serialize($data, 'json')));exit;
                break;

            default:
            case 'json':
                return new JsonResponse(json_decode($serializer->serialize($data, 'json')));
                break;
        }
    }
}