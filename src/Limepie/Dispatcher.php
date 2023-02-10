<?php declare(strict_types=1);

namespace Limepie;

class Dispatcher
{
    public function forward($forward) : ?object
    {
        if (false === Di::has('application')) {
            // ERRORCODE: 10003, provider not found
            throw new Exception('"application" service provider not found', 10003);
        }

        $application = clone Di::get('application');
        Di::get('application')->addPrevious($application);

        return Di::get('application')->handle($forward);
        // \pr(\get_class($r));
    }
}
