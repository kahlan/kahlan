<?php
namespace kahlan\reporter\coverage\exporter;

use RuntimeException;

class CodeClimate
{
    /**
     * Write a coverage to an ouput file.
     *
     * @param  array   $options The option where the possible values are:
     *                 -`'file'` _string_: The output file name.
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
     * Export a coverage to a string.
     *
     * @param  array   $options The option array where the possible values are:
     *                 -`'collector'`      _object_ : The collector instance.
     *                 -`'repo_token'`     _string_ : The Coveralls repo token.
     *                 -`'environment'`    _array_  : The Environment. Possible values are:
     *                   -`'pwd'`          _string_ : The repo absolute path.
     *                 -`'ci'`             _array_  : The CI service. Possible values are:
     *                   - `'name`             _string_ : CI service name
     *                   - `'build_identifier` _string_ : build identifier
     *                   - `'build_url`        _string_ : build url
     *                   - `'branch`           _string_ : branch name
     *                   - `'commit_sha`       _string_ : commit SHA
     *                   - `'pull_request`     _string_ : pull request id
     *                 -`'run_at'`         _integer_: The date of a timestamp.
     * @return string
     */
    public static function export($options)
    {
        $defaults = [
            'collector'   => null,
            'repo_token'  => null,
            'environment' => [
                'pwd' => getcwd()
            ],
            'ci'          => [],
            'run_at'      => time()
        ];
        $options += $defaults;

        return json_encode([
            'partial'      => false,
            'run_at'       => $options['run_at'],
            'repo_token'   => $options['repo_token'],
            'environment'  => $options['environment'] + ['package_version' => '0.1.2'],
            'git'          => [
                'head'         => `git log -1 --pretty=format:'%H'`,
                'branch'       => trim(`git rev-parse --abbrev-ref HEAD`),
                'committed_at' => `git log -1 --pretty=format:'%ct'`
            ],
            'ci'           => $options['ci'],
            'source_files' => static::_sourceFiles($options['collector'])
        ]);
    }

    /**
     * Export source file coverage
     *
     * @param  object $collector The collector instance.
     * @return array
     */
    protected static function _sourceFiles($collector)
    {
        $result = [];
        foreach ($collector->export() as $file => $data) {
            $content = file_get_contents($file);
            $nbLines = substr_count($content, "\n");

            $lines = [];
            for ($i = 0; $i <= $nbLines; $i++) {
                $lines[] = isset($data[$i]) ? $data[$i] : null;
            }

            $result[] = [
                'name'     => $file,
                'coverage' => $lines,
                'blob_id'  => sha1('blob ' . strlen($content) . "\0" . $content)
            ];
        }

        return $result;
    }

}
