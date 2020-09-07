<?php

namespace AdvancedResourceTemplate\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Controller\AbstractRestfulController;
use Omeka\View\Model\ApiJsonModel;

class IndexController extends AbstractRestfulController
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function valuesAction()
    {
        $maxResults = 10;

        $query = $this->params()->fromQuery();
        $q = isset($query['q']) ? trim($query['q']) : '';
        if (!strlen($q)) {
            return $this->returnError(['suggestions' => $this->translate('The query is empty.')]); // @translate
        }

        $qq = isset($query['type']) && $query['type'] === 'in'
             ? '%' . addcslashes($q, '%_') . '%'
             : addcslashes($q, '%_') . '%';

        $property = isset($query['prop']) ? (int) $query['prop'] : null;

        $qb = $this->entityManager->createQueryBuilder();
        $expr = $qb->expr();
        $qb
            ->select('DISTINCT omeka_root.value')
            ->from(\Omeka\Entity\Value::class, 'omeka_root')
            ->where($expr->like('omeka_root.value', ':qq'))
            ->setParameter('qq', $qq)
            ->groupBy('omeka_root.value')
            ->orderBy('omeka_root.value', 'ASC')
            ->setMaxResults($maxResults);
        if ($property) {
            $qb
                ->andWhere($expr->eq('omeka_root.property', ':prop'))
                ->setParameter('prop', $property);
        }
        $result = $qb->getQuery()->getScalarResult();

        // Output for jSend + jQuery Autocomplete.
        // @link https://github.com/omniti-labs/jsend
        // @link https://www.devbridge.com/sourcery/components/jquery-autocomplete
        $result = array_map('trim', array_column($result, 'value'));
        return new ApiJsonModel([
            'status' => 'success',
            'data' => [
                'suggestions' => $result,
            ],
        ]);
    }

    /**
     * Return a jSend message of error.
     *
     * @link https://github.com/omniti-labs/jsend
     *
     * @param string|array $message
     * @param int $statusCode
     * @param array $errors
     * @return \Zend\Http\Response
     */
    protected function returnError($message, $statusCode = HttpResponse::STATUS_CODE_400, array $errors = null)
    {
        if ($statusCode >= 500) {
            $result = [
                'status' => 'error',
                'message' => is_array($message) ? reset($message) : $message,
            ];
        } else {
            $result = [
                'status' => 'fail',
                'data' => is_array($message) ? $message : ['message' => $message],
            ];
        }
        if (is_array($errors)) {
            $result['data]']['errors'] = $errors;
        }
        $response = $this->getResponse()
            ->setStatusCode($statusCode)
            ->setContent(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        return $response;
    }
}
