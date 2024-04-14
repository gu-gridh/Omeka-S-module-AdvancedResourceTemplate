<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Controller\Admin;

use AdvancedResourceTemplate\Autofiller\AutofillerPluginManager as AutofillerManager;
use Common\Stdlib\PsrMessage;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\ResourceTemplateRepresentation;
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
            return $this->returnError(null, null, [
                'suggestions' => new PsrMessage('The query is empty.'), // @translate
            ]);
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
            return $this->returnError(new PsrMessage(
                'The remote service "{service}" seems unavailable.', // @translate
                $autofiller->getLabel()
            ), HttpResponse::STATUS_CODE_502);
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
            return $this->returnError(null, null, [
                'suggestions' => new PsrMessage('The service is empty.'), // @translate
            ]);
        }

        if (empty($query['template'])) {
            return $this->returnError(null, null, [
                'suggestions' => new PsrMessage('The template is empty.'), // @translate
            ]);
        }

        try {
            // Resource template does not support search by id, so use read().
            /** @var \Omeka\Api\Representation\ResourceTemplateRepresentation $template */
            $template = $this->api()->read('resource_templates', ['id' => $query['template']])->getContent();
        } catch (NotFoundException $e) {
            return $this->returnError(null, null, [
                'suggestions' => new PsrMessage(
                    'The template "{template_id}" is not available.', // @translate
                    ['template_id' => $query['template']]
                ),
            ]);
        }

        $serviceMapping = $this->prepareServiceMapping($template, $query['service']);
        if (empty($serviceMapping)) {
            return $this->returnError(new PsrMessage(
                'The service "{service}" has no mapping.', // @translate
                ['service' => $query['service']]
            ), HttpResponse::STATUS_CODE_501);
        }

        if (!$this->autofillerManager->has($serviceMapping['service'])) {
            return $this->returnError(new PsrMessage(
                'The service "{service}" is not available.', // @translate
                ['service' => $query['service']]
            ), HttpResponse::STATUS_CODE_501);
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
     * Return a message of error.
     *
     * @see https://github.com/omniti-labs/jsend
     *
     * @param \Common\Stdlib\PsrMessage|string $message
     * @param int $statusCode
     * @param \Omeka\Stdlib\ErrorStore|array $messages
     * @return \Laminas\View\Model\JsonModel
     */
    protected function returnError($message, int $statusCode = Response::STATUS_CODE_400, $messages = null): JsonModel
    {
        $statusCode ??= Response::STATUS_CODE_400;

        $response = $this->getResponse();
        $response->setStatusCode($statusCode);

        $translator = $this->translator();

        if (is_array($messages) && count($messages)) {
            foreach ($messages as &$msg) {
                is_object($msg) ? $msg->setTranslator($translator) : $this->translate($msg);
            }
            unset($msg);
        } elseif (is_object($messages) && $messages instanceof ErrorStore && $messages->hasErrors()) {
            $msgs = [];
            foreach ($messages->getErrors() as $key => $msg) {
                $msgs[$key] = is_object($msg) ? $msg->setTranslator($translator) : $this->translate($msg);
            }
            $messages = $msgs;
        } else {
            $messages = [];
        }

        $status = $statusCode >= 500 ? 'error' : 'fail';

        $result = [];
        $result['status'] = $status;

        if (is_object($message)) {
            $message->setTranslator($translator);
        } elseif ($message) {
            $message = $this->translate($message);
        } elseif ($status === 'error') {
            // A message is required for error.
            if ($messages) {
                $message = reset($messages);
                if (count($messages) === 1) {
                    $messages = [];
                }
            } else {
                $message = $this->translate('An error occurred.'); // @translate;
            }
        }

        // Normally, only in error, not fail, but a main message may be useful
        // in any case.
        if ($message) {
            $result['message'] = $message;
        }

        // Normally, not in error.
        if (count($messages)) {
            $result['data'] = $messages;
        }

        return new JsonModel($result);
    }
}
