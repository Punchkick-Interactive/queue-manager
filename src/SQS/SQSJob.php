<?php

namespace Punchkick\QueueManager\SQS;

use Aws\Sqs\SqsClient;
use Exception;
use Punchkick\QueueManager\DoneLog\DoneLogInterface;
use Punchkick\QueueManager\JobInterface;

/**
 * Class SQSJob
 * @package Punchkick\QueueManager\SQS
 */
class SQSJob implements JobInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var SqsClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $queueUrl;

    /**
     * @var DoneLogInterface
     */
    protected $doneLog;

    /**
     * @var string
     */
    protected $receiptHandle;

    /**
     * @var string
     */
    protected $messageId;

    /**
     * SQSJob constructor.
     *
     * @param array $data
     * @param SqsClient $client
     * @param string $queueUrl
     * @param string $receiptHandle
     * @param DoneLogInterface $doneLog
     * @param string $messageId
     */
    public function __construct(
        array $data,
        SqsClient $client,
        string $queueUrl,
        string $receiptHandle,
        DoneLogInterface $doneLog,
        string $messageId
    ) {
        $this->data = $data;
        $this->client = $client;
        $this->queueUrl = $queueUrl;
        $this->receiptHandle = $receiptHandle;
        $this->doneLog = $doneLog;
        $this->messageId = $messageId;
    }

    /**
     * @return bool
     */
    public function markProcessing(): bool
    {
        // no need; the message won't be given to anyone else for 30 seconds
        return true;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function markDone(): bool
    {
        try {
            $result = $this->client->deleteMessage(
                [
                    'QueueUrl'      => $this->queueUrl,
                    'ReceiptHandle' => $this->receiptHandle,
                ]
            );

            $this->doneLog->logJob($this->messageId);

            return $result['@metadata']['statusCode'] === 200;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function markFailed(): bool
    {
        // SQS will re-queue an item after 30 seconds
        return true;
    }
}
