<?php
namespace kahlan\reporter;

use kahlan\util\Text;

class Bar extends Terminal
{
    /**
     * Color preferences.
     *
     * var array
     */
    protected $_preferences = [];

    /**
     * Format of the progress bar.
     *
     * var string
     */
    protected $_format = '';

    /**
     * Char preferences.
     *
     * var array
     */
    protected $_chars = [];

    /**
     * Progress bar color.
     *
     * var integer
     */
    protected $_color = 37;

    /**
     * Size of the progress bar.
     *
     * var integer
     */
    protected $_size = 0;

    /**
     * Constructor
     *
     * @param array $config The config array.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $defaults = [
            'size' => 50,
            'preferences' => [
                'pass'       => 'green',
                'fail'       => 'red',
                'incomplete' => 'yellow',
                'exception'  => 'magenta'
            ],
            'chars' => [
                'bar'       => '=',
                'indicator' => '>'
            ],
            'format' => '[{:b}{:i}] {:p}%'
        ];
        $config += $defaults;

        $config['chars'] += $defaults['chars'];
        $config['preferences'] += $defaults['preferences'];

        foreach ($config as $key => $value) {
            $_key = "_{$key}";
            $this->$_key = $value;
        }
        $this->_color = $this->_preferences['pass'];
    }

    /**
     * Callback called before any specs processing.
     *
     * @param array $params The suite params array.
     */
    public function start($params)
    {
        parent::start($params);
        $this->write("\n");
    }

    /**
     * Callback called on a spec start.
     *
     * @param object $report The report object of the whole spec.
     */
    public function specStart($report = null)
    {
        parent::specStart($report);
        $this->_progressBar();
    }


    /**
     * Callback called on failure.
     *
     * @param array $report The report array.
     */
    public function fail($report = [])
    {
        $this->_color = $this->_preferences['fail'];
        $this->write("\n");
        $this->_report($report);
    }

    /**
     * Callback called when an exception occur.
     *
     * @param array $report The report array.
     */
    public function exception($report = [])
    {
        $this->_color = $this->_preferences['exception'];
        $this->write("\n");
        $this->_report($report);
    }

    /**
     * Callback called when a `kahlan\IncompleteException` occur.
     *
     * @param array $report The report array.
     */
    public function incomplete($report = [])
    {
        $this->_color = $this->_preferences['incomplete'];
        $this->write("\n");
        $this->_report($report);
    }

    /**
     * Ouputs the progress bar to STDOUT.
     */
    protected function _progressBar()
    {
        if ($this->_current > $this->_total) {
            return;
        }

        $percent = $this->_current / $this->_total;
        $nb = $percent * $this->_size;

        $b = str_repeat($this->_chars['bar'], floor($nb));
        $i = '';

        if ($nb < $this->_size) {
            $i = str_pad($this->_chars['indicator'], $this->_size - strlen($b));
        }

        $p = floor($percent * 100);

        $string = Text::insert($this->_format, compact('p', 'b', 'i'));

        $this->write("\r" . $string, $this->_color);
    }

    /**
     * Callback called at the end of specs processing.
     */
    public function end($results = [])
    {
        $this->write("\n\n");
        $this->_summary($results);
        $this->_reportFocused($results);
    }
}
