<?php

namespace Oro\Component\ChainProcessor;

class ProcessorIterator implements \Iterator
{
    /**
     * @var array
     *  [
     *      action => [
     *          [
     *              'processor'  => processorId,
     *              'attributes' => [key => value, ...]
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     */
    protected $processors;

    /** @var ContextInterface */
    protected $context;

    /** @var ApplicableCheckerInterface */
    protected $applicableChecker;

    /** @var ProcessorFactoryInterface */
    protected $processorFactory;

    /** @var string */
    private $action;

    /** @var int */
    private $index;

    /** @var int */
    private $maxIndex;

    /**
     * @param array                      $processors
     * @param ContextInterface           $context
     * @param ApplicableCheckerInterface $applicableChecker
     * @param ProcessorFactoryInterface  $processorFactory
     */
    public function __construct(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorFactoryInterface $processorFactory
    ) {
        $this->processors        = $processors;
        $this->context           = $context;
        $this->applicableChecker = $applicableChecker;
        $this->processorFactory  = $processorFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $processorId = $this->processors[$this->action][$this->index]['processor'];
        $processor   = $this->processorFactory->getProcessor($processorId);
        if (null === $processor) {
            throw new \RuntimeException(sprintf('The processor "%s" does not exist.', $processorId));
        }

        return $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->nextApplicable();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->index <= $this->maxIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->action   = $this->context->getAction();
        $this->index    = -1;
        $this->maxIndex = isset($this->processors[$this->action])
            ? count($this->processors[$this->action]) - 1
            : -1;
        $this->nextApplicable();
    }

    /**
     * Moves forward to next applicable processor
     */
    protected function nextApplicable()
    {
        $this->index++;
        while ($this->index <= $this->maxIndex) {
            $applicable = $this->applicableChecker->isApplicable(
                $this->context,
                $this->processors[$this->action][$this->index]['attributes']
            );
            if ($applicable !== ApplicableCheckerInterface::NOT_APPLICABLE) {
                break;
            }
            $this->index++;
        }
    }
}
