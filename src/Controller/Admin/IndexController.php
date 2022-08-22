<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Controller\Admin;

use AdvancedResourceTemplate\Autofiller\AutofillerPluginManager as AutofillerManager;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\ResourceTemplateRepresentation;
use Omeka\Stdlib\Message;
use Omeka\View\Model\ApiJsonModel;

class IndexController extends AbstractRestfulController
{
    /**
     * @var AutofillerManager
     */
    protected $autofillerManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param AutofillerManager $autofillerManager
     * @param EntityManager $entityManager
     */
    public function __construct(
        AutofillerManager $autofillerManager,
        EntityManager $entityManager
    ) {
        $this->autofillerManager = $autofillerManager;
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

    public function autofillerAction()
    {
        $query = $this->params()->fromQuery();
        $q = isset($query['q']) ? trim($query['q']) : '';
        if (!strlen($q)) {
            return $this->returnError(['suggestions' => $this->translate('The query is empty.')]); // @translate
        }

        /** @var \AdvancedResourceTemplate\Autofiller\AutofillerInterface $autofiller */
        $autofiller = $this->requestToAutofiller();
        if ($autofiller instanceof HttpResponse) {
            return $autofiller;
        }

        $lang = $this->userSettings()->get('locale')
            ?: ($this->settings()->get('locale')
                ?: $this->viewHelpers()->get('translate')->getTranslatorTextDomain()->getDelegatedTranslator()->getLocale());

        $results = $autofiller
            ->getResults($q, $lang);

        if (is_null($results)) {
            return $this->returnError($this->translate(new Message(
                'The remote service "%s" seems unavailable.', // @translate
                $autofiller->getLabel()
            )), HttpResponse::STATUS_CODE_502);
        }

        return new ApiJsonModel([
            'status' => 'success',
            'data' => [
                'suggestions' => $results,
            ],
        ]);
    }

    public function autofillerSettingsAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new NotFoundException;
        }

        /** @var \AdvancedResourceTemplate\Autofiller\AutofillerInterface $autofiller */
        $autofiller = $this->requestToAutofiller();
        if ($autofiller instanceof HttpResponse) {
            return $autofiller;
        }

        return new ApiJsonModel([
            'status' => 'success',
            'data' => [
                'autofiller' => [
                    'label' => $autofiller->getLabel(),
                ],
            ],
        ]);
    }

    /**
     * @return \AdvancedResourceTemplate\Autofiller\AutofillerInterface|\Laminas\Http\Response
     */
    protected function requestToAutofiller()
    {
        $query = $this->params()->fromQuery();
        if (empty($query['service'])) {
            return $this->returnError(['suggestions' => $this->translate('The service is empty.')]); // @translate
        }

        if (empty($query['template'])) {
            return $this->returnError(['suggestions' => $this->translate('The template is empty.')]); // @translate
        }

        try {
            // Resource template does not support search by id, so use read().
            /** @var \Omeka\Api\Representation\ResourceTemplateRepresentation $template */
            $template = $this->api()->read('resource_templates', ['id' => $query['template']])->getContent();
        } catch (NotFoundException $e) {
            return $this->returnError(['suggestions' => $this->translate(new Message(
                'The template "%s" is not available.', // @translate
                $query['template']
            ))]);
        }

        $serviceMapping = $this->prepareServiceMapping($template, $query['service']);
        if (empty($serviceMapping)) {
            return $this->returnError($this->translate(new Message(
                'The service "%1" has no mapping.', // @translate
                $query['service']
            )), HttpResponse::STATUS_CODE_501);
        }

        if (!$this->autofillerManager->has($serviceMapping['service'])) {
            return $this->returnError($this->translate(new Message(
                'The service "%s" is not available.', // @translate
                $query['service']
            )), HttpResponse::STATUS_CODE_501);
        }

        $serviceOptions = $serviceMapping;
        unset($serviceOptions['mapping']);

        return $this->autofillerManager
            ->get($serviceMapping['service'], $serviceOptions)
            ->setMapping($serviceMapping['mapping']);
    }

    protected function prepareServiceMapping(ResourceTemplateRepresentation $template, $service)
    {
        $autofillers = $template->data('autofillers');
        if (empty($autofillers)) {
            return [];
        }
        $mappings = $this->settings()->get('advancedresourcetemplate_autofillers', []);
        return $mappings[$service] ?? [];
    }

    /**
     * Check if the request contains an identifier.
     *
     * This method overrides parent in order to allow to query on one or
     * multiple ids.
     *
     * @see \Omeka\Controller\ApiController::getIdentifier()
     *
     * {@inheritDoc}
     * @see \Laminas\Mvc\Controller\AbstractRestfulController::getIdentifier()
     */
    protected function getIdentifier($routeMatch, $request)
    {
        $identifier = $this->getIdentifierName();
        return $routeMatch->getParam($identifier, false);
    }

    /**
     * Return a jSend message of error.
     *
     * @link https://github.com/omniti-labs/jsend
     *
     * @param string|array $message
     * @param int $statusCode
     * @param array $errors
     * @return \Laminas\Http\Response
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
