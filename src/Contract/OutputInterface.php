<?php declare(strict_types=1);


namespace Toolkit\Extlib\Contract;

/**
 * Class OutputInterface
 *
 * @package Toolkit\Extlib\Contract
 */
interface OutputInterface
{
    /**
     * Write a message to output
     *
     * @param string $content
     *
     * @return int
     */
    public function write(string $content): int;

    /**
     * Write a message to output with newline
     *
     * @param string $content
     *
     * @return int
     */
    public function writeln(string $content): int;

    /**
     * Whether the stream is an interactive terminal
     */
    public function isInteractive() : bool;
}
