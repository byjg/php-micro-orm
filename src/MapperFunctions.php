<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\MapperFunctions\FormatSelectUuidMapper;
use ByJG\MicroOrm\MapperFunctions\FormatUpdateUuidMapper;
use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;
use ByJG\MicroOrm\MapperFunctions\ReadOnlyMapper;
use ByJG\MicroOrm\MapperFunctions\StandardMapper;

/**
 * @deprecated use any of the classes implemented in the MapperFunctionsInterface
 *
 * @see StandardMapper
 * @see ReadOnlyMapper
 * @see FormatUpdateUuidMapper
 * @see FormatSelectUuidMapper
 * @see NowUtcMapper
 */
class MapperFunctions
{
    const STANDARD = StandardMapper::class;
    const READ_ONLY = ReadOnlyMapper::class;
    const UPDATE_BINARY_UUID = FormatUpdateUuidMapper::class;
    const SELECT_BINARY_UUID = FormatSelectUuidMapper::class;
    const NOW_UTC = NowUtcMapper::class;
}