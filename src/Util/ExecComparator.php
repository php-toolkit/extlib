<?php declare(strict_types=1);

namespace Inhere\Extra\Components;

use Toolkit\Stdlib\Math;
use Toolkit\Stdlib\OS;

/**
 * Class ExecComparator - PHP code exec speed comparator
 */
class ExecComparator
{
    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var array
     */
    private array $vars = [];

    /**
     * @var array
     */
    private array $results = [];

    /** @var string[] */
    private array $sample1 = [
        'code' => '',
        'title' => '',
    ];

    /** @var string[] */
    private array $sample2 = [
        'code' => '',
        'title' => '',
    ];

    /** @var string */
    private $common;

    /** @var int */
    private int $loops = 100000;

    /** @var string */
    private $time;

    /**
     * ExecComparator constructor.
     * @param string|null $tmpDir
     */
    public function __construct(string $tmpDir = null)
    {
        $this->tmpDir = $tmpDir ?? OS::getTempDir();
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCommon(string $code)
    {
        $this->common = $code;

        return $this;
    }

    /**
     * @param int $times
     * @return $this
     */
    public function setLoops(int $times)
    {
        if ($times <= 0) {
            throw new \InvalidArgumentException('The time must be gt zero');
        }

        $this->loops = $times;

        return $this;
    }

    /**
     * @param string $code
     * @param string $title
     * @return $this
     */
    public function setSample1(string $code, string $title)
    {
        $this->sample1['code'] = $code;
        $this->sample1['title'] = $title;

        return $this;
    }

    /**
     * @param string $code
     * @param string $title
     * @return $this
     */
    public function setSample2(string $code, string $title)
    {
        $this->sample2['code'] = $code;
        $this->sample2['title'] = $title;

        return $this;
    }

    /**
     * @param int $loops
     * @return $this
     */
    public function compare(int $loops = 0)
    {
        if ($loops) {
            $this->setLoops($loops);
        }

        $sTime = microtime(true);
        $this->time = date('ymdH');

        $id = 1;
        $file1 = $this->dump($this->sample1['code'], $id);
        $this->results['sample1'] = $this->runSampleFile($file1, $id);

        $id = 2;
        $file2 = $this->dump($this->sample2['code'], $id);
        $this->results['sample2'] = $this->runSampleFile($file2, $id);
        $eTime = microtime(true);

        $this->results['total'] = [
            'startTime' => $sTime,
            'endTime' => $eTime,
            'timeDiff' => round($eTime - $sTime, 3),
        ];

        return $this;
    }

    /**
     * @param string $as
     * @return array|string
     */
    public function getResults(string $as = 'text')
    {
        $ret = $this->results;
        $s1 = $ret['sample1'];
        $s2 = $ret['sample2'];
        $t1 = $this->sample1['title'];
        $t2 = $this->sample2['title'];

        $ret['title'] = 'Code execution speed comparison';
        $ret['description'] = "sample1({$t1}) VS sample2({$t2})";
        $timeDiff = round($s1['timeConsumption'] - $s2['timeConsumption'], 4);
        $memDiff = round(($s1['memConsumption'] - $s2['memConsumption']) / 1024, 4);

        $faster = $timeDiff > 0 ? $t2 : $t1;
        $eatMore = $memDiff > 0 ? $t1 : $t2;

        if ((string)$memDiff === '0') {
            $eatMore = '[IGNORE - can be ignored]';
        }
        // $fastDiff = abs($timeDiff);
        // $memDiff = abs($memDiff);

        switch ($as) {
            case 'text':
                $results = <<<TXT
    {$ret['title']}

- loop times: {$this->loops}
- {$ret['description']}

DETAIL

item      sample 1   sample 2    diff(1 - 2)
time      {$s1['timeConsumption']}    {$s2['timeConsumption']}     $timeDiff s
memory    {$s1['memConsumption']} b     {$s2['memConsumption']} b      $memDiff k

RESULT

- Run faster is: $faster
- Consume more memory is: $eatMore
- Test the total time spent: {$ret['total']['timeDiff']} s
TXT;
                break;
            case 'json':
                $results = json_encode($ret, JSON_PRETTY_PRINT);
                break;
            case 'md':
            case 'markdown':
                $results = <<<TXT
# {$ret['title']}

- loop times: {$this->loops}
- {$ret['description']}

## Detail

 item   | sample 1 | sample 2 |  diff(1 - 2)
--------|----------|----------|--------------
 time   | {$s1['timeConsumption']}  |  {$s2['timeConsumption']} | $timeDiff s
 memory | {$s1['memConsumption']} b |   {$s2['memConsumption']} b | $memDiff k

## Result

- Run faster is: $faster
- Consume more memory is: $eatMore
- Test the total time spent: {$ret['total']['timeDiff']} s
TXT;
                break;
            case 'array':
            default:
                return $ret;
        }

        return $results;
    }

    public function resultToText()
    {

    }

    /**
     * @param string $file
     * @param int $id
     * @return array
     */
    public function runSampleFile(string $file, int $id): array
    {
        // load php file
        require $file;

        $func = 'sample_func_' . $id;
        $sMem = memory_get_usage();
        $sTime = microtime(true);

        // running
        // ob_start();
        $ret = $func();
        // $out = ob_get_clean();

        $eMem = memory_get_usage();
        $eTime = microtime(true);

        return [
            'startTime' => $sTime,
            'endTime' => $eTime,
            'startMem' => $sMem,
            'endMem' => $eMem,
            // 'output' => $out,
            'return' => $ret,
            'memConsumption' => round($eMem - $sMem, 4),
            'timeConsumption' => round($eTime - $sTime, 5),
        ];
    }

    /**
     * @param string $code
     * @param int $id
     * @return string
     */
    public function dump(string $code, int $id): string
    {
        $file = $this->tmpDir . '/' . $this->time . '_' . md5($code . Math::random(1000, 100000)) . '.php';
        $common = $this->common;

        $content = <<<CODE
function sample_func_{$id}() {
    // prepare
$common

    // exec
    for (\$i = 0; \$i < $this->loops; \$i++) {
    $code
    }
}
CODE;

        file_put_contents($file, '<?php' . PHP_EOL . $content);
        return $file;
    }

    /**
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * @param array $vars
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * @param array $results
     */
    public function setResults(array $results)
    {
        $this->results = $results;
    }
}
