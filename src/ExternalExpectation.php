<?php
namespace Kahlan;

use Exception;
use Kahlan\Analysis\Debugger;
use Throwable;

/**
 * Class ExternalExpectation
 */
class ExternalExpectation extends Expectation
{
    /**
     * The external callback.
     *
     * @var callable
     */
    protected $_callback;

    /**
     * The supported exception type.
     *
     * @var string
     */
    protected $_type;

    /**
     * Constructor.
     *
     * @param array $config The config array. Options are:
     *                       -`'callback'` _callable_ : the callback to execute.
     *                       -`'type'`     _string_   : the supported exception type.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'callback' => function () {},
            'type' => 'Exception',
        ];
        $config += $defaults;

        $this->_callback = $config['callback'];
        $this->_type = $config['type'];

        parent::__construct($config);
    }

    /**
     * Processes the expectation.
     *
     * @return mixed
     */
    protected function _process()
    {
        $result = null;
        $exception = null;

        try {
            $result = call_user_func($this->_callback);
        } catch (Throwable $e) {
            $exception = $e;
        } catch (Exception $e) {
            $exception = $e;
        }

        if (!$exception) {
            $this->_logs[] = ['type' => 'passed'];
            $this->_passed = true;

            return $result;
        }

        $this->_passed = false;

        if (!$exception instanceof $this->_type) {
            throw $exception;
        }

        $this->_logs[] = [
            'type' => 'failed',
            'data' => [
                'external' => true,
                'description' => $exception->getMessage()
            ],
            'backtrace' => Debugger::normalize($exception->getTrace())
        ];

        return false;
    }
}
