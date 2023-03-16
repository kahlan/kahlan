<?php
namespace Kahlan\Reporter\Coverage\Exporter;

use RuntimeException;

class Istanbul
{
    /**
     * Writes a coverage to an ouput file.
     *
     * @param  array   $options The option where the possible values are:
     *                          -`'file'` _string_: The output file name.
     * @return boolean
     */
    public static function write($options)
    {
        $defaults = [
            'file' => null
        ];
        $options += $defaults;

        if (!$file = $options['file']) {
            throw new RuntimeException("Missing file name");
        }
        unset($options['file']);
        return file_put_contents($file, static::export($options));
    }

    /**
     * Exports a coverage to a Istanbul compatible JSON format.
     *
     * @param  array  $options The option array where the possible values are:
     *                         -`'collector'`      _object_ : The collector instance.
     * @return string
     */
    public static function export($options)
    {
        $defaults = [
            'collector' => null,
            'base_path' => getcwd()
        ];
        $options += $defaults;

        $collector = $options['collector'];

        $export = [];

        $base = $options['base_path'] ? rtrim($options['base_path'], DS) . DS : '';

        foreach ($collector->export() as $file => $coverage) {
            $path = $base . $file;
            $export[$path] = static::_export($path, $collector->parse($file), $coverage);
        }

        return json_encode($export, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Exports source file coverage
     *
     * @return array
     */
    protected static function _export($path, $tree, $coverage)
    {
        $s = (object) [];
        $statementMap = (object) [];
        $statementIndex = 0;

        $f = (object) [];
        $fnMap = (object) [];
        $fnIndex = 0;

        $fnCurr = null;
        $fnCurrIndex = null;

        foreach ($tree->lines['content'] as $num => $content) {
            $coverable = null;
            foreach ($content['nodes'] as $node) {
                if ($node->type === 'function' && $node->lines['start'] === $num) {
                    if ($node->isMethod || !$node->isClosure) {
                        $f->{$fnIndex} = 0;
                        $fnMap->{$fnIndex} = [
                            'name' => $node->name,
                            'line' => $num + 1,
                            'decl' => [
                                'start' => [
                                    'line' => $num + 1,
                                    'column' => 0
                                ],
                                'end' => [
                                    'line' => $num + 1,
                                    'column' => strlen($content['body']) + 1
                                ]
                            ],
                            'loc' => [
                                'start' => [
                                    'line' => $num + 1,
                                    'column' => 0
                                ],
                                'end' => [
                                    'line' => $node->lines['stop'] + 1,
                                    'column' => strlen($tree->lines['content'][$node->lines['stop']]['body']) + 1
                                ]
                            ]
                        ];
                        $fnCurr = $node;
                        $fnCurrIndex = $fnIndex;
                        $fnIndex++;
                    }
                }
                if ($node->coverable && $node->lines['stop'] === $num) {
                    $coverable = $node;
                    break;
                }
            }
            if (!$coverable) {
                continue;
            }
            $s->{$statementIndex} = $coverage[$num] ?? 0;

            $statementMap->{$statementIndex} = [
                'start' => [
                    'line' => $num + 1,
                    'column' => 0
                ],
                'end' => [
                    'line' => $coverable->lines['stop'] + 1,
                    'column' => strlen($content['body']) + 1
                ]
            ];

            if ($fnCurr) {
                if ($fnCurr->lines['stop'] >= $coverable->lines['stop']) {
                    $f->{$fnCurrIndex} = max($f->{$fnCurrIndex}, $s->{$statementIndex});
                }
            }

            $statementIndex++;
        }
        $b = (object) [];
        $branchMap = (object) [];
        return compact('path', 'statementMap', 'fnMap', 'branchMap', 's', 'f', 'b');
    }
}
