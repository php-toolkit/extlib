<?php declare(strict_types=1);

namespace Toolkit\Extlib\Contract;

/**
 * Interface InputInterface
 * @package Toolkit\Extlib\Contract
 */
interface InputInterface
{
    /**
     * @param int $length
     *
     * @return string
     */
    public function read(int $length): string;

    /**
     * @return string
     */
    public function readln(): string;

    /**
     * @return string
     */
    public function readAll(): string;

    /**
     * Whether the stream is an interactive terminal
     */
    public function isInteractive() : bool;
}