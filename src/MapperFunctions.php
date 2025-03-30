<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;
use ByJG\MicroOrm\MapperFunctions\ReadOnlyMapper;
use ByJG\MicroOrm\MapperFunctions\SelectBinaryUuidMapper;
use ByJG\MicroOrm\MapperFunctions\StandardMapper;
use ByJG\MicroOrm\MapperFunctions\UpdateBinaryUuidMapper;

/**
 * @deprecated use any of the classes implemented in the MapperFunctionsInterface
 *
 * @see StandardMapper
 * @see ReadOnlyMapper
 * @see UpdateBinaryUuidMapper
 * @see SelectBinaryUuidMapper
 * @see NowUtcMapper
 */
class MapperFunctions
{
    const STANDARD = StandardMapper::class;
    const READ_ONLY = ReadOnlyMapper::class;
    const UPDATE_BINARY_UUID = UpdateBinaryUuidMapper::class;
    const SELECT_BINARY_UUID = SelectBinaryUuidMapper::class;
    const NOW_UTC = NowUtcMapper::class;
}