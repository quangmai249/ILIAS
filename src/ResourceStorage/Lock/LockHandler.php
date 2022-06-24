<?php declare(strict_types=1);

namespace ILIAS\ResourceStorage\Lock;

/**
 * Interface LockHandler
 * @package ILIAS\ResourceStorage
 */
interface LockHandler
{
    public function lockTables(array $table_names, callable $during) : LockHandlerResult;
}
