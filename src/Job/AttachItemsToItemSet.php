<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Job;

use Omeka\Job\AbstractJob;
use Omeka\Stdlib\Message;

class AttachItemsToItemSet extends AbstractJob
{
    public function perform(): void
    {
        /**
         * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
         * @var \Omeka\Api\Manager $api
         * @var \Omeka\Settings\Settings $settings
         * @var \Laminas\Log\Logger $logger
         */
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $settings = $services->get('Omeka\Settings');
        $logger = $services->get('Omeka\Logger');

        // The reference id is the job id for now.
        if (class_exists('Log\Stdlib\PsrMessage')) {
            $referenceIdProcessor = new \Laminas\Log\Processor\ReferenceId();
            $referenceIdProcessor->setReferenceId('art/attach_to_itemset/job_' . $this->job->getId());
            $logger->addProcessor($referenceIdProcessor);
        }

        $itemSetId = (int) $this->getArg('item_set_id');
        if (!$itemSetId) {
            $logger->err(new Message(
                'No item set defined.' // @translate
            ));
            return;
        }

        try {
            $api->read('item_sets', ['id' => $itemSetId]);
        } catch (\Exception $e) {
            $logger->err(new Message(
                'The item set #%d does not exist.', // @translate
                $itemSetId
            ));
            return;
        }

        $queries = $settings->get('advancedresourcetemplate_item_set_queries') ?: [];
        $query = $queries[$itemSetId] ?? null;

        $existingItemIds = $api->search('items', ['item_set_id' => $itemSetId], ['returnScalar' => 'id'])->getContent();
        $newItemIds = $query ? $api->search('items', $query, ['returnScalar' => 'id'])->getContent() : [];

        // Batch update the resources in chunks.

        // Detach all items that are not in new items.
        $detachItemIds = array_diff($existingItemIds, $newItemIds);
        if ($detachItemIds) {
            $i = 0;
            foreach (array_chunk($detachItemIds, 100) as $idsChunk) {
                if ($this->shouldStop()) {
                    return;
                }
                $api->batchUpdate('items', $idsChunk, ['o:item_set' => [$itemSetId]], ['continueOnError' => true, 'collectionAction' => 'remove']);
                $logger->info(new Message(
                    '%1$d/%2$d items detached from item set #%3$d.', // @translate
                    min(++$i * 100, count($detachItemIds)), count($detachItemIds), $itemSetId
                ));
            }
        }

        // Attach new items only.
        $newItemIds = $newItemIds ? array_diff($newItemIds, $existingItemIds) : [];
        if ($newItemIds) {
            $i = 0;
            foreach (array_chunk($newItemIds, 100) as $idsChunk) {
                if ($this->shouldStop()) {
                    return;
                }
                $api->batchUpdate('items', $idsChunk, ['o:item_set' => [$itemSetId]], ['continueOnError' => true, 'collectionAction' => 'append']);
                $logger->info(new Message(
                    '%1$d/%2$d new items attached to item set #%3$d.', // @translate
                    min(++$i * 100, count($newItemIds)), count($newItemIds), $itemSetId
                ));
            }
        }

        $logger->notice(new Message(
            'Process ended for item set #%1$d: %2$d items was attached, %3$d items were detached, %4$d new items were attached.', // @translate
            $itemSetId, count($existingItemIds), count($detachItemIds), count($newItemIds)
        ));
    }
}
