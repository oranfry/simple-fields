<?php

namespace simplefields\traits;

use Closure;

trait SimpleFields
{
    use SimpleBooleans;
    use SimpleDateTimes;
    use SimpleEnums;
    use SimpleGeos;
    use SimpleHexes;
    use SimpleIpAddresses;
    use SimpleLiterals;
    use SimpleNumbers;
    use SimplePorts;
    use SimpleStrings;

    static function sf_resolve_value($value, object $line)
    {
        if ($value instanceof Closure) {
            return $value($line);
        }

        return $value;
    }
}
